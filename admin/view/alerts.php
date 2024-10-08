<?php include '../../fetch_php/admin_protect.php'; ?>
<?php
require '../../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();
// Pagination variables
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total number of records
$totalResultsQuery = "SELECT COUNT(*) AS total FROM sensor_readings";
$totalResults = $conn->query($totalResultsQuery);
$totalRecords = $totalResults->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch the sensor readings with pagination
$query = "SELECT * FROM sensor_readings ORDER BY alert_time DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

// Function to get background color based on the alert level
function getAlertClass($alert) {
    switch ($alert) {
        case 'Extreme Caution':
            return 'bg-warning';
        case 'Danger':
            return 'bg-danger text-white';
        case 'Extreme Danger':
            return 'bg-dark text-white';
        default:
            return 'bg-light'; // Normal
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include '../components/head.php'; ?>
    <style>
        /* Additional custom styling if needed */
        .table-responsive {
            margin-top: 20px;
        }

        .legend {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
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
<div class="container my-5">
    <h1 class="text-center mb-4">Heat Index Alerts</h1>

    <!-- Legend for alert levels -->
    <div class="legend">
        <div class="d-flex align-items-center">
            <div class="legend-color bg-warning"></div>Extreme Caution
        </div>
        <div class="d-flex align-items-center">
            <div class="legend-color bg-danger text-white"></div>Danger
        </div>
        <div class="d-flex align-items-center">
            <div class="legend-color bg-dark text-white"></div>Extreme Danger
        </div>
    </div>

    <!-- Table displaying the sensor readings -->
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Temperature (°C)</th>
                    <th>Humidity (%)</th>
                    <th>Heat Index (°C)</th>
                    <th>Alert</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                    <th>Alert Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="<?= getAlertClass($row['alert']) ?>">
                            <td><?= $row['id'] ?></td>
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
                        <td colspan="8" class="text-center">No data available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

</main>

   <!-- footer and scroll to top -->
   <?php include '../components/footer.php'; ?>
    <!-- include scripts -->
    <?php include '../components/scripts.php'; ?>

</body>
</html>
