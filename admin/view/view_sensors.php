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
$sql .= " ORDER BY alert_time DESC LIMIT ?, ?"; // Add ORDER BY alert_time DESC

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

// Function to determine the background color class based on the heat index


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
    background-color: #e5e5e5; /* Darker Light Gray */
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
                <div><div class="legend-color normal"></div>Not Hazardous (&lt;27°C)</div>
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
        </form>

        <table>
            <thead>
                <tr>
                    <!-- <th>ID</th> -->
                    <th>Sensor ID</th>
                    <th>Location Name</th> <!-- New column for location -->
                    <th>Temperature (°C)</th>
                    <th>Humidity (%)</th>
                    <th>Heat Index</th>
                    <th>Alert Level</th>
                    <!-- <th>Latitude</th>
                    <th>Longitude</th> -->
                    <th>Alert Time</th>
                </tr>
            </thead>
            <?php
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
            <tbody>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="<?= getAlertClass($row['heat_index']) ?>">
                <td><?= htmlspecialchars($row['sensor_id']) ?></td>
                <td><?= htmlspecialchars($row['location_name']) ?></td>
                <td><?= htmlspecialchars($row['temperature']) ?></td>
                <td><?= htmlspecialchars($row['humidity']) ?></td>
                <td><?= htmlspecialchars($row['heat_index']) ?></td>
                <td><?= htmlspecialchars($row['alert']) ?></td>
                <td>
                    <?php
                        $date = new DateTime($row['alert_time']);
                        echo $date->format('F j, Y g:i:s A'); // Example: October 10, 2024 03:45 PM
                    ?>
                </td>

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
