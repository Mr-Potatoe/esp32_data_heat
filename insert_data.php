<?php
// Include the config.php file
require 'config.php'; // Adjust the path if needed

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

// Get data from POST request
$temperature = $_POST['temperature'] ?? null;
$humidity = $_POST['humidity'] ?? null;
$heat_index = $_POST['heat_index'] ?? null;
$alert = $_POST['alert'] ?? null;
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;
$sensor_id = $_POST['sensor_id'] ?? null; // New sensor_id field

// Validate inputs
if (
    !is_numeric($temperature) || 
    !is_numeric($humidity) || 
    !is_numeric($heat_index) || 
    !is_numeric($latitude) || 
    !is_numeric($longitude) || 
    !is_numeric($sensor_id)
) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    exit;
}

// Prepare the SQL query using prepared statements
$sql = "INSERT INTO sensor_readings (sensor_id, temperature, humidity, heat_index, alert, latitude, longitude, alert_time) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);

// Check if the statement was prepared successfully
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database query preparation failed: ' . $conn->error]);
    exit;
}

// Bind the parameters to the prepared statement
$stmt->bind_param("ddddssd", $sensor_id, $temperature, $humidity, $heat_index, $alert, $latitude, $longitude);

// Execute the statement
header('Content-Type: application/json');
if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Data inserted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to insert data: ' . $stmt->error]);
}

// Close the prepared statement and connection
$stmt->close();
$conn->close();
?>
