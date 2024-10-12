<!-- Responsive Navbar -->
<nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">

        <!-- Profile Dropdown -->
        <li class="nav-item dropdown pe-3">
            <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                <img src="../../assets/img/school.png" alt="Profile" class="rounded-circle" style="width: 40px; height: 40px;">
                <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo ucfirst($_SESSION['admin_username']); ?></span>
            </a>

            <!-- Dropdown Menu -->
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                <li class="dropdown-header text-center">
                    <strong><?php echo ucfirst($_SESSION['admin_username']); ?></strong>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>

                <!-- Logout Button -->
                <li>
                    <a href="#" onclick="confirmLogout(event)" class="dropdown-item d-flex align-items-center">
                        <i class="bi bi-box-arrow-right me-2"></i>
                        <span>Sign out</span>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</nav>

<script>
function confirmLogout(event) {
    event.preventDefault();
    Swal.fire({
        title: 'Are you sure you want to log out?',
        text: "You will be logged out of your account.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, log me out!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '../../fetch_php/logout.php';
        }
    });
}
</script>
