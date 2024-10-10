<?php include '../../fetch_php/admin_protect.php'; ?>
<?php
require '../../config.php'; // Configuration and database connection
loadEnv(); // Load environment variables
$conn = dbConnect(); // Connect to the database

// Define how many charts to show per page
$chartsPerPage = 2; // Adjust this value to control the number of charts displayed per page

// Get the current page from the URL, default to 1 if not set
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $chartsPerPage;

// Query to get total number of locations
$totalLocationsQuery = "SELECT COUNT(DISTINCT location_name) AS total_locations FROM sensor_readings";
$totalLocationsResult = $conn->query($totalLocationsQuery);
$totalLocations = $totalLocationsResult->fetch_assoc()['total_locations'];

// Calculate the total number of pages
$totalPages = ceil($totalLocations / $chartsPerPage);

// Fetch sensor readings grouped by location with pagination
$query = "SELECT location_name, AVG(temperature) AS avg_temperature, AVG(humidity) AS avg_humidity, AVG(heat_index) AS avg_heat_index
          FROM sensor_readings
          GROUP BY location_name
          ORDER BY location_name
          LIMIT $chartsPerPage OFFSET $offset"; // Limit the query for pagination
$result = $conn->query($query);

// Prepare data for charts
$locations = [];
$avgTemperatures = [];
$avgHumidity = [];
$avgHeatIndexes = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $locations[] = htmlspecialchars($row['location_name']);
        $avgTemperatures[] = floatval($row['avg_temperature']);
        $avgHumidity[] = floatval($row['avg_humidity']);
        $avgHeatIndexes[] = floatval($row['avg_heat_index']);
    }
}


// Fetch time series data based on the selected interval (default: daily)
$interval = isset($_GET['interval']) ? $_GET['interval'] : 'hour';
$interval_query = "";

switch ($interval) {
    case 'hour':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%m-%d %H:00:00') as time_label";
        break;
    case 'day':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%m-%d') as time_label";
        break;
    case 'week':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%u') as time_label"; // week number
        break;
    case 'month':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%m') as time_label";
        break;
    case 'year':
        $interval_query = "DATE_FORMAT(alert_time, '%Y') as time_label";
        break;
}

// Prepare SQL query for time series data for all locations
$query_time_series = "SELECT $interval_query, location_name, AVG(temperature) AS avg_temperature, AVG(humidity) AS avg_humidity, AVG(heat_index) AS avg_heat_index
                      FROM sensor_readings
                      GROUP BY time_label, location_name
                      ORDER BY alert_time";
$result_time_series = $conn->query($query_time_series);

// Prepare data for the line charts
$locationData = [];

if ($result_time_series->num_rows > 0) {
    while($row = $result_time_series->fetch_assoc()) {
        $timeLabel = htmlspecialchars($row['time_label']);
        $locationName = htmlspecialchars($row['location_name']);
        
        if (!isset($locationData[$locationName])) {
            $locationData[$locationName] = [
                'timeLabels' => [],
                'avgTemperatures' => [],
                'avgHumidity' => [],
                'avgHeatIndexes' => []
            ];
        }
        
        $locationData[$locationName]['timeLabels'][] = $timeLabel;
        $locationData[$locationName]['avgTemperatures'][] = floatval($row['avg_temperature']);
        $locationData[$locationName]['avgHumidity'][] = floatval($row['avg_humidity']);
        $locationData[$locationName]['avgHeatIndexes'][] = floatval($row['avg_heat_index']);
    }
}

// Add this check to display a message if no data is available
if (empty($locations)) {
    $noDataMessage = "No data available for the selected time interval.";
} else {
    $noDataMessage = "";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../components/head.php'; ?>
    <title>Sensor Readings Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->
</head>
<body>

    <!-- ======= Header ======= -->
    <?php include '../components/header.php'; ?>

    <!-- ======= Sidebar ======= -->
    <?php include '../components/sidebar.php'; ?>

    <main id="main" class="main">
    <div class="container-fluid">
        <h1 class="mt-4">Sensor Readings Dashboard</h1>

        <!-- Filter Dropdown for Time Interval -->
        <div class="mb-4">
            <label for="interval" class="form-label">Select Time Interval:</label>
            <select id="interval" class="form-select" onchange="filterData()">
                <option value="hour" <?php echo $interval === 'hour' ? 'selected' : ''; ?>>Hourly</option>
                <option value="day" <?php echo $interval === 'day' ? 'selected' : ''; ?>>Daily</option>
                <option value="week" <?php echo $interval === 'week' ? 'selected' : ''; ?>>Weekly</option>
                <option value="month" <?php echo $interval === 'month' ? 'selected' : ''; ?>>Monthly</option>
                <option value="year" <?php echo $interval === 'year' ? 'selected' : ''; ?>>Yearly</option>
            </select>
        </div>

        <!-- Check if there are no locations and display the message -->
<?php if (!empty($noDataMessage)): ?>
    <div class="alert alert-warning" role="alert">
        <?php echo $noDataMessage; ?>
    </div>
<?php endif; ?>


      <!-- Individual Bar and Line Charts for Each Location -->
<div id="charts" class="row">
    <?php foreach ($locations as $index => $locationName): ?>
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card shadow-sm rounded">
                <div class="card-body">
                    <h4><?php echo htmlspecialchars($locationName); ?> Average Readings</h4>
                    <canvas id="barChart_<?php echo $index; ?>"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card shadow-sm rounded">
                <div class="card-body">
                    <h4><?php echo htmlspecialchars($locationName); ?> Trends</h4>
                    <canvas id="lineChart_<?php echo $index; ?>"></canvas>
                </div>
            </div>
        </div>

        <script>
            // Bar Chart Rendering
            (function(index, locationName) {
                const avgTemperature = <?php echo json_encode($avgTemperatures[$index] ?? 0); ?>;
                const avgHumidity = <?php echo json_encode($avgHumidity[$index] ?? 0); ?>;
                const avgHeatIndex = <?php echo json_encode($avgHeatIndexes[$index] ?? 0); ?>;

                const ctxBar = document.getElementById(`barChart_${index}`).getContext('2d');
                new Chart(ctxBar, {
                    type: 'bar',
                    data: {
                        labels: ['Average Temperature (°C)', 'Average Humidity (%)', 'Average Heat Index'],
                        datasets: [{
                            label: locationName,
                            data: [avgTemperature, avgHumidity, avgHeatIndex],
                            backgroundColor: ['rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(255, 206, 86, 0.2)'],
                            borderColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)'],
                            borderWidth: 1,
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true }
                        },
                        plugins: {
                            legend: { display: false },
                            title: { display: true, text: `Average Readings for ${locationName}` }
                        }
                    }
                });
            })(<?php echo $index; ?>, '<?php echo addslashes($locationName); ?>');

            // Line Chart Rendering
            
            (function(index, locationName) {
                const data = <?php echo json_encode($locationData[$locationName]); ?>;

                const ctxLine = document.getElementById(`lineChart_${index}`).getContext('2d');
                new Chart(ctxLine, {
                    type: 'line',
                    data: {
                        labels: data.timeLabels, // Ensure labels are available but optimized in the options
                        datasets: [
                            { 
                                label: 'Average Temperature (°C)',
                                data: data.avgTemperatures,
                                fill: false,
                                borderColor: 'rgba(255, 99, 132, 1)',
                                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                tension: 0.1,
                                pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                                pointBorderColor: '#fff'
                            },
                            { 
                                label: 'Average Humidity (%)',
                                data: data.avgHumidity,
                                fill: false,
                                borderColor: 'rgba(54, 162, 235, 1)',
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                tension: 0.1,
                                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                                pointBorderColor: '#fff'
                            },
                            { 
                                label: 'Average Heat Index',
                                data: data.avgHeatIndexes,
                                fill: false,
                                borderColor: 'rgba(255, 206, 86, 1)',
                                backgroundColor: 'rgba(255, 206, 86, 0.2)',
                                tension: 0.1,
                                pointBackgroundColor: 'rgba(255, 206, 86, 1)',
                                pointBorderColor: '#fff'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                ticks: {
                                    autoSkip: true,               // Automatically skip labels
                                    maxTicksLimit: 8,             // Limit the number of labels
                                    maxRotation: 0,               // Prevent label rotation unless necessary
                                    minRotation: 0                // Keep it flat for better readability
                                },
                                grid: {
                                    display: true,
                                    color: 'rgba(200, 200, 200, 0.2)' // Subtle gridlines
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    display: true,
                                    color: 'rgba(200, 200, 200, 0.2)'
                                }
                            }
                        },
                        plugins: {
                            legend: { display: true },
                            title: { display: true, text: `Trends for ${locationName}` }
                        },
                        elements: {
                            point: {
                                radius: 5,
                                hoverRadius: 8
                            }
                        }
                    }
                });
            })(<?php echo $index; ?>, '<?php echo addslashes($locationName); ?>');
        
        </script>
    <?php endforeach; ?>
</div>

        <!-- Pagination Links -->
<div class="d-flex justify-content-between align-items-center mt-4">
    <div>
        <!-- Previous Page Link -->
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&interval=<?= $interval ?>" class="btn btn-outline-primary">
                <i class="bi bi-chevron-left"></i> Previous
            </a>
        <?php else: ?>
            <button class="btn btn-outline-secondary" disabled>
                <i class="bi bi-chevron-left"></i> Previous
            </button>
        <?php endif; ?>
    </div>

    <div>
        Page <?= $page ?> of <?= $totalPages ?>
    </div>

    <div>
        <!-- Next Page Link -->
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&interval=<?= $interval ?>" class="btn btn-outline-primary">
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

    <script>
        function filterData() {
            const interval = document.getElementById('interval').value;
            window.location.href = `?interval=${interval}`; // Redirect with selected interval
        }
    </script>
    <?php include '../components/scripts.php'; ?>

</body>
</html>
