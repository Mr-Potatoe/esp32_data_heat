<?php

require '../config.php'; // Configuration and database connection
loadEnv(); // Load environment variables
$conn = dbConnect(); // Connect to the database

// Define how many charts to show per page
$chartsPerPage = 2; // Adjust this value to control the number of charts displayed per page

// Get the current page from the URL, default to 1 if not set
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $chartsPerPage;

// Query to get total number of locations
$totalLocationsQuery = "SELECT COUNT(DISTINCT location_name) AS total_locations FROM sensor_readings";
$totalLocationsResult = $conn->query($totalLocationsQuery);
$totalLocations = $totalLocationsResult->fetch_assoc()['total_locations'];

// Calculate the total number of pages
$totalPages = ceil($totalLocations / $chartsPerPage);

// Fetch sensor readings grouped by location with pagination
$query = "SELECT location_name, AVG(temperature) AS avg_temperature, AVG(humidity) AS avg_humidity, AVG(heat_index) AS avg_heat_index
          FROM sensor_readings
          GROUP BY location_name
          ORDER BY location_name
          LIMIT $chartsPerPage OFFSET $offset"; // Limit the query for pagination
$result = $conn->query($query);

// Prepare data for charts
$locations = [];
$avgTemperatures = [];
$avgHumidity = [];
$avgHeatIndexes = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $locations[] = htmlspecialchars($row['location_name']);
        $avgTemperatures[] = floatval($row['avg_temperature']);
        $avgHumidity[] = floatval($row['avg_humidity']);
        $avgHeatIndexes[] = floatval($row['avg_heat_index']);
    }
}

// Get the selected time interval, start date, and end date
$interval = isset($_GET['interval']) ? $_GET['interval'] : 'hour';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Adjust the interval query based on the selected interval
$interval_query = "";
switch ($interval) {
    case 'hour':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%m-%d %H:00:00') as time_label"; // Format to show hours
        break;
    case 'day':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%m-%d') as time_label"; // Show full date
        break;
    case 'week':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%u') as time_label"; // Week number
        break;
    case 'month':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%m') as time_label"; // Month and year
        break;
    case 'year':
        $interval_query = "DATE_FORMAT(alert_time, '%Y') as time_label"; // Just year
        break;
}

// Modify the time series query to include date filtering
$query_time_series = "SELECT $interval_query, location_name, AVG(temperature) AS avg_temperature, AVG(humidity) AS avg_humidity, AVG(heat_index) AS avg_heat_index
                      FROM sensor_readings
                      WHERE alert_time IS NOT NULL";

// Append start and end date conditions to the query if provided
if (!empty($startDate)) {
    $query_time_series .= " AND alert_time >= '$startDate'";
}
if (!empty($endDate)) {
    $query_time_series .= " AND alert_time <= '$endDate'";
}

$query_time_series .= " GROUP BY time_label, location_name ORDER BY alert_time";

$result_time_series = $conn->query($query_time_series);

// Prepare data for line charts
$locationData = [];
if ($result_time_series->num_rows > 0) {
    while ($row = $result_time_series->fetch_assoc()) {
        $timeLabel = htmlspecialchars($row['time_label']);
        $locationName = htmlspecialchars($row['location_name']);
        
        if (!isset($locationData[$locationName])) {
            $locationData[$locationName] = [
                'timeLabels' => [],
                'avgTemperatures' => [],
                'avgHumidity' => [],
                'avgHeatIndexes' => []
            ];
        }
        
        $locationData[$locationName]['timeLabels'][] = $timeLabel;
        $locationData[$locationName]['avgTemperatures'][] = floatval($row['avg_temperature']);
        $locationData[$locationName]['avgHumidity'][] = floatval($row['avg_humidity']);
        $locationData[$locationName]['avgHeatIndexes'][] = floatval($row['avg_heat_index']);
    }
}

// Display message if no data is available
$noDataMessage = empty($locations) ? "No data available for the selected time interval." : "";

// Prepare the data for the front end
$chartData = [];
foreach ($locations as $index => $location) {
    $chartData[] = [
        'locationName' => $location,
        'avgTemperature' => $avgTemperatures[$index],
        'avgHumidity' => $avgHumidity[$index],
        'avgHeatIndex' => $avgHeatIndexes[$index],
    ];
}

// Optionally, encode the locationData for JavaScript if needed
$locationDataJson = json_encode($locationData);
?>


<div class="card p-3 mb-4 filter-form">
    <h5 class="card-title">Filter Data</h5>
    <form method="GET">
        <div class="form-row d-flex flex-wrap">
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
            <a href="../../generate_pdf/generate_report.php?interval=<?= $interval; ?>&start_date=<?= htmlspecialchars($startDate); ?>&end_date=<?= htmlspecialchars($endDate); ?>" class="btn btn-secondary ms-2" target="_blank">Download PDF</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->
    <!-- Include jQuery from a CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


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
                                    // Check if there's data for the location
                                    if (isset($locationData[$locationName]['timeLabels']) && !empty($locationData[$locationName]['timeLabels'])) {
                                        // Get the latest time label
                                        $latestTimeLabel = end($locationData[$locationName]['timeLabels']);
                                        $formattedTimeLabel = date("F j, Y, g:i A", strtotime($latestTimeLabel));
                                        echo '<span data-toggle="tooltip" title="Full Time: ' . htmlspecialchars($latestTimeLabel) . '">' . htmlspecialchars($formattedTimeLabel) . '</span>';
                                    } else {
                                        // No data message
                                        echo "No data available";
                                    }
                                ?>
                            </div>
                        </div>
                        <canvas id="barChart_<?php echo $index; ?>" style="height: 250px;"></canvas>
                    </div>
                </div>
            </div>
        <?php 
        endforeach; 
        ?>
        
</div>

<!-- HTML and JavaScript to display charts go here -->
<script>
    const chartData = <?php echo json_encode($chartData); ?>;
    const locationData = <?php echo $locationDataJson; ?>;

    // Code to generate charts using Chart.js will go here.
 // Code to generate charts using Chart.js will go here.
chartData.forEach((data, index) => {
    const ctxBar = document.getElementById(`barChart_${index}`).getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['Average Temperature (°C)', 'Average Humidity (%)', 'Average Heat Index'],
            datasets: [{
                label: data.locationName,
                data: [data.avgTemperature, data.avgHumidity, data.avgHeatIndex],
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
                title: { display: true, text: `Average Readings for ${data.locationName}` },
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
});

    // Handle pagination controls (optional)
    // Code for pagination goes here
</script>

