<?php include '../php/admin_protect.php'; ?>
<?php include '../php/db.php'; ?>
<?php
$recent_activity_query = "SELECT * FROM logs ORDER BY timestamp DESC LIMIT 5";
$recent_activity_result = $conn->query($recent_activity_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
</head>
<body>

    <!-- ======= Header ======= -->
    <?php include 'header.php'; ?>


    <!-- ======= Sidebar ======= -->
    <?php include 'sidebar.php'; ?>

    <main id="main" class="main">
    <!-- Page Title -->
    <div class="pagetitle mb-4">
        <h1>Dashboard</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <!-- Heat Index Management Header -->
    <!-- <div class="mb-4">
        <h2 class="h4">Heat Index Management</h2>
        <p class="text-muted">This is the admin panel where you can manage sensor data and view logs.</p>
    </div> -->
    <section class="section dashboard">
    <!-- Overview Cards -->
    <div class="row">
    <?php
    // Fetch total sensors
    $total_sensors_query = "SELECT COUNT(*) as total FROM Sensors";
    $total_result = $conn->query($total_sensors_query);
    $total_sensors = $total_result->fetch_assoc()['total'];

    // Fetch active sensors
    $active_sensors_query = "SELECT COUNT(*) as active FROM Sensors WHERE status = 'active'";
    $active_result = $conn->query($active_sensors_query);
    $active_sensors = $active_result->fetch_assoc()['active'];

    // Fetch recent heat index
    $recent_heat_index_query = "SELECT heat_index FROM heatindexdata ORDER BY timestamp DESC LIMIT 1";
    $heat_index_result = $conn->query($recent_heat_index_query);
    $recent_heat_index = $heat_index_result->num_rows > 0 ? $heat_index_result->fetch_assoc()['heat_index'] . '°F' : 'N/A';

    // Fetch alerts
    $alerts_query = "SELECT COUNT(*) as alerts FROM alerts WHERE resolved = 0";
    $alerts_result = $conn->query($alerts_query);
    $alerts_count = $alerts_result->fetch_assoc()['alerts'];
    ?>

    <!-- Card: Total Sensors -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Total Sensors</h6>
                <p class="card-text display-4 font-weight-bold"><?php echo $total_sensors; ?></p>
            </div>
        </div>
    </div>

    <!-- Card: Active Sensors -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Active Sensors</h6>
                <p class="card-text display-4 font-weight-bold"><?php echo $active_sensors; ?></p>
            </div>
        </div>
    </div>

    <!-- Card: Recent Heat Index -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Recent Heat Index</h6>
                <p class="card-text display-4 font-weight-bold"><?php echo $recent_heat_index; ?></p>
            </div>
        </div>
    </div>

    <!-- Card: Active Alerts -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Active Alerts</h6>
                <p class="card-text display-4 font-weight-bold"><?php echo $alerts_count; ?></p>
            </div>
        </div>
    </div>
</div><!-- End Overview Cards -->


<!-- Heat Index Chart -->
<div class="mb-5">
    <h4 class="mb-4">Heat Index Trends</h4>
    <div class="btn-group mb-4" role="group" aria-label="Chart Filter Buttons">
        <button id="hourlyButton" class="btn btn-outline-primary active" onclick="updateChart('hourly', this)">Hourly</button>
        <button id="dailyButton" class="btn btn-outline-primary" onclick="updateChart('daily', this)">Daily</button>
        <button id="weeklyButton" class="btn btn-outline-primary" onclick="updateChart('weekly', this)">Weekly</button>
        <button id="monthlyButton" class="btn btn-outline-primary" onclick="updateChart('monthly', this)">Monthly</button>
        <button id="yearlyButton" class="btn btn-outline-primary" onclick="updateChart('yearly', this)">Yearly</button>
    </div>
    <div class="chart-container" style="position: relative; height:60vh; width:100%">
        <canvas id="heatIndexChart"></canvas>
    </div>
</div>
    <!-- Recent Activity Log -->
    <div class="mb-5">
        <h4 class="mb-4">Recent Activity</h4>
        <ul class="list-group mb-4">
            <?php
            if ($recent_activity_result->num_rows > 0) {
                while ($row = $recent_activity_result->fetch_assoc()) {
                    echo "<li class='list-group-item'>{$row['action']} - <em>{$row['timestamp']}</em></li>";
                }
            } else {
                echo "<li class='list-group-item'>No recent activity found.</li>";
            }
            ?>
        </ul>
        <a href="view_logs.php" class="btn btn-primary">View All Logs</a>
    </div><!-- End Recent Activity Log -->
    </section>
    </main>

    <!-- footer and scroll to top -->
    <?php include 'footer.php'; ?>
    <!-- include scripts -->
    <?php include 'scripts.php'; ?>

    <!-- Heat Index Chart -->
    <script>
    // Chart initialization and update function
    const ctx = document.getElementById('heatIndexChart').getContext('2d');
    const heatIndexChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Heat Index',
                data: [],
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    function updateChart(range, button) {
        // Fetch new data for the selected range
        fetch(`../php/fetch_heat_index_data.php?range=${range}`)
            .then(response => response.json())
            .then(data => {
                const labels = data.map(point => point.timestamp);
                const heatIndexData = data.map(point => point.heat_index);

                heatIndexChart.data.labels = labels;
                heatIndexChart.data.datasets[0].data = heatIndexData;
                heatIndexChart.update();
            })
            .catch(error => console.error('Error fetching data:', error));

        // Remove active class from all buttons and add it to the clicked one
        document.querySelectorAll('.btn-group button').forEach(btn => {
            btn.classList.remove('active', 'btn-primary');
            btn.classList.add('btn-outline-primary');
        });

        // Set the clicked button as active
        button.classList.remove('btn-outline-primary');
        button.classList.add('active', 'btn-primary');
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize with the 'hourly' chart
        updateChart('hourly', document.getElementById('hourlyButton'));

        // Quick Actions Dropdown Logic (if needed)
        const quickActionsButton = document.getElementById('quickActionsButton');
        const quickActionsDropdown = document.getElementById('quickActionsDropdown');

        quickActionsButton?.addEventListener('click', (e) => {
            e.stopPropagation();
            quickActionsDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', () => {
            quickActionsDropdown?.classList.add('hidden');
        });
    });
    </script>

</body>
</html>