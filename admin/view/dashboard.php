<?php include '../../fetch_php/admin_protect.php'; ?>
<?php 
require '../../config.php'; // Configuration and database connection

// Load environment variables
loadEnv();

// Connect to the database
$conn = dbConnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../components/head.php'; ?>
</head>
<body>

    <!-- ======= Header ======= -->
    <?php include '../components/header.php'; ?>


    <!-- ======= Sidebar ======= -->
    <?php include '../components/sidebar.php'; ?>

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

    <section class="section dashboard">
    <!-- Overview Cards -->
    <div class="row">

    <!-- Card: Total Sensors -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Total Sensors</h6>
                <p class="card-text display-4 font-weight-bold"></p>
            </div>
        </div>
    </div>

    <!-- Card: Active Sensors -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Active Sensors</h6>
                <p class="card-text display-4 font-weight-bold"></p>
            </div>
        </div>
    </div>

    <!-- Card: Recent Heat Index -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Recent Heat Index</h6>
                <p class="card-text display-4 font-weight-bold"></p>
            </div>
        </div>
    </div>

    <!-- Card: Active Alerts -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="card-title text-muted">Active Alerts</h6>
                <p class="card-text display-4 font-weight-bold"></p>
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
    </section>
    </main>

    <!-- footer and scroll to top -->
    <?php include '../components/footer.php'; ?>
    <!-- include scripts -->
    <?php include '../components/scripts.php'; ?>

    <!-- Heat Index Chart -->

</body>
</html>