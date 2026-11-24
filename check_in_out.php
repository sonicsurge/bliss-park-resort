<?php
/**
 * check_in_out.php — move a booking along a fixed path.
 *
 * booked -> checked_in -> checked_out
 *
 * The browser submits an action name and a booking id. Both are checked.
 * The UPDATE itself requires the current status, so a repeated request or a
 * forged "checked_out" on a stay that is still only booked changes nothing.
 * That is the state machine for A04. CSRF and the staff role are A01/A07.
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';
require_role('admin', 'receptionist');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post_and_csrf();

    $bookingId = posted_int('booking_id');
    $action = posted_string('action');
    $from = null;
    $to = null;

    if ($action === 'check_in') {
        $from = 'booked';
        $to = 'checked_in';
    } elseif ($action === 'check_out') {
        $from = 'checked_in';
        $to = 'checked_out';
    }

    if ($bookingId === null || $bookingId < 1 || $from === null || $to === null) {
        flash('That check-in action was not recognised.', 'error');
        redirect('check_in_out.php');
    }

    $stmt = db()->prepare(
        'UPDATE bookings SET status = ? WHERE booking_id = ? AND status = ?'
    );
    $stmt->bind_param('sis', $to, $bookingId, $from);
    $stmt->execute();
    $changed = $stmt->affected_rows === 1;
    $stmt->close();

    audit('booking_status', ['booking_id' => $bookingId, 'to' => $to, 'changed' => $changed]);
    flash($changed ? 'Booking updated.' : 'That booking was not in the right state.', $changed ? 'success' : 'error');
    redirect('check_in_out.php');
}

$list = db()->prepare(
    "SELECT b.booking_id, u.full_name, r.room_number, b.check_in, b.check_out, b.status
     FROM bookings b
     JOIN users u ON u.user_id = b.user_id
     JOIN rooms r ON r.room_id = b.room_id
     WHERE b.status IN ('booked', 'checked_in')
     ORDER BY b.check_in"
);
$list->execute();
$rows = $list->get_result()->fetch_all(MYSQLI_ASSOC);
$list->close();

page_head('Check in and check out');
require __DIR__ . '/includes/admin_nav.php';
?>
<main class="panel">
    <h1>Check in and check out</h1>
    <?php render_flash(); ?>
    <table>
        <tr>
            <th>Guest</th>
            <th>Room</th>
            <th>Dates</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?php echo h((string) $row['full_name']); ?></td>
                <td><?php echo h((string) $row['room_number']); ?></td>
                <td><?php echo h((string) $row['check_in'] . ' to ' . $row['check_out']); ?></td>
                <td><?php echo h((string) $row['status']); ?></td>
                <td>
                    <form method="post" action="check_in_out.php" class="inline">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="booking_id" value="<?php echo h((string) $row['booking_id']); ?>">
                        <?php if ($row['status'] === 'booked'): ?>
                            <input type="hidden" name="action" value="check_in">
                            <button type="submit">Check in</button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="check_out">
                            <button type="submit">Check out</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if ($rows === []): ?><p>No guests are due to check in or out.</p><?php endif; ?>
</main>
<?php page_end(); ?>
