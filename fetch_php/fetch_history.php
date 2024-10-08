<?php

// Get the filter value from the dropdown (hourly, daily, weekly, etc.)
$filterType = isset($_GET['filter']) ? $_GET['filter'] : 'hourly';

// SQL query based on the filter type
switch ($filterType) {
    case 'daily':
        $sql = "SELECT 
                    DATE(alert_time) AS period,
                    AVG(temperature) AS avg_temp,
                    AVG(humidity) AS avg_humidity,
                    AVG(heat_index) AS avg_heat_index
                FROM sensor_readings
                GROUP BY DATE(alert_time)
                ORDER BY period DESC";
        break;
    case 'weekly':
        $sql = "SELECT 
                    YEARWEEK(alert_time) AS period,
                    AVG(temperature) AS avg_temp,
                    AVG(humidity) AS avg_humidity,
                    AVG(heat_index) AS avg_heat_index
                FROM sensor_readings
                GROUP BY YEARWEEK(alert_time)
                ORDER BY period DESC";
        break;
    case 'monthly':
        $sql = "SELECT 
                    DATE_FORMAT(alert_time, '%Y-%m') AS period,
                    AVG(temperature) AS avg_temp,
                    AVG(humidity) AS avg_humidity,
                    AVG(heat_index) AS avg_heat_index
                FROM sensor_readings
                GROUP BY DATE_FORMAT(alert_time, '%Y-%m')
                ORDER BY period DESC";
        break;
    case 'yearly':
        $sql = "SELECT 
                    YEAR(alert_time) AS period,
                    AVG(temperature) AS avg_temp,
                    AVG(humidity) AS avg_humidity,
                    AVG(heat_index) AS avg_heat_index
                FROM sensor_readings
                GROUP BY YEAR(alert_time)
                ORDER BY period DESC";
        break;
    default:
        // Hourly filter as default
        $sql = "SELECT 
                    DATE_FORMAT(alert_time, '%Y-%m-%d %H:00:00') AS period,
                    AVG(temperature) AS avg_temp,
                    AVG(humidity) AS avg_humidity,
                    AVG(heat_index) AS avg_heat_index
                FROM sensor_readings
                GROUP BY DATE_FORMAT(alert_time, '%Y-%m-%d %H:00:00')
                ORDER BY period DESC";
}

// Execute the query
$result = $conn->query($sql);

// Function to determine the background color based on the heat index
function getAlertClass($heatIndex) {
    if ($heatIndex < 27) {
        return 'normal'; // No background color for normal
    } elseif ($heatIndex >= 27 && $heatIndex < 32) {
        return 'caution'; // Yellow
    } elseif ($heatIndex >= 32 && $heatIndex < 41) {
        return 'extreme-caution'; // Orange
    } elseif ($heatIndex >= 41 && $heatIndex < 54) {
        return 'danger'; // Red
    } else {
        return 'extreme-danger'; // Dark Red
    }
}