<?php
require '../config.php'; // Configuration and database connection
loadEnv(); // Load environment variables
$conn = dbConnect(); // Connect to the database

// Execute the query to get sensor statuses
$query = "
SELECT 
    sensor_id,
    location_name,
    MAX(alert_time) AS last_update,
    CASE 
        WHEN TIMESTAMPDIFF(MINUTE, MAX(alert_time), NOW()) < 5 THEN 'Active'
        ELSE 'Inactive'
    END AS status
FROM 
    sensor_readings
GROUP BY 
    sensor_id, location_name
ORDER BY 
    last_update DESC
";

$result = $conn->query($query);

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensor Status Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Sensor Status Dashboard</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Sensor ID</th>
                    <th>Location Name</th>
                    <th>Last Update</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Check if there are results
                if ($result->num_rows > 0) {
                    // Fetch each row and display it in the table
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['sensor_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['location_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['last_update']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No sensors found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
