<?php
include 'db.php';

// Fetch data from the HeatIndexData table
$sql = "SELECT sensor_id, temperature, humidity, heat_index, timestamp, Sensors.location 
        FROM HeatIndexData 
        JOIN Sensors ON HeatIndexData.sensor_id = Sensors.sensor_id 
        ORDER BY timestamp DESC LIMIT 100"; // Adjust limit as necessary
$result = $conn->query($sql);

$data = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($data);

$conn->close();
?>
