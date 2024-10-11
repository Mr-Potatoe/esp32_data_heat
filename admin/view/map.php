


<?php include '../../fetch_php/admin_protect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include '../components/head.php'; ?> <!-- Include the head -->
<style>
    .container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            <h1 class="text-2xl font-bold text-center">Zamboanga del Sur Provincial Government College Campus Map</h1>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-body">
                            <div id="map-container" class="position-relative">
                                <img id="base-map" src="../../assets/zdspgc_map.png" alt="Campus Map" class="img-fluid rounded">
                                <canvas id="heatmap-overlay" class="position-absolute top-0 start-0"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </main>

<!-- Bootstrap JS (optional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>


    <!-- footer and scroll to top -->
    <?php include '../components/footer.php'; ?>
    <!-- include scripts -->
    <?php include '../components/scripts.php'; ?>
</body>
</html>
