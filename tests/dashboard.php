<?php

require '../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

date_default_timezone_set('Asia/Manila');

// Define how many charts to show per page
$chartsPerPage = 100; // Adjust this value to control the number of charts displayed per page

// Get the current page from the URL, default to 1 if not set
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $chartsPerPage;

// Get the selected location, time interval, start date, and end date from the URL
$selectedLocation = isset($_GET['location']) ? $_GET['location'] : '';
$interval = isset($_GET['interval']) ? $_GET['interval'] : 'hour';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Escape the selected location to prevent SQL injection
$selectedLocationEscaped = $conn->real_escape_string($selectedLocation);

// Adjust the interval query based on the selected interval
$interval_query = "";
switch ($interval) {
    case 'hour':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%m-%d %H:00:00') as time_label"; // Format to show hours
        break;
    case 'day':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%m-%d') as time_label"; // Show full date
        break;
    case 'week':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%u') as time_label"; // Week number
        break;
    case 'month':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%m') as time_label"; // Month and year
        break;
    case 'year':
        $interval_query = "DATE_FORMAT(alert_time, '%Y') as time_label"; // Just year
        break;
}

// Main query for both average and max heat index, grouped by location, with pagination and location filter
$query_avg = "SELECT location_name, AVG(heat_index) AS avg_heat_index
              FROM sensor_readings
              WHERE 1=1"; // Default condition

$query_max = "SELECT location_name, MAX(heat_index) AS max_heat_index
              FROM sensor_readings
              WHERE 1=1"; // Default condition

// Add location, start date, and end date filters
if (!empty($selectedLocation)) {
    $query_avg .= " AND location_name = '$selectedLocationEscaped'";
    $query_max .= " AND location_name = '$selectedLocationEscaped'";
}
if (!empty($startDate)) {
    $query_avg .= " AND alert_time >= '$startDate'";
    $query_max .= " AND alert_time >= '$startDate'";
}
if (!empty($endDate)) {
    $query_avg .= " AND alert_time <= '$endDate'";
    $query_max .= " AND alert_time <= '$endDate'";
}

$query_avg .= " GROUP BY location_name ORDER BY location_name LIMIT $chartsPerPage OFFSET $offset";
$query_max .= " GROUP BY location_name ORDER BY location_name LIMIT $chartsPerPage OFFSET $offset";

$result_avg = $conn->query($query_avg);
$result_max = $conn->query($query_max);

// Prepare data for charts
$locations = [];
$avgHeatIndexes = [];
$maxHeatIndexes = [];

if ($result_avg->num_rows > 0) {
    while ($row = $result_avg->fetch_assoc()) {
        $locations[] = htmlspecialchars($row['location_name']);
        $avgHeatIndexes[] = floatval($row['avg_heat_index']);
    }
}

if ($result_max->num_rows > 0) {
    while ($row = $result_max->fetch_assoc()) {
        $maxHeatIndexes[] = floatval($row['max_heat_index']);
    }
}

// Query for time series data, grouped by time interval, for average and max heat index
$query_time_series_avg = "SELECT $interval_query, location_name, AVG(heat_index) AS avg_heat_index
                          FROM sensor_readings
                          WHERE alert_time IS NOT NULL";

$query_time_series_max = "SELECT $interval_query, location_name, MAX(heat_index) AS max_heat_index
                          FROM sensor_readings
                          WHERE alert_time IS NOT NULL";

// Apply date filters
if (!empty($startDate)) {
    $query_time_series_avg .= " AND alert_time >= '$startDate'";
    $query_time_series_max .= " AND alert_time >= '$startDate'";
}
if (!empty($endDate)) {
    $query_time_series_avg .= " AND alert_time <= '$endDate'";
    $query_time_series_max .= " AND alert_time <= '$endDate'";
}

// Add location filter if selected
if (!empty($selectedLocation)) {
    $query_time_series_avg .= " AND location_name = '$selectedLocationEscaped'";
    $query_time_series_max .= " AND location_name = '$selectedLocationEscaped'";
}

$query_time_series_avg .= " GROUP BY time_label, location_name ORDER BY alert_time";
$query_time_series_max .= " GROUP BY time_label, location_name ORDER BY alert_time";

$result_time_series_avg = $conn->query($query_time_series_avg);
$result_time_series_max = $conn->query($query_time_series_max);

// Prepare data for line charts (average and highest heat index)
$locationData = [];
if ($result_time_series_avg->num_rows > 0 || $result_time_series_max->num_rows > 0) {
    while ($row_avg = $result_time_series_avg->fetch_assoc()) {
        $timeLabel = htmlspecialchars($row_avg['time_label']);
        $locationName = htmlspecialchars($row_avg['location_name']);
        
        if (!isset($locationData[$locationName])) {
            $locationData[$locationName] = [
                'timeLabels' => [],
                'avgHeatIndexes' => [],
                'maxHeatIndexes' => []
            ];
        }
        
        $locationData[$locationName]['timeLabels'][] = $timeLabel;
        $locationData[$locationName]['avgHeatIndexes'][] = floatval($row_avg['avg_heat_index']);
    }

    while ($row_max = $result_time_series_max->fetch_assoc()) {
        $locationName = htmlspecialchars($row_max['location_name']);
        
        if (isset($locationData[$locationName])) {
            $locationData[$locationName]['maxHeatIndexes'][] = floatval($row_max['max_heat_index']);
        }
    }
}

// Display message if no data is available
$noDataMessage = empty($locations) ? "No data available" : "";

?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->
    <!-- Include jQuery from a CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="card p-3 mb-4 filter-form">
            <h5 class="card-title">Filter Data</h5>
            <form method="GET">
                <div class="form-row d-flex flex-wrap">

                   <!-- Location Filter Dropdown -->
                   <div class="form-group col-md-4 col-sm-12">
                        <label for="location" class="mr-2">Select Location:</label>
                        <select id="location" name="location" class="form-control">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= htmlspecialchars($location); ?>" <?= (isset($_GET['location']) && $_GET['location'] === $location) ? 'selected' : ''; ?>><?= htmlspecialchars($location); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Time Filter Dropdown with Icon -->
                    <div class="form-group col-md-4 col-sm-12">
                        <label for="interval" class="mr-2">Select Time Interval:</label>
                        <div class="dropdown-icon-wrapper">
                            <select id="interval" name="interval" class="form-control">
                                <option value="hour" <?= $interval === 'hour' ? 'selected' : ''; ?>>Hourly</option>
                                <option value="day" <?= $interval === 'day' ? 'selected' : ''; ?>>Daily</option>
                                <option value="week" <?= $interval === 'week' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="month" <?= $interval === 'month' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="year" <?= $interval === 'year' ? 'selected' : ''; ?>>Yearly</option>
                            </select>
                            <i class="fas fa-chevron-down dropdown-icon"></i> <!-- Font Awesome icon -->
                        </div>
                    </div>

                    <!-- Start Date Input -->
                    <div class="form-group col-md-4 col-sm-12">
                        <label for="start_date" class="mr-2">Start Date and Time:</label>
                        <input type="datetime-local" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars(isset($_GET['start_date']) ? $_GET['start_date'] : ''); ?>" required>
                    </div>

                    <!-- End Date Input -->
                    <div class="form-group col-md-4 col-sm-12">
                        <label for="end_date" class="mr-2">End Date and Time:</label>
                        <input type="datetime-local" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars(isset($_GET['end_date']) ? $_GET['end_date'] : ''); ?>" required>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="form-group col-md-4 col-sm-12 align-self-end">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bar Chart Section -->
        <canvas id="barChart"></canvas>
        <script>
            var ctxBar = document.getElementById('barChart').getContext('2d');
            var barChart = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($locations); ?>,
                    datasets: [{
                        label: 'Average Heat Index',
                        data: <?= json_encode($avgHeatIndexes); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Max Heat Index',
                        data: <?= json_encode($maxHeatIndexes); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Heat Index'
                            }
                        }
                    }
                }
            });
        </script>

        <!-- Line Chart Section -->
        <?php foreach ($locationData as $locationName => $data): ?>
            <h5>Time Series Heat Index Data for <?= htmlspecialchars($locationName); ?></h5>
            <canvas id="lineChart-<?= htmlspecialchars($locationName); ?>"></canvas>
            <script>
                var ctxLine = document.getElementById('lineChart-<?= htmlspecialchars($locationName); ?>').getContext('2d');
                var lineChart = new Chart(ctxLine, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($data['timeLabels']); ?>,
                        datasets: [{
                            label: 'Average Heat Index',
                            data: <?= json_encode($data['avgHeatIndexes']); ?>,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Max Heat Index',
                            data: <?= json_encode($data['maxHeatIndexes']); ?>,
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Heat Index'
                                }
                            }
                        }
                    }
                });
            </script>
        <?php endforeach; ?>

<?php
$conn->close(); // Close database connection
?>
