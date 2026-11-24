<?php
/**
 * my_bookings.php — the signed-in user's own rows.
 *
 * WHERE user_id = ? uses the session id. A query-string user_id is not read.
 * That is what stops one guest opening another guest's stays by changing a
 * number in the address bar (IDOR, which OWASP lists under A01).
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';
require_role('guest', 'admin', 'receptionist');

$me = current_user_id();
$stmt = db()->prepare(
    'SELECT b.booking_id, r.room_number, r.room_type, b.check_in, b.check_out, b.status
     FROM bookings b
     JOIN rooms r ON r.room_id = b.room_id
     WHERE b.user_id = ?
     ORDER BY b.booking_id DESC'
);
$stmt->bind_param('i', $me);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

page_head('My bookings');
require __DIR__ . '/includes/public_nav.php';
?>
<main class="panel">
    <h1>My bookings</h1>
    <?php render_flash(); ?>
    <table>
        <tr>
            <th>Room</th>
            <th>Type</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Status</th>
        </tr>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?php echo h((string) $row['room_number']); ?></td>
                <td><?php echo h((string) $row['room_type']); ?></td>
                <td><?php echo h((string) $row['check_in']); ?></td>
                <td><?php echo h((string) $row['check_out']); ?></td>
                <td>
                    <?php echo h((string) $row['status']); ?>
                    <?php if ($row['status'] === 'pending_payment'): ?>
                        <a href="payment.php?booking_id=<?php echo h((string) $row['booking_id']); ?>">Continue</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if ($rows === []): ?><p>You have no bookings yet. <a href="booking.php">Book a room</a>.</p><?php endif; ?>
</main>
<?php page_end(); ?>
