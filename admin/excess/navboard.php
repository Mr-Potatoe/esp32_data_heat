

<?php include '../php/admin_protect.php'; ?>
<?php include '../php/db.php'; ?>
<?php
$recent_activity_query = "SELECT * FROM logs ORDER BY timestamp DESC LIMIT 5";
$recent_activity_result = $conn->query($recent_activity_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
</head>
<body class="flex">
        <!-- navbar -->
        <?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Responsive Navbar -->
<nav id="navbar" class="bg-gray-800 text-white transition-all duration-300 ease-in-out overflow-hidden">
    <div class="flex flex-col h-full">
        <!-- Logo and Toggle Button Section -->
        <div class="flex items-center justify-between p-4">
            <a href="dashboard.php" id="navbar-logo" class="text-xl font-bold truncate">Welcome, <?php echo $_SESSION['admin_username']; ?></a>
            <button id="navbar-toggle" class="text-gray-300 hover:text-white focus:outline-none">
                <i class="fas fa-bars text-lg"></i>
            </button>
        </div>

        <!-- Menu Links -->
        <ul id="navbar-menu" class="flex-grow flex flex-col space-y-2 p-4">
            <?php 
            $menu_items = [
                ['link' => 'dashboard.php', 'icon' => 'home', 'label' => 'Dashboard'],
                ['link' => 'map.php', 'icon' => 'map-marked-alt', 'label' => 'Heat Index Map'],
                ['link' => 'manage_sensors.php', 'icon' => 'tools', 'label' => 'Manage Sensors'],
                ['link' => 'history.php', 'icon' => 'history', 'label' => 'Heat Index History'],
                ['link' => 'alerts.php', 'icon' => 'exclamation-triangle', 'label' => 'Alerts'],
                ['link' => 'view_logs.php', 'icon' => 'file-alt', 'label' => 'View Logs'],
            ];

            foreach ($menu_items as $item): ?>
                <li>
                    <a href="<?php echo $item['link']; ?>" class="flex items-center space-x-2 p-2 rounded <?php echo $current_page == $item['link'] ? 'bg-gray-700 text-white' : 'text-gray-300'; ?> hover:bg-gray-700 hover:text-white transition-colors duration-300">
                        <i class="fas fa-<?php echo $item['icon']; ?> w-6 text-center"></i>
                        <span class="navbar-item-label"><?php echo $item['label']; ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
            <li>
                <a href="#" onclick="confirmLogout(event)" class="flex items-center space-x-2 p-2 rounded text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-300">
                    <i class="fas fa-sign-out-alt w-6 text-center"></i>
                    <span class="navbar-item-label">Logout</span>
                </a>
            </li>
        </ul>

        <!-- Optional Footer/Bottom Section -->
        <div class="p-4 mt-auto">
            <p class="text-gray-400 text-sm">&copy; 2024 Heat Index Monitor</p>
        </div>
    </div>
</nav>

<script>
    const navbarToggle = document.getElementById('navbar-toggle');
    const navbar = document.getElementById('navbar');
    const navbarMenu = document.getElementById('navbar-menu');
    const navbarLogo = document.getElementById('navbar-logo');
    const mainContent = document.querySelector('.flex-1');

    let isExpanded = true;

    function toggleNavbar() {
        if (window.innerWidth < 768) {
            // Mobile behavior
            navbarMenu.classList.toggle('hidden');
        } else {
            // Desktop behavior
            isExpanded = !isExpanded;
            if (isExpanded) {
                navbar.classList.remove('w-20');
                navbar.classList.add('w-64');
                mainContent.classList.remove('ml-20');
                mainContent.classList.add('ml-64');
                document.querySelectorAll('.navbar-item-label').forEach(el => el.classList.remove('hidden'));
                navbarLogo.classList.remove('hidden');
            } else {
                navbar.classList.remove('w-64');
                navbar.classList.add('w-20');
                mainContent.classList.remove('ml-64');
                mainContent.classList.add('ml-20');
                document.querySelectorAll('.navbar-item-label').forEach(el => el.classList.add('hidden'));
                navbarLogo.classList.add('hidden');
            }
        }
    }

    navbarToggle.addEventListener('click', toggleNavbar);

    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            navbarMenu.classList.remove('hidden');
            navbar.classList.remove('w-full');
            navbar.classList.add(isExpanded ? 'w-64' : 'w-20');
            mainContent.classList.add(isExpanded ? 'ml-64' : 'ml-20');
        } else {
            navbarMenu.classList.add('hidden');
            navbar.classList.remove('w-64', 'w-20');
            navbar.classList.add('w-full');
            mainContent.classList.remove('ml-64', 'ml-20');
        }
    });

    // Initialize navbar state on page load
    window.addEventListener('load', () => {
        if (window.innerWidth < 768) {
            navbar.classList.add('w-full');
            mainContent.classList.remove('ml-64', 'ml-20');
        } else {
            navbar.classList.add('w-64');
            mainContent.classList.add('ml-64');
        }
    });

    function confirmLogout(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Are you sure?',
            text: "You will be logged out of your account.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, logout!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../php/logout.php';
            }
        });
    }
</script>

<style>
    @media (max-width: 767px) {
        #navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: auto;
        }
        #navbar-menu {
            background-color: #1f2937;
        }
        .flex-1 {
            margin-top: 60px; /* Adjust based on your navbar height */
        }
    }
    @media (min-width: 768px) {
        #navbar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 16rem;
            transition: width 0.3s ease-in-out;
        }
        .flex-1 {
            margin-left: 16rem;
            transition: margin-left 0.3s ease-in-out;
        }
        #navbar.w-20 {
            width: 5rem;
        }
        .flex-1.ml-20 {
            margin-left: 5rem;
        }

        /* Sticky navbar and main content when collapsed */
        #navbar.collapsed {
            width: 5rem;
        }
        .flex-1.sticky {
            margin-left: 5rem;
        }
    }
</style>

        <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
            <!-- Mobile Header -->
            <header class="bg-blue-600 text-white p-4 md:hidden">
            <h1 class="text-xl font-bold text-center">DashBoard</h1>
            </header>
            <header class="bg-blue-600 text-white p-4 hidden md:flex justify-between items-center">
                
                <h1 class="text-2xl font-bold">Dashboard</h1>
                <!-- Quick Actions Dropdown -->
                <div class="relative">
                    <button id="quickActionsButton" class="flex items-center bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-800 focus:outline-none transition duration-200">
                        Quick Actions
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="quickActionsDropdown" class="absolute right-0 mt-2 w-48 bg-white border border-gray-300 rounded-md shadow-lg hidden z-40">
                        <ul class="py-1">
                            <li>
                                <a href="add_sensor.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-100">Add New Sensor</a>
                            </li>
                            <li>
                                <a href="view_logs.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-100">View Logs</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>
                <section class="bg-white p-4 md:p-6 max-w-6xl mx-auto w-full mt-6">
                    <h2 class="text-xl font-semibold mb-4">Heat Index Management</h2>
                    <p class="text-gray-600 mb-6">This is the admin panel where you can manage sensor data and view logs.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <!-- Overview Cards -->
                        <?php
                        // Fetch total sensors
                        $total_sensors_query = "SELECT COUNT(*) as total FROM Sensors";
                        $total_result = $conn->query($total_sensors_query);
                        $total_sensors = $total_result->fetch_assoc()['total'];
            
                        // Fetch active sensors
                        $active_sensors_query = "SELECT COUNT(*) as active FROM Sensors WHERE status = 'active'";
                        $active_result = $conn->query($active_sensors_query);
                        $active_sensors = $active_result->fetch_assoc()['active'];
            
                        // Fetch recent heat index
                        $recent_heat_index_query = "SELECT heat_index FROM heatindexdata ORDER BY timestamp DESC LIMIT 1";
                        $heat_index_result = $conn->query($recent_heat_index_query);
                        $recent_heat_index = $heat_index_result->num_rows > 0 ? $heat_index_result->fetch_assoc()['heat_index'] . '°F' : 'N/A';
            
                        // Fetch alerts
                        $alerts_query = "SELECT COUNT(*) as alerts FROM alerts WHERE resolved = 0";
                        $alerts_result = $conn->query($alerts_query);
                        $alerts_count = $alerts_result->fetch_assoc()['alerts'];
                        ?>
            

                        <div class="bg-blue-100 p-4 rounded shadow">
                            <h3 class="font-bold text-lg">Total Sensors</h3>
                            <p class="text-3xl font-bold"><?php echo $total_sensors; ?></p>
                        </div>
                        <div class="bg-green-100 p-4 rounded shadow">
                            <h3 class="font-bold text-lg">Active Sensors</h3>
                            <p class="text-3xl font-bold"><?php echo $active_sensors; ?></p>
                        </div>
                        <div class="bg-yellow-100 p-4 rounded shadow">
                            <h3 class="font-bold text-lg">Recent Heat Index</h3>
                            <p class="text-3xl font-bold"><?php echo $recent_heat_index; ?></p>
                        </div>
                        <div class="bg-red-100 p-4 rounded shadow">
                            <h3 class="font-bold text-lg">Active Alerts</h3>
                            <p class="text-3xl font-bold"><?php echo $alerts_count; ?></p>
                        </div>
                    </div>

                    <!-- Heat Index Chart -->
                    <div class="mb-8 full-width">
                        <h3 class="font-bold text-lg mb-4">Heat Index Trends</h3>
                        <div class="flex flex-wrap gap-2 mb-4">
                            <button id="hourlyButton" class="chart-button bg-blue-800 text-white px-3 py-1 rounded hover:bg-blue-700 transition duration-200" onclick="updateChart('hourly', this)">Hourly</button>
                            <button id="dailyButton" class="chart-button bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition duration-200" onclick="updateChart('daily', this)">Daily</button>
                            <button id="weeklyButton" class="chart-button bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition duration-200" onclick="updateChart('weekly', this)">Weekly</button>
                            <button id="monthlyButton" class="chart-button bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition duration-200" onclick="updateChart('monthly', this)">Monthly</button>
                            <button id="yearlyButton" class="chart-button bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition duration-200" onclick="updateChart('yearly', this)">Yearly</button>
                        </div>
                        <div class="w-full h-64 md:h-96">
                            <canvas id="heatIndexChart"></canvas>
                        </div>
                    </div>

                    <!-- Recent Activity Log -->
                    <div class="full-width">
                        <h3 class="font-bold text-lg mb-4">Recent Activity</h3>
                        <ul class="bg-gray-100 p-4 rounded mb-4">
                            <?php
                            if ($recent_activity_result->num_rows > 0) {
                                while ($row = $recent_activity_result->fetch_assoc()) {
                                    echo "<li class='mb-2'>{$row['action']} - <em>{$row['timestamp']}</em></li>";
                                }
                            } else {
                                echo "<li>No recent activity found.</li>";
                            }
                            ?>
                        </ul>
                        <a href="view_logs.php" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">View All Logs</a>
                    </div>
                </section>
            </main>
    <script>
         // Chart initialization and update function (unchanged)
         const ctx = document.getElementById('heatIndexChart').getContext('2d');
         const heatIndexChart = new Chart(ctx, {
             type: 'line',
             data: {
                 labels: [],
                 datasets: [{
                     label: 'Heat Index',
                     data: [],
                     backgroundColor: 'rgba(75, 192, 192, 0.2)',
                     borderColor: 'rgba(75, 192, 192, 1)',
                     borderWidth: 1,
                     fill: true
                 }]
             },
             options: {
                 responsive: true,
                 maintainAspectRatio: false,
                 scales: {
                     y: {
                         beginAtZero: true
                     }
                 }
             }
         });
 
         function updateChart(range, button) {
            // Fetch the new data for the selected range
            fetch(`../php/fetch_heat_index_data.php?range=${range}`)
                .then(response => response.json())
                .then(data => {
                    const labels = data.map(point => point.timestamp);
                    const heatIndexData = data.map(point => point.heat_index);
        
                    heatIndexChart.data.labels = labels;
                    heatIndexChart.data.datasets[0].data = heatIndexData;
                    heatIndexChart.update();
                })
                .catch(error => console.error('Error fetching data:', error));
        
            // Update the active button
            document.querySelectorAll('.chart-button').forEach(btn => {
                btn.classList.remove('bg-blue-800', 'active'); // Remove active class from all buttons
                btn.classList.add('bg-blue-600'); // Reset non-active buttons' colors
            });
        
            // Set the clicked button as active
            button.classList.remove('bg-blue-600');
            button.classList.add('bg-blue-800', 'active'); // Make it darker to show it's active
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            updateChart('hourly', document.getElementById('hourlyButton'));

            // Quick Actions Dropdown
            const quickActionsButton = document.getElementById('quickActionsButton');
            const quickActionsDropdown = document.getElementById('quickActionsDropdown');

            quickActionsButton.addEventListener('click', (e) => {
                e.stopPropagation();
                quickActionsDropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', () => {
                quickActionsDropdown.classList.add('hidden');
            });
        });
    </script>
</body>
</html>