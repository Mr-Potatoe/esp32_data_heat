<?php include '../../fetch_php/admin_protect.php'; ?>
<?php
require '../../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

// Define how many results you want per page
$resultsPerPage = 10;

// Find out the number of results stored in the database
$result = $conn->query("SELECT COUNT(*) AS total FROM sensor_readings");
$row = $result->fetch_assoc();
$totalResults = $row['total'];

// Calculate the number of pages needed
$totalPages = ceil($totalResults / $resultsPerPage);

// Get the current page number from URL, if not set, default to 1
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, min($currentPage, $totalPages)); // Ensure it's within range

// Calculate the starting limit for the SQL query
$startLimit = ($currentPage - 1) * $resultsPerPage;

// Fetch all unique sensor IDs
$sensorIds = $conn->query("SELECT DISTINCT sensor_id FROM sensor_readings")->fetch_all(MYSQLI_ASSOC);

// Get the selected sensor_id from the dropdown
$selectedSensorId = isset($_GET['sensor_id']) ? $_GET['sensor_id'] : '';

// Prepare the SQL query to fetch data based on the selected sensor_id with pagination
$sql = "SELECT * FROM sensor_readings";
if ($selectedSensorId) {
    $sql .= " WHERE sensor_id = ?";
}
$sql .= " LIMIT ?, ?";

// Prepare the statement
$stmt = $conn->prepare($sql);
if ($selectedSensorId) {
    $stmt->bind_param("sii", $selectedSensorId, $startLimit, $resultsPerPage); // Binding sensor_id as string, and start limit and results per page as integers
} else {
    $stmt->bind_param("ii", $startLimit, $resultsPerPage); // Binding start limit and results per page as integers
}

// Execute the statement
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../components/head.php'; ?>
    <style>
        /* Custom styles for the alert levels */
        .normal { background-color: #dff0d8; }
        .caution { background-color: #fcf8e3; }
        .extreme-caution { background-color: #f0ad4e; }
        .danger { background-color: #d9534f; }
        .extreme-danger { background-color: #c9302c; }
    </style>
</head>
<body>

    <!-- ======= Header ======= -->
    <?php include '../components/header.php'; ?>

    <!-- ======= Sidebar ======= -->
    <?php include '../components/sidebar.php'; ?>

    <main id="main" class="main">
        <div class="container mt-5">
            <h2>Sensor Data View</h2>

            <form method="GET" class="mb-4">
                <div class="form-group">
                    <label for="sensor_id">Select Sensor ID:</label>
                    <select name="sensor_id" id="sensor_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- All Sensors --</option>
                        <?php foreach ($sensorIds as $sensor): ?>
                            <option value="<?= $sensor['sensor_id'] ?>" <?= ($selectedSensorId == $sensor['sensor_id']) ? 'selected' : '' ?>>
                                Sensor ID: <?= $sensor['sensor_id'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <table class="table table-bordered table-responsive">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Sensor ID</th>
                        <th>Temperature (°C)</th>
                        <th>Humidity (%)</th>
                        <th>Heat Index</th>
                        <th>Alert Level</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Alert Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="<?= getAlertClass($row['heat_index']) ?>">
                                <td><?= $row['id'] ?></td>
                                <td><?= $row['sensor_id'] ?></td>
                                <td><?= $row['temperature'] ?></td>
                                <td><?= $row['humidity'] ?></td>
                                <td><?= $row['heat_index'] ?></td>
                                <td><?= $row['alert'] ?></td>
                                <td><?= $row['latitude'] ?></td>
                                <td><?= $row['longitude'] ?></td>
                                <td><?= $row['alert_time'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No data available for this sensor.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $currentPage - 1 ?>&sensor_id=<?= $selectedSensorId ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($currentPage == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&sensor_id=<?= $selectedSensorId ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $currentPage + 1 ?>&sensor_id=<?= $selectedSensorId ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <!-- footer and scroll to top -->
    <?php include '../components/footer.php'; ?>
    <!-- include scripts -->
    <?php include '../components/scripts.php'; ?>

</body>
</html>

<?php
// Function to determine the background color based on the heat index
function getAlertClass($heatIndex) {
    if ($heatIndex < 27) {
        return 'normal'; // No background color for normal
    } elseif ($heatIndex >= 27 && $heatIndex < 32) {
        return 'caution'; // Yellow
    } elseif ($heatIndex >= 32 && $heatIndex < 41) {
        return 'extreme-caution'; // Orange
    } elseif ($heatIndex >= 41 && $heatIndex < 54) {
        return 'danger'; // Red
    } else {
        return 'extreme-danger'; // Dark Red
    }
}
?>
