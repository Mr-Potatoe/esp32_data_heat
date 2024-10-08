<?php
require '../../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

// Define how many results you want per page
$resultsPerPage = 10;

// Get the selected sensor_id from the AJAX request
$selectedSensorId = isset($_GET['sensor_id']) ? $_GET['sensor_id'] : '';

// Find out the total number of results for pagination
$sqlCount = "SELECT COUNT(*) AS total FROM sensor_readings";
if ($selectedSensorId) {
    $sqlCount .= " WHERE sensor_id = ?";
}
$stmtCount = $conn->prepare($sqlCount);
if ($selectedSensorId) {
    $stmtCount->bind_param("s", $selectedSensorId);
}
$stmtCount->execute();
$resultCount = $stmtCount->get_result();
$rowCount = $resultCount->fetch_assoc();
$totalResults = $rowCount['total'];

// Calculate the number of pages needed
$totalPages = ceil($totalResults / $resultsPerPage);

// Get the current page number from the AJAX request, default to 1
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, min($currentPage, $totalPages)); // Ensure it's within range

// Calculate the starting limit for the SQL query
$startLimit = ($currentPage - 1) * $resultsPerPage;

// Prepare the SQL query to fetch data based on the selected sensor_id with pagination
$sql = "SELECT * FROM sensor_readings";
if ($selectedSensorId) {
    $sql .= " WHERE sensor_id = ?";
}
$sql .= " LIMIT ?, ?";
$stmt = $conn->prepare($sql);
if ($selectedSensorId) {
    $stmt->bind_param("sii", $selectedSensorId, $startLimit, $resultsPerPage);
} else {
    $stmt->bind_param("ii", $startLimit, $resultsPerPage);
}

// Execute the statement
$stmt->execute();
$result = $stmt->get_result();

// Prepare an array to store the fetched data
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Return the data as JSON
echo json_encode(['data' => $data, 'totalPages' => $totalPages, 'currentPage' => $currentPage]);
?>
