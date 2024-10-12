<?php
require '../vendor/autoload.php'; // Load TCPDF
require '../config.php'; // Load your database connection

loadEnv(); 
$conn = dbConnect();

// Retrieve the selected time interval, start date, and end date from GET parameters
$interval = isset($_GET['interval']) ? $_GET['interval'] : 'hour';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Define the interval query similar to the dashboard
$interval_query = "";
switch ($interval) {
    case 'hour':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%m-%d %H:%i:%s') as time_label"; 
        break;
    case 'day':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%m-%d') as time_label";
        break;
    case 'week':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%u') as time_label"; 
        break;
    case 'month':
        $interval_query = "DATE_FORMAT(alert_time, '%Y-%m') as time_label";
        break;
    case 'year':
        $interval_query = "DATE_FORMAT(alert_time, '%Y') as time_label";
        break;
}

// Query to retrieve data
$query_time_series = "SELECT $interval_query, location_name, AVG(temperature) AS avg_temperature, AVG(humidity) AS avg_humidity, AVG(heat_index) AS avg_heat_index
                      FROM sensor_readings
                      WHERE alert_time IS NOT NULL";

if (!empty($startDate)) {
    $query_time_series .= " AND alert_time >= '$startDate'";
}
if (!empty($endDate)) {
    $query_time_series .= " AND alert_time <= '$endDate'";
}

$query_time_series .= " GROUP BY time_label, location_name ORDER BY alert_time";

$result = $conn->query($query_time_series);

// Prepare data for the PDF
$html = "<h1>Heat Index Report</h1>";
$html .= "<p>Interval: " . htmlspecialchars($interval) . "</p>";
if ($startDate) $html .= "<p>Start Date: " . htmlspecialchars($startDate) . "</p>";
if ($endDate) $html .= "<p>End Date: " . htmlspecialchars($endDate) . "</p>";

$dataForChart = []; // Array to hold data for Google Charts
if ($result->num_rows > 0) {
    $html .= "<table border='1' cellpadding='5' cellspacing='0'>";
    $html .= "<thead><tr><th>Time</th><th>Location</th><th>Avg Temperature</th><th>Avg Humidity</th><th>Avg Heat Index</th></tr></thead>";
    $html .= "<tbody>";
    
    while ($row = $result->fetch_assoc()) {
        $html .= "<tr>";
        $html .= "<td>" . htmlspecialchars($row['time_label']) . "</td>";
        $html .= "<td>" . htmlspecialchars($row['location_name']) . "</td>";
        $html .= "<td>" . htmlspecialchars($row['avg_temperature']) . "</td>";
        $html .= "<td>" . htmlspecialchars($row['avg_humidity']) . "</td>";
        $html .= "<td>" . htmlspecialchars($row['avg_heat_index']) . "</td>";
        $html .= "</tr>";
        
        // Prepare data for Google Charts (use only the Avg Heat Index for the chart)
        $dataForChart[] = ["'".$row['time_label']."'", $row['avg_heat_index']];
    }
    
    $html .= "</tbody></table>";
} else {
    $html .= "<p>No data available for the selected time interval.</p>";
}

// Generate the PDF with TCPDF
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->writeHTML($html);
$pdf->Output('heat_index_report.pdf', 'D'); // Force download of PDF

// Prepare the data for the Google Chart in JSON format
$chartData = json_encode($dataForChart);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Average Heat Index Chart</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            var data = google.visualization.arrayToDataTable([
                ['Time', 'Avg Heat Index'],
                <?php
                // Print the chart data from PHP array
                foreach ($dataForChart as $dataPoint) {
                    echo "[" . implode(", ", $dataPoint) . "],";
                }
                ?>
            ]);

            var options = {
                title: 'Average Heat Index Over Time',
                curveType: 'function',
                legend: { position: 'bottom' }
            };

            var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));
            chart.draw(data, options);
        }
    </script>
</head>
<body>
    <h1>Average Heat Index Chart</h1>
    <div id="curve_chart" style="width: 800px; height: 400px;"></div>
</body>
</html>
