<?php
ob_start(); // Start output buffering

require 'config.php'; // Include database configuration
require 'vendor/autoload.php'; // Load Composer dependencies

loadEnv();

// Connect to the database
$conn = dbConnect();

// Create instance of FPDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);

// Title
$pdf->Cell(0, 10, 'Heat Index Alerts', 0, 1, 'C');

// Add current date
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Date: ' . date('F j, Y'), 0, 1, 'C'); // Current date at the top
$pdf->Ln(5); // Add a small space below the date

// Add summary information
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Summary', 0, 1);
$pdf->SetFont('Arial', '', 12);

// Fetch summary data
$querySummary = "SELECT COUNT(*) AS total_alerts, MAX(heat_index) AS highest_heat_index FROM sensor_readings WHERE alert IS NOT NULL";
$summaryResult = $conn->query($querySummary);
$summaryData = $summaryResult->fetch_assoc();
$pdf->Cell(0, 10, 'Total Alerts: ' . $summaryData['total_alerts'], 0, 1);
$pdf->Cell(0, 10, 'Highest Heat Index: ' . number_format($summaryData['highest_heat_index'], 2) . ' °C', 0, 1);

// Add a line break
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Alerts Details', 0, 1);
$pdf->SetFont('Arial', '', 12);

// Set margins
$tableMargin = 10; // Half an inch in mm (approx. 12.7 mm)
$pdf->SetX($tableMargin); // Set X position to create left margin

// Define column widths dynamically
$colWidths = [
    'location' => 20,  // Location width
    'temperature' => 30, // Temperature width
    'humidity' => 30, // Humidity width
    'heat_index' => 40, // Heat Index width
    'alert' => 40, // Alert Level width
    'alert_time' => 40 // Alert Time width (shortened)
];

// Table header
$pdf->Cell($colWidths['location'], 10, 'Location', 1);
$pdf->Cell($colWidths['temperature'], 10, 'Temperature (°C)', 1);
$pdf->Cell($colWidths['humidity'], 10, 'Humidity (%)', 1);
$pdf->Cell($colWidths['heat_index'], 10, 'Heat Index', 1);
$pdf->Cell($colWidths['alert'], 10, 'Alert Level', 1);
$pdf->Cell($colWidths['alert_time'], 10, 'Alert Time', 1);
$pdf->Ln();

// Fetch detailed alerts
$query = "SELECT location_name, temperature, humidity, heat_index, alert, alert_time 
          FROM sensor_readings 
          WHERE alert IS NOT NULL 
          ORDER BY alert_time DESC";
$result = $conn->query($query);

// Populate table rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Cell for Location without background color
        $pdf->Cell($colWidths['location'], 10, htmlspecialchars($row['location_name']), 1);
        
        // Set background color based on alert level
        switch ($row['alert']) {
            case 'Not Hazardous':
                $pdf->SetFillColor(200, 255, 200); // Light Green
                break;
            case 'Caution':
                $pdf->SetFillColor(255, 200, 0); // Light Orange
                break;
            case 'Extreme Caution':
                $pdf->SetFillColor(255, 255, 200); // Light Yellow
                break;
            case 'Danger':
                $pdf->SetFillColor(255, 100, 100); // Light Red
                break;
            case 'Extreme Danger':
                $pdf->SetFillColor(200, 0, 200); // Light Purple
                break;
            default:
                $pdf->SetFillColor(255, 255, 255); // Default to white
                break;
        }

        // Fill cells with background color
        $pdf->Cell($colWidths['temperature'], 10, htmlspecialchars($row['temperature']), 1, 0, '', true);
        $pdf->Cell($colWidths['humidity'], 10, htmlspecialchars($row['humidity']), 1, 0, '', true);
        $pdf->Cell($colWidths['heat_index'], 10, htmlspecialchars($row['heat_index']), 1, 0, '', true);
        $pdf->Cell($colWidths['alert'], 10, htmlspecialchars($row['alert']), 1, 0, '', true);
        
        // Shorten the alert time format
        $date = new DateTime($row['alert_time']);
        $pdf->Cell($colWidths['alert_time'], 10, htmlspecialchars($date->format('M j, g:i A')), 1, 0, '', true); // Short format
        $pdf->Ln();
        
        // Reset fill color to white for the next row
        $pdf->SetFillColor(255, 255, 255); 
    }
} else {
    $pdf->Cell(0, 10, 'No alerts found', 1);
}

// Clear output buffer
ob_end_clean(); // Clear the output buffer and turn off output buffering

// Output the PDF to the browser
$pdf->Output('D', 'heat_index_alerts.pdf'); // Download as a file named heat_index_alerts.pdf
?>
