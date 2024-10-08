<?php
include 'db.php';

if (isset($_GET['id'])) {
    $alert_id = $_GET['id'];

    // Update the alert status to resolved
    $update_query = "UPDATE alerts SET resolved = 1 WHERE alert_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('i', $alert_id);
    
    if ($stmt->execute()) {
        header("Location: admin/alerts.php?success=Alert resolved successfully.");
    } else {
        header("Location: admin/alerts.php?error=Failed to resolve alert.");
    }
    
    $stmt->close();
} else {
    header("Location: admin/alerts.php?error=No alert ID provided.");
}
$conn->close();
