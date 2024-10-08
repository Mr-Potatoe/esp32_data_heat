<?php
// Include the config.php file
require 'config.php'; // Adjust the path if needed

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

// Handle filter if set
$filterAlert = isset($_GET['alert']) ? $_GET['alert'] : 'All';

// Prepare SQL query based on filter
if ($filterAlert === 'All') {
    $sql = "SELECT * FROM sensor_readings ORDER BY alert_time DESC";
} else {
    $sql = "SELECT * FROM sensor_readings WHERE alert = '$filterAlert' ORDER BY alert_time DESC";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sensor Data with Alerts and Locations</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="container mx-auto mt-10">
    <h1 class="text-3xl font-bold mb-5 text-center">Sensor Data Overview</h1>

    <!-- Filter Form -->
    <form method="GET" class="mb-5">
        <label for="alert" class="mr-2">Filter by Alert Level:</label>
        <select name="alert" id="alert" class="border border-gray-400 rounded px-2 py-1">
            <option value="All" <?php if ($filterAlert === 'All') echo 'selected'; ?>>All</option>
            <option value="Normal" <?php if ($filterAlert === 'Normal') echo 'selected'; ?>>Normal</option>
            <option value="Caution" <?php if ($filterAlert === 'Caution') echo 'selected'; ?>>Caution</option>
            <option value="Extreme Caution" <?php if ($filterAlert === 'Extreme Caution') echo 'selected'; ?>>Extreme Caution</option>
            <option value="Danger" <?php if ($filterAlert === 'Danger') echo 'selected'; ?>>Danger</option>
            <option value="Extreme Danger" <?php if ($filterAlert === 'Extreme Danger') echo 'selected'; ?>>Extreme Danger</option>
        </select>
        <button type="submit" class="bg-blue-500 text-white px-4 py-1 rounded ml-2">Filter</button>
    </form>

    <!-- Data Table -->
    <div class="overflow-x-auto">
        <table class="table-auto w-full bg-white shadow-md rounded-lg">
            <thead>
                <tr class="bg-gray-700 text-white">
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Temperature (°C)</th>
                    <th class="px-4 py-2">Humidity (%)</th>
                    <th class="px-4 py-2">Heat Index (°C)</th>
                    <th class="px-4 py-2">Alert Level</th>
                    <th class="px-4 py-2">Latitude</th>
                    <th class="px-4 py-2">Longitude</th>
                    <th class="px-4 py-2">Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Determine row color based on alert level
                        switch ($row['alert']) {
                            case 'Caution':
                                $rowClass = 'bg-yellow-100';
                                break;
                            case 'Extreme Caution':
                                $rowClass = 'bg-orange-100';
                                break;
                            case 'Danger':
                                $rowClass = 'bg-red-100';
                                break;
                            case 'Extreme Danger':
                                $rowClass = 'bg-red-300';
                                break;
                            default:
                                $rowClass = '';
                        }
                        echo "<tr class='$rowClass'>";
                        echo "<td class='border px-4 py-2'>{$row['id']}</td>";
                        echo "<td class='border px-4 py-2'>{$row['temperature']}</td>";
                        echo "<td class='border px-4 py-2'>{$row['humidity']}</td>";
                        echo "<td class='border px-4 py-2'>{$row['heat_index']}</td>";
                        echo "<td class='border px-4 py-2'>{$row['alert']}</td>";
                        echo "<td class='border px-4 py-2'>{$row['latitude']}</td>";
                        echo "<td class='border px-4 py-2'>{$row['longitude']}</td>";
                        echo "<td class='border px-4 py-2'>{$row['alert_time']}</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center py-4'>No data available</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>


</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
