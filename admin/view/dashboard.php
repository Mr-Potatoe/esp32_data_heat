<?php include '../../fetch_php/admin_protect.php'; ?>
<?php
require '../../config.php'; // Configuration and database connection
loadEnv(); // Load environment variables
$conn = dbConnect(); // Connect to the database
?>

<?php include '../../fetch_php/fetch_dashboard.php'; ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../components/head.php'; ?>
    <title>Sensor Readings Dashboard</title>

<!-- Script for Bar Chart -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        .container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

    .dropdown-icon-wrapper {
        position: relative;
    }

    .dropdown-icon {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .card {
    background-color: #f8f9fa; /* Light background for the card */
    border: 1px solid #e1e1e1; /* Soft border */
    border-radius: 5px; /* Rounded corners */
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Subtle shadow */
}

.card-title {
    font-weight: bold; /* Bold title for emphasis */
    margin-bottom: 1rem; /* Space below the title */
}

.form-inline {
    display: flex;
    flex-wrap: wrap; /* Allow wrapping for small screens */
}

.form-group {
    flex: 1; /* Each form group takes equal space */
    min-width: 250px; /* Ensure inputs have a minimum width */
}
.card-hover:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    .text-center { text-align: center; }
    .h-100 { height: 100%; }
    </style>
</head>
<body>

    <!-- ======= Header ======= -->
    <?php include '../components/header.php'; ?>

    <!-- ======= Sidebar ======= -->
    <?php include '../components/sidebar.php'; ?>

    <main id="main" class="main">
    <div class="container">
        <!-- Header with Icon -->
        <h1 class="mt-4"><i class="bi bi-house me-2"></i>Sensor Dashboard</h1>

        <!-- Filter Form in a Card -->
        <div class="card p-3 mb-4 filter-form">
            <h5 class="card-title"><i class="bi bi-funnel me-2"></i>Filter Data</h5>
            <form method="GET">
                <div class="form-row d-flex flex-wrap">

                    <!-- Location Filter Dropdown -->
                    <div class="form-group col-md-4 col-sm-12">
                        <label for="location" class="mr-2">Select Location:</label>
                        <select id="location" name="location" class="form-control">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= htmlspecialchars($location); ?>" <?= (isset($_GET['location']) && $_GET['location'] === $location) ? 'selected' : ''; ?>><?= htmlspecialchars($location); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Time Interval Dropdown -->
                    <div class="form-group col-md-4 col-sm-12">
                        <label for="interval" class="mr-2">Select Time Interval:</label>
                        <select id="interval" name="interval" class="form-control">
                            <option value="hour" <?= $interval === 'hour' ? 'selected' : ''; ?>>Hourly</option>
                            <option value="day" <?= $interval === 'day' ? 'selected' : ''; ?>>Daily</option>
                            <option value="week" <?= $interval === 'week' ? 'selected' : ''; ?>>Weekly</option>
                            <option value="month" <?= $interval === 'month' ? 'selected' : ''; ?>>Monthly</option>
                            <option value="year" <?= $interval === 'year' ? 'selected' : ''; ?>>Yearly</option>
                        </select>
                    </div>

                                    <!-- Start Date -->
                    <div class="form-group col-md-4 col-sm-12">
                        <label for="start_date" class="mr-2">Start Date and Time:</label>
                        <input type="datetime-local" id="start_date" name="start_date" class="form-control" 
                            value="<?= htmlspecialchars($startDate); ?>" required>
                    </div>

                    <!-- End Date -->
                    <div class="form-group col-md-4 col-sm-12">
                        <label for="end_date" class="mr-2">End Date and Time:</label>
                        <input type="datetime-local" id="end_date" name="end_date" class="form-control" 
                            value="<?= htmlspecialchars($endDate); ?>" required>
                    </div>


                    <!-- Submit Button -->
                    <div class="form-group mt-4 col-md-4 col-sm-12 align-self-end">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
                        <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-clockwise me-1"></i>Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Alert if No Data -->
        <?php if (!empty($noDataMessage)): ?>
            <div class="alert alert-warning" role="alert">
                <?= $noDataMessage; ?>
            </div>
        <?php endif; ?>

<!-- Bar Chart in a Card -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Heat Index Overview</h5>
        <div class="time-frame" id="timeFrame-barChart" 
            style="background-color: rgba(255, 255, 255, 0.9); 
                   padding: 10px 15px; 
                   border-radius: 5px; 
                   box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); 
                   font-weight: bold; 
                   font-size: 14px;">
           <strong>Time Frame:</strong><br>
           <?= htmlspecialchars(date('M j, Y', strtotime($startDate))) ?> - <?= htmlspecialchars(date('M j, Y', strtotime($endDate))) ?>
        </div>
    </div>

    <div class="card-body">
        <div id="barChart"></div> <!-- Use a div for ApexCharts -->
    </div>
</div>



<script>
    var options = {
        chart: {
            type: 'bar',
            height: 400,
            zoom: {
                enabled: true,       // Enable zooming
                type: 'x',           // Allow zooming on the x-axis
                autoScaleYaxis: true // Automatically scale the y-axis when zooming
            },
            toolbar: {
                show: true // Show the toolbar
            }
        },
        series: [{
            name: 'Average Heat Index',
            data: <?= json_encode($avgHeatIndexes); ?>
        }, {
            name: 'Max Heat Index',
            data: <?= json_encode($maxHeatIndexes); ?>
        }],
        xaxis: {
            categories: <?= json_encode($locations); ?>,
            title: {
                text: 'Locations',
                style: {
                    fontSize: '14px', // Set font size for the title
                    fontWeight: 'bold'
                }
            }
        },
        yaxis: {
            title: {
                text: 'Heat Index (°C)', // Include unit in the title
                style: {
                    fontSize: '14px', // Set font size for the title
                    fontWeight: 'bold'
                }
            },
            min: 0, // Start y-axis from 0
            labels: {
                formatter: function(value) {
                    return Math.floor(value); // Remove decimals from y-axis labels
                }
            }
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function(value) {
                    return value.toFixed(2) + ' °C'; // Show data points with two decimal places and unit
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'center',
            fontSize: '14px' // Increase font size for legend
        },
        plotOptions: {
            bar: {
                horizontal: false, // Make the bars vertical
                columnWidth: '70%', // Width of the bars
                endingShape: 'rounded' // Round the edges of the bars
            }
        },
        dataLabels: {
            enabled: true, // Show data labels on the bars
            style: {
                fontSize: '12px', // Font size for data labels
                colors: ['#304758'] // Color of the data labels
            },
            formatter: function(value) {
                return value.toFixed(2) + ' °C'; // Format data labels to two decimals with unit
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#barChart"), options);
    chart.render();
</script>

        <!-- Line Charts for Each Location -->
        <?php foreach ($locationData as $locationName => $data): ?>
            <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Heat Index Trends for <?= htmlspecialchars($locationName); ?></h5>
        <div class="time-frame" id="timeFrame-<?= htmlspecialchars($locationName); ?>" 
            style="background-color: rgba(255, 255, 255, 0.9); 
                   padding: 10px 15px; 
                   border-radius: 5px; 
                   box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); 
                   font-weight: bold; 
                   font-size: 14px;">
            <strong>Time Frame:</strong><br>
            <?= htmlspecialchars(date('M j, Y', strtotime($startDate))) ?> - <?= htmlspecialchars(date('M j, Y', strtotime($endDate))) ?>
        </div>
    </div>
    
    <div class="card-body">
        <div id="lineChart-<?= htmlspecialchars($locationName); ?>" style="height: 400px;"></div>
    </div>
</div>



<script>

    var selectedInterval = 'hourly'; // Change this value dynamically based on user selection

    // Set the time unit and x-axis label format based on the selected interval
    var timeUnit;
    var xAxisLabelFormat;

    switch (selectedInterval) {
        case 'hourly':
            timeUnit = 'hour';
            xAxisLabelFormat = {
                hour: 'hh:mm a', // Format for hour
            };
            break;
        case 'daily':
            timeUnit = 'day';
            xAxisLabelFormat = {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            };
            break;
        case 'weekly':
            timeUnit = 'week';
            xAxisLabelFormat = {
                week: 'MMM dd',
                month: 'long',
                year: 'numeric',
            };
            break;
        case 'monthly':
            timeUnit = 'month';
            xAxisLabelFormat = {
                month: 'long',
                year: 'numeric',
            };
            break;
        case 'yearly':
            timeUnit = 'year';
            xAxisLabelFormat = {
                year: 'numeric',
            };
            break;
        default:
            timeUnit = 'day'; // Default to daily if not recognized
            xAxisLabelFormat = {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            };
    }

    var options = {
        chart: {
            type: 'line',
            height: 400,
            zoom: {
                enabled: true
            }
        },
        series: [{
            name: 'Average Heat Index',
            data: <?= json_encode($data['avgHeatIndexes']); ?>
        }, {
            name: 'Max Heat Index',
            data: <?= json_encode($data['maxHeatIndexes']); ?>
        }],
        xaxis: {
            categories: <?= json_encode($data['timeLabels']); ?>,
            type: 'datetime',
            labels: {
                formatter: function (value) {
                    const date = new Date(value);
                    if (selectedInterval === 'hourly') {
                        return date.toLocaleString('en-US', { 
                            hour: 'numeric', 
                            minute: 'numeric', 
                            hour12: true 
                        });
                    } else if (selectedInterval === 'daily') {
                        return date.toLocaleString('en-US', { 
                            day: 'numeric', 
                            month: 'long', 
                            year: 'numeric' 
                        });
                    } else if (selectedInterval === 'weekly') {
                        return date.toLocaleString('en-US', { 
                            month: 'long', 
                            year: 'numeric' 
                        });
                    } else if (selectedInterval === 'monthly') {
                        return date.toLocaleString('en-US', { 
                            month: 'long', 
                            year: 'numeric' 
                        });
                    } else if (selectedInterval === 'yearly') {
                        return date.toLocaleString('en-US', { 
                            year: 'numeric' 
                        });
                    }
                },
                rotate: -15, // Rotate labels for better visibility
                style: {
                    fontSize: '12px' // Increase font size for readability
                }
            }
        },
        yaxis: {
            title: {
                text: 'Heat Index (°C)', // Include unit in the title
                style: {
                    fontSize: '14px', // Increase font size for the title
                    fontWeight: 'bold'
                }
            },
            min: 0,
            max: Math.max(...<?= json_encode($data['avgHeatIndexes']); ?>, ...<?= json_encode($data['maxHeatIndexes']); ?>) + 10, // Set max based on data
            tickAmount: 5, // Control the number of ticks on the y-axis
            labels: {
                formatter: function (value) {
                    return value.toFixed(2); // Format to two decimal places
                },
                style: {
                    fontSize: '12px' // Increase font size for readability
                }
            }
        },
        tooltip: {
            shared: false,
            intersect: false,
            x: {
                formatter: function (value) {
                    const date = new Date(value);
                    return date.toLocaleString('en-US', { 
                        day: 'numeric', 
                        month: 'long', 
                        year: 'numeric', 
                        hour: 'numeric', 
                        minute: 'numeric', 
                        hour12: true 
                    });
                }
            },
            y: {
                formatter: function (value) {
                    return value.toFixed(2) + ' °C'; // Show data points with two decimal places and unit
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'center',
            fontSize: '14px' // Increase font size for legend
        }
    };

    var chart = new ApexCharts(document.querySelector("#lineChart-<?= htmlspecialchars($locationName); ?>"), options);
    chart.render();
</script>








        <?php endforeach; ?>

        <?php $conn->close(); ?>
    </div>
</main>



    <!-- Include Bootstrap's tooltip initialization -->
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>

    <!-- ======= Footer ======= -->
    <?php include '../components/footer.php'; ?>

    <?php include '../components/scripts.php'; ?>

</body>
</html>
