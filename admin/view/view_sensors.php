<?php include '../../fetch_php/admin_protect.php'; ?>
<?php
require '../../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();


?>

<?php include '../../fetch_php/fetch_view_sensors.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include '../components/head.php'; ?>
<link rel="stylesheet" href="../../assets/css/page.css">
<style>

  /* Pagination styles */
  .pagination .page-item.active .page-link {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }

    .pagination .page-link {
        padding: 8px 12px;
        border: 1px solid #007bff;
        border-radius: 4px;
        color: #007bff;
        text-decoration: none;
        margin: 0 5px; /* Add margin for spacing */
    }

    .pagination .page-link:hover {
        background-color: #007bff;
        color: white;
    }

    .pagination .disabled .page-link {
        pointer-events: none;
        background-color: #e9ecef;
        color: #6c757d;
        border-color: #dee2e6;
    }

    .total-pages-label {
        font-size: 1rem;
        font-weight: bold;
        color: #333;
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
        <input type="datetime-local" name="start_date" id="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>" class="form-control" onchange="this.form.submit()">
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
    <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <!-- Previous Button -->
        <li class="page-item <?= ($currentPage == 1) ? 'disabled' : '' ?>">
            <a href="?page=<?= max(1, $currentPage - 1) ?>&location_name=<?= urlencode($selectedLocation) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="page-link">Previous</a>
        </li>

        <!-- Page Numbers -->
        <?php
        // Define how many buttons you want to show (e.g., 5)
        $visiblePages = 5;

        // Calculate the start and end page numbers for the pagination buttons
        $startPage = max(1, $currentPage - floor($visiblePages / 2));
        $endPage = min($totalPages, $currentPage + floor($visiblePages / 2));

        // Adjust if we're near the beginning or end of the page list
        if ($currentPage <= floor($visiblePages / 2)) {
            $endPage = min($visiblePages, $totalPages);
        }
        if ($currentPage + floor($visiblePages / 2) >= $totalPages) {
            $startPage = max(1, $totalPages - $visiblePages + 1);
        }

        for ($page = $startPage; $page <= $endPage; $page++): ?>
            <li class="page-item <?= ($page == $currentPage) ? 'active' : '' ?>">
                <a href="?page=<?= $page ?>&location_name=<?= urlencode($selectedLocation) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="page-link"><?= $page ?></a>
            </li>
        <?php endfor; ?>

        <!-- Next Button -->
        <li class="page-item <?= ($currentPage == $totalPages) ? 'disabled' : '' ?>">
            <a href="?page=<?= min($totalPages, $currentPage + 1) ?>&location_name=<?= urlencode($selectedLocation) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="page-link">Next</a>
        </li>
    </ul>
</nav>



<!-- Total pages label -->
<div class="total-pages-label text-center mt-2">
    <strong>Page <?= $currentPage; ?> of <?= $totalPages; ?></strong>
</div>

</div>
</main>

<!-- ======= Footer ======= -->
<?php include '../components/footer.php'; ?>
<?php include '../components/scripts.php'; ?>
</body>
</html>

