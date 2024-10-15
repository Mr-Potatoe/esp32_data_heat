<?php
// Fetch total number of alerts (this was previously missing)
$queryTotalAlerts = "SELECT COUNT(*) AS total_alerts FROM sensor_readings WHERE alert IS NOT NULL AND alert_time >= NOW() - INTERVAL 1 DAY";
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
               WHERE alert IS NOT NULL AND alert_time >= NOW() - INTERVAL 1 DAY
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
          WHERE alert IS NOT NULL AND alert_time >= NOW() - INTERVAL 1 DAY 
          ORDER BY alert_time DESC 
          LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

// Count total number of records
$totalQuery = "SELECT COUNT(*) AS total FROM sensor_readings WHERE alert IS NOT NULL";
$totalResult = $conn->query($totalQuery);
$totalData = $totalResult->fetch_assoc();
$totalRows = $totalData['total'];
$totalPages = ceil($totalRows / $limit);

// Initialize filter variables
$location = isset($_GET['location']) ? $_GET['location'] : '';
$alert_level = isset($_GET['alert_level']) ? $_GET['alert_level'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Base query
$query = "SELECT location_name, latitude, longitude, temperature, humidity, heat_index, alert, alert_time 
          FROM sensor_readings 
          WHERE alert IS NOT NULL";

// Apply location filter if selected
if (!empty($location)) {
    $query .= " AND location_name = '$location'";
}

// Apply alert level filter if selected
if (!empty($alert_level)) {
    $query .= " AND heat_index BETWEEN ";
    switch ($alert_level) {
        case 'Not Hazardous':
            $query .= "0 AND 26.99";
            break;
        case 'Caution':
            $query .= "27 AND 32.99";
            break;
        case 'Extreme Caution':
            $query .= "33 AND 41.99";
            break;
        case 'Danger':
            $query .= "42 AND 51.99";
            break;
        case 'Extreme Danger':
            $query .= "52 AND 100";
            break;
    }
}

// Apply start date and time filter if selected
if (!empty($start_date)) {
    $query .= " AND alert_time >= '$start_date'";
}

// Apply end date and time filter if selected
if (!empty($end_date)) {
    $query .= " AND alert_time <= '$end_date'";
}

// Add sorting and pagination
$query .= " ORDER BY alert_time DESC LIMIT $limit OFFSET $offset";

// Execute query
$result = $conn->query($query);



$location_names = [];
$alert_counts = [];

while ($row = $chartResult->fetch_assoc()) {
    $location_names[] = "'" . $row['location_name'] . "'";
    $alert_counts[] = $row['alert_count'];
}

$location_names_str = implode(',', $location_names);
$alert_counts_str = implode(',', $alert_counts);

?>
