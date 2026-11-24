<?php
/**
 * view_rooms.php — list rooms and offer a delete form.
 *
 * The old page used <a href="delete_room.php?room_id=...">. A GET delete can
 * be triggered by an image tag on another site, and the id was concatenated
 * into SQL. Each row is now a POST form with a CSRF token. The id is printed
 * with h() even though it is an integer, so the habit stays consistent (A03).
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';
require_role('admin');

$stmt = db()->prepare('SELECT room_id, room_number, room_type, price FROM rooms ORDER BY room_number');
$stmt->execute();
$rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

page_head('Rooms');
require __DIR__ . '/includes/admin_nav.php';
?>
<main class="panel">
    <h1>Rooms</h1>
    <?php render_flash(); ?>
    <table>
        <tr>
            <th>Number</th>
            <th>Type</th>
            <th>Price (KSH)</th>
            <th>Action</th>
        </tr>
        <?php foreach ($rooms as $room): ?>
            <tr>
                <td><?php echo h((string) $room['room_number']); ?></td>
                <td><?php echo h((string) $room['room_type']); ?></td>
                <td><?php echo h((string) $room['price']); ?></td>
                <td>
                    <form method="post" action="delete_room.php" class="inline">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="room_id" value="<?php echo h((string) $room['room_id']); ?>">
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if ($rooms === []): ?><p>No rooms yet.</p><?php endif; ?>
</main>
<?php page_end(); ?>
