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

// Fetch all unique location names
$locations = $conn->query("SELECT DISTINCT location_name FROM sensor_readings")->fetch_all(MYSQLI_ASSOC);

// Get the selected location and date range from the dropdown and filters
$selectedLocation = isset($_GET['location_name']) ? $_GET['location_name'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d\TH:i', strtotime('-1 day'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d\TH:i');


// Prepare the SQL query to fetch data based on the selected location and date range with pagination
$sql = "SELECT *, 
               CASE 
                   WHEN heat_index < 27 THEN 'Normal' 
                   WHEN heat_index >= 27 AND heat_index < 32 THEN 'Caution' 
                   WHEN heat_index >= 32 AND heat_index < 41 THEN 'Extreme Caution' 
                   WHEN heat_index >= 41 AND heat_index < 54 THEN 'Danger' 
                   ELSE 'Extreme Danger' 
               END AS alert_level 
         FROM sensor_readings WHERE 1=1";

$params = [];
if ($selectedLocation) {
    $sql .= " AND location_name = ?";
    $params[] = $selectedLocation;
}
if ($startDate) {
    $sql .= " AND alert_time >= ?";
    $params[] = $startDate; // Already in 'Y-m-d H:i:s' format
}
if ($endDate) {
    $sql .= " AND alert_time <= ?";
    $params[] = $endDate; // Already in 'Y-m-d H:i:s' format
}

$sql .= " ORDER BY alert_time DESC LIMIT ?, ?"; // Add ORDER BY alert_time DESC

// Prepare the statement
$stmt = $conn->prepare($sql);
$types = str_repeat("s", count($params)); // Determine parameter types
if ($params) {
    $types .= "ii"; // Adding start limit and results per page as integers
 // Combine pagination parameters with existing parameters
 $paginationParams = [$startLimit, $resultsPerPage];
 $allParams = array_merge($params, $paginationParams);
 
 // Bind parameters to statement
 $stmt->bind_param($types, ...$allParams);
 
} else {
    $stmt->bind_param("ii", $startLimit, $resultsPerPage); // Binding start limit and results per page as integers
}


// Execute the statement
$stmt->execute();
$result = $stmt->get_result();

// Function to determine the background color class based on the heat index
function getAlertClass($heatIndex) {
    if ($heatIndex < 27) {
        return 'normal'; // Normal (<27°C)
    } elseif ($heatIndex >= 27 && $heatIndex < 32) {
        return 'caution'; // Caution (27°C - 32°C)
    } elseif ($heatIndex >= 32 && $heatIndex < 41) {
        return 'extreme-caution'; // Extreme Caution (32°C - 41°C)
    } elseif ($heatIndex >= 41 && $heatIndex < 54) {
        return 'danger'; // Danger (41°C - 54°C)
    } else {
        return 'extreme-danger'; // Extreme Danger (>54°C)
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
select, input[type="date"] {
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
    background-color: #E6E6E6;
}
.caution {
    background-color: #FFFF00; /* Light Yellow */
}
.extreme-caution {
    background-color: #FFCC00; /* Light Orange */
}
.danger {
    background-color: #FF6600; /* Light Red */
}
.extreme-danger {
    background-color: #CC0001; /* Darker Red */
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
.pagination {
    display: flex;
    justify-content: center;
    margin: 20px 0;
}
.page-item {
    margin: 0 5px;
}
.page-link {
    padding: 8px 12px;
    border: 1px solid #007bff;
    border-radius: 4px;
    color: #007bff;
    text-decoration: none;
}
.page-link:hover {
    background-color: #007bff;
    color: white;
}
.page-item.active .page-link {
    background-color: #007bff;
    color: white;
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
    <h2>Sensor Data View</h2>
    <!-- Legend -->
    <div class="legend">
        <div><div class="legend-color normal"></div>Normal (&lt;27°C)</div>
        <div><div class="legend-color caution"></div>Caution (27°C - 32°C)</div>
        <div><div class="legend-color extreme-caution"></div>Extreme Caution (32°C - 41°C)</div>
        <div><div class="legend-color danger"></div>Danger (41°C - 54°C)</div>
        <div><div class="legend-color extreme-danger"></div>Extreme Danger (&gt;54°C)</div>
    </div>

    <form method="GET" class="mb-4">
    <div class="form-group">
        <label for="location_name">Select Location Name:</label>
        <select name="location_name" id="location_name" class="form-control" onchange="this.form.submit()">
            <option value="">-- All Locations --</option>
            <?php foreach ($locations as $location): ?>
                <option value="<?= $location['location_name'] ?>" <?= ($selectedLocation == $location['location_name']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($location['location_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="start_date">Start Date and Time:</label>
        <input type="datetime-local" name="start_date" id="start_date"  value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>" class="form-control" onchange="this.form.submit()">
    </div>
    <div class="form-group">
        <label for="end_date">End Date and Time:</label>
        <input type="datetime-local" name="end_date" id="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>" class="form-control" onchange="this.form.submit()">
    </div>
    <button type="submit" class="btn btn-primary">Filter</button>
</form>



    <table>
        <thead>
            <tr>
                <th>Sensor ID</th>
                <th>Location Name</th>
                <th>Temperature (°C)</th>
                <th>Humidity (%)</th>
                <th>Heat Index</th>
                <th>Alert Level</th>
                <th>Alert Time</th>
            </tr>
        </thead>
        <tbody>
<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr class="<?= getAlertClass($row['heat_index']) ?>">
            <td><?= htmlspecialchars($row['sensor_id']) ?></td>
            <td><?= htmlspecialchars($row['location_name']) ?></td>
            <td><?= htmlspecialchars($row['temperature']) ?></td>
            <td><?= htmlspecialchars($row['humidity']) ?></td>
            <td><?= htmlspecialchars($row['heat_index']) ?></td>
            <td><?= htmlspecialchars($row['alert_level']) ?></td>
            <td><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($row['alert_time']))) ?></td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="7">No data available for the selected filters.</td>
    </tr>
<?php endif; ?>
</tbody>

    </table>

    <div class="pagination">
        <?php if ($currentPage > 1): ?>
            <a href="?page=<?= $currentPage - 1 ?>&location_name=<?= urlencode($selectedLocation) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="page-link">Previous</a>
        <?php endif; ?>
        <?php for ($page = 1; $page <= $totalPages; $page++): ?>
            <div class="page-item <?= ($page === $currentPage) ? 'active' : '' ?>">
                <a href="?page=<?= $page ?>&location_name=<?= urlencode($selectedLocation) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="page-link"><?= $page ?></a>
            </div>
        <?php endfor; ?>
        <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?= $currentPage + 1 ?>&location_name=<?= urlencode($selectedLocation) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="page-link">Next</a>
        <?php endif; ?>
    </div>
</div>
</main>

<!-- ======= Footer ======= -->
<?php include '../components/footer.php'; ?>
<?php include '../components/scripts.php'; ?>
</body>
</html>

