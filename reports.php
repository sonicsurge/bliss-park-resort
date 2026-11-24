<?php
/**
 * reports.php — counts for the admin.
 * The SQL has no user input, so there is nothing to inject. The numbers are
 * still printed with h() so a future change that selects text stays safe.
 * A receptionist does not get this page (A01). Counts are not a secret from
 * staff, but the unit's rule is that each page names the roles that may see it.
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';
require_role('admin');

function count_rows(string $sql): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    return $total;
}

$rooms = count_rows('SELECT COUNT(*) AS total FROM rooms');
$bookings = count_rows('SELECT COUNT(*) AS total FROM bookings');
$guests = count_rows("SELECT COUNT(*) AS total FROM users WHERE role = 'guest'");
$inHouse = count_rows("SELECT COUNT(*) AS total FROM bookings WHERE status = 'checked_in'");

page_head('Reports');
require __DIR__ . '/includes/admin_nav.php';
?>
<main class="panel">
    <h1>Reports</h1>
    <div class="report-box">Rooms: <?php echo h((string) $rooms); ?></div>
    <div class="report-box">Bookings: <?php echo h((string) $bookings); ?></div>
    <div class="report-box">Guest accounts: <?php echo h((string) $guests); ?></div>
    <div class="report-box">Checked in now: <?php echo h((string) $inHouse); ?></div>
</main>
<?php page_end(); ?>
