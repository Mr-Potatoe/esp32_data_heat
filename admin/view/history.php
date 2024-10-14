<?php include '../../fetch_php/admin_protect.php'; ?>
<?php
require '../../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

?>

<?php include '../../fetch_php/fetch_history.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../components/head.php'; ?>
    <link rel="stylesheet" href="../../assets/css/page.css">
<style>

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

#downloadPdf {
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dropdown-icon-wrapper {
    position: relative;
}

.dropdown-icon-wrapper select {
    padding-right: 30px; /* Add space for the icon */
}

.dropdown-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none; /* Prevent the icon from blocking clicks on the dropdown */
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
        <h1><i class="bi bi-table"></i> Heatmap Table Data by Location</h1>


    

<div class="card p-3 mb-4 filter-form">
    <h5 class="card-title">Filter Data</h5>
    <form method="GET">
        <div class="form-row d-flex flex-wrap">
<!-- Time Filter Dropdown with Icon -->
<div class="form-group col-md-4 col-sm-12">
    <label for="filter" class="mr-2">Select Time Filter:</label>
    <div class="dropdown-icon-wrapper">
        <select id="filter" name="filter" class="form-control">
            <option value="hourly" <?= $filterType == 'hourly' ? 'selected' : '' ?>>Hourly</option>
            <option value="daily" <?= $filterType == 'daily' ? 'selected' : '' ?>>Daily</option>
            <option value="weekly" <?= $filterType == 'weekly' ? 'selected' : '' ?>>Weekly</option>
            <option value="monthly" <?= $filterType == 'monthly' ? 'selected' : '' ?>>Monthly</option>
            <option value="yearly" <?= $filterType == 'yearly' ? 'selected' : '' ?>>Yearly</option>
        </select>
        <i class="fas fa-chevron-down dropdown-icon"></i> <!-- Font Awesome icon -->
    </div>
</div>




            <!-- Start Date Input -->
            <div class="form-group col-md-4 col-sm-12">
                <label for="start_date" class="mr-2">Start Date and Time:</label>
                <input type="datetime-local" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
            </div>

            <!-- End Date Input -->
            <div class="form-group col-md-4 col-sm-12">
                <label for="end_date" class="mr-2">End Date and Time:</label>
                <input type="datetime-local" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
            </div>
        </div>

        <!-- Filter Button (aligned at the bottom left) -->
        <div class="form-group  d-flex justify-content-start">
            <button type="submit" class="btn btn-primary me-2">Filter</button>
            <a href="history.php" class="btn btn-secondary">Clear Filters</a>
        </div>
    </form>
</div>


<?php include '../components/legend.php' ?>


<?php if ($locationsResult && $locationsResult->num_rows > 0): ?>
    <?php while ($locationRow = $locationsResult->fetch_assoc()): ?>
<!-- Responsive Wrapper for Heading and Table -->
<div class="container">
    <h2><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($locationRow['location_name']) ?></h2>

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
                    // Use the updated function to get both alert level and class
                    list($alertLevel, $alertClass) = getAlertLevelAndClass($row['avg_heat_index']);
                    $dataAvailable = true; // Data exists for this location
                    ?>
                    <tr class="<?= $alertClass ?>">
                        <td><?= formatPeriod($row['period'], $filterType) ?></td>
                        <td><?= number_format($row['avg_temp'], 2) ?></td>
                        <td><?= number_format($row['avg_humidity'], 2) ?></td>
                        <td><?= number_format($row['avg_heat_index'], 2) ?></td>
                        <td><?= $alertLevel ?></td> <!-- Displaying alert level text -->
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
</div>

    <?php endwhile; ?>
<?php else: ?>
    <p>No locations available for the selected filters.</p>
<?php endif; ?>


            <!-- Pagination Controls -->

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
