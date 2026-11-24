<?php
/**
 * admin_dashboard.php — home page for the admin role only.
 * require_role('admin') is the access-control gate (A01). A receptionist
 * who types this URL is rejected even if they are signed in.
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';
require_role('admin');

page_head('Admin dashboard');
require __DIR__ . '/includes/admin_nav.php';
?>
<main class="panel">
    <h1>Admin dashboard</h1>
    <?php render_flash(); ?>
    <p class="muted">Signed in as <?php echo h((string) ($_SESSION['username'] ?? 'admin')); ?>.</p>
    <div class="stack">
        <a class="btn-logout" href="manage_users.php">Manage users</a>
        <a class="btn-logout" href="add_room.php">Add room</a>
        <a class="btn-logout" href="view_rooms.php">View and delete rooms</a>
        <a class="btn-logout" href="view_bookings.php">View bookings</a>
        <a class="btn-logout" href="reports.php">Reports</a>
        <a class="btn-logout" href="create_booking.php">Create a desk booking</a>
        <a class="btn-logout" href="check_in_out.php">Check in and check out</a>
    </div>
</main>
<?php page_end(); ?>
