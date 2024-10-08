<?php
include '../php/db.php'; // Include your DB connection script

// Fetch heat index data
$query = "SELECT * FROM heatindexdata";
$result = $conn->query($query);
$data = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'sensor_id' => $row['sensor_id'],
            'temperature' => $row['temperature'],
            'humidity' => $row['humidity'],
            'heat_index' => $row['heat_index'],
            'timestamp' => $row['timestamp'],
        ];
    }
} else {
    $data = []; // No data available
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heat Index Data Heatmap</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>

</head>
<body>
    <header>
        <h1>History</h1>
    
    </header>

    <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="../index.php">View Heat Index Map</a></li>
            <li><a href="history.php">View Heat Index History</a></li>
        </ul>
    </nav>

    <section>
        <h2>Heat Index Data</h2>
        <canvas id="heatmap" width="400" height="200"></canvas>

        <script>
    let heatmapData = <?php echo json_encode($data); ?>;
    const ctx = document.getElementById('heatmap').getContext('2d');
    const labels = heatmapData.map(d => new Date(d.timestamp)); // Convert timestamps to Date objects
    const temperatures = heatmapData.map(d => d.heat_index);

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Heat Index',
                data: temperatures,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                fill: true,
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'hour' // You can adjust this to 'day', 'month', etc. as needed
                    },
                    title: {
                        display: true,
                        text: 'Timestamp'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Heat Index (°C)'
                    }
                }
            }
        }
    });
</script>

    </section>
</body>
</html>
