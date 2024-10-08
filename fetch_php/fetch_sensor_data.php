<?php
require '../../config.php'; // Configuration and database connection

// Connect to the database
$conn = dbConnect();

// Get the selected sensor_id from the AJAX request
$selectedSensorId = isset($_GET['sensor_id']) ? $_GET['sensor_id'] : '';

// Prepare the SQL query to fetch data based on the selected sensor_id
$sql = "SELECT * FROM sensor_readings";
if ($selectedSensorId) {
    $sql .= " WHERE sensor_id = ?";
}

// Prepare the statement
$stmt = $conn->prepare($sql);
if ($selectedSensorId) {
    $stmt->bind_param("s", $selectedSensorId); // Binding sensor_id as string
}

// Execute the statement
$stmt->execute();
$result = $stmt->get_result();

// Fetch all data
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Return data in JSON format
header('Content-Type: application/json');
echo json_encode($data);
?>
