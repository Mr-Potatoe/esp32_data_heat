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
    <!-- Include jQuery from a CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <style>
        .container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

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
        <h1 class="mt-4"><i class="fas fa-chart-line me-2"></i>Sensor Readings Dashboard</h1>

        <div class="card p-3 mb-4 filter-form">
            <h5 class="card-title">Filter Data</h5>
            <form method="GET">
                <div class="form-row d-flex flex-wrap">

                   <!-- Location Filter Dropdown -->
                   <div class="form-group col-md-4 col-sm-12">
                        <label for="location" class="mr-2">Select Location:</label>
                        <select id="location" name="location" class="form-control">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= htmlspecialchars($location); ?>" <?= (isset($_GET['location']) && $_GET['location'] === $location) ? 'selected' : ''; ?>><?= htmlspecialchars($location); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Time Filter Dropdown with Icon -->
                    <div class="form-group col-md-4 col-sm-12">
                        <label for="interval" class="mr-2">Select Time Interval:</label>
                        <div class="dropdown-icon-wrapper">
                            <select id="interval" name="interval" class="form-control">
                                <option value="hour" <?= $interval === 'hour' ? 'selected' : ''; ?>>Hourly</option>
                                <option value="day" <?= $interval === 'day' ? 'selected' : ''; ?>>Daily</option>
                                <option value="week" <?= $interval === 'week' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="month" <?= $interval === 'month' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="year" <?= $interval === 'year' ? 'selected' : ''; ?>>Yearly</option>
                            </select>
                            <i class="fas fa-chevron-down dropdown-icon"></i> <!-- Font Awesome icon -->
                        </div>
                    </div>

                    <!-- Start Date Input -->
                    <div class="form-group col-md-4 col-sm-12">
                        <label for="start_date" class="mr-2">Start Date and Time:</label>
                        <input type="datetime-local" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars(isset($_GET['start_date']) ? $_GET['start_date'] : ''); ?>" required>
                    </div>

                    <!-- End Date Input -->
                    <div class="form-group col-md-4 col-sm-12">
                        <label for="end_date" class="mr-2">End Date and Time:</label>
                        <input type="datetime-local" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars(isset($_GET['end_date']) ? $_GET['end_date'] : ''); ?>" required>
                    </div>

                 
                </div>

                <!-- Filter Button -->
                <div class="d-flex justify-content-start mt-3">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="dashboard.php" class="btn btn-secondary ms-2">Clear Filters</a>
                </div>
            </form>
        </div>

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
                // Only render charts if the selected location matches or if no location is selected
                if (empty($_GET['location']) || $_GET['location'] === $locationName): 
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

    const formatData = (dataArray) => dataArray.map(value => Number(value).toFixed(2));

    const ctxLine = document.getElementById(`lineChart_${index}`).getContext('2d');
    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: data.timeLabels.map(label => new Date(label).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })), // Show only hours
            datasets: [
                { 
                    label: 'Average Temperature (°C)',
                    data: formatData(data.avgTemperatures),
                    fill: false,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    tension: 0.1,
                    pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                },
                { 
                    label: 'Average Humidity (%)',
                    data: formatData(data.avgHumidity),
                    fill: false,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    tension: 0.1,
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                },
                { 
                    label: 'Average Heat Index',
                    data: formatData(data.avgHeatIndexes),
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
                            return `${tooltipItem.dataset.label}: ${Number(tooltipItem.raw).toFixed(2)}`;
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

            <?php 
                endif; // End of location check
            endforeach; 
            ?>
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

    <?php include '../components/scripts.php'; ?>

</body>
</html>
