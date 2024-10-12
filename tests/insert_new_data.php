<?php
require '../config.php'; // Configuration and database connection
loadEnv(); // Load environment variables
$conn = dbConnect(); // Connect to the database

// Define the new location parameters
$sensor_id = 14; // Use the sensor_id assigned above
$latitude = 7.336327;
$longitude = 123.383421;
$location_name = 'Mars';

// Prepare to insert data for the last 24 hours
$currentTime = new DateTime(); // Get the current time

for ($i = 0; $i < 24; $i++) {
    // Generate random temperature, humidity, heat index, and alert level
    $temperature = rand(25, 35); // Random temperature between 25°C and 35°C
    $humidity = rand(50, 80); // Random humidity between 50% and 80%

    // Calculate heat index using the formula
    $heat_index = $temperature + (0.55 - 0.55 * (0.01 * $humidity)) * ($temperature - 14.5); 

    // Determine alert level based on the heat index
    if ($heat_index < 27) {
        $alert = 'Normal'; // Normal (< 27°C)
    } elseif ($heat_index >= 27 && $heat_index < 32) {
        $alert = 'Caution'; // Caution (27°C - 32°C)
    } elseif ($heat_index >= 32 && $heat_index < 41) {
        $alert = 'Extreme Caution'; // Extreme Caution (32°C - 41°C)
    } elseif ($heat_index >= 41 && $heat_index < 54) {
        $alert = 'Danger'; // Danger (41°C - 54°C)
    } else {
        $alert = 'Extreme Danger'; // Extreme Danger (> 54°C)
    }

    // Calculate alert time (1 hour intervals)
    $alert_time = $currentTime->modify('-1 hour')->format('Y-m-d H:i:s');

    // Insert into the database
    $insertQuery = "INSERT INTO sensor_readings (sensor_id, temperature, humidity, heat_index, alert, latitude, longitude, location_name, alert_time)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param('iddssddss', $sensor_id, $temperature, $humidity, $heat_index, $alert, $latitude, $longitude, $location_name, $alert_time);
    
    if (!$stmt->execute()) {
        echo "Error inserting data: " . $stmt->error . "\n";
    }
}

// Close the connection
$stmt->close();
$conn->close();

echo "Inserted 24 hours of data for $location_name.";
?>
