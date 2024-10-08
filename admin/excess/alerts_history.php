<?php include '../php/admin_protect.php'; ?>
<?php include '../php/db.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?> <!-- Include the head -->
</head>
<body>

    <!-- ======= Header ======= -->
    <?php include 'header.php'; ?>


    <!-- ======= Sidebar ======= -->
    <?php include 'sidebar.php'; ?>
    
    <main id="main" class="main">
    
    <header class="bg-primary text-white p-4">
        <h1 class="h2 text-center">Alerts Management</h1>
    </header>

    <section class="container mt-4 p-4 bg-white rounded shadow">
        <h2 class="h4 font-weight-bold">Alerts History</h2>

        <div class="d-flex justify-content-between mb-3">
            <a href="alerts.php" class="btn btn-primary">Back to Active Alerts</a>
        </div>

        <table class="table table-bordered table-hover mt-4">
            <thead class="thead-light">
                <tr>
                    <th scope="col">Alert ID</th>
                    <th scope="col">Message</th>
                    <th scope="col">Timestamp</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Pagination logic
                $limit = 10; // Number of results per page
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $offset = ($page - 1) * $limit;

                // Query to count total alerts
                $count_query = "SELECT COUNT(*) AS total FROM alerts";
                $count_result = $conn->query($count_query);
                $total_alerts = $count_result->fetch_assoc()['total'];
                $total_pages = ceil($total_alerts / $limit);

                // Fetch alerts with limit and offset
                $alerts_query = "SELECT * FROM alerts ORDER BY timestamp DESC LIMIT $limit OFFSET $offset"; 
                $alerts_result = $conn->query($alerts_query);

                if ($alerts_result->num_rows > 0) {
                    while ($row = $alerts_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['alert_id'] . "</td>";
                        echo "<td>" . $row['message'] . "</td>";
                        echo "<td>" . $row['timestamp'] . "</td>";
                        echo "<td>" . ($row['resolved'] ? 'Resolved' : 'Active') . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' class='text-center'>No alerts found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Pagination Links -->
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <!-- Previous Button -->
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>">Previous</a>
                </li>

                <?php
                // Calculate range for pagination buttons
                $start = max(1, $page - 1);
                $end = min($total_pages, $page + 1);

                // Display pagination buttons
                for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <!-- Next Button -->
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo min($total_pages, $page + 1); ?>">Next</a>
                </li>
            </ul>
        </nav>
    </section>

    </main>


    <!-- footer and scroll to top -->
    <?php include 'footer.php'; ?>
    <!-- include scripts -->
    <?php include 'scripts.php'; ?>

</body>
</html>
