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
            background: #ffffff; /* White background for contrast */
            border-radius: 10px; /* Rounded corners */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); /* Enhanced shadow for depth */
        }
        #map {
            height: 600px;
            width: 100%;
            border-radius: 10px; /* Rounded corners */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); /* Deeper shadow */
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
            z-index: 1000; /* Ensures tooltips are above all */
        }

        /* Alert styles */
        .alert-normal { background-color: #E6E6E6; }
        .alert-caution { background-color: #FFFF00; }
        .alert-extreme-caution { background-color: #FFCC00; }
        .alert-danger { background-color: #FF6600; }
        .alert-extreme-danger { background-color: #CC0001; }
        
        .alert-normal, .alert-caution, .alert-extreme-caution, .alert-danger, .alert-extreme-danger {
            color: #333; /* Dark text for readability */
            padding: 3px 8px; /* Increased padding for alert text */
            border-radius: 4px; /* Rounded corners for alert text */
            font-weight: bold; /* Bold text for emphasis */
        }

        .legend {
            background: rgba(255, 255, 255, 0.9); /* Slightly transparent for depth */
            padding: 15px; /* Increased padding */
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3); /* Shadow for legend */
            position: absolute;
            top: 10px; /* Adjusted positioning */
            right: 10px; /* Adjusted positioning */
            z-index: 500; /* Ensure it is on top */
            width: 250px; /* Adjusted width for readability */
            font-family: Arial, sans-serif;
            border: 1px solid #ddd; /* Border for clarity */
        }

        .legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px; /* Font size for better readability */
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            margin-right: 8px;
        }

        .legend-range {
            font-size: 12px;
            font-weight: normal;
            color: #666;
        }

.sensor_card {
position: absolute;
bottom: 10px; /* Position it at the bottom of the map */
left: 10px; /* Positioning to the left */
width: 300px; /* Adjusted width for better spacing */
background: white; /* White background for contrast */
padding: 4px; /* Increased padding for comfort */
border-radius: 8px; /* Rounded corners */
box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3); /* Shadow for depth */
z-index: 500; /* Ensure it is on top */
}

.card-body {
    display: flex; /* Use flexbox for horizontal layout */
    justify-content: space-between; /* Space out items */
    align-items: center; /* Center items vertically */
}

.card-title {
    font-size: 18px; /* Adjust the font size for the title */
    display: flex; /* Use flexbox to align items */
    align-items: center; /* Center items vertically */
}

.card-title i {
    margin-right: 8px; /* Space between icon and text */
    color: #007bff; /* Icon color (Bootstrap primary) */
}

.form-check-input {
    cursor: pointer; /* Change cursor to pointer on hover */
}

.form-check-label {
    display: flex; /* Use flexbox for alignment */
    align-items: center; /* Center items vertically */
}

.form-check-label i {
    margin-right: 6px; /* Space between icon and text */
    color: #28a745; /* Icon color for the checkbox */
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
            <h1 class="mb-4"><i class="bi bi-map"></i> Heat Index Map</h1>
            <div id="map">
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #E6E6E6;"></div>
                        <span>Not Hazardous</span>
                        <span class="legend-range">(&lt; 27°C)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #FFFF00;"></div>
                        <span>Caution</span>
                        <span class="legend-range">(27°C - 32°C)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #FFCC00;"></div>
                        <span>Extreme Caution</span>
                        <span class="legend-range">(33°C - 41°C)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #FF6600;"></div>
                        <span>Danger</span>
                        <span class="legend-range">(42°C - 51°C)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #CC0001;"></div>
                        <span>Extreme Danger</span>
                        <span class="legend-range">(≥ 52°C)</span>
                    </div>
                </div>
                <div class="sensor_card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-sensors"></i> <!-- Icon for sensors -->
                        Active Sensors
                    </h5>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="filterActive">
                        <label class="form-check-label" for="filterActive">
                            <i class="bi bi-check-circle"></i> <!-- Icon for check -->
                            Show Active
                        </label>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </main>

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
            blur: 20,
            maxZoom: 18,
            gradient: {
                0.0: 'blue',        // Very low heat index
                0.2: 'cyan',        // Cool
                0.4: 'lime',        // Mild warmth
                0.6: 'yellow',      // Neutral
                0.8: 'orange',      // Starting to get hot
                1.0: 'red'          // Extreme heat
            }
        }).addTo(map);


        // Layer group for tooltips
        const markerLayer = L.layerGroup().addTo(map);

    // Function to fetch and update heatmap data
function fetchSensorData() {
    
    fetch('../../fetch_php/fetch_map_data.php') // Fetch data from your PHP script
        .then(response => response.json())
        .then(data => {
            // Get the state of the filter checkbox
            const showActiveOnly = document.getElementById('filterActive').checked;

            // Filter data based on the checkbox state
            const filteredData = showActiveOnly ? data.filter(sensor => sensor.active) : data;

            const heatData = filteredData.map(sensor => {
                const { latitude, longitude, heat_index } = sensor;
                return [parseFloat(latitude), parseFloat(longitude), heat_index / 50]; // Adjust intensity
            });

            // Clear the current heatmap and markers
            heat.setLatLngs([]);
            markerLayer.clearLayers();

            // Update heatmap
            heat.setLatLngs(heatData);

            // Add markers with tooltips
            filteredData.forEach(sensor => {
                const { latitude, longitude, heat_index, temperature, humidity, alert, location_name, alert_time, active } = sensor;
                const lat = parseFloat(latitude);
                const lng = parseFloat(longitude);

                const marker = L.circleMarker([lat, lng], {
                    radius: 10,
                    color: 'transparent',
                    fillOpacity: 0
                }).addTo(markerLayer);

                // Determine alert class and icons based on PAGASA standards
                let alertClass = '';
                let recommendation = ''; // New variable for recommendations
                let alertIcon = ''; // Icon representation for each alert
                let backgroundColor = ''; // Background color for the alert level
                let textColor = ''; // Text color based on background for better contrast

                switch (alert) {
                    case 'Not Hazardous':
                        alertClass = 'alert-normal';
                        alertIcon = '✅'; // Check mark icon
                        recommendation = 'Good for outdoor activities. Stay hydrated.';
                        backgroundColor = '#d4edda'; // Light green
                        textColor = '#155724'; // Darker green for contrast
                        break;
                    case 'Caution':
                        alertClass = 'alert-caution';
                        alertIcon = '⚠️'; // Warning icon
                        recommendation = 'Stay hydrated, avoid strenuous activities.';
                        backgroundColor = '#fff3cd'; // Light yellow
                        textColor = '#856404'; // Darker yellow-brown for contrast
                        break;
                    case 'Extreme Caution':
                        alertClass = 'alert-extreme-caution';
                        alertIcon = '🌞'; // Sun icon
                        recommendation = 'Limit outdoor activities, take breaks in cool areas.';
                        backgroundColor = '#ffeeba'; // Deeper yellow
                        textColor = '#856404'; // Same darker yellow-brown for consistency
                        break;
                    case 'Danger':
                        alertClass = 'alert-danger';
                        alertIcon = '🚨'; // Alarm icon
                        recommendation = 'Minimize outdoor exposure, stay hydrated.';
                        backgroundColor = '#f8d7da'; // Light red
                        textColor = '#721c24'; // Darker red for contrast
                        break;
                    case 'Extreme Danger':
                        alertClass = 'alert-extreme-danger';
                        alertIcon = '⛔'; // Stop icon
                        recommendation = 'Stay indoors, avoid outdoor activities.';
                        backgroundColor = '#f5c6cb'; // Deep red
                        textColor = '#721c24'; // Same darker red for consistency
                        break;
                    default:
                        alertClass = 'alert-normal'; // Default case
                        alertIcon = '✅'; // Check mark icon
                        recommendation = 'Good for outdoor activities. Stay hydrated.';
                        backgroundColor = '#d4edda'; // Light green
                        textColor = '#155724'; // Darker green for contrast
                }

                // Determine the active status
                let activeStatus = active ? 'Active' : 'Inactive';

                // Bind tooltip to show sensor details with alert level background color and icons
                marker.bindTooltip(`
                                    <div class="custom-tooltip" style="
                                        padding: 8px;  /* Reduced padding */
                                        background-color: ${backgroundColor}; 
                                        border-radius: 5px;
                                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                                        font-family: Arial, sans-serif;
                                        color: #333;
                                        text-align: left;
                                        width: 300px; /* Set a max width for better control */
                                        font-size: 14px; /* Reduced font size */
                                        overflow: hidden; /* Prevent overflow */
                                        white-space: normal; /* Allow text to wrap */
                                        word-wrap: break-word; /* Break long words */
                                        ">
                                        <div style="margin-bottom: 4px;">
                                            <i class="bi bi-compass-fill" style="color: #007bff;"></i> <!-- Blue for location -->
                                            <strong>Location:</strong> ${location_name}
                                        </div>
                                        <div style="margin-bottom: 4px;">
                                            <i class="bi bi-exclamation-circle-fill" style="color: #dc3545;"></i> <!-- Red for alert level -->
                                            <strong>Alert Level:</strong> 
                                            <span class="${alertClass}" style="
                                                font-size: 14px;  /* Match the tooltip font size */
                                                font-weight: bold;
                                                color: ${textColor};
                                                padding: 4px 8px;  /* Reduced padding */
                                                border-radius: 4px;
                                                background: rgba(255, 255, 255, 0.3);
                                            ">${alertIcon} ${alert}</span>
                                        </div>
                                        <div style="margin-bottom: 4px;">
                                            <i class="bi bi-thermometer-high" style="color: #ff9800;"></i> <!-- Orange for heat index -->
                                            <strong>Heat Index:</strong> ${heat_index} °C
                                        </div>
                                        <div style="margin-bottom: 4px;">
                                            <i class="bi bi-thermometer" style="color: #2196f3;"></i> <!-- Blue for temperature -->
                                            <strong>Temperature:</strong> ${temperature} °C
                                        </div>
                                        <div style="margin-bottom: 4px;">
                                            <i class="bi bi-water" style="color: #007bff;"></i> <!-- Blue for humidity -->
                                            <strong>Humidity:</strong> ${humidity}%
                                        </div>
                                        <div style="margin-bottom: 4px;">
                                            <i class="bi bi-clock" style="color: #6c757d;"></i> <!-- Gray for last update -->
                                            <strong>Last Update:</strong> ${alert_time}
                                        </div>
                                        <div style="margin-bottom: 6px;">
                                            <i class="bi bi-arrow-clockwise" style="color: #28a745;"></i> <!-- Green for status -->
                                            <strong>Status:</strong> ${activeStatus}
                                        </div>
                                        <div style="padding: 6px; background: rgba(0, 0, 0, 0.05); border-radius: 3px;">
                                            <i class="bi bi-lightbulb-fill" style="color: #ffc107;"></i> <!-- Yellow for recommendation -->
                                            <strong>Recommendation:</strong> ${recommendation}
                                        </div>
                                    </div>
                                `, { className: 'custom-tooltip', direction: 'top', offset: [0, -10] });

    });
    })
    .catch(error => console.error('Error fetching sensor data:', error));
}

// Fetch sensor data and update heatmap every 60 seconds
fetchSensorData();
setInterval(fetchSensorData, 5000);

    </script>
    <!-- ======= Footer ======= -->
<?php include '../components/footer.php'; ?>
<?php include '../components/scripts.php'; ?>

</body>
</html>
