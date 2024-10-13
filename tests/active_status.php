<?php
require '../config.php'; // Configuration and database connection
loadEnv(); // Load environment variables
$conn = dbConnect(); // Connect to the database

// Define the new location parameters
$sensor_id = 77; // Use the sensor_id assigned above
$latitude = 7.945608;
$longitude = 123.587641;
$location_name = 'Haumea';

// Prepare to insert data for the last 24 hours
$currentTime = new DateTime(); // Get the current time

// Simulate a more realistic trend for temperature and humidity over 24 hours
$base_temperature_day = 30; // Average daytime temperature
$base_temperature_night = 22; // Average nighttime temperature
$base_humidity_day = 60; // Average daytime humidity
$base_humidity_night = 75; // Average nighttime humidity

for ($i = 0; $i < 24; $i++) {
    // Calculate the current hour in the loop (24-hour clock format)
    $currentHour = (int)$currentTime->format('H');

    // Simulate temperature variation between day and night
    if ($currentHour >= 6 && $currentHour <= 18) {
        // Daytime (6 AM to 6 PM)
        $temperature = $base_temperature_day + rand(-2, 2); // Slight fluctuations during the day
        $humidity = $base_humidity_day + rand(-5, 5); // Humidity fluctuates less during the day
    } else {
        // Nighttime (6 PM to 6 AM)
        $temperature = $base_temperature_night + rand(-2, 2); // Slight fluctuations during the night
        $humidity = $base_humidity_night + rand(-5, 5); // Humidity is typically higher at night
    }

    // Calculate heat index using the formula
    $heat_index = $temperature + (0.55 - 0.55 * (0.01 * $humidity)) * ($temperature - 14.5);

    // Determine alert level based on the heat index
    if ($heat_index < 27) {
        $alert = 'Not Hazardous'; // Normal (< 27°C)
    } elseif ($heat_index >= 27 && $heat_index < 33) {
        $alert = 'Caution'; // Caution (27°C - 32°C)
    } elseif ($heat_index >= 33 && $heat_index < 42) {
        $alert = 'Extreme Caution'; // Extreme Caution (33°C - 41°C)
    } elseif ($heat_index >= 42 && $heat_index < 52) {
        $alert = 'Danger'; // Danger (42°C - 51°C)
    } else {
        $alert = 'Extreme Danger'; // Extreme Danger (>= 52°C)
    }
    

    // Calculate alert time (1 hour intervals)
    $alert_time = $currentTime->modify('-1 hour')->format('Y-m-d H:i:s');

    // Set status to 'active'
    $status = 'active'; // Set the status as 'active'

    // Insert into the database
    $insertQuery = "INSERT INTO sensor_readings (sensor_id, temperature, humidity, heat_index, alert, latitude, longitude, location_name, alert_time, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param('iddssddssi', $sensor_id, $temperature, $humidity, $heat_index, $alert, $latitude, $longitude, $location_name, $alert_time, $status);
    
    if (!$stmt->execute()) {
        echo "Error inserting data: " . $stmt->error . "\n";
    }
}

// Close the connection
$stmt->close();
$conn->close();

echo "Inserted 24 hours of realistic data for $location_name.";
?>
