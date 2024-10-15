<?php

// Define how many charts to show per page
$chartsPerPage = 100; // Adjust this value to control the number of charts displayed per page

// Get the current page from the URL, default to 1 if not set
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $chartsPerPage;

// Get the selected location, time interval, start date, and end date from the URL
// Get the selected location, time interval, start date, and end date from the URL
$selectedLocation = isset($_GET['location']) ? $_GET['location'] : '';
$interval = isset($_GET['interval']) ? $_GET['interval'] : 'hour';

// Set default start and end dates if not provided
if (empty($_GET['start_date']) || empty($_GET['end_date'])) {
    $endDate = date('Y-m-d\TH:i'); // Current date and time in the correct format
    $startDate = date('Y-m-d\TH:i', strtotime('-1 week')); // One week before in the correct format
} else {
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
}



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
