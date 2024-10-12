<?php
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
