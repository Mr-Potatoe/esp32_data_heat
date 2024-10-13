<?php include '../../fetch_php/admin_protect.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../components/head.php'; ?>
    <link rel="stylesheet" href="../../assets/css/page.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .status-active {
            color: green;
            font-weight: bold;
        }
        .status-inactive {
            color: red;
            font-weight: bold;
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
            <h1 class="mb-4 text-center">Sensor Status Dashboard</h1>

                <!-- Legend -->
        <div class="legend">
            <div><div class="legend-color normal"></div>Normal (&lt;27°C)</div>
            <div><div class="legend-color caution"></div>Caution (27°C - 32°C)</div>
            <div><div class="legend-color extreme-caution"></div>Extreme Caution (32°C - 41°C)</div>
            <div><div class="legend-color danger"></div>Danger (41°C - 54°C)</div>
            <div><div class="legend-color extreme-danger"></div>Extreme Danger (&gt;54°C)</div>
        </div>

            <table id="sensorTable">
                <thead>
                    <tr>
                        <th>Sensor ID</th>
                        <th>Location Name</th>
                        <th>Temperature</th>
                        <th>Humidity</th>
                        <th>Heat Index</th>
                        <th>Alert Level</th>
                        <th>Status</th>
                        <th>Last Update</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Sensor data will be inserted here -->
                </tbody>
            </table>

            <!-- Pagination will be inserted here -->
            <div id="paginationContainer"></div>
        </div>
    </main>

    <!-- ======= Footer ======= -->
    <?php include '../components/footer.php'; ?>
    <?php include '../components/scripts.php'; ?>

    <script>
let currentPage = 1; // Initialize current page

// Function to fetch sensor data with pagination
function fetchSensorData(page = 1) {
    fetch(`../../fetch_php/fetch_sensor_status.php?page=${page}&_=${new Date().getTime()}`) // Add cache-busting parameter
        .then(response => response.json())
        .then(data => {
            console.log(data); // Log the fetched data for debugging
            const tbody = document.querySelector('#sensorTable tbody');
            const paginationContainer = document.getElementById('paginationContainer');

            tbody.innerHTML = ''; // Clear existing table data

            // Populate table with sensor data
            if (data.sensors.length > 0) {
                data.sensors.forEach(sensor => {
                    const row = document.createElement('tr');
                    const statusClass = sensor.status === 'Active' ? 'status-active' : 'status-inactive';

                    // Determine the alert class based on the heat index
                    let alertClass;
                    if (sensor.heat_index < 27) {
                        alertClass = 'normal';
                    } else if (sensor.heat_index >= 27 && sensor.heat_index < 32) {
                        alertClass = 'caution';
                    } else if (sensor.heat_index >= 32 && sensor.heat_index < 41) {
                        alertClass = 'extreme-caution';
                    } else if (sensor.heat_index >= 41 && sensor.heat_index < 54) {
                        alertClass = 'danger';
                    } else {
                        alertClass = 'extreme-danger';
                    }

                    row.innerHTML = `
                        <td>${sensor.sensor_id}</td>
                        <td>${sensor.location_name}</td>
                        <td>${sensor.temperature} °C</td>
                        <td>${sensor.humidity} %</td>
                        <td class="${alertClass}">${sensor.heat_index}</td>
                        <td>${sensor.alert}</td>
                        <td class="${statusClass}">${sensor.status}</td>
                        <td>${sensor.last_update}</td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="8">No sensors found.</td></tr>';
            }

            // Populate pagination
            paginationContainer.innerHTML = data.paginationHTML;
        })
        .catch(error => console.error('Error fetching sensor data:', error));
}

// Function to fetch sensor data at intervals
function fetchDataAtIntervals() {
    fetchSensorData(currentPage); // Fetch data for the current page
    setInterval(() => {
        fetchSensorData(currentPage); // Update data every 5 seconds
    }, 5000); // 5000 milliseconds = 5 seconds
}

// Fetch the initial data
fetchDataAtIntervals();

// Event delegation for pagination links
document.getElementById('paginationContainer').addEventListener('click', function(event) {
    if (event.target.matches('.page-link')) {
        event.preventDefault(); // Prevent the default anchor behavior
        currentPage = new URL(event.target.href).searchParams.get('page'); // Get the page number from the link
        fetchSensorData(currentPage); // Fetch data for the selected page
    }
});
</script>

</body>
</html>
