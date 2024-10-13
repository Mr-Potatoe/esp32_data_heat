<?php include '../../fetch_php/admin_protect.php'; ?>
<?php
require '../../config.php'; // Configuration and database connection
loadEnv(); // Load environment variables
$conn = dbConnect(); // Connect to the database
?>

<?php include '../../fetch_php/fetch_dashboard.php'; ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../components/head.php'; ?>
    <title>Sensor Readings Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->

    <style>
        .container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
.card-hover:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    .text-center { text-align: center; }
    .h-100 { height: 100%; }
    </style>
</head>
<body>

    <!-- ======= Header ======= -->
    <?php include '../components/header.php'; ?>

    <!-- ======= Sidebar ======= -->
    <?php include '../components/sidebar.php'; ?>

    <main id="main" class="main">
    <div class="container">
        <h1 class="mt-4">Sensor Readings Dashboard</h1>

<!-- Filter Form -->
<form method="GET" action="" class="mb-4">
    <div class="row mb-3">
        <div class="col">
            <!-- Time Interval Dropdown -->
            <label for="interval" class="form-label">Interval:</label>
            <select id="interval" name="interval" class="form-select">
                <option value="hour" <?php echo $interval === 'hour' ? 'selected' : ''; ?>>Hourly</option>
                <option value="day" <?php echo $interval === 'day' ? 'selected' : ''; ?>>Daily</option>
                <option value="week" <?php echo $interval === 'week' ? 'selected' : ''; ?>>Weekly</option>
                <option value="month" <?php echo $interval === 'month' ? 'selected' : ''; ?>>Monthly</option>
                <option value="year" <?php echo $interval === 'year' ? 'selected' : ''; ?>>Yearly</option>
            </select>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col">
            <!-- Start Date Picker -->
            <label for="start_date" class="form-label">Start:</label>
            <input type="datetime-local" id="start_date" name="start_date" class="form-control" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
        </div>
        <div class="col">
            <!-- End Date Picker -->
            <label for="end_date" class="form-label">End:</label>
            <input type="datetime-local" id="end_date" name="end_date" class="form-control" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
        </div>
    </div>

    <div class="d-flex justify-content-between mt-3">
        <!-- Filter Button -->
        <button type="submit" class="btn btn-primary">Filter</button>
        <!-- Download PDF Button -->
        <a href="../../generate_pdf/generate_report.php?interval=<?php echo $interval; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-secondary" target="_blank">Download PDF</a>
    </div>
</form>




        <!-- Check if there are no locations and display the message -->
<?php if (!empty($noDataMessage)): ?>
    <div class="alert alert-warning" role="alert">
        <?php echo $noDataMessage; ?>
    </div>
<?php endif; ?>


      <!-- Individual Bar and Line Charts for Each Location -->
      <div id="charts" class="row">
    <?php 
    // Reverse the locations array to render the latest data first
    $reversedLocations = array_reverse($locations);
    
    foreach ($reversedLocations as $index => $locationName): 
    ?>
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card shadow-sm rounded h-100 card-hover" style="border: none; transition: transform 0.2s;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h4 style="font-size: 1.5rem; font-weight: bold;"><?php echo htmlspecialchars($locationName); ?> Average Readings</h4>
                        <div class="text-right" id="timeLabel_<?php echo $index; ?>" style="font-size: 14px; color: #666; font-weight: bold;">
                            <?php
                                // Display the latest time label for the location
                                if (isset($locationData[$locationName]['timeLabels']) && !empty($locationData[$locationName]['timeLabels'])) {
                                    $latestTimeLabel = end($locationData[$locationName]['timeLabels']);
                                    $formattedTimeLabel = date("F j, Y, g:i A", strtotime($latestTimeLabel));
                                    echo '<span data-toggle="tooltip" title="Full Time: ' . htmlspecialchars($latestTimeLabel) . '">' . htmlspecialchars($formattedTimeLabel) . '</span>';
                                } else {
                                    echo "No data available";
                                }
                            ?>
                        </div>
                    </div>
                    <canvas id="barChart_<?php echo $index; ?>" style="height: 250px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card shadow-sm rounded h-100 card-hover" style="border: none; transition: transform 0.2s;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h4 style="font-size: 1.5rem; font-weight: bold;"><?php echo htmlspecialchars($locationName); ?> Trends</h4>
                        <div class="text-right" id="timeLabel_<?php echo $index; ?>" style="font-size: 14px; color: #666; font-weight: bold;">
                            <?php
                                // Display the latest time label for the location
                                if (isset($locationData[$locationName]['timeLabels']) && !empty($locationData[$locationName]['timeLabels'])) {
                                    $latestTimeLabel = end($locationData[$locationName]['timeLabels']);
                                    $formattedTimeLabel = date("F j, Y, g:i A", strtotime($latestTimeLabel));
                                    echo '<span data-toggle="tooltip" title="Full Time: ' . htmlspecialchars($latestTimeLabel) . '">' . htmlspecialchars($formattedTimeLabel) . '</span>';
                                } else {
                                    echo "No data available";
                                }
                            ?>
                        </div>
                    </div>
                    <canvas id="lineChart_<?php echo $index; ?>" style="height: 250px;"></canvas>
                </div>
            </div>
        </div>

        <script>
            // Bar Chart Rendering
            (function(index, locationName) {
                const avgTemperature = <?php echo json_encode($avgTemperatures[$index] ?? 0); ?>;
                const avgHumidity = <?php echo json_encode($avgHumidity[$index] ?? 0); ?>;
                const avgHeatIndex = <?php echo json_encode($avgHeatIndexes[$index] ?? 0); ?>;

                const ctxBar = document.getElementById(`barChart_${index}`).getContext('2d');
                new Chart(ctxBar, {
                    type: 'bar',
                    data: {
                        labels: ['Average Temperature (°C)', 'Average Humidity (%)', 'Average Heat Index'],
                        datasets: [{
                            label: locationName,
                            data: [avgTemperature, avgHumidity, avgHeatIndex],
                            backgroundColor: ['rgba(255, 99, 132, 0.6)', 'rgba(54, 162, 235, 0.6)', 'rgba(255, 206, 86, 0.6)'],
                            borderColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)'],
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true }
                        },
                        plugins: {
                            legend: { display: false },
                            title: { display: true, text: `Average Readings for ${locationName}` },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        return `${tooltipItem.dataset.label}: ${tooltipItem.raw}`;
                                    }
                                }
                            }
                        }
                    }
                });
            })(<?php echo $index; ?>, '<?php echo addslashes($locationName); ?>');

            // Line Chart Rendering
            (function(index, locationName) {
                const data = <?php echo json_encode($locationData[$locationName]); ?>;

                const ctxLine = document.getElementById(`lineChart_${index}`).getContext('2d');
                new Chart(ctxLine, {
                    type: 'line',
                    data: {
                        labels: data.timeLabels.map(label => new Date(label).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })), // Show only hours
                        datasets: [
                            { 
                                label: 'Average Temperature (°C)',
                                data: data.avgTemperatures,
                                fill: false,
                                borderColor: 'rgba(255, 99, 132, 1)',
                                tension: 0.1,
                                pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                            },
                            { 
                                label: 'Average Humidity (%)',
                                data: data.avgHumidity,
                                fill: false,
                                borderColor: 'rgba(54, 162, 235, 1)',
                                tension: 0.1,
                                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                            },
                            { 
                                label: 'Average Heat Index',
                                data: data.avgHeatIndexes,
                                fill: false,
                                borderColor: 'rgba(255, 206, 86, 1)',
                                tension: 0.1,
                                pointBackgroundColor: 'rgba(255, 206, 86, 1)',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                ticks: {
                                    autoSkip: true,
                                    maxTicksLimit: 8,
                                    maxRotation: 0,
                                    minRotation: 0
                                },
                                grid: {
                                    display: true,
                                    color: 'rgba(200, 200, 200, 0.5)'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    display: true,
                                    color: 'rgba(200, 200, 200, 0.5)'
                                }
                            }
                        },
                        plugins: {
                            legend: { display: true },
                            title: { display: true, text: `Trends for ${locationName}` },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        return `${tooltipItem.dataset.label}: ${tooltipItem.raw}`;
                                    }
                                }
                            }
                        },
                        elements: {
                            point: {
                                radius: 5,
                                hoverRadius: 8
                            }
                        }
                    }
                });
            })(<?php echo $index; ?>, '<?php echo addslashes($locationName); ?>');
        </script>
    <?php endforeach; ?>
</div>



        <!-- Pagination Links -->
<div class="d-flex justify-content-between align-items-center mt-4">
    <div>
        <!-- Previous Page Link -->
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&interval=<?= $interval ?>" class="btn btn-outline-primary">
                <i class="bi bi-chevron-left"></i> Previous
            </a>
        <?php else: ?>
            <button class="btn btn-outline-secondary" disabled>
                <i class="bi bi-chevron-left"></i> Previous
            </button>
        <?php endif; ?>
    </div>

    <div>
        Page <?= $page ?> of <?= $totalPages ?>
    </div>

    <div>
        <!-- Next Page Link -->
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&interval=<?= $interval ?>" class="btn btn-outline-primary">
                Next <i class="bi bi-chevron-right"></i>
            </a>
        <?php else: ?>
            <button class="btn btn-outline-secondary" disabled>
                Next <i class="bi bi-chevron-right"></i>
            </button>
        <?php endif; ?>
    </div>
</div>


    </div>
    </main>

    <!-- Include Bootstrap's tooltip initialization -->
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>

    <!-- ======= Footer ======= -->
    <?php include '../components/footer.php'; ?>

    <script>
    function filterData() {
        const interval = document.getElementById('interval').value;
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        // Ensure both start and end dates are provided
        if (startDate && endDate) {
            window.location.href = `?interval=${interval}&start_date=${startDate}&end_date=${endDate}`;
        } else {
            alert('Please provide both start and end dates.');
        }
    }
</script>


    <?php include '../components/scripts.php'; ?>

</body>
</html>
