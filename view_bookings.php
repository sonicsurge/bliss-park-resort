<?php
/**
 * view_bookings.php — every booking, for admin and receptionist.
 *
 * A guest who opens this URL is rejected. Guests have my_bookings.php, which
 * adds WHERE user_id = the session id. Showing every row here to a guest
 * would be broken access control (A01), also called IDOR when the row is
 * chosen by a number the browser supplies. This page does not take a user id
 * from the browser at all. The SQL text is fixed; there is no concatenation.
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';
require_role('admin', 'receptionist');

$sql = 'SELECT b.booking_id, u.full_name, r.room_number, r.room_type,
               b.check_in, b.check_out, b.status
        FROM bookings b
        JOIN users u ON u.user_id = b.user_id
        JOIN rooms r ON r.room_id = b.room_id
        ORDER BY b.booking_id DESC';
$stmt = db()->prepare($sql);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

page_head('Bookings');
require __DIR__ . '/includes/admin_nav.php';
?>
<main class="panel">
    <h1>Bookings</h1>
    <?php render_flash(); ?>
    <table>
        <tr>
            <th>Guest</th>
            <th>Room</th>
            <th>Type</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Status</th>
        </tr>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?php echo h((string) $row['full_name']); ?></td>
                <td><?php echo h((string) $row['room_number']); ?></td>
                <td><?php echo h((string) $row['room_type']); ?></td>
                <td><?php echo h((string) $row['check_in']); ?></td>
                <td><?php echo h((string) $row['check_out']); ?></td>
                <td><?php echo h((string) $row['status']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if ($rows === []): ?><p>No bookings yet.</p><?php endif; ?>
</main>
<?php page_end(); ?>
