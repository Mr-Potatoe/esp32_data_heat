<?php
include 'db.php';
session_start();

if (isset($_GET['id'])) {
    $sensor_id = $_GET['id'];
    
    // Fetch the sensor details before deletion for logging purposes
    $fetch_sql = "SELECT sensor_name, location, latitude, longitude FROM Sensors WHERE sensor_id = $sensor_id";
    $fetch_result = $conn->query($fetch_sql);
    
    if ($fetch_result->num_rows > 0) {
        $sensor = $fetch_result->fetch_assoc();
        
        // Delete the sensor from the Sensors table
        $sql = "DELETE FROM Sensors WHERE sensor_id = $sensor_id";
        
        if ($conn->query($sql) === TRUE) {
            // Log the deletion action
            $log_action = 'Deleted Sensor';
            $log_details = "Sensor ID: $sensor_id, Sensor Name: {$sensor['sensor_name']}, Location: {$sensor['location']}, Latitude: {$sensor['latitude']}, Longitude: {$sensor['longitude']}";
            $admin_username = $_SESSION['admin_username']; // Get the admin username from the session

            $log_sql = "INSERT INTO Logs (action, admin_username, details) VALUES ('$log_action', '$admin_username', '$log_details')";
            $conn->query($log_sql); // Log the action

            header('Location: ../admin/manage_sensors.php');
            exit;
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "Sensor not found.";
    }
}

$conn->close();
?>
