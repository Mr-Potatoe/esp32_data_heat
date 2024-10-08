<?php
include 'db.php'; // Include your database connection

$time_range = isset($_GET['range']) ? $_GET['range'] : 'hourly';
$dataPoints = [];

switch ($time_range) {
    case 'yearly':
        $groupBy = "YEAR(timestamp)";
        $date_format = "%Y";
        break;
    case 'monthly':
        $groupBy = "YEAR(timestamp), MONTH(timestamp)";
        $date_format = "%Y-%m";
        break;
    case 'weekly':
        $groupBy = "YEAR(timestamp), WEEK(timestamp)";
        $date_format = "%Y-%u"; // ISO week number
        break;
    case 'daily':
        $groupBy = "DATE(timestamp)";
        $date_format = "%Y-%m-%d";
        break;
    case 'hourly':
    default:
        $groupBy = "DATE(timestamp), HOUR(timestamp)";
        $date_format = "%Y-%m-%d %H:00";
        break;
}

$query = "SELECT DATE_FORMAT(timestamp, '$date_format') as time_period, AVG(heat_index) as average_heat_index 
          FROM heatindexdata 
          GROUP BY $groupBy 
          ORDER BY timestamp ASC";

$result = $conn->query($query);

if (!$result) {
    echo json_encode(['error' => $conn->error]);
    exit;
}

while ($row = $result->fetch_assoc()) {
    $dataPoints[] = [
        'timestamp' => $row['time_period'],
        'heat_index' => $row['average_heat_index']
    ];
}

header('Content-Type: application/json');
echo json_encode($dataPoints);
?>
