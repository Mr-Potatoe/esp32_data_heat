<?php
// Include the config.php file
require 'config.php'; // Adjust the path if needed

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

// Fetch the latest sensor readings
$sql = "SELECT temperature, humidity, heat_index, alert, latitude, longitude, location_name, alert_time FROM sensor_readings";
$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($data);

$conn->close();
?>
