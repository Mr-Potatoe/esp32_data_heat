<?php include '../../fetch_php/admin_protect.php'; ?>
<?php
require '../../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

require_once '../../fetch_php/fetch_history.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../components/head.php'; ?>
    <style>
        /* Basic CSS for table and color coding */
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
    <h1>Heatmap Data Table</h1>

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

    <!-- Data Table -->
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
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $alertClass = getAlertClass($row['avg_heat_index']); // Get the appropriate color class
                    echo "<tr class='{$alertClass}'>";
                    echo "<td>{$row['period']}</td>";
                    echo "<td>{$row['avg_temp']}</td>";
                    echo "<td>{$row['avg_humidity']}</td>";
                    echo "<td>{$row['avg_heat_index']}</td>";
                    echo "<td>" . ucfirst(str_replace('-', ' ', $alertClass)) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No data available</td></tr>";
            }
            ?>
        </tbody>
    </table>

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
