<?php

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

// Get the selected time interval, start date, and end date
$interval = isset($_GET['interval']) ? $_GET['interval'] : 'hour';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

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

// Modify the time series query to include date filtering
$query_time_series = "SELECT $interval_query, location_name, AVG(temperature) AS avg_temperature, AVG(humidity) AS avg_humidity, AVG(heat_index) AS avg_heat_index
                      FROM sensor_readings
                      WHERE alert_time IS NOT NULL";

// Append start and end date conditions to the query if provided
if (!empty($startDate)) {
    $query_time_series .= " AND alert_time >= '$startDate'";
}
if (!empty($endDate)) {
    $query_time_series .= " AND alert_time <= '$endDate'";
}

$query_time_series .= " GROUP BY time_label, location_name ORDER BY alert_time";

$result_time_series = $conn->query($query_time_series);

// Prepare data for line charts
$locationData = [];
if ($result_time_series->num_rows > 0) {
    while ($row = $result_time_series->fetch_assoc()) {
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

// Display message if no data is available
$noDataMessage = empty($locations) ? "No data available for the selected time interval." : "";

?>