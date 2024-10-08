<?php include '../php/admin_protect.php'; ?>
<?php include '../php/db.php'; ?>

<?php
if (isset($_GET['id'])) {
    $sensor_id = $_GET['id'];
    $sql = "SELECT * FROM Sensors WHERE sensor_id = $sensor_id";
    $result = $conn->query($sql);
    $sensor = $result->fetch_assoc();
} else {
    header('Location: manage_sensors.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?> <!-- Include the head -->
</head>
<body>

    <!-- ======= Header ======= -->
    <?php include 'header.php'; ?>


    <!-- ======= Sidebar ======= -->
    <?php include 'sidebar.php'; ?>

    <main id="main" class="main">

    <header class="bg-primary text-white p-4">
        <h1 class="h4 text-center">Edit Sensor</h1>
    </header>

    <section class="bg-white p-4 md:p-6 max-w-6xl mx-auto w-full mt-6 shadow-sm rounded">
        <h2 class="h5 font-semibold mb-4">Update Sensor Information</h2>
        <form action="../php/update_sensor.php" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="sensor_id" value="<?php echo $sensor['sensor_id']; ?>">
            
            <div class="mb-3">
                <label for="sensor_name" class="form-label">Sensor Name:</label>
                <input type="text" id="sensor_name" name="sensor_name" value="<?php echo $sensor['sensor_name']; ?>" required class="form-control" />
                <div class="invalid-feedback">
                    Please provide a sensor name.
                </div>
            </div>
            <div class="mb-3">
                <label for="location" class="form-label">Location:</label>
                <input type="text" id="location" name="location" value="<?php echo $sensor['location']; ?>" required class="form-control" />
                <div class="invalid-feedback">
                    Please provide a location.
                </div>
            </div>
            <div class="mb-3">
                <label for="latitude" class="form-label">Latitude:</label>
                <input type="text" id="latitude" name="latitude" value="<?php echo $sensor['latitude']; ?>" required class="form-control" />
                <div class="invalid-feedback">
                    Please provide a latitude.
                </div>
            </div>
            <div class="mb-3">
                <label for="longitude" class="form-label">Longitude:</label>
                <input type="text" id="longitude" name="longitude" value="<?php echo $sensor['longitude']; ?>" required class="form-control" />
                <div class="invalid-feedback">
                    Please provide a longitude.
                </div>
            </div>

            <div class="d-flex mt-4">
                <button type="submit" class="btn btn-primary me-2">Update Sensor</button>
                <button type="button" onclick="window.location.href='manage_sensors.php';" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </section>

    </main>


    <!-- footer and scroll to top -->
    <?php include 'footer.php'; ?>
    <!-- include scripts -->
    <?php include 'scripts.php'; ?>


</body>
</html>
