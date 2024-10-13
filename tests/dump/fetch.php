<?php
// Include the config.php file
require 'config.php'; // Adjust the path if needed

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();
// Function to get the alert class based on heat index
function getAlertClass($heatIndex) {
    if ($heatIndex < 27) return 'normal';
    if ($heatIndex < 32) return 'caution';
    if ($heatIndex < 41) return 'extreme-caution';
    if ($heatIndex < 54) return 'danger';
    return 'extreme-danger';
}

// Helper function to format the period
function formatPeriod($period, $filterType) {
    $date = new DateTime($period);
    switch ($filterType) {
        case 'hourly': return $date->format('F j, Y, g A');
        case 'daily': return $date->format('F j, Y');
        case 'weekly': return 'Week ' . $date->format('W, Y');
        case 'monthly': return $date->format('F Y');
        case 'yearly': return $date->format('Y');
        default: return $period;
    }
}

// Function to render a location table
function renderLocationTable($locationRow, $filterType, $stmt) {
    $locationName = $locationRow['location_name'];
    echo '<h2>Location: ' . htmlspecialchars($locationName) . '</h2>';
    echo '<button class="btn btn-success downloadPdf" data-location="' . htmlspecialchars($locationName) . '">
            <i class="bi bi-file-earmark-pdf"></i> Download PDF
          </button>';
    
    echo '<table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Avg Temperature (°C)</th>
                    <th>Avg Humidity (%)</th>
                    <th>Avg Heat Index (°C)</th>
                    <th>Alert Level</th>
                </tr>
            </thead>
            <tbody>';
    
    // Execute the prepared statement for current location
    $stmt->execute();
    $result = $stmt->get_result();
    $dataAvailable = false; // Track data availability
    
    while ($row = $result->fetch_assoc()) {
        if ($row['location_name'] == $locationName) {
            $alertClass = getAlertClass($row['avg_heat_index']);
            $dataAvailable = true;
            echo '<tr class="' . $alertClass . '">
                    <td>' . formatPeriod($row['period'], $filterType) . '</td>
                    <td>' . number_format($row['avg_temp'], 2) . '</td>
                    <td>' . number_format($row['avg_humidity'], 2) . '</td>
                    <td>' . number_format($row['avg_heat_index'], 2) . '</td>
                    <td>' . ucfirst(str_replace('-', ' ', $alertClass)) . '</td>
                  </tr>';
        }
    }
    
    if (!$dataAvailable) {
        echo '<tr><td colspan="5">No readings available for this location.</td></tr>';
    }
    
    echo '</tbody></table>';
}

// Function to render pagination controls
function renderPagination($page, $totalPages, $filterType, $startDate, $endDate) {
    echo '<div class="d-flex justify-content-between align-items-center mt-4">';
    // Previous Page Link
    if ($page > 1) {
        echo '<a href="?page=' . ($page - 1) . '&filter=' . $filterType . '&start_date=' . $startDate . '&end_date=' . $endDate . '" class="btn btn-outline-primary">
                <i class="bi bi-chevron-left"></i> Previous
              </a>';
    } else {
        echo '<button class="btn btn-outline-secondary" disabled>
                <i class="bi bi-chevron-left"></i> Previous
              </button>';
    }
    
    echo '<span>Page ' . $page . ' of ' . $totalPages . '</span>';
    
    // Next Page Link
    if ($page < $totalPages) {
        echo '<a href="?page=' . ($page + 1) . '&filter=' . $filterType . '&start_date=' . $startDate . '&end_date=' . $endDate . '" class="btn btn-outline-primary">
                Next <i class="bi bi-chevron-right"></i>
              </a>';
    } else {
        echo '<button class="btn btn-outline-secondary" disabled>
                Next <i class="bi bi-chevron-right"></i>
              </button>';
    }
    echo '</div>';
}

// Database operations
$locationsPerPage = 2;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $locationsPerPage;

$totalLocationsQuery = "SELECT COUNT(DISTINCT location_name) AS total_locations FROM sensor_readings";
$totalLocationsResult = $conn->query($totalLocationsQuery);
$totalLocationsRow = $totalLocationsResult->fetch_assoc();
$totalLocations = $totalLocationsRow['total_locations'];

$totalPages = ceil($totalLocations / $locationsPerPage);

$locationsQuery = "SELECT DISTINCT location_name FROM sensor_readings LIMIT $locationsPerPage OFFSET $offset";
$locationsResult = $conn->query($locationsQuery);

// Get filter type and date range
$filterType = $_GET['filter'] ?? 'hourly';
$startDate = $_GET['start_date'] ?? date('Y-m-d H:i:s', strtotime('-1 year'));
$endDate = $_GET['end_date'] ?? date('Y-m-d H:i:s');

// Prepare SQL query for sensor readings
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
    WHERE alert_time >= ? AND alert_time <= ?
    GROUP BY location_name, period
    ORDER BY location_name, period DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssss", $filterType, $filterType, $filterType, $filterType, $filterType, $startDate, $endDate);

// Render locations and pagination
if ($locationsResult && $locationsResult->num_rows > 0) {
    while ($locationRow = $locationsResult->fetch_assoc()) {
        renderLocationTable($locationRow, $filterType, $stmt);
    }
    renderPagination($page, $totalPages, $filterType, $startDate, $endDate);
} else {
    echo '<p>No locations available for the selected filters.</p>';
}

$stmt->close();
?>
