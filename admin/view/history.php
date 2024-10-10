<?php include '../../fetch_php/admin_protect.php'; ?>
<?php
require '../../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

// Pagination variables
$locationsPerPage = 2; // Number of location tables per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
$offset = ($page - 1) * $locationsPerPage; // Calculate offset

// Get the total number of locations
$totalLocationsQuery = "SELECT COUNT(DISTINCT location_name) AS total_locations FROM sensor_readings";
$totalLocationsResult = $conn->query($totalLocationsQuery);
$totalLocationsRow = $totalLocationsResult->fetch_assoc();
$totalLocations = $totalLocationsRow['total_locations'];

// Calculate total number of pages
$totalPages = ceil($totalLocations / $locationsPerPage);

// Fetch the current page's locations with pagination
$locationsQuery = "SELECT DISTINCT location_name FROM sensor_readings LIMIT $locationsPerPage OFFSET $offset";
$locationsResult = $conn->query($locationsQuery);

// Get the filter type from the dropdown (hourly, daily, etc.)
$filterType = isset($_GET['filter']) ? $_GET['filter'] : 'hourly';

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

// Helper function to format the period column in a more human-readable way
function formatPeriod($period, $filterType) {
    $date = new DateTime($period);
    
    switch ($filterType) {
        case 'hourly':
            return $date->format('F j, Y, g A'); // Example: January 1, 2024, 1 PM
        case 'daily':
            return $date->format('F j, Y'); // Example: January 1, 2024
        case 'weekly':
            return 'Week ' . $date->format('W, Y'); // Example: Week 1, 2024
        case 'monthly':
            return $date->format('F Y'); // Example: January 2024
        case 'yearly':
            return $date->format('Y'); // Example: 2024
        default:
            return $period;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../components/head.php'; ?>

<style>
.container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

h1, h2 {
    margin-bottom: 20px;
}

form {
    margin-bottom: 20px;
}

label {
    font-weight: bold;
    margin-right: 10px;
}

select {
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #ddd;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

th, td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: center;
}

th {
    background-color: #f4f4f4;
    color: #555;
}

.normal {
    background-color: #e5e5e5;
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

.legend {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap; /* Wrap items on smaller screens */
}

.legend div {
    display: flex;
    align-items: center;
    margin: 5px 0;
}

.legend-color {
    width: 20px;
    height: 20px;
    margin-right: 10px;
}

/* Responsive styles */
@media (max-width: 768px) {
    table {
        font-size: 14px; /* Adjust font size for smaller screens */
    }

    th, td {
        padding: 8px; /* Reduce padding */
    }

    h1 {
        font-size: 24px; /* Adjust heading size */
    }

    h2 {
        font-size: 20px; /* Adjust subheading size */
    }
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

            <!-- Legend -->
            <div class="legend">
                <div><div class="legend-color normal"></div>Normal (&lt;27°C)</div>
                <div><div class="legend-color caution"></div>Caution (27°C - 32°C)</div>
                <div><div class="legend-color extreme-caution"></div>Extreme Caution (32°C - 41°C)</div>
                <div><div class="legend-color danger"></div>Danger (41°C - 54°C)</div>
                <div><div class="legend-color extreme-danger"></div>Extreme Danger (&gt;54°C)</div>
            </div>

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
                            while ($row = $result->fetch_assoc()) {
                                if ($row['location_name'] == $locationName) {
                                    $alertClass = getAlertClass($row['avg_heat_index']);
                                    ?>
                                    <tr class="<?= $alertClass ?>">
                                        <td><?= formatPeriod($row['period'], $filterType) ?></td>
                                        <td><?= number_format($row['avg_temp'], 2) ?></td>
                                        <td><?= number_format($row['avg_humidity'], 2) ?></td>
                                        <td><?= number_format($row['avg_heat_index'], 2) ?></td>
                                        <td><?= ucfirst(str_replace('-', ' ', $alertClass)) ?></td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No locations found.</p>
            <?php endif; ?>

         <!-- Pagination Links -->
<div class="d-flex justify-content-between align-items-center mt-4">
    <div>
        <!-- Previous Page Link -->
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&filter=<?= $filterType ?>" class="btn btn-outline-primary">
                <i class="bi bi-chevron-left"></i> Previous
            </a>
        <?php else: ?>
            <button class="btn btn-outline-secondary" disabled>
                <i class="bi bi-chevron-left"></i> Previous
            </button>
        <?php endif; ?>
    </div>

    <div>
        <span>Page <?= $page ?> of <?= $totalPages ?></span>
    </div>

    <div>
        <!-- Next Page Link -->
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&filter=<?= $filterType ?>" class="btn btn-outline-primary">
                Next <i class="bi bi-chevron-right"></i>
            </a>
        <?php else: ?>
            <button class="btn btn-outline-secondary" disabled>
                Next <i class="bi bi-chevron-right"></i>
            </button>
        <?php endif; ?>
    </div>
</div>


        </div>
    </main><!-- End #main -->

    <!-- ======= Footer ======= -->
    <?php include '../components/footer.php'; ?>
    <?php include '../components/scripts.php'; ?>
</body>

</html>
