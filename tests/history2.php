<?php
require '../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

date_default_timezone_set('Asia/Manila');

// Get all available locations
$allLocationsQuery = "SELECT DISTINCT location_name FROM sensor_readings ORDER BY location_name";
$allLocationsResult = $conn->query($allLocationsQuery);
$allLocations = [];
while ($row = $allLocationsResult->fetch_assoc()) {
    $allLocations[] = $row['location_name'];
}

// Get selected locations from the form
$selectedLocations = isset($_GET['locations']) ? $_GET['locations'] : $allLocations;

// Get the filter type from the dropdown (hourly, daily, etc.)
$filterType = isset($_GET['filter']) ? $_GET['filter'] : 'hourly';

// Get the start and end date from the form, default to past year
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d H:i', strtotime('-1 year'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d H:i');

// Prepare the SQL query to use alert_time instead of timestamp with date range and location filter
// Prepare the SQL query to use alert_time instead of timestamp with date range and location filter
$locationPlaceholders = implode(',', array_fill(0, count($selectedLocations), '?'));
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
    WHERE alert_time >= ? AND alert_time <= ? AND location_name IN ($locationPlaceholders)
    GROUP BY location_name, period
    ORDER BY location_name, period DESC";

$stmt = $conn->prepare($sql);

// Create an array of parameters for binding
$params = array_merge([$filterType, $filterType, $filterType, $filterType, $filterType, $startDate, $endDate], $selectedLocations);

// Create a string of types for bind_param
$types = str_repeat('s', count($params));

// Ensure parameters are passed by reference
$refs = array();
foreach ($params as $key => $value) {
    $refs[$key] = &$params[$key]; // Using a reference here
}

// Bind parameters using call_user_func_array
call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $refs));



$stmt->execute();
$result = $stmt->get_result();

// Function to determine the background color class based on the heat index
function getAlertLevelAndClass($heatIndex) {
    if ($heatIndex < 27) {
        return ['Not Hazardous', 'normal']; // Normal (<27°C)
    } elseif ($heatIndex < 33) { // Caution is < 33
        return ['Caution', 'caution']; // Caution (27°C - <33°C)
    } elseif ($heatIndex < 42) { // Extreme Caution is < 42
        return ['Extreme Caution', 'extreme-caution']; // Extreme Caution (33°C - <42°C)
    } elseif ($heatIndex < 52) { // Danger is < 52
        return ['Danger', 'danger']; // Danger (42°C - <52°C)
    } else {
        return ['Extreme Danger', 'extreme-danger']; // Extreme Danger (>=52°C)
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
    <link rel="stylesheet" href="../../assets/css/page.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>
</head>
<body>
    <main id="main" class="main">
        <div class="container">
            <h1><i class="bi bi-table"></i> Heatmap Table Data by Location</h1>

            <div class="card p-3 mb-4 filter-form">
                <h5 class="card-title">Filter Data</h5>
                <form method="GET">
                    <div class="form-row d-flex flex-wrap">
                        <!-- Location Filter Dropdown -->
                        <div class="form-group col-md-4 col-sm-12">
                            <label for="locations">Select Locations:</label>
                            <select id="locations" name="locations[]" class="form-control" multiple>
                                <?php foreach ($allLocations as $location): ?>
                                    <option value="<?= htmlspecialchars($location) ?>" <?= in_array($location, $selectedLocations) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($location) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group col-md-4 col-sm-12">
                            <label for="filter" class="mr-2">Select Time Filter:</label>
                            <div class="dropdown-icon-wrapper">
                                <select id="filter" name="filter" class="form-control">
                                    <option value="hourly" <?= $filterType == 'hourly' ? 'selected' : '' ?>>Hourly</option>
                                    <option value="daily" <?= $filterType == 'daily' ? 'selected' : '' ?>>Daily</option>
                                    <option value="weekly" <?= $filterType == 'weekly' ? 'selected' : '' ?>>Weekly</option>
                                    <option value="monthly" <?= $filterType == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                    <option value="yearly" <?= $filterType == 'yearly' ? 'selected' : '' ?>>Yearly</option>
                                </select>
                                <i class="fas fa-chevron-down dropdown-icon"></i> <!-- Font Awesome icon -->
                            </div>
                        </div>

                        <!-- Start Date Input -->
                        <div class="form-group col-md-4 col-sm-12">
                            <label for="start_date" class="mr-2">Start Date and Time:</label>
                            <input type="datetime-local" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
                        </div>

                        <!-- End Date Input -->
                        <div class="form-group col-md-4 col-sm-12">
                            <label for="end_date" class="mr-2">End Date and Time:</label>
                            <input type="datetime-local" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
                        </div>
                    </div>

                    <!-- Filter Button -->
                    <div class="form-group d-flex justify-content-start">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="history.php" class="btn btn-secondary">Clear Filters</a>
                    </div>
                </form>
            </div>

            <?php
            // Reset result pointer
            $stmt->execute();
            $result = $stmt->get_result();

            $currentLocation = null;
            while ($row = $result->fetch_assoc()):
                if ($currentLocation !== $row['location_name']):
                    if ($currentLocation !== null):
                        // Close previous table if it exists
                        echo '</tbody></table></div>';
                    endif;
                    $currentLocation = $row['location_name'];
            ?>
                    <div class="container">
                        <h2><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($currentLocation) ?></h2>
                        <table class="table table-striped table-bordered">
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
                endif;

                // Determine alert level and CSS class
                list($alertLevel, $alertClass) = getAlertLevelAndClass($row['avg_heat_index']);
            ?>
                <tr class="<?= htmlspecialchars($alertClass) ?>">
                    <td><?= formatPeriod($row['period'], $filterType) ?></td>
                    <td><?= htmlspecialchars(round($row['avg_temp'], 2)) ?></td>
                    <td><?= htmlspecialchars(round($row['avg_humidity'], 2)) ?></td>
                    <td><?= htmlspecialchars(round($row['avg_heat_index'], 2)) ?></td>
                    <td><?= htmlspecialchars($alertLevel) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
            </table>
        </div>

        <?php
        // Closing last location's table
        if ($currentLocation !== null):
        ?>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
