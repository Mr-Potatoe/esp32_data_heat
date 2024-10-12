<?php
require 'config.php'; // Configuration and database connection
loadEnv(); // Load environment variables
$conn = dbConnect(); // Connect to the database

// Define the location parameters
$locations = [
    ['sensor_id' => 15, 'latitude' => 7.9473004, 'longitude' => 123.5876167, 'location_name' => 'First location near guardhouse'],
    ['sensor_id' => 16, 'latitude' => 7.9480162, 'longitude' => 123.5881823, 'location_name' => 'Second location near main building right side'],
    ['sensor_id' => 17, 'latitude' => 7.947642, 'longitude' => 123.588116, 'location_name' => '3rd location behind main hall'],
    ['sensor_id' => 18, 'latitude' => 7.948238, 'longitude' => 123.588524, 'location_name' => '4th location near Akasya tree (left)'],
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

    // Generate random temperature and humidity
    // $temperature = rand(25, 35); // Random temperature between 25°C and 35°C
    // $humidity = rand(50, 80); // Random humidity between 50% and 80%

    // Generate random temperature and humidity
$temperature = rand(35, 45); // Higher temperature range: 35°C - 45°C
$humidity = rand(70, 100); // Higher humidity range: 70% - 100%


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

    // Get the current time for alert_time
    $alert_time = date('Y-m-d H:i:s'); // Format: Y-m-d H:i:s

    // Insert into the database
    $insertQuery = "INSERT INTO sensor_readings (sensor_id, temperature, humidity, heat_index, alert, latitude, longitude, location_name, alert_time)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param('iddssddss', $sensor_id, $temperature, $humidity, $heat_index, $alert, $latitude, $longitude, $location_name, $alert_time);
    
    if (!$stmt->execute()) {
        echo "Error inserting data: " . $stmt->error . "\n";
    } else {
        echo "Inserted data for $location_name: Temperature: $temperature, Humidity: $humidity%, Heat Index: $heat_index, Alert: $alert\n";
    }

    // Wait for 5 seconds before the next iteration
    sleep(5);
}

// Close the connection (this will never be reached in an infinite loop)
$stmt->close();
$conn->close();
?>
