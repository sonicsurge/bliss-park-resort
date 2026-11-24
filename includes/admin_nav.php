<?php
/**
 * Staff navigation. Included only after security.php, from pages that have
 * already called require_role(). The logout control is a POST form so another
 * website cannot log the staff member out by embedding a link or an image.
 */
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="admin_dashboard.php">Bliss Park Resort</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#staffNav"
            aria-controls="staffNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="staffNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="add_room.php">Add room</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_rooms.php">Rooms</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="receptionist_dashboard.php">Dashboard</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="view_bookings.php">Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="create_booking.php">New booking</a></li>
                <li class="nav-item"><a class="nav-link" href="check_in_out.php">Check in / out</a></li>
            </ul>
            <form method="post" action="logout.php" class="d-flex">
                <?php csrf_field(); ?>
                <button type="submit" class="btn btn-logout">Logout</button>
            </form>
        </div>
    </div>
</nav>
