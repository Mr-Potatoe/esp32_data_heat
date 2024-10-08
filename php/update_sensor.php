<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sensor_id = $_POST['sensor_id'];
    $sensor_name = $conn->real_escape_string($_POST['sensor_name']);
    $location = $conn->real_escape_string($_POST['location']);
    $latitude = $conn->real_escape_string($_POST['latitude']);
    $longitude = $conn->real_escape_string($_POST['longitude']);
    
    // Update the sensor in the Sensors table
    $sql = "UPDATE Sensors SET sensor_name='$sensor_name', location='$location', latitude='$latitude', longitude='$longitude' WHERE sensor_id=$sensor_id";
    
    if ($conn->query($sql) === TRUE) {
        // Log the update action
        $log_action = 'Updated Sensor';
        $log_details = "Sensor ID: $sensor_id, New Sensor Name: $sensor_name, New Location: $location, New Latitude: $latitude, New Longitude: $longitude";
        $admin_username = $_SESSION['admin_username']; // Get the admin username from the session

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
