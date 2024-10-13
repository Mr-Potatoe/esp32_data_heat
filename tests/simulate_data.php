<?php
require '../config.php'; // Configuration and database connection
loadEnv(); // Load environment variables
$conn = dbConnect(); // Connect to the database

// Define the location parameters
$locations = [
    ['sensor_id' => 15, 'latitude' => 7.9473004, 'longitude' => 123.5876167, 'location_name' => 'Earth'],
    ['sensor_id' => 16, 'latitude' => 7.9480162, 'longitude' => 123.5881823, 'location_name' => 'Sun'],
    // Add more locations as needed...
];

// Run indefinitely to simulate data insertion every 5 seconds
while (true) {
    // Randomly select a location
    $location = $locations[array_rand($locations)];
    
    // Use the randomly selected location's details
    $sensor_id = $location['sensor_id'];
    $latitude = $location['latitude'];
    $longitude = $location['longitude'];
    $location_name = $location['location_name'];

    // Simulate realistic temperature and humidity
    // For instance, during the day temperatures could be higher
    $currentHour = (int)date('H'); // Get current hour (0-23)

    if ($currentHour >= 6 && $currentHour <= 18) { // Daytime
        $temperature = rand(28, 37); // Average daytime temperature: 28°C - 37°C
        $humidity = rand(50, 75); // Average daytime humidity: 50% - 75%
    } else { // Nighttime
        $temperature = rand(22, 30); // Average nighttime temperature: 22°C - 30°C
        $humidity = rand(70, 90); // Average nighttime humidity: 70% - 90%
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
    

    // Get the current time for alert_time
    $alert_time = date('Y-m-d H:i:s'); // Format: Y-m-d H:i:s

    // Set status to 'active' for the new insertion
    $status = 'active';

    // Insert into the database
    $insertQuery = "INSERT INTO sensor_readings (sensor_id, temperature, humidity, heat_index, alert, latitude, longitude, location_name, alert_time, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param('iddssddsss', $sensor_id, $temperature, $humidity, $heat_index, $alert, $latitude, $longitude, $location_name, $alert_time, $status);
    
    if (!$stmt->execute()) {
        echo "Error inserting data: " . $stmt->error . "\n";
    } else {
        echo "Inserted data for $location_name: Temperature: $temperature, Humidity: $humidity%, Heat Index: $heat_index, Alert: $alert, Status: $status\n";
    }

    // Wait for 5 seconds before the next iteration
    sleep(5);
}

// Close the connection (this will never be reached in an infinite loop)
$stmt->close();
$conn->close();
?>
