<?php
// Get all available locations
$allLocationsQuery = "SELECT DISTINCT location_name FROM sensor_readings ORDER BY location_name";
$allLocationsResult = $conn->query($allLocationsQuery);
$allLocations = [];
while ($row = $allLocationsResult->fetch_assoc()) {
    $allLocations[] = $row['location_name'];
}

// Get selected location from the form
$selectedLocation = isset($_GET['location']) ? $_GET['location'] : '';

// Get the filter type from the dropdown (hourly, daily, etc.)
$filterType = isset($_GET['filter']) ? $_GET['filter'] : 'daily';

// Get the start and end date from the form, default to past year
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d H:i', strtotime('-1 day'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d H:i');

// Prepare the SQL query with alert_time and location filter
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
    WHERE alert_time >= ? AND alert_time <= ?";

// If a location is selected, add it to the WHERE clause
if ($selectedLocation !== '') {
    $sql .= " AND location_name = ?";
}

$sql .= " GROUP BY location_name, period ORDER BY location_name, period DESC";

// Prepare the statement
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('Prepare failed: ' . $conn->error); // Handle error
}

// Create the parameters array based on whether a location is selected
if ($selectedLocation !== '') {
    // Include the selected location in the parameters
    $params = [$filterType, $filterType, $filterType, $filterType, $filterType, $startDate, $endDate, $selectedLocation];
    $types = 'sssssss' . 's'; // 7 's' for the previous params, 1 for location
} else {
    // Exclude the location if it's not selected
    $params = [$filterType, $filterType, $filterType, $filterType, $filterType, $startDate, $endDate];
    $types = 'sssssss'; // 7 's' for the parameters without location
}

// Create an array of references for bind_param
$refs = [];
foreach ($params as $key => $value) {
    $refs[$key] = &$params[$key]; // References needed for bind_param
}

// Bind the parameters dynamically
call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $refs));

// Execute the query
$stmt->execute();
$result = $stmt->get_result();


// Function to determine the background color class based on the heat index
function getAlertLevelAndClass($heatIndex) {
    if ($heatIndex < 27) {
        return ['Not Hazardous', 'normal']; // Normal (<27°C)
    } elseif ($heatIndex < 33) { // Caution is < 33
        return ['Caution', 'caution']; // Caution (27°C - <33°C)
    } elseif ($heatIndex < 42) { // Extreme Caution is < 42
        return ['Extreme Caution', 'extreme-caution']; // Extreme Caution (33°C - <42°C)
    } elseif ($heatIndex < 52) { // Danger is < 52
        return ['Danger', 'danger']; // Danger (42°C - <52°C)
    } else {
        return ['Extreme Danger', 'extreme-danger']; // Extreme Danger (>=52°C)
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