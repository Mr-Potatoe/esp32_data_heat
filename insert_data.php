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

// Validate inputs (for example, ensuring numbers are numbers)
if (!is_numeric($temperature) || !is_numeric($humidity) || !is_numeric($heat_index) || !is_numeric($latitude) || !is_numeric($longitude)) {
    die("Invalid input data");
}

// Prepare the SQL query using prepared statements
$sql = "INSERT INTO sensor_readings (temperature, humidity, heat_index, alert, latitude, longitude, alert_time) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);

// Bind the parameters to the prepared statement
$stmt->bind_param("dddsdd", $temperature, $humidity, $heat_index, $alert, $latitude, $longitude);

// Execute the statement
if ($stmt->execute()) {
    echo "Data inserted successfully";
} else {
    echo "Error: " . $stmt->error;
}

// Close the prepared statement and connection
$stmt->close();
$conn->close();

?>
