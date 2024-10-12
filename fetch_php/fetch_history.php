<?php

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

// Get the start and end date from the form, default to past week
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d H:i:s', strtotime('-1 year'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d H:i:s');

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