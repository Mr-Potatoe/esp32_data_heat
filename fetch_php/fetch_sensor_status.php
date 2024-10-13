<?php
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable error reporting for debugging

require '../config.php'; // Configuration and database connection
loadEnv(); // Load environment variables
$conn = dbConnect(); // Connect to the database

// Get the current page number from the URL, default to 1 if not present
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5; // Number of records per page
$offset = ($page - 1) * $limit; // Calculate offset for pagination

// Query to get the total number of sensor records (distinct by sensor and location)
$totalQuery = "SELECT COUNT(DISTINCT sensor_id, location_name) as total FROM sensor_readings";
$totalResult = $conn->query($totalQuery);
$totalRecords = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit); // Calculate total pages

// Query to fetch the latest sensor data with pagination
$query = "
SELECT 
    sr.sensor_id,
    sr.location_name,
    sr.temperature,
    sr.humidity,
    sr.heat_index,
    sr.alert,
    sr.alert_time AS last_update,
    CASE 
        WHEN TIMESTAMPDIFF(MINUTE, sr.alert_time, NOW()) < 5 THEN 'Active'
        ELSE 'Inactive'
    END AS status
FROM 
    sensor_readings sr
INNER JOIN (
    SELECT sensor_id, MAX(alert_time) AS max_alert_time
    FROM sensor_readings
    GROUP BY sensor_id
) AS latest ON sr.sensor_id = latest.sensor_id AND sr.alert_time = latest.max_alert_time
ORDER BY 
    sr.alert_time DESC
LIMIT $limit OFFSET $offset";




$result = $conn->query($query);

// Prepare sensor data
$sensor_data = [];
while ($row = $result->fetch_assoc()) {
    $sensor_data[] = $row;
}

// Pagination HTML structure
$paginationHTML = '<nav aria-label="Page navigation">';
$paginationHTML .= '<ul class="pagination justify-content-center">';

// Previous Button
$paginationHTML .= '<li class="page-item ' . (($page == 1) ? 'disabled' : '') . '">';
$paginationHTML .= '<a href="?page=' . max(1, $page - 1) . '" class="page-link">Previous</a></li>';

// Page Numbers
$visiblePages = 5;
$startPage = max(1, $page - floor($visiblePages / 2));
$endPage = min($totalPages, $page + floor($visiblePages / 2));

// Adjust if near the start or end of the page list
if ($page <= floor($visiblePages / 2)) {
    $endPage = min($visiblePages, $totalPages);
}
if ($page + floor($visiblePages / 2) >= $totalPages) {
    $startPage = max(1, $totalPages - $visiblePages + 1);
}

for ($i = $startPage; $i <= $endPage; $i++) {
    $paginationHTML .= '<li class="page-item ' . (($i == $page) ? 'active' : '') . '">';
    $paginationHTML .= '<a href="?page=' . $i . '" class="page-link">' . $i . '</a></li>';
}

// Next Button
$paginationHTML .= '<li class="page-item ' . (($page == $totalPages) ? 'disabled' : '') . '">';
$paginationHTML .= '<a href="?page=' . min($totalPages, $page + 1) . '" class="page-link">Next</a></li>';
$paginationHTML .= '</ul></nav>';

// Return sensor data and pagination HTML
$response = [
    'sensors' => $sensor_data,
    'paginationHTML' => $paginationHTML
];

echo json_encode($response);

?>
