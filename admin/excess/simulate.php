<?php
include '../php/db.php'; // Include your database connection

function generateRandomData($sensor_id) {
    $temperature = rand(25, 40); // Random temperature between 25°C and 40°C
    $humidity = rand(40, 80);     // Random humidity between 40% and 80%
    $heat_index = round($temperature + ($humidity / 100) * ($temperature - 14.5), 2); // Simple heat index calculation

    return [
        'sensor_id' => $sensor_id,
        'temperature' => $temperature,
        'humidity' => $humidity,
        'heat_index' => $heat_index,
    ];
}

function insertHeatIndexData($data) {
    global $conn;

    // Prepare the statement
    $stmt = $conn->prepare("INSERT INTO heatindexdata (sensor_id, temperature, humidity, heat_index, timestamp) VALUES (?, ?, ?, ?, NOW())");
    
    // Bind parameters: "idd" means 1 integer and 3 doubles (for temperature, humidity, and heat_index)
    $stmt->bind_param("iddd", $data['sensor_id'], $data['temperature'], $data['humidity'], $data['heat_index']);
    
    // Execute the statement
    $stmt->execute();
    
    // Close the statement
    $stmt->close();
}


function insertAlert($sensor_id, $message) {
    global $conn;

    $stmt = $conn->prepare("INSERT INTO alerts (sensor_id, alert_type, message, timestamp, resolved) VALUES (?, ?, ?, NOW(), ?)");
    $resolved = 0; // Unresolved alert
    $alert_type = "High Heat Index"; // Example alert type
    $stmt->bind_param("issi", $sensor_id, $alert_type, $message, $resolved);
    $stmt->execute();
    $stmt->close();
}

// Simulate data input for sensor with ID 3
$sensor_id = 3; // Change this based on your sensors
for ($i = 0; $i < 10; $i++) { // Generate 10 random entries
    $data = generateRandomData($sensor_id);
    insertHeatIndexData($data);

    // If heat index exceeds a threshold, insert an alert
    if ($data['heat_index'] > 38) { // Example threshold
        insertAlert($sensor_id, "Heat index exceeded threshold: " . $data['heat_index'] . "°C");
    }
}

echo "Simulated data input completed!";
?>
