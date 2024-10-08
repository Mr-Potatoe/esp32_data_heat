<?php
include '../php/db.php';

if (isset($_GET['id'])) {
    $log_id = intval($_GET['id']);
    
    // Prepare the SQL statement to prevent SQL injection
    $stmt = $conn->prepare("DELETE FROM Logs WHERE log_id = ?");
    $stmt->bind_param("i", $log_id);
    
    if ($stmt->execute()) {
        header("Location: ../admin/view_logs.php?msg=Log deleted successfully");
    } else {
        echo "Error deleting log: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>
