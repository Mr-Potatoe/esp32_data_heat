<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">

        <?php 
        $menu_items = [
            ['link' => 'dashboard.php', 'icon' => 'bi-house', 'label' => 'Sensor Dashboard'],
            ['link' => 'map.php', 'icon' => 'bi-map', 'label' => 'Heat Index Map'],
            ['link' => 'sensor_status.php', 'icon' => 'bi-tools', 'label' => 'Sensor Status'],
            ['link' => 'view_sensors.php', 'icon' => 'bi-clock', 'label' => 'Sensor Data View' ],
            ['link' => 'history.php', 'icon' => 'bi-table', 'label' => 'Location Table Data'],
            ['link' => 'alerts.php', 'icon' => 'bi-bell', 'label' => '24-Hour Alerts'],
        ];
        
        
    

        foreach ($menu_items as $item):
            // Check if the current page matches the link
            $active_class = ($current_page == $item['link']) ? 'active' : '';
        ?>
            <li class="nav-item">
                <a href="<?php echo $item['link']; ?>" class="nav-link <?php echo $active_class; ?>">
                    <i class="<?php echo $item['icon']; ?>"></i>
                    <span><?php echo $item['label']; ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</aside>
<style>

.nav-link.active {
    background-color: #dce4ff; /* A more intense blue */
    color: #4154f1;
    font-weight: bold;
}

.nav-link:hover {

background-color: #dce4f4; /* A more intense blue */
color: #4154f1;
font-weight: bold;

}

</style>


