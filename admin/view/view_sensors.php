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

// Get the selected location from the dropdown
$selectedLocation = isset($_GET['location_name']) ? $_GET['location_name'] : '';

// Prepare the SQL query to fetch data based on the selected location with pagination
$sql = "SELECT * FROM sensor_readings";
if ($selectedLocation) {
    $sql .= " WHERE location_name = ?";
}
$sql .= " LIMIT ?, ?";

// Prepare the statement
$stmt = $conn->prepare($sql);
if ($selectedLocation) {
    $stmt->bind_param("sii", $selectedLocation, $startLimit, $resultsPerPage); // Binding location as string, and start limit and results per page as integers
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
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
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
        .alert {
            text-align: center;
            color: red;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        </form>

        <table>
            <thead>
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
                    <th>Location Name</th> <!-- New column for location -->
                </tr>
            </thead>
            <tbody>
                <?php
                // Function to determine the background color based on the heat index
                function getAlertClass($heatIndex) {
                    if ($heatIndex < 27) {
                        return ''; // No background color for normal
                    } elseif ($heatIndex >= 27 && $heatIndex < 32) {
                        return 'background-color: #ffc107;'; // Caution: Yellow
                    } elseif ($heatIndex >= 32 && $heatIndex < 41) {
                        return 'background-color: #ff9800;'; // Extreme Caution: Orange
                    } elseif ($heatIndex >= 41 && $heatIndex < 54) {
                        return 'background-color: #f44336;'; // Danger: Red
                    } else {
                        return 'background-color: #c62828;'; // Extreme Danger: Dark Red
                    }
                }
                ?>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr style="<?= getAlertClass($row['heat_index']) ?>">
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['sensor_id']) ?></td>
                            <td><?= htmlspecialchars($row['temperature']) ?></td>
                            <td><?= htmlspecialchars($row['humidity']) ?></td>
                            <td><?= htmlspecialchars($row['heat_index']) ?></td>
                            <td><?= htmlspecialchars($row['alert']) ?></td>
                            <td><?= htmlspecialchars($row['latitude']) ?></td>
                            <td><?= htmlspecialchars($row['longitude']) ?></td>
                            <td><?= htmlspecialchars($row['alert_time']) ?></td>
                            <td><?= htmlspecialchars($row['location_name']) ?></td> <!-- Display location name -->
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="alert">No data available for this location.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav class="pagination">
            <a class="page-link <?= ($currentPage <= 1) ? 'disabled' : '' ?>" href="?page=<?= $currentPage - 1 ?>&location_name=<?= $selectedLocation ?>">Previous</a>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <span class="page-item <?= ($currentPage == $i) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&location_name=<?= $selectedLocation ?>"><?= $i ?></a>
                </span>
            <?php endfor; ?>
            <a class="page-link <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>" href="?page=<?= $currentPage + 1 ?>&location_name=<?= $selectedLocation ?>">Next</a>
        </nav>
    </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <?php include '../components/footer.php'; ?>
    <?php include '../components/scripts.php'; ?>

</body>
</html>
