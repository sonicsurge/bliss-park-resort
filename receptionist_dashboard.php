<?php
/**
 * receptionist_dashboard.php — front-desk home.
 * The old page printed $_GET['message'] straight into the HTML. That is
 * reflected XSS (A03): a link could carry a script in the query string.
 * Messages now come from flash(), which escapes them, and this page does
 * not read a message from the query string at all.
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';
require_role('receptionist');

page_head('Reception');
require __DIR__ . '/includes/admin_nav.php';
?>
<main class="panel">
    <h1>Reception</h1>
    <?php render_flash(); ?>
    <p class="muted">Signed in as <?php echo h((string) ($_SESSION['username'] ?? '')); ?>.</p>
    <div class="stack">
        <a class="btn-logout" href="create_booking.php">New booking</a>
        <a class="btn-logout" href="view_bookings.php">View bookings</a>
        <a class="btn-logout" href="check_in_out.php">Check in and check out</a>
    </div>
</main>
<?php page_end(); ?>
