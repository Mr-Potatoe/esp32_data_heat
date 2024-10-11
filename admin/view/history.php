<?php include '../../fetch_php/admin_protect.php'; ?>
<?php
require '../../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

// Pagination variables
$locationsPerPage = 2; // Number of location tables per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
$offset = ($page - 1) * $locationsPerPage; // Calculate offset

// Get the total number of locations
$totalLocationsQuery = "SELECT COUNT(DISTINCT location_name) AS total_locations FROM sensor_readings";
$totalLocationsResult = $conn->query($totalLocationsQuery);
$totalLocationsRow = $totalLocationsResult->fetch_assoc();
$totalLocations = $totalLocationsRow['total_locations'];

// Calculate total number of pages
$totalPages = ceil($totalLocations / $locationsPerPage);

// Fetch the current page's locations with pagination
$locationsQuery = "SELECT DISTINCT location_name FROM sensor_readings LIMIT $locationsPerPage OFFSET $offset";
$locationsResult = $conn->query($locationsQuery);

// Get the filter type from the dropdown (hourly, daily, etc.)
$filterType = isset($_GET['filter']) ? $_GET['filter'] : 'hourly';

// Get the start and end date from the form
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';


// Prepare the SQL query to use alert_time instead of timestamp with date range
$sql = "
    SELECT location_name, 
           CASE 
               WHEN ? = 'hourly' THEN DATE_FORMAT(alert_time, '%Y-%m-%d %H:00:00')
               WHEN ? = 'daily' THEN DATE_FORMAT(alert_time, '%Y-%m-%d')
               WHEN ? = 'weekly' THEN CONCAT(YEAR(alert_time), '-W', WEEK(alert_time))
               WHEN ? = 'monthly' THEN DATE_FORMAT(alert_time, '%Y-%m')
               WHEN ? = 'yearly' THEN YEAR(alert_time)
           END AS period, 
           AVG(temperature) AS avg_temp, 
           AVG(humidity) AS avg_humidity, 
           AVG(heat_index) AS avg_heat_index
    FROM sensor_readings
    WHERE alert_time >= ? AND alert_time <= ?
    GROUP BY location_name, period
    ORDER BY location_name, period DESC";

$stmt = $conn->prepare($sql);

// Bind all parameters (5 for filterType and 2 for startDate and endDate)
$stmt->bind_param("sssssss", $filterType, $filterType, $filterType, $filterType, $filterType, $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();



// Function to determine the background color class based on the heat index
function getAlertClass($heatIndex) {
    if ($heatIndex < 27) {
        return 'normal';
    } elseif ($heatIndex >= 27 && $heatIndex < 32) {
        return 'caution';
    } elseif ($heatIndex >= 32 && $heatIndex < 41) {
        return 'extreme-caution';
    } elseif ($heatIndex >= 41 && $heatIndex < 54) {
        return 'danger';
    } else {
        return 'extreme-danger';
    }
}

// Helper function to format the period column in a more human-readable way
function formatPeriod($period, $filterType) {
    $date = new DateTime($period);
    
    switch ($filterType) {
        case 'hourly':
            return $date->format('F j, Y, g A'); // Example: January 1, 2024, 1 PM
        case 'daily':
            return $date->format('F j, Y'); // Example: January 1, 2024
        case 'weekly':
            return 'Week ' . $date->format('W, Y'); // Example: Week 1, 2024
        case 'monthly':
            return $date->format('F Y'); // Example: January 2024
        case 'yearly':
            return $date->format('Y'); // Example: 2024
        default:
            return $period;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../components/head.php'; ?>
    <style>
.container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

h1, h2 {
    margin-bottom: 20px;
}

form {
    margin-bottom: 20px;
}

label {
    font-weight: bold;
    margin-right: 10px;
}

select {
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #ddd;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

th, td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: center;
}

th {
    background-color: #f4f4f4;
    color: #555;
}

.normal {
    background-color: #E6E6E6;
}

.caution {
    background-color: #FFFF00; /* Light Yellow */
}

.extreme-caution {
    background-color: #FFCC00; /* Light Orange */
}

.danger {
    background-color: #FF6600; /* Light Red */
}

.extreme-danger {
    background-color: #CC0001; /* Darker Red */
}

.legend {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap; /* Wrap items on smaller screens */
}

.legend div {
    display: flex;
    align-items: center;
    margin: 5px 0;
}

.legend-color {
    width: 20px;
    height: 20px;
    margin-right: 10px;
}

.card {
    background-color: #f8f9fa; /* Light background for the card */
    border: 1px solid #e1e1e1; /* Soft border */
    border-radius: 5px; /* Rounded corners */
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Subtle shadow */
}

.card-title {
    font-weight: bold; /* Bold title for emphasis */
    margin-bottom: 1rem; /* Space below the title */
}

.form-inline {
    display: flex;
    flex-wrap: wrap; /* Allow wrapping for small screens */
}

.form-group {
    flex: 1; /* Each form group takes equal space */
    min-width: 250px; /* Ensure inputs have a minimum width */
}

.btn-primary {
    margin-left: 10px; /* Space between the button and inputs */
    transition: background-color 0.3s; /* Smooth transition for hover effect */
}

.btn-primary:hover {
    background-color: #0056b3; /* Darker shade on hover */
}



/* Responsive styles */
@media (max-width: 768px) {
    table {
        font-size: 14px; /* Adjust font size for smaller screens */
    }

    th, td {
        padding: 8px; /* Reduce padding */
    }

    h1 {
        font-size: 24px; /* Adjust heading size */
    }

    h2 {
        font-size: 20px; /* Adjust subheading size */
    }
    .form-inline {
        flex-direction: column; /* Stack inputs on smaller screens */
        align-items: flex-start; /* Align items to the left */
    }

    .form-group {
        width: 100%; /* Full width for inputs */
        margin-bottom: 15px; /* Space between stacked inputs */
    }
}
#downloadPdf {
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>
</head>

<body>
    <!-- ======= Header ======= -->
    <?php include '../components/header.php'; ?>

    <!-- ======= Sidebar ======= -->
    <?php include '../components/sidebar.php'; ?>

    <main id="main" class="main">
        <div class="container">
            <h1>Heatmap Data by Location</h1>

    
<!-- Filter Form -->
<div class="card p-3 mb-4">
    <h5 class="card-title">Filter Data</h5>
    <form method="GET" class="form-inline">
        <div class="form-group mr-3">
            <label for="filter" class="mr-2">Select Time Filter:</label>
            <select id="filter" name="filter" class="form-control" onchange="this.form.submit()">
                <option value="hourly" <?= $filterType == 'hourly' ? 'selected' : '' ?>>Hourly</option>
                <option value="daily" <?= $filterType == 'daily' ? 'selected' : '' ?>>Daily</option>
                <option value="weekly" <?= $filterType == 'weekly' ? 'selected' : '' ?>>Weekly</option>
                <option value="monthly" <?= $filterType == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="yearly" <?= $filterType == 'yearly' ? 'selected' : '' ?>>Yearly</option>
            </select>
        </div>

        <div class="form-group mr-3">
            <label for="start_date" class="mr-2">Start Date and Time:</label>
            <input type="datetime-local" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>" required>
        </div>

        <div class="form-group mr-3">
            <label for="end_date" class="mr-2">End Date and Time:</label>
            <input type="datetime-local" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
</div>




            <!-- Legend -->
            <div class="legend">
                <div><div class="legend-color normal"></div>Normal (&lt;27°C)</div>
                <div><div class="legend-color caution"></div>Caution (27°C - 32°C)</div>
                <div><div class="legend-color extreme-caution"></div>Extreme Caution (32°C - 41°C)</div>
                <div><div class="legend-color danger"></div>Danger (41°C - 54°C)</div>
                <div><div class="legend-color extreme-danger"></div>Extreme Danger (&gt;54°C)</div>
            </div>

<?php if ($locationsResult && $locationsResult->num_rows > 0): ?>
    <?php while ($locationRow = $locationsResult->fetch_assoc()): ?>
        <h2>Location: <?= htmlspecialchars($locationRow['location_name']) ?></h2>

        <!-- Download PDF Button for each location -->
        <button class="btn btn-success downloadPdf" data-location="<?= htmlspecialchars($locationRow['location_name']) ?>">
            <i class="bi bi-file-earmark-pdf"></i> Download PDF
        </button>

        <table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Avg Temperature (°C)</th>
                    <th>Avg Humidity (%)</th>
                    <th>Avg Heat Index (°C)</th>
                    <th>Alert Level</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Reset result pointer
                $locationName = $locationRow['location_name'];
                $stmt->execute(); // Execute the prepared statement again for new results
                $result = $stmt->get_result();

                $dataAvailable = false; // Track if data is available for the current location
                while ($row = $result->fetch_assoc()) {
                    if ($row['location_name'] == $locationName) {
                        $alertClass = getAlertClass($row['avg_heat_index']);
                        $dataAvailable = true; // Data exists for this location
                        ?>
                        <tr class="<?= $alertClass ?>">
                            <td><?= formatPeriod($row['period'], $filterType) ?></td>
                            <td><?= number_format($row['avg_temp'], 2) ?></td>
                            <td><?= number_format($row['avg_humidity'], 2) ?></td>
                            <td><?= number_format($row['avg_heat_index'], 2) ?></td>
                            <td><?= ucfirst(str_replace('-', ' ', $alertClass)) ?></td>
                        </tr>
                        <?php
                    }
                }
                if (!$dataAvailable) {
                    echo '<tr><td colspan="5">No readings available for this location.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    <?php endwhile; ?>
<?php else: ?>
    <p>No locations available for the selected filters.</p>
<?php endif; ?>


            <!-- Pagination Controls -->
            <div class="d-flex justify-content-between align-items-center mt-4">
               
                <div>
                    <!-- Previous Page Link -->
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&filter=<?= $filterType ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-outline-primary">
                            <i class="bi bi-chevron-left"></i> Previous
                        </a>
                    <?php else: ?>
                        <button class="btn btn-outline-secondary" disabled>
                            <i class="bi bi-chevron-left"></i> Previous
                        </button>
                    <?php endif; ?>

                </div>

                    <div>
                    <span>Page <?= $page ?> of <?= $totalPages ?></span>
                </div>
                <div>
                    <!-- Next Page Link -->
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&filter=<?= $filterType ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-outline-primary">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-outline-secondary" disabled>
                            Next <i class="bi bi-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- ======= Footer ======= -->
    <?php include '../components/footer.php'; ?>
    <?php include '../components/scripts.php'; ?>

  
    <script>
document.querySelectorAll('.downloadPdf').forEach(button => {
    button.addEventListener('click', function () {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        const locationName = this.getAttribute('data-location');
        const filterType = document.querySelector('#filter').value;

        // Title and introduction
        const marginLeft = 10;
        const marginTop = 10;
        const pageWidth = doc.internal.pageSize.getWidth() - 2 * marginLeft; // Usable page width

        doc.setFontSize(16);
        doc.text('Heatmap Data Report', marginLeft, marginTop);
        doc.setFontSize(12);
        doc.text(`Location: ${locationName}`, marginLeft, marginTop + 10);
        doc.text(`Filter: ${filterType.charAt(0).toUpperCase() + filterType.slice(1)}`, marginLeft, marginTop + 20);

        // Wrap long introduction text
        const introText1 = 'This report summarizes the heatmap data for the selected location, including average temperature, humidity, heat index, and alert levels.';
        const introText2 = 'The data is aggregated based on the selected time filter.';
        const wrappedText1 = doc.splitTextToSize(introText1, pageWidth);
        const wrappedText2 = doc.splitTextToSize(introText2, pageWidth);

        // Position the wrapped text
        doc.text(wrappedText1, marginLeft, marginTop + 30);
        doc.text(wrappedText2, marginLeft, marginTop + 50);
        doc.text('Table: Heatmap Data', marginLeft, marginTop + 70);

        // Collect rows only for the selected location's table
        const rows = [];
        const tableRows = this.nextElementSibling.querySelectorAll('tbody tr');
        tableRows.forEach((tr) => {
            const row = [];
            tr.querySelectorAll('td').forEach((td) => {
                row.push(td.textContent.trim());
            });
            rows.push(row);
        });

        const headers = [['Period', 'Avg Temperature (°C)', 'Avg Humidity (%)', 'Avg Heat Index (°C)', 'Alert Level']];

        // Generate table with color coding based on alert level
        doc.autoTable({
            head: headers,
            body: rows,
            startY: marginTop + 80, // Start table below the introduction text
            theme: 'grid',
            styles: { cellPadding: 2, fontSize: 10 },
            didParseCell: function (data) {
                if (data.section === 'body' && data.column.index !== 0) { // Apply to all columns except the first one
                    const alertLevel = data.row.raw[4]; // Get the alert level from the 5th column
                    const backgroundColor = getColorForAlertLevel(alertLevel);
                    data.cell.styles.fillColor = backgroundColor;
                }
            },
            margin: { top: marginTop + 80 } // Ensure table fits within margins
        });

        doc.save(`Heatmap_Report_${locationName}.pdf`);
    });
});


// Helper function to determine background color for alert level
function getColorForAlertLevel(alertLevel) {
    switch (alertLevel.toLowerCase()) {
        case 'caution':
            return [255, 255, 0]; // Light Yellow
        case 'extreme caution':
            return [255, 204, 0]; // Light Orange
        case 'danger':
            return [255, 102, 0]; // Light Red
        case 'extreme danger':
            return [204, 0, 1]; // Dark Red
        default:
            return [230, 230, 230]; // Normal (Gray)
    }
}

</script>


</body>

</html>
