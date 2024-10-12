<?php include '../../fetch_php/admin_protect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../components/head.php'; ?> <!-- Include the head -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
       <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 30px; /* Increased padding */
            background: #f9f9f9; /* Light background color */
            border-radius: 10px; /* Rounded corners */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* Slightly darker shadow */
        }
        #map {
            height: 600px;
            width: 100%;
            border-radius: 10px; /* Rounded corners */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Shadow for depth */
            position: relative; /* For positioning legend */
            z-index: 500;
        }
        .custom-tooltip {
            padding: 10px;
            border-radius: 5px;
            font-size: 15px; /* Slightly larger font size */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3); /* Shadow for tooltip */
            border: 1px solid #ccc; /* Border for clarity */
            background-color: white; /* White background for tooltips */
        }
        /* Alert styles */
        .alert-normal { background-color: #E6E6E6; }
        .alert-caution { background-color: #FFFF00; }
        .alert-extreme-caution { background-color: #FFCC00; }
        .alert-danger { background-color: #FF6600; }
        .alert-extreme-danger { background-color: #CC0001; }
        .alert-normal, .alert-caution, .alert-extreme-caution, .alert-danger, .alert-extreme-danger {
            color: white; /* Ensure text is readable */
            padding: 3px 8px; /* Increased padding for alert text */
            border-radius: 4px; /* Rounded corners for alert text */
        }
        .legend {
            background: white;
            padding: 15px; /* Increased padding */
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3); /* Shadow for legend */
            position: absolute;
            top: 20px; /* Adjusted positioning */
            right: 20px; /* Adjusted positioning */
            z-index: 500; /* Ensure it is on top */
            width: 200px; /* Fixed width for the legend */
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            font-size: 14px; /* Font size for better readability */
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            margin-right: 5px;
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
        <h1 class="text-center mb-4">Heat Index Map ZDSPGC</h1>
        <div id="map">
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #E6E6E6;"></div>
                    <span>Not Hazardous</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #FFFF00;"></div>
                    <span>Caution</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #FFCC00;"></div>
                    <span>Extreme Caution</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #FF6600;"></div>
                    <span>Danger</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #CC0001;"></div>
                    <span>Extreme Danger</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet.js -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>


    <script>
        // Initialize the map
        const map = L.map('map').setView([7.9473004, 123.5876167], 18); // Default view at ZDSPGC coordinates

        // Add a tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Create a heatmap layer
        let heat = L.heatLayer([], {
            radius: 25,
            blur: 15,
            maxZoom: 18,
            gradient: {
                0.2: 'blue',
                0.4: 'lime',
                0.6: 'yellow',
                0.8: 'orange',
                1.0: 'red'
            }
        }).addTo(map);

        // Layer group for tooltips
        const markerLayer = L.layerGroup().addTo(map);

        // Function to fetch and update heatmap data
        function fetchSensorData() {
            fetch('../../fetch_php/data_fetch_map.php') // Fetch data from your PHP script
                .then(response => response.json())
                .then(data => {
                    const heatData = data.map(sensor => {
                        const { latitude, longitude, heat_index } = sensor;
                        return [parseFloat(latitude), parseFloat(longitude), heat_index / 50]; // Adjust intensity
                    });

                    // Clear the current heatmap and markers
                    heat.setLatLngs([]);
                    markerLayer.clearLayers();

                    // Update heatmap
                    heat.setLatLngs(heatData);

                    // Add markers with tooltips
                    data.forEach(sensor => {
                        const { latitude, longitude, heat_index, temperature, humidity, alert, location_name, alert_time } = sensor;
                        const lat = parseFloat(latitude);
                        const lng = parseFloat(longitude);

                        const marker = L.circleMarker([lat, lng], {
                            radius: 10,
                            color: 'transparent',
                            fillOpacity: 0
                        }).addTo(markerLayer);

                        // Determine alert class based on PAGASA standards
                        let alertClass = '';
                        switch (alert) {
                            case 'Not Hazardous':
                                alertClass = 'alert-normal';
                                break;
                            case 'Caution':
                                alertClass = 'alert-caution';
                                break;
                            case 'Extreme Caution':
                                alertClass = 'alert-extreme-caution';
                                break;
                            case 'Danger':
                                alertClass = 'alert-danger';
                                break;
                            case 'Extreme Danger':
                                alertClass = 'alert-extreme-danger';
                                break;
                            default:
                                alertClass = 'alert-normal'; // Default case
                        }

                        // Bind tooltip to show sensor details with alert level background color
                        marker.bindTooltip(`
                            <div class="custom-tooltip">
                                <strong>Location:</strong> ${location_name}<br>
                                <strong>Alert Level:</strong> <span class="${alertClass}">${alert}</span><br>
                                <strong>Heat Index:</strong> ${heat_index} °C<br>
                                <strong>Temperature:</strong> ${temperature} °C<br>
                                <strong>Humidity:</strong> ${humidity}%<br>
                                <strong>Last Update:</strong> ${alert_time}
                            </div>
                        `, { className: 'custom-tooltip', direction: 'top', offset: [0, -10] });
                    });
                })
                .catch(error => console.error('Error fetching sensor data:', error));
        }

        // Fetch sensor data and update heatmap every 60 seconds
        fetchSensorData();
        setInterval(fetchSensorData, 60000);
    </script>
    <!-- ======= Footer ======= -->
<?php include '../components/footer.php'; ?>
<?php include '../components/scripts.php'; ?>
</main>
</body>
</html>
