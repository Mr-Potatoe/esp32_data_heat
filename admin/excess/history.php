<?php
include '../php/admin_protect.php';
include '../php/db.php'; // Include your DB connection script

// Function to determine heat index category based on PAGASA standards
function getHeatIndexCategory($heat_index) {
    if ($heat_index < 26) {
        return 'No Danger (Below 26°C)';
    } elseif ($heat_index < 33) {
        return 'Caution (26-32°C)';
    } elseif ($heat_index < 39) {
        return 'Extreme Caution (33-38°C)';
    } elseif ($heat_index < 42) {
        return 'Danger (39-41°C)';
    } else {
        return 'Extreme Danger (Above 42°C)';
    }
}

// Fetch average heat index data
$timeframes = ['hourly', 'daily', 'weekly', 'monthly', 'yearly'];
$data = [];

foreach ($timeframes as $range) {
    switch ($range) {
        case 'hourly':
            $query = "SELECT DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as period, AVG(heat_index) as avg_heat_index
                      FROM heatindexdata
                      GROUP BY period
                      ORDER BY period DESC";
            break;
        case 'daily':
            $query = "SELECT DATE_FORMAT(timestamp, '%Y-%m-%d') as period, AVG(heat_index) as avg_heat_index
                      FROM heatindexdata
                      GROUP BY period
                      ORDER BY period DESC";
            break;
        case 'weekly':
            $query = "SELECT DATE_FORMAT(timestamp, '%Y-%u') as period, AVG(heat_index) as avg_heat_index
                      FROM heatindexdata
                      GROUP BY period
                      ORDER BY period DESC";
            break;
        case 'monthly':
            $query = "SELECT DATE_FORMAT(timestamp, '%Y-%m') as period, AVG(heat_index) as avg_heat_index
                      FROM heatindexdata
                      GROUP BY period
                      ORDER BY period DESC";
            break;
        case 'yearly':
            $query = "SELECT DATE_FORMAT(timestamp, '%Y') as period, AVG(heat_index) as avg_heat_index
                      FROM heatindexdata
                      GROUP BY period
                      ORDER BY period DESC";
            break;
    }

    $result = $conn->query($query);
    $data[$range] = $result->fetch_all(MYSQLI_ASSOC);
}
$current_range = 'hourly'; // Set default range for active button tracking
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
</head>
<body>

    <!-- ======= Header ======= -->
    <?php include 'header.php'; ?>

    <!-- ======= Sidebar ======= -->
    <?php include 'sidebar.php'; ?>

    <main id="main" class="main">
        <header class="bg-primary text-white p-4">
            <h1 class="h4 text-center">Heat Index History</h1>
        </header>

        <section class="max-w-6xl mx-auto p-6 bg-white rounded shadow-md mt-6">
            <h2 class="h5 font-semibold mb-4">Average Heat Index (Heatmap)</h2>
            
            <div class="btn-group mb-4">
                <?php foreach ($timeframes as $range): ?>
                    <button class="btn btn-outline-primary" onclick="showTable('<?php echo $range; ?>')" id="<?php echo $range; ?>-btn"><?php echo ucfirst($range); ?></button>
                <?php endforeach; ?>
            </div>

            <div id="heatmap-table" class="overflow-x-auto">
                <?php foreach ($data as $range => $values): ?>
                    <table id="<?php echo $range; ?>" class="table table-bordered table-striped d-none mb-4">
                        <thead class="table-light">
                            <tr>
                                <th>Period</th>
                                <th>Average Heat Index (°C)</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($values as $row): 
                                $category = getHeatIndexCategory($row['avg_heat_index']);
                            ?>
                                <tr class="<?php echo $category == 'Caution (26-32°C)' ? 'table-warning' : ($category == 'Extreme Caution (33-38°C)' ? 'table-danger' : ($category == 'Danger (39-41°C)' ? 'table-danger' : 'table-primary')); ?>">
                                    <td><?php echo $row['period']; ?></td>
                                    <td><?php echo round($row['avg_heat_index'], 2); ?></td>
                                    <td><?php echo $category; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            </div>

            <!-- Legend -->
            <div class="mt-4">
                <h3 class="font-bold">Heat Index Legend</h3>
                <div class="d-flex">
                    <div class="d-flex align-items-center me-3">
                        <div class="w-4 h-4 bg-primary border border-gray-300 me-1"></div>
                        <span>No Danger (Below 26°C)</span>
                    </div>
                    <div class="d-flex align-items-center me-3">
                        <div class="w-4 h-4 bg-warning border border-gray-300 me-1"></div>
                        <span>Caution (26-32°C)</span>
                    </div>
                    <div class="d-flex align-items-center me-3">
                        <div class="w-4 h-4 bg-danger border border-gray-300 me-1"></div>
                        <span>Extreme Caution (33-38°C)</span>
                    </div>
                    <div class="d-flex align-items-center me-3">
                        <div class="w-4 h-4 bg-danger border border-gray-300 me-1"></div>
                        <span>Danger (39-41°C)</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="w-4 h-4 bg-danger border border-gray-300 me-1"></div>
                        <span>Extreme Danger (Above 42°C)</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- footer and scroll to top -->
    <?php include 'footer.php'; ?>
    <!-- include scripts -->
    <?php include 'scripts.php'; ?>

    <script>
        function showTable(range) {
            const tables = document.querySelectorAll('table');
            tables.forEach(table => {
                table.classList.add('d-none'); // Hide all tables
            });
            document.getElementById(range).classList.remove('d-none'); // Show selected table
            
            // Update active button
            const buttons = document.querySelectorAll('.btn-group button');
            buttons.forEach(button => {
                if (button.id === range + '-btn') {
                    button.classList.remove('btn-outline-primary');
                    button.classList.add('btn-primary');
                } else {
                    button.classList.add('btn-outline-primary');
                    button.classList.remove('btn-primary');
                }
            });
        }

        // Show the hourly table by default
        document.addEventListener('DOMContentLoaded', function() {
            showTable('<?php echo $current_range; ?>');
        });
    </script>

</body>
</html>

