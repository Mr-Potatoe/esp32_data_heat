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
    </style>
</head>
<body>
    <!-- ======= Header ======= -->
    <?php include '../components/header.php'; ?>

    <!-- ======= Sidebar ======= -->
    <?php include '../components/sidebar.php'; ?>
    
    <main id="main" class="main">
        <div class="container">
        <h1 class="mb-4"><i class="bi bi-tools"></i> Sensor Status</h1>

        <div class="card filter-container p-3 mb-4">
    <h5 class="card-title"><i class="bi bi-funnel me-2"></i>Filter</h5>
    <div class="form-row d-flex flex-wrap">
        <div class="form-group col-md-4 col-sm-12">
            <select id="filterStatus" class="form-select form-control">
                <option value="">All Status</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
        </div>
        <div class="form-group col-md-4 col-sm-12">
            <select id="filterAlert" class="form-select form-control">
                <option value="">All Alert Levels</option>
                <option value="Normal">Normal</option>
                <option value="Caution">Caution</option>
                <option value="Extreme Caution">Extreme Caution</option>
                <option value="Danger">Danger</option>
                <option value="Extreme Danger">Extreme Danger</option>
            </select>
        </div>
        <div class="form-group col-md-4 col-sm-12">
            <input type="text" id="filterSensorID" class="form-control" placeholder="Search by Sensor ID">
        </div>
        <div class="form-group col-md-4 col-sm-12">
            <input type="text" id="filterLocation" class="form-control" placeholder="Search by Location">
        </div>
    </div>
</div>



        <?php include '../components/legend.php' ?>


            <table id="sensorTable">
                <thead>
                    <tr>
                        <th>Sensor ID</th>
                        <th>Location Name</th>
                        <th>Temperature (°C)</th>
                        <th>Humidity (%)</th>
                        <th>Heat Index (°C)</th>
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

/// Get filter values
function getFilterValues() {
    const sensorID = document.getElementById('filterSensorID').value;
    const location = document.getElementById('filterLocation').value;
    const status = document.getElementById('filterStatus').value;
    const alert = document.getElementById('filterAlert').value;

    return {
        sensorID,
        location,
        status,
        alert
    };
}

// Fetch sensor data with pagination and filters
function fetchSensorData(page = 1) {
    const filters = getFilterValues();
    const query = new URLSearchParams({
        page,
        sensorID: filters.sensorID,
        location: filters.location,
        status: filters.status,
        alert: filters.alert,
        _: new Date().getTime() // Cache busting
    });

    fetch(`../../fetch_php/fetch_sensor_status.php?${query.toString()}`)
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
                    } else if (sensor.heat_index >= 42 && sensor.heat_index < 51) {
                        alertClass = 'danger';
                    } else {
                        alertClass = 'extreme-danger';
                    }

                    row.innerHTML = `
                        <td>${sensor.sensor_id}</td>
                        <td>${sensor.location_name}</td>
                        <td>${sensor.temperature}</td>
                        <td>${sensor.humidity}</td>
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

// Event listener to trigger filtering when input values change
document.querySelectorAll('.filter-container input, .filter-container select').forEach(filterElement => {
    filterElement.addEventListener('input', () => {
        fetchSensorData(currentPage);
    });
});



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
