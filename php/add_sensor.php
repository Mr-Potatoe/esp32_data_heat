<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sensor_name = $conn->real_escape_string($_POST['sensor_name']);
    $location = $conn->real_escape_string($_POST['location']);
    $latitude = $conn->real_escape_string($_POST['latitude']);
    $longitude = $conn->real_escape_string($_POST['longitude']);
    
    // Insert new sensor into the Sensors table
    $sql = "INSERT INTO Sensors (sensor_name, location, latitude, longitude) VALUES ('$sensor_name', '$location', '$latitude', '$longitude')";
    
    if ($conn->query($sql) === TRUE) {
        // Log the addition of the new sensor
        $log_action = 'Added Sensor';
        $log_details = "Sensor Name: $sensor_name, Location: $location, Latitude: $latitude, Longitude: $longitude";
        $admin_username = $_SESSION['admin_username']; // Assuming the username is stored in the session

        $log_sql = "INSERT INTO Logs (action, admin_username, details) VALUES ('$log_action', '$admin_username', '$log_details')";
        $conn->query($log_sql); // Log the action

        header('Location: ../admin/manage_sensors.php');
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>
