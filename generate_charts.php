<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Charts</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <canvas id="barChart" width="400" height="200"></canvas>
    <canvas id="lineChart" width="400" height="200"></canvas>
    <script>
        const barCtx = document.getElementById('barChart').getContext('2d');
        const lineCtx = document.getElementById('lineChart').getContext('2d');

        // Create a Bar Chart
        const barChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['Location A', 'Location B', 'Location C'],
                datasets: [{
                    label: 'Average Temperature (°C)',
                    data: [28, 30, 35],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Create a Line Chart
        const lineChart = new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: ['Location A', 'Location B', 'Location C'],
                datasets: [{
                    label: 'Average Humidity (%)',
                    data: [60, 65, 70],
                    fill: false,
                    borderColor: 'rgba(153, 102, 255, 1)',
                    tension: 0.1
                }]
            }
        });

        // Function to save canvas as image
        function saveChartAsImage(canvasId) {
            const canvas = document.getElementById(canvasId);
            const image = canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.href = image;
            link.download = canvasId + '.png';
            link.click();
        }

        // Wait for the charts to finish rendering and then save them
        window.onload = function () {
            saveChartAsImage('barChart');
            saveChartAsImage('lineChart');
        };
    </script>
</body>
</html>
