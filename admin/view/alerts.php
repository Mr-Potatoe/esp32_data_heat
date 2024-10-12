<?php include '../../fetch_php/admin_protect.php'; ?>
<?php
require '../../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

// Fetch total number of alerts (this was previously missing)
$queryTotalAlerts = "SELECT COUNT(*) AS total_alerts FROM sensor_readings WHERE alert IS NOT NULL";
$totalAlertsResult = $conn->query($queryTotalAlerts);
$totalAlertsData = $totalAlertsResult->fetch_assoc(); // This will contain total_alerts

// Fetch summary data including the location of the highest heat index
$querySummary = "
  SELECT location_name, MAX(heat_index) AS highest_heat_index 
  FROM sensor_readings 
  WHERE alert IS NOT NULL 
  GROUP BY location_name 
  ORDER BY highest_heat_index DESC 
  LIMIT 1";
$summaryResult = $conn->query($querySummary);
$summaryData = $summaryResult->fetch_assoc();

// Fetch total number of unique locations
$queryLocationSummary = "SELECT COUNT(DISTINCT location_name) AS total_locations FROM sensor_readings WHERE alert IS NOT NULL";
$locationSummaryResult = $conn->query($queryLocationSummary);
$locationSummaryData = $locationSummaryResult->fetch_assoc();


// Fetch chart data (alerts by location)
$queryChart = "SELECT location_name, COUNT(*) AS alert_count 
               FROM sensor_readings 
               WHERE alert IS NOT NULL 
               GROUP BY location_name 
               ORDER BY alert_count DESC";
$chartResult = $conn->query($queryChart);

// Pagination logic
$limit = 10; // Number of entries to show in a page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch detailed alerts with pagination
$query = "SELECT location_name, latitude, longitude, temperature, humidity, heat_index, alert, alert_time 
          FROM sensor_readings 
          WHERE alert IS NOT NULL 
          ORDER BY alert_time DESC 
          LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

// Count total number of records
$totalQuery = "SELECT COUNT(*) AS total FROM sensor_readings WHERE alert IS NOT NULL";
$totalResult = $conn->query($totalQuery);
$totalData = $totalResult->fetch_assoc();
$totalRows = $totalData['total'];
$totalPages = ceil($totalRows / $limit);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../components/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->
    <style>
.container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
        /* Custom styles for better UI/UX */
        .card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .card:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- ======= Header ======= -->
    <?php include '../components/header.php'; ?>

    <!-- ======= Sidebar ======= -->
    <?php include '../components/sidebar.php'; ?>

    <main id="main" class="main">
    <div class="container">
    <h1 class="text-center mb-4">Heat Index Alerts</h1>

<!-- Summary Section with responsive cards -->
<div class="row text-center mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-light p-3 h-100">
            <h4>Total Alerts</h4>
            <p class="display-4"><?php echo $totalAlertsData['total_alerts']; ?></p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
    <div class="card bg-light p-3 h-100">
        <h4>Highest Heat Index</h4>
        <?php if (isset($summaryData['highest_heat_index'])): ?>
            <p class="display-4"><?php echo number_format($summaryData['highest_heat_index'], 2); ?> °C</p>
        <?php else: ?>
            <p class="display-4">N/A</p>
        <?php endif; ?>
    </div>
</div>
<div class="col-lg-3 col-md-6 mb-3">
    <div class="card bg-light p-3 h-100">
        <h4>Location of Highest Heat Index</h4>
        <?php if (isset($summaryData['location_name'])): ?>
            <p class="display-4"><?php echo $summaryData['location_name']; ?></p>
        <?php else: ?>
            <p class="display-4">N/A</p>
        <?php endif; ?>
    </div>
</div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-light p-3 h-100">
            <h4>Total Locations</h4>
            <p class="display-4"><?php echo $locationSummaryData['total_locations']; ?></p>
        </div>
    </div>
</div>


    <!-- Chart Section -->
    <div class="row">
        <div class="col-12 mb-4">
            <h3 class="text-center mb-2">Alerts by Location</h3> <!-- Chart label -->
            <div class="card p-3">
                <canvas id="alertChart"></canvas> <!-- Placeholder for the chart -->
            </div>
        </div>
    </div>

    <!-- Alerts Table with hover effect and responsive design -->
    <h3 class="text-center mb-2">Detailed Alerts</h3> <!-- Table label -->
    <table>
        <thead>
            <tr>
                <th scope="col">Location</th>
                <th scope="col">Temperature (°C)</th>
                <th scope="col">Humidity (%)</th>
                <th scope="col">Heat Index</th>
                <th scope="col">Alert Level</th>
                <th scope="col">Alert Time</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Function to determine the background color class based on the heat index
            function getAlertClass($heatIndex) {
                if ($heatIndex < 27) {
                    return 'normal'; // Changed from 'Normal' to 'normal'
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

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Determine the alert class based on the heat index
                    $alertClass = getAlertClass($row['heat_index']);
                    echo "<tr class='{$alertClass}'>"; // Only include alert class
                    echo "<td>" . htmlspecialchars($row['location_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['temperature']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['humidity']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['heat_index']) . "</td>";
                    echo "<td>" . htmlspecialchars($alertClass) . "</td>"; // Display the alert class

                    // Format the alert time
                    $date = new DateTime($row['alert_time']);
                    echo "<td>" . htmlspecialchars($date->format('F j, Y g:i:s A')) . "</td>"; // Example: October 10, 2024 03:45 PM

                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='text-center'>No alerts found</td></tr>";
            }
            ?>
        </tbody>
    </table>

                <!-- Pagination -->
                <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <!-- Previous button -->
        <li class="page-item <?php if($page <= 1){ echo 'disabled'; } ?>">
            <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                Previous
            </a>
        </li>

        <?php
        // Define the range of pages to display
        $range = 2;

        // Calculate start and end page numbers
        $startPage = max(1, $page - $range);
        $endPage = min($totalPages, $page + $range);

        // Loop through the pages within the range
        for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="page-item <?php if($page == $i){ echo 'active'; } ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <!-- Next button -->
        <li class="page-item <?php if($page >= $totalPages){ echo 'disabled'; } ?>">
            <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                Next
            </a>
        </li>
    </ul>
</nav>

<!-- Total pages label -->
<div class="total-pages-label text-center mt-2">
    <strong>Page <?php echo $page; ?> of <?php echo $totalPages; ?></strong>
</div>

<style>
    .pagination .page-item.active .page-link {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }

    .pagination .page-link {
        padding: 8px 12px;
        border: 1px solid #007bff;
        border-radius: 4px;
        color: #007bff;
        text-decoration: none;
        margin: 0 5px; /* Add margin for spacing */
    }

    .pagination .page-link:hover {
        background-color: #007bff;
        color: white;
    }

    .pagination .disabled .page-link {
        pointer-events: none;
        background-color: #e9ecef;
        color: #6c757d;
        border-color: #dee2e6;
    }

    .total-pages-label {
        font-size: 1rem;
        font-weight: bold;
        color: #333;
    }
</style>


        </div>
    </main>

    <!-- Footer and scripts -->
    <?php include '../components/footer.php'; ?>
    <?php include '../components/scripts.php'; ?>

    <!-- Chart.js Script -->
    <script>
        const ctx = document.getElementById('alertChart').getContext('2d');
        const alertChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    <?php
                    $location_names = [];
                    $alert_counts = [];
                    while ($row = $chartResult->fetch_assoc()) {
                        $location_names[] = "'" . $row['location_name'] . "'";
                        $alert_counts[] = $row['alert_count'];
                    }
                    echo implode(',', $location_names);
                    ?>
                ],
                datasets: [{
                    label: 'Number of Alerts by Location',
                    data: [<?php echo implode(',', $alert_counts); ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
    </script>
</body>
</html>s