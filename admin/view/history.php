<?php include '../../fetch_php/admin_protect.php'; ?>
<?php
require '../../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

// Get the filter type from the dropdown (hourly, daily, etc.)
$filterType = isset($_GET['filter']) ? $_GET['filter'] : 'hourly';

// Fetch all distinct locations
$locationsQuery = "SELECT DISTINCT location_name FROM sensor_readings";
$locationsResult = $conn->query($locationsQuery);

// Modify the SQL query to use alert_time instead of timestamp
$sql = "
    SELECT location_name, 
           CASE 
               WHEN ? = 'hourly' THEN DATE_FORMAT(alert_time, '%Y-%m-%d %H:00:00')
               WHEN ? = 'daily' THEN DATE_FORMAT(alert_time, '%Y-%m-%d')
               WHEN ? = 'weekly' THEN CONCAT(YEAR(alert_time), '-W', WEEK(alert_time))
               WHEN ? = 'monthly' THEN DATE_FORMAT(alert_time, '%Y-%m')
               WHEN ? = 'yearly' THEN YEAR(alert_time)
           END AS period, 
           AVG(temperature) AS avg_temp, 
           AVG(humidity) AS avg_humidity, 
           AVG(heat_index) AS avg_heat_index
    FROM sensor_readings
    GROUP BY location_name, period
    ORDER BY location_name, period";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $filterType, $filterType, $filterType, $filterType, $filterType);
$stmt->execute();
$result = $stmt->get_result();



// Function to determine the background color class based on the heat index
function getAlertClass($heatIndex) {
    if ($heatIndex < 27) {
        return 'normal';
    } elseif ($heatIndex >= 27 && $heatIndex < 32) {
        return 'caution';
    } elseif ($heatIndex >= 32 && $heatIndex < 41) {
        return 'extreme-caution';
    } elseif ($heatIndex >= 41 && $heatIndex < 54) {
        return 'danger';
    } else {
        return 'extreme-danger';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../components/head.php'; ?>
    <style>
        /* Table and color coding */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
        .normal {
            background-color: #ffffff;
        }
        .caution {
            background-color: #ffff99; /* Light Yellow */
        }
        .extreme-caution {
            background-color: #ffcc99; /* Light Orange */
        }
        .danger {
            background-color: #ff9999; /* Light Red */
        }
        .extreme-danger {
            background-color: #ff6666; /* Darker Red */
        }
        /* Legend styling */
        .legend {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .legend div {
            display: flex;
            align-items: center;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <!-- ======= Header ======= -->
    <?php include '../components/header.php'; ?>


    <!-- ======= Sidebar ======= -->
    <?php include '../components/sidebar.php'; ?>

    <main id="main" class="main">
<div class="container">
    <h1>Heatmap Data by Location</h1>

    <!-- Filter Dropdown -->
    <form method="GET">
        <label for="filter">Select Time Filter:</label>
        <select id="filter" name="filter" onchange="this.form.submit()">
            <option value="hourly" <?= $filterType == 'hourly' ? 'selected' : '' ?>>Hourly</option>
            <option value="daily" <?= $filterType == 'daily' ? 'selected' : '' ?>>Daily</option>
            <option value="weekly" <?= $filterType == 'weekly' ? 'selected' : '' ?>>Weekly</option>
            <option value="monthly" <?= $filterType == 'monthly' ? 'selected' : '' ?>>Monthly</option>
            <option value="yearly" <?= $filterType == 'yearly' ? 'selected' : '' ?>>Yearly</option>
        </select>
    </form>

    <!-- Loop through each location and generate a table for each -->
    <?php if ($locationsResult && $locationsResult->num_rows > 0): ?>
        <?php while ($locationRow = $locationsResult->fetch_assoc()): ?>
            <h2>Location: <?= htmlspecialchars($locationRow['location_name']) ?></h2>

            <table>
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Avg Temperature (°C)</th>
                        <th>Avg Humidity (%)</th>
                        <th>Avg Heat Index (°C)</th>
                        <th>Alert Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Reset result pointer and loop through data to display only for the current location
                    $result->data_seek(0);
                    $locationName = $locationRow['location_name'];
                    $hasData = false; // To check if the current location has any data
                    
                    while ($row = $result->fetch_assoc()) {
                        if ($row['location_name'] == $locationName) {
                            $hasData = true;
                            $alertClass = getAlertClass($row['avg_heat_index']);
                            echo "<tr class='{$alertClass}'>";
                            echo "<td>{$row['period']}</td>";
                            echo "<td>{$row['avg_temp']}</td>";
                            echo "<td>{$row['avg_humidity']}</td>";
                            echo "<td>{$row['avg_heat_index']}</td>";
                            echo "<td>" . ucfirst(str_replace('-', ' ', $alertClass)) . "</td>";
                            echo "</tr>";
                        }
                    }
                    
                    // If no data is available for the location
                    if (!$hasData) {
                        echo "<tr><td colspan='5'>No data available for this location.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No locations found.</p>
    <?php endif; ?>

    <!-- Legend -->
    <div class="legend">
        <div><div class="legend-color normal"></div>Normal (&lt;27°C)</div>
        <div><div class="legend-color caution"></div>Caution (27°C - 32°C)</div>
        <div><div class="legend-color extreme-caution"></div>Extreme Caution (32°C - 41°C)</div>
        <div><div class="legend-color danger"></div>Danger (41°C - 54°C)</div>
        <div><div class="legend-color extreme-danger"></div>Extreme Danger (&gt;54°C)</div>
    </div>
</div>
</main>

   <!-- footer and scroll to top -->
   <?php include '../components/footer.php'; ?>
    <!-- include scripts -->
    <?php include '../components/scripts.php'; ?>
    
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
