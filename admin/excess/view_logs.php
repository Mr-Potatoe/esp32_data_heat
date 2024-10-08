<?php
include '../php/admin_protect.php';
include '../php/db.php';

// Pagination setup
$limit = 5; // Number of logs per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of logs
$total_sql = "SELECT COUNT(*) as total FROM Logs";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_logs = $total_row['total'];
$total_pages = ceil($total_logs / $limit);

// Get logs for the current page
$sql = "SELECT * FROM Logs ORDER BY timestamp DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'head.php'; ?> <!-- Include the head -->
</head>
<body class="flex bg-gray-100">

    <!-- ======= Header ======= -->
    <?php include 'header.php'; ?>


    <!-- ======= Sidebar ======= -->
    <?php include 'sidebar.php'; ?>

    <main id="main" class="main">

    <header class="bg-primary text-white p-4">
        <h1 class="h2 text-center">View Logs</h1>
    </header>

    <section class="container mt-4 p-4 bg-white rounded shadow">
        <table class="table table-bordered table-hover mt-4">
            <thead class="thead-light">
                <tr>
                    <th scope="col">Log ID</th>
                    <th scope="col">Action</th>
                    <th scope="col">Timestamp</th>
                    <th scope="col">Admin Username</th>
                    <th scope="col">Details</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['log_id'] . "</td>";
                        echo "<td>" . $row['action'] . "</td>";
                        echo "<td>" . $row['timestamp'] . "</td>";
                        echo "<td>" . $row['admin_username'] . "</td>";
                        echo "<td>" . $row['details'] . "</td>";
                        echo "<td>
                                <a href='#' class='text-danger' onclick='confirmDelete(" . $row['log_id'] . ")'>Delete</a>
                            </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center'>No logs found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="mt-4">
            <nav aria-label="Page navigation example">
                <ul class="pagination justify-content-between">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <span class="align-self-center">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </section>

    <!-- SweetAlert JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/SweetAlert/1.1.3/sweetalert.min.js"></script>
    <script>
    function confirmDelete(logId) {
        swal({
            title: "Are you sure?",
            text: "Once deleted, you will not be able to recover this log!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {
            if (willDelete) {
                // Redirect to the delete log PHP script
                window.location.href = '../php/delete_log.php?id=' + logId;
            } else {
                swal("Your log is safe!");
            }
        });
    }
    </script>
    </main>

    <!-- footer and scroll to top -->
    <?php include 'footer.php'; ?>
    <!-- include scripts -->
    <?php include 'scripts.php'; ?>

</body>
</html>
