<style>
  .toggle-sidebar-btn {
    font-size: 24px; /* Adjust size if needed */
    color: #333; /* Initial color */
    transition: color 0.3s ease, transform 0.3s ease; /* Smooth transition */
}

.toggle-sidebar-btn:hover {
    color: #007bff; /* Change color on hover (blue in this case) */
    transform: scale(1.1); /* Slightly enlarge the icon */
    cursor: pointer; /* Show pointer cursor on hover */
}

</style>

<header id="header" class="header fixed-top d-flex align-items-center">

<div class="d-flex align-items-center justify-content-between">
  <a href="dashboard.php" class="logo d-flex align-items-center">
    <img src="../../assets/img/logo.png" alt="">
    <span class="d-none d-lg-block">Heat Index Map</span>
  </a>
  <i class="bi bi-list toggle-sidebar-btn"></i>
</div><!-- End Logo -->

<!-- <div class="search-bar">
  <form class="search-form d-flex align-items-center" onsubmit="return false;">
    <input type="text" id="sidebarSearch" placeholder="Search menu" title="Enter search keyword">
    <button type="button" title="Search"><i class="bi bi-search"></i></button>
  </form>
</div> -->


<!-- End Search Bar -->
        <!-- navbar -->
        <?php include 'navbar.php'; ?>

</header><!-- End Header -->


