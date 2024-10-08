<?php include '../php/admin_protect.php'; ?>
<?php include '../php/db.php'; ?>

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
        <h1 class="text-xl font-bold text-center">Manage Sensors</h1>
    </header>

    <section class="bg-white p-4 md:p-6 max-w-6xl mx-auto w-full mt-6 rounded shadow-sm">
        <button onclick="toggleForm()" class="btn btn-primary mb-4">
            Add New Sensor
        </button>

        <div id="add-sensor-form" class="mt-4" style="display: none;">
            <h2 class="h5 font-semibold mb-4">Add New Sensor</h2>
            <form action="../php/add_sensor.php" method="POST" class="space-y-4">
                <div>
                    <label for="sensor_name" class="form-label">Sensor Name:</label>
                    <input type="text" id="sensor_name" name="sensor_name" required class="form-control" />
                </div>
                <div>
                    <label for="location" class="form-label">Location:</label>
                    <input type="text" id="location" name="location" required class="form-control" />
                </div>
                <div>
                    <label for="latitude" class="form-label">Latitude:</label>
                    <input type="text" id="latitude" name="latitude" required class="form-control" />
                </div>
                <div>
                    <label for="longitude" class="form-label">Longitude:</label>
                    <input type="text" id="longitude" name="longitude" required class="form-control" />
                </div>
                <button type="submit" class="btn btn-primary mt-4">Add Sensor</button>
            </form>
        </div>

        <h2 class="h5 font-semibold mt-8">Current Sensors</h2>
        <div class="overflow-auto">
            <table class="table table-bordered mt-4">
                <thead class="table-light">
                    <tr>
                        <th>Sensor ID</th>
                        <th>Sensor Name</th>
                        <th>Location</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM sensors";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr class='hover:bg-light'>";
                            echo "<td>" . $row['sensor_id'] . "</td>";
                            echo "<td>" . $row['sensor_name'] . "</td>";
                            echo "<td>" . $row['location'] . "</td>";
                            echo "<td>" . $row['latitude'] . "</td>";
                            echo "<td>" . $row['longitude'] . "</td>";
                            echo "<td>" . $row['status'] . "</td>";
                            echo "<td class='d-flex justify-content-center'>
                                    <a href='edit_sensor.php?id=" . $row['sensor_id'] . "' class='btn btn-sm btn-primary me-2'>Edit</a>
                                    <button onclick='confirmDelete(" . $row['sensor_id'] . ")' class='btn btn-sm btn-danger'>Delete</button>
                                 </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>No sensors found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </section>
    </main>

    <script>
        function toggleForm() {
            const form = document.getElementById('add-sensor-form');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block'; // Show the form
            } else {
                form.style.display = 'none'; // Hide the form
            }
        }
    </script>



    <!-- footer and scroll to top -->
    <?php include 'footer.php'; ?>
    <!-- include scripts -->
    <?php include 'scripts.php'; ?>
</body>
</html>
