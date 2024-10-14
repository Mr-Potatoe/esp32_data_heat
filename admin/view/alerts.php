<?php include '../../fetch_php/admin_protect.php'; ?>
<?php require '../../vendor/autoload.php';?>

<?php
require '../../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();

?>

<?php include '../../fetch_php/fetch_alerts.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../components/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../../assets/css/page.css">
<style>

        
        
        /* Custom styles for better UI/UX */
        .card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
/* 
        .card:hover {
            transform: scale(1.05);
        } */
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
        <h1 class="text-center mb-4">Heat Index Alerts</h1>

      <!-- Summary Section with responsive cards -->
<div class="row text-center mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-light p-3 h-100">
            <h5 class="text-muted"><i class="bi bi-exclamation-triangle-fill"></i> Total Alerts</h5>
            <p class="display-4"><?php echo $totalAlertsData['total_alerts']; ?></p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-light p-3 h-100">
            <h5 class="text-muted"><i class="bi bi-thermometer-half"></i> Highest Heat Index</h5>
            <?php if (isset($summaryData['highest_heat_index'])): ?>
                <p class="display-4 text-danger"><?php echo number_format($summaryData['highest_heat_index'], 2); ?> °C</p>
            <?php else: ?>
                <p class="display-4">N/A</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-light p-3 h-100">
            <h5 class="text-muted"><i class="bi bi-geo-alt-fill"></i> Location of Highest Heat Index</h5>
            <?php if (isset($summaryData['location_name'])): ?>
                <p class="display-4"><?php echo htmlspecialchars($summaryData['location_name']); ?></p>
            <?php else: ?>
                <p class="display-4">N/A</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-light p-3 h-100">
            <h5 class="text-muted"><i class="bi bi-building"></i> Total Locations</h5>
            <p class="display-4"><?php echo $locationSummaryData['total_locations']; ?></p>
        </div>
    </div>
</div>


        <!-- Chart Section -->
        <div class="row">
            <div class="col-12 mb-4">
                <h3 class="text-center mb-2">Alerts by Location</h3> <!-- Chart label -->
                <div class="card p-3">
                    <canvas id="alertChart"></canvas> <!-- Placeholder for the chart -->
                </div>
            </div>
        </div>

        <!-- Alerts Table with hover effect and responsive design -->
        <h3 class="text-center mb-2">Recent Alerts (Past 24 Hours)</h3> <!-- Table label -->


        <div class="card p-3 mb-4 filter-form shadow-sm">
    <h5 class="card-title mb-3">Filter Alerts</h5>
    <form method="GET" action="" class="mb-3">
        <div class="row g-3">
            <!-- Location Filter -->
            <div class="col-md-4 col-sm-12">
                <label for="location" class="form-label">Location</label>
                <select class="form-select" name="location" id="location">
                    <option value="">All Locations</option>
                    <?php
                    // Fetch distinct locations from the database
                    $locationQuery = "SELECT DISTINCT location_name FROM sensor_readings WHERE alert IS NOT NULL";
                    $locationResult = $conn->query($locationQuery);
                    while ($locRow = $locationResult->fetch_assoc()) {
                        $selected = isset($_GET['location']) && $_GET['location'] === $locRow['location_name'] ? 'selected' : '';
                        echo "<option value='{$locRow['location_name']}' $selected>{$locRow['location_name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Alert Level Filter -->
            <div class="col-md-4 col-sm-12">
                <label for="alert_level" class="form-label">Alert Level</label>
                <select class="form-select" name="alert_level" id="alert_level">
                    <option value="">All Alert Levels</option>
                    <option value="Not Hazardous" <?php if (isset($_GET['alert_level']) && $_GET['alert_level'] == 'Not Hazardous') echo 'selected'; ?>>Not Hazardous</option>
                    <option value="Caution" <?php if (isset($_GET['alert_level']) && $_GET['alert_level'] == 'Caution') echo 'selected'; ?>>Caution</option>
                    <option value="Extreme Caution" <?php if (isset($_GET['alert_level']) && $_GET['alert_level'] == 'Extreme Caution') echo 'selected'; ?>>Extreme Caution</option>
                    <option value="Danger" <?php if (isset($_GET['alert_level']) && $_GET['alert_level'] == 'Danger') echo 'selected'; ?>>Danger</option>
                    <option value="Extreme Danger" <?php if (isset($_GET['alert_level']) && $_GET['alert_level'] == 'Extreme Danger') echo 'selected'; ?>>Extreme Danger</option>
                </select>
            </div>
        </div>

        <!-- Filter Buttons -->
        <div class="d-flex justify-content-start mt-4">
            <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
            <a href="alerts.php" class="btn btn-secondary">Clear Filters</a>
        </div>
    </form>
</div>


<?php include '../components/legend.php' ?>


        <table>
            <thead>
                <tr>
                    <th scope="col">Location</th>
                    <th scope="col">Temperature (°C)</th>
                    <th scope="col">Humidity (%)</th>
                    <th scope="col">Heat Index (°C)</th>
                    <th scope="col">Alert Level</th>
                    <th scope="col">Alert Time</th>
                </tr>
            </thead>
            <tbody>
    <?php
    // Function to determine the alert level and background color class based on the heat index
    function getAlertLevelAndClass($heatIndex) {
        if ($heatIndex < 27) {
            return ['Not Hazardous', 'normal']; // Normal (<27°C)
        } elseif ($heatIndex >= 27 && $heatIndex < 33) {
            return ['Caution', 'caution']; // Caution (27°C - <33°C)
        } elseif ($heatIndex >= 33 && $heatIndex < 42) {
            return ['Extreme Caution', 'extreme-caution']; // Extreme Caution (33°C - <42°C)
        } elseif ($heatIndex >= 42 && $heatIndex < 52) {
            return ['Danger', 'danger']; // Danger (42°C - <52°C)
        } else {
            return ['Extreme Danger', 'extreme-danger']; // Extreme Danger (>=52°C)
        }
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Determine the alert level and class based on the heat index
            list($alertLevel, $alertClass) = getAlertLevelAndClass($row['heat_index']);
            echo "<tr class='{$alertClass}'>"; // Apply the alert class for styling
            echo "<td>" . htmlspecialchars($row['location_name']) . "</td>";
            echo "<td>" . htmlspecialchars(number_format($row['temperature'], 2)) . "</td>"; // Format temperature
            echo "<td>" . htmlspecialchars(number_format($row['humidity'], 2)) . "</td>"; // Format humidity
            echo "<td>" . htmlspecialchars(number_format($row['heat_index'], 2)) . "</td>"; // Format heat index
            echo "<td>" . htmlspecialchars($alertLevel) . "</td>"; // Display the alert level text

            // Format the alert time
            $date = new DateTime($row['alert_time']);
            echo "<td>" . htmlspecialchars($date->format('F j, Y g:i:s A')) . "</td>";

            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='6' class='text-center'>No alerts found</td></tr>";
    }
    ?>
</tbody>

        </table>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <!-- First button -->
        <li class="page-item <?php if ($page <= 1) { echo 'disabled'; } ?>">
            <a class="page-link" href="?page=1&location=<?php echo urlencode($location); ?>&alert_level=<?php echo urlencode($alert_level); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" aria-label="First" data-bs-toggle="tooltip" data-bs-placement="top" title="First">
                <i class="bi bi-skip-backward-fill"></i>
            </a>
        </li>

        <!-- Previous button -->
        <li class="page-item <?php if ($page <= 1) { echo 'disabled'; } ?>">
            <a class="page-link" href="?page=<?php echo $page - 1; ?>&location=<?php echo urlencode($location); ?>&alert_level=<?php echo urlencode($alert_level); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" aria-label="Previous" data-bs-toggle="tooltip" data-bs-placement="top" title="Previous">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>

        <?php
        // Define the range of pages to display
        $range = 2;

        // Calculate start and end page numbers
        $startPage = max(1, $page - $range);
        $endPage = min($totalPages, $page + $range);

        // Loop through the pages within the range
        for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="page-item <?php if ($page == $i) { echo 'active'; } ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&location=<?php echo urlencode($location); ?>&alert_level=<?php echo urlencode($alert_level); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <!-- Next button -->
        <li class="page-item <?php if ($page >= $totalPages) { echo 'disabled'; } ?>">
            <a class="page-link" href="?page=<?php echo $page + 1; ?>&location=<?php echo urlencode($location); ?>&alert_level=<?php echo urlencode($alert_level); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" aria-label="Next" data-bs-toggle="tooltip" data-bs-placement="top" title="Next">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>

        <!-- Last button -->
        <li class="page-item <?php if ($page >= $totalPages) { echo 'disabled'; } ?>">
            <a class="page-link" href="?page=<?php echo $totalPages; ?>&location=<?php echo urlencode($location); ?>&alert_level=<?php echo urlencode($alert_level); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" aria-label="Last" data-bs-toggle="tooltip" data-bs-placement="top" title="Last">
                <i class="bi bi-skip-forward-fill"></i>
            </a>
        </li>
    </ul>
</nav>




        <!-- Total pages label -->
        <div class="total-pages-label text-center mt-2">
            <strong>Page <?php echo $page; ?> of <?php echo $totalPages; ?></strong>
        </div>
    </div>
</main>


    <!-- Footer and scripts -->
    <?php include '../components/footer.php'; ?>
    <?php include '../components/scripts.php'; ?>

    <!-- Chart.js Script -->
    <script>
        const ctx = document.getElementById('alertChart').getContext('2d');
        const alertChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    <?php
                    $location_names = [];
                    $alert_counts = [];
                    while ($row = $chartResult->fetch_assoc()) {
                        $location_names[] = "'" . $row['location_name'] . "'";
                        $alert_counts[] = $row['alert_count'];
                    }
                    echo implode(',', $location_names);
                    ?>
                ],
                datasets: [{
                    label: 'Number of Alerts by Location',
                    data: [<?php echo implode(',', $alert_counts); ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
    </script>
</body>
</html>