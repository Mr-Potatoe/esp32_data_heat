<?php


// Define how many results you want per page
$resultsPerPage = 10;

// Find out the number of results stored in the database
$result = $conn->query("SELECT COUNT(*) AS total FROM sensor_readings");
$row = $result->fetch_assoc();
$totalResults = $row['total'];

// Calculate the number of pages needed
$totalPages = ceil($totalResults / $resultsPerPage);

// Get the current page number from URL, if not set, default to 1
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, min($currentPage, $totalPages)); // Ensure it's within range

// Calculate the starting limit for the SQL query
$startLimit = ($currentPage - 1) * $resultsPerPage;

// Fetch all unique location names
$locations = $conn->query("SELECT DISTINCT location_name FROM sensor_readings")->fetch_all(MYSQLI_ASSOC);

// Get the selected filters from the dropdown and URL parameters
$selectedLocation = isset($_GET['location_name']) ? $_GET['location_name'] : '';
$selectedAlertLevel = isset($_GET['alert_level']) ? $_GET['alert_level'] : '';

// Default start date to 1 day ago and end date to the current Philippine time
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d\TH:i:s', strtotime('-1 day'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d\TH:i:s');

// Prepare the SQL query to fetch data based on the selected location, date range, and alert level with pagination
$sql = "SELECT *, 
               CASE 
                   WHEN heat_index < 27 THEN 'Not Hazardous' 
                   WHEN heat_index >= 27 AND heat_index < 33 THEN 'Caution' 
                   WHEN heat_index >= 33 AND heat_index < 42 THEN 'Extreme Caution' 
                   WHEN heat_index >= 42 AND heat_index < 52 THEN 'Danger' 
                   ELSE 'Extreme Danger' 
               END AS alert_level 
         FROM sensor_readings 
         WHERE 1=1";

$params = [];
if ($selectedLocation) {
    $sql .= " AND location_name = ?";
    $params[] = $selectedLocation;
}
if ($startDate) {
    $sql .= " AND alert_time >= ?";
    $params[] = $startDate;
}
if ($endDate) {
    $sql .= " AND alert_time <= ?";
    $params[] = $endDate;
}
if ($selectedAlertLevel) {
    $sql .= " AND CASE 
                   WHEN heat_index < 27 THEN 'Not Hazardous' 
                   WHEN heat_index >= 27 AND heat_index < 33 THEN 'Caution' 
                   WHEN heat_index >= 33 AND heat_index < 42 THEN 'Extreme Caution' 
                   WHEN heat_index >= 42 AND heat_index < 52 THEN 'Danger' 
                   ELSE 'Extreme Danger' 
               END = ?";
    $params[] = $selectedAlertLevel;
}

$sql .= " ORDER BY alert_time DESC LIMIT ?, ?"; // Add ORDER BY alert_time DESC

// Prepare the statement
$stmt = $conn->prepare($sql);
$types = str_repeat("s", count($params)); // Determine parameter types
if ($params) {
    $types .= "ii"; // Adding start limit and results per page as integers
    // Combine pagination parameters with existing parameters
    $paginationParams = [$startLimit, $resultsPerPage];
    $allParams = array_merge($params, $paginationParams);
    
    // Bind parameters to statement
    $stmt->bind_param($types, ...$allParams);
    
} else {
    $stmt->bind_param("ii", $startLimit, $resultsPerPage); // Binding start limit and results per page as integers
}

// Execute the statement
$stmt->execute();
$result = $stmt->get_result();

// Function to determine the background color class based on the heat index
function getAlertLevelAndClass($heatIndex) {
    if ($heatIndex < 27) {
        return ['Not Hazardous', 'normal']; // Normal (<27°C)
    } elseif ($heatIndex >= 27 && $heatIndex < 33) { // Caution is < 33
        return ['Caution', 'caution']; // Caution (27°C - <33°C)
    } elseif ($heatIndex >= 33 && $heatIndex < 42) { // Extreme Caution is < 42
        return ['Extreme Caution', 'extreme-caution']; // Extreme Caution (33°C - <42°C)
    } elseif ($heatIndex >= 42 && $heatIndex < 52) { // Danger is < 52
        return ['Danger', 'danger']; // Danger (42°C - <52°C)
    } else {
        return ['Extreme Danger', 'extreme-danger']; // Extreme Danger (>=52°C)
    }
}

?>