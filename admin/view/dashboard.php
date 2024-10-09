<?php include '../../fetch_php/admin_protect.php'; ?>
<?php
require '../../config.php'; // Configuration and database connection
loadEnv(); // Load environment variables
$conn = dbConnect(); // Connect to the database

// Fetch sensor readings grouped by location
$query = "SELECT location_name, AVG(temperature) AS avg_temperature, AVG(humidity) AS avg_humidity, AVG(heat_index) AS avg_heat_index
          FROM sensor_readings
          GROUP BY location_name
          ORDER BY location_name";
$result = $conn->query($query);

// Prepare data for the charts
$locations = [];
$avgTemperatures = [];
$avgHumidity = [];
$avgHeatIndexes = []; // For average heat index

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $locations[] = htmlspecialchars($row['location_name']);
        $avgTemperatures[] = floatval($row['avg_temperature']);
        $avgHumidity[] = floatval($row['avg_humidity']);
        $avgHeatIndexes[] = floatval($row['avg_heat_index']); // Store avg heat index
    }
}

// Fetch time series data based on the selected interval (default: daily)
$interval = isset($_GET['interval']) ? $_GET['interval'] : 'day';
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

        <!-- Individual Bar and Line Charts for Each Location -->
        <div id="charts" class="row">
            <?php foreach ($locations as $index => $locationName): ?>
                <div class="col-lg-6 col-md-12 mb-4"> <!-- Adjusted column size for two charts in a row -->
                    <div class="card shadow-sm rounded">
                        <div class="card-body">
                            <h4><?php echo htmlspecialchars($locationName); ?> Average Readings</h4>
                            <canvas id="barChart_<?php echo $index; ?>"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 col-md-12 mb-4"> <!-- Adjusted column size for two charts in a row -->
                    <div class="card shadow-sm rounded">
                        <div class="card-body">
                            <h4><?php echo htmlspecialchars($locationName); ?> Trends</h4>
                            <canvas id="lineChart_<?php echo $index; ?>"></canvas>
                        </div>
                    </div>
                </div>

                <script>
                    // Prepare data for individual bar charts
                    (function(index, locationName) { // Pass both index and locationName
                        const avgTemperature = <?php echo json_encode($avgTemperatures[$index] ?? 0); ?>; // Default to 0 if undefined
                        const avgHumidity = <?php echo json_encode($avgHumidity[$index] ?? 0); ?>; // Default to 0 if undefined
                        const avgHeatIndex = <?php echo json_encode($avgHeatIndexes[$index] ?? 0); ?>; // Default to 0 if undefined

                        const ctxBar = document.getElementById(`barChart_${index}`).getContext('2d');
                        new Chart(ctxBar, {
                            type: 'bar',
                            data: {
                                labels: ['Average Temperature (°C)', 'Average Humidity (%)', 'Average Heat Index'],
                                datasets: [{
                                    label: '<?php echo htmlspecialchars($locationName); ?>', // Use locationName here
                                    data: [avgTemperature, avgHumidity, avgHeatIndex],
                                    backgroundColor: [
                                        'rgba(255, 99, 132, 0.2)',
                                        'rgba(54, 162, 235, 0.2)',
                                        'rgba(255, 206, 86, 0.2)'
                                    ],
                                    borderColor: [
                                        'rgba(255, 99, 132, 1)',
                                        'rgba(54, 162, 235, 1)',
                                        'rgba(255, 206, 86, 1)'
                                    ],
                                    borderWidth: 1,
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: false,
                                    },
                                    title: {
                                        display: true,
                                        text: `Average Readings for ${<?php echo json_encode($locationName); ?>}`,
                                    }
                                }
                            }
                        });
                    })(<?php echo $index; ?>, '<?php echo addslashes($locationName); ?>'); // Immediately Invoked Function Expression (IIFE)

                    // Prepare line charts for each location
                    (function(index, locationName) { // Create an IIFE for the line chart
                        const data = <?php echo json_encode($locationData[$locationName]); ?>; // Moved inside the function

                        const ctxLine = document.getElementById(`lineChart_${index}`).getContext('2d');
                        // In the line chart configuration, modify the options to improve X-axis labels
new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: data.timeLabels,
        datasets: [
            {
                label: 'Average Temperature (°C)',
                data: data.avgTemperatures,
                fill: false,
                borderColor: 'rgba(255, 99, 132, 1)',
                tension: 0.1
            },
            {
                label: 'Average Humidity (%)',
                data: data.avgHumidity,
                fill: false,
                borderColor: 'rgba(54, 162, 235, 1)',
                tension: 0.1
            },
            {
                label: 'Average Heat Index',
                data: data.avgHeatIndexes,
                fill: false,
                borderColor: 'rgba(255, 206, 86, 1)',
                tension: 0.1
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            x: {
                ticks: {
                    autoSkip: true, // Skip some labels for better spacing
                    maxTicksLimit: 10, // Limit the number of ticks
                    rotation: 45, // Rotate labels for better readability
                    callback: function(value, index, values) {
                        // Custom formatting based on interval
                        if (interval === 'day') {
                            return value.substring(5); // Show MM-DD format
                        }
                        return value; // Default display
                    }
                }
            },
            y: {
                beginAtZero: true,
            }
        },
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: `Trends for ${locationName}`,
            }
        }
    }
});

                    })(<?php echo $index; ?>, '<?php echo addslashes($locationName); ?>'); // IIFE for line chart
                </script>

            <?php endforeach; ?>
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
