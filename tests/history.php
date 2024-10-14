
<?php
require '../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

date_default_timezone_set('Asia/Manila');

// Pagination variables
$locationsPerPage = 100; // Number of location tables per page
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

// Get the start and end date from the form, default to past week
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d H:i', strtotime('-1 year'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d H:i');

// Get the location filter from the form
$locationFilter = isset($_GET['location_filter']) ? $_GET['location_filter'] : '';

// Prepare the SQL query to use alert_time instead of timestamp with date range
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
    WHERE alert_time >= ? AND alert_time <= ?";

// Include location filter if it exists
if (!empty($_GET['location_filter'])) {
    $locationFilter = $_GET['location_filter'];
    $sql .= " AND location_name = ?";
}

// Grouping and ordering
$sql .= " GROUP BY location_name, period
          ORDER BY location_name, period DESC";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Prepare parameters for binding
$params = [$filterType, $filterType, $filterType, $filterType, $filterType, $startDate, $endDate];

// Add location filter to parameters if set
if (!empty($locationFilter)) {
    $params[] = $locationFilter;
}

// Create a type string for bind_param based on number of parameters
$typeString = str_repeat('s', count($params));

// Bind all parameters
$stmt->bind_param($typeString, ...$params);
$stmt->execute();
$result = $stmt->get_result();


// Function to determine the background color class based on the heat index
function getAlertLevelAndClass($heatIndex) {
    if ($heatIndex < 27) {
        return ['Not Hazardous', 'normal']; // Normal (<27°C)
    } elseif ($heatIndex >= 27 && $heatIndex < 33) { // Caution is < 33
        return ['Caution', 'caution']; // Caution (27°C - <33°C)
    } elseif ($heatIndex >= 33 && $heatIndex < 42) { // Extreme Caution is < 42
        return ['Extreme Caution', 'extreme-caution']; // Extreme Caution (33°C - <42°C)
    } elseif ($heatIndex >= 42 && $heatIndex < 52) { // Danger is < 52
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
<!-- Time Filter Dropdown with Icon -->
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


<!-- Location Filter Dropdown -->
<div class="form-group col-md-4 col-sm-12">
    <label for="location_filter" class="mr-2">Select Location:</label>
    <select id="location_filter" name="location_filter" class="form-control">
        <option value="">All Locations</option>
        <?php
        // Fetch all distinct locations for the dropdown
        $distinctLocationsQuery = "SELECT DISTINCT location_name FROM sensor_readings";
        $distinctLocationsResult = $conn->query($distinctLocationsQuery);
        
        while ($distinctLocationRow = $distinctLocationsResult->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($distinctLocationRow['location_name']) ?>" 
                <?= isset($_GET['location_filter']) && $_GET['location_filter'] == $distinctLocationRow['location_name'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($distinctLocationRow['location_name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
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

        <!-- Filter Button (aligned at the bottom left) -->
        <div class="form-group  d-flex justify-content-start">
            <button type="submit" class="btn btn-primary me-2">Filter</button>
            <a href="history.php" class="btn btn-secondary">Clear Filters</a>
        </div>
    </form>
</div>




<?php if ($locationsResult && $locationsResult->num_rows > 0): ?>
    <?php while ($locationRow = $locationsResult->fetch_assoc()): ?>
<!-- Responsive Wrapper for Heading and Table -->
<div class="container">
    <h2><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($locationRow['location_name']) ?></h2>

    <!-- Download PDF Button for each location -->
    <button class="btn btn-success downloadPdf" data-location="<?= htmlspecialchars($locationRow['location_name']) ?>">
        <i class="bi bi-file-earmark-pdf"></i> Download PDF
    </button>

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
            // Reset result pointer
            $locationName = $locationRow['location_name'];
            $stmt->execute(); // Execute the prepared statement again for new results
            $result = $stmt->get_result();

            $dataAvailable = false; // Track if data is available for the current location
            while ($row = $result->fetch_assoc()) {
                if ($row['location_name'] == $locationName) {
                    // Use the updated function to get both alert level and class
                    list($alertLevel, $alertClass) = getAlertLevelAndClass($row['avg_heat_index']);
                    $dataAvailable = true; // Data exists for this location
                    ?>
                    <tr class="<?= $alertClass ?>">
                        <td><?= formatPeriod($row['period'], $filterType) ?></td>
                        <td><?= number_format($row['avg_temp'], 2) ?></td>
                        <td><?= number_format($row['avg_humidity'], 2) ?></td>
                        <td><?= number_format($row['avg_heat_index'], 2) ?></td>
                        <td><?= $alertLevel ?></td> <!-- Displaying alert level text -->
                    </tr>
                    <?php
                }
            }
            if (!$dataAvailable) {
                echo '<tr><td colspan="5">No readings available for this location.</td></tr>';
            }
            ?>
            </tbody>
        </table>
</div>

    <?php endwhile; ?>
<?php else: ?>
    <p>No locations available for the selected filters.</p>
<?php endif; ?>


            <!-- Pagination Controls -->

        </div>
    </main>




</body>

</html>
