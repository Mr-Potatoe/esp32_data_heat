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

    /*Custom CSS for Dropdown Icon */

    .dropdown-icon-wrapper {
        position: relative;
    }

    .dropdown-icon {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .card {
    background-color: #f8f9fa; /* Light background for the card */
    border: 1px solid #e1e1e1; /* Soft border */
    border-radius: 5px; /* Rounded corners */
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Subtle shadow */
}

.card-title {
    font-weight: bold; /* Bold title for emphasis */
    margin-bottom: 1rem; /* Space below the title */
}

.form-inline {
    display: flex;
    flex-wrap: wrap; /* Allow wrapping for small screens */
}

.form-group {
    flex: 1; /* Each form group takes equal space */
    min-width: 250px; /* Ensure inputs have a minimum width */
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
<h2><i class="bi bi-speedometer2"></i> Sensor Data View</h2>


<?php include '../components/legend.php' ?>



<div class="card p-3 mb-4 filter-form">
    <h5 class="card-title">Filter by Location</h5>
    <form method="GET">
        <div class="form-row d-flex flex-wrap">
            <!-- Location Name Dropdown -->
            <div class="form-group col-md-4 col-sm-12">
                <label for="location_name">Select Location Name:</label>
                <div class="dropdown-icon-wrapper">
                    <select name="location_name" id="location_name" class="form-control" onchange="this.form.submit()">
                        <option value="">-- All Locations --</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= htmlspecialchars($location['location_name']) ?>" <?= ($selectedLocation == $location['location_name']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($location['location_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down dropdown-icon"></i> <!-- Font Awesome icon -->
                </div>
            </div>

            <!-- Start Date Input -->
            <div class="form-group col-md-4 col-sm-12">
                <label for="start_date">Start Date and Time:</label>
                <input type="datetime-local" name="start_date" id="start_date" value="<?= htmlspecialchars($startDate); ?>" class="form-control" onchange="this.form.submit()">
            </div>

            <!-- End Date Input -->
            <div class="form-group col-md-4 col-sm-12">
                <label for="end_date">End Date and Time:</label>
                <input type="datetime-local" name="end_date" id="end_date" value="<?= htmlspecialchars($endDate); ?>" class="form-control" onchange="this.form.submit()">
            </div>

        </div>

        <!-- Filter Button -->
        <div class="d-flex justify-content-start">
            <button type="submit" class="btn btn-primary me-2">Filter</button>
            <a href="view_sensors.php" class="btn btn-secondary">Clear Filters</a>
        </div>
    </form>
</div>






    <table>
        <thead>
            <tr>
                <!-- <th>Sensor ID</th> -->
                <th>Location Name</th>
                <th>Temperature (°C)</th>
                <th>Humidity (%)</th>
                <th>Heat Index (°C)</th>
                <th>Alert Level</th>
                <th>Alert Time</th>
            </tr>
        </thead>
        <tbody>
<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <?php 
            // Get the alert level text and class using the updated function
            list($alertLevel, $alertClass) = getAlertLevelAndClass($row['heat_index']); 
        ?>
        <tr class="<?= $alertClass ?>">
            <td><?= htmlspecialchars($row['location_name']) ?></td>
            <td><?= htmlspecialchars(number_format($row['temperature'], 2)) ?></td>
            <td><?= htmlspecialchars(number_format($row['humidity'], 2)) ?></td>
            <td><?= htmlspecialchars(number_format($row['heat_index'], 2)) ?></td>
            <td><?= htmlspecialchars($alertLevel) ?></td> <!-- Displaying alert level text -->
            <td>
                <?php 
                    $date = new DateTime($row['alert_time']);
                    echo htmlspecialchars($date->format('F j, Y g:i:s A'));
                ?>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="6">No data available for the selected filters.</td>
    </tr>
<?php endif; ?>
</tbody>


    </table>
    <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <!-- First Button -->
        <li class="page-item <?= ($currentPage == 1) ? 'disabled' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="First">
            <a href="?page=1&location_name=<?= urlencode($selectedLocation) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="page-link">
                <i class="bi bi-skip-backward-fill"></i>
            </a>
        </li>

        <!-- Previous Button -->
        <li class="page-item <?= ($currentPage == 1) ? 'disabled' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Previous">
            <a href="?page=<?= max(1, $currentPage - 1) ?>&location_name=<?= urlencode($selectedLocation) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="page-link">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>

        <!-- Page Numbers -->
        <?php
        $visiblePages = 5;
        $startPage = max(1, $currentPage - floor($visiblePages / 2));
        $endPage = min($totalPages, $currentPage + floor($visiblePages / 2));

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
        <li class="page-item <?= ($currentPage == $totalPages) ? 'disabled' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Next">
            <a href="?page=<?= min($totalPages, $currentPage + 1) ?>&location_name=<?= urlencode($selectedLocation) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="page-link">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>

        <!-- Last Button -->
        <li class="page-item <?= ($currentPage == $totalPages) ? 'disabled' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Last">
            <a href="?page=<?= $totalPages ?>&location_name=<?= urlencode($selectedLocation) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="page-link">
                <i class="bi bi-skip-forward-fill"></i>
            </a>
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

