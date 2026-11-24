<?php
/**
 * delete_room.php — admin confirms, then deletes one room.
 *
 * Two POST steps on purpose. JavaScript confirm() is not a security control:
 * it can be skipped. The database changes only when this script receives
 * confirm=yes, a valid CSRF token, the admin role, and an integer id.
 *
 * The old query was: DELETE FROM rooms WHERE room_id=$room_id
 * The id was part of the SQL sentence, so the caller could change the sentence.
 * The question mark below is a placeholder. MySQL treats the bound value as
 * data even if it contains quotes or SQL words (A03).
 *
 * A room that still has bookings is refused by the foreign key (RESTRICT).
 * We catch that and show a normal sentence instead of the driver error.
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';
require_role('admin');
require_post_and_csrf();

$roomId = posted_int('room_id');
if ($roomId === null || $roomId < 1) {
    fail(400, 'No valid room was selected.');
}

$lookup = db()->prepare('SELECT room_number, room_type FROM rooms WHERE room_id = ?');
$lookup->bind_param('i', $roomId);
$lookup->execute();
$room = $lookup->get_result()->fetch_assoc();
$lookup->close();

if ($room === null) {
    flash('That room was not found.', 'error');
    redirect('view_rooms.php');
}

if (posted_string('confirm') !== 'yes') {
    page_head('Confirm delete');
    require __DIR__ . '/includes/admin_nav.php';
    ?>
    <main class="panel">
        <h1>Delete this room?</h1>
        <p>Room <?php echo h((string) $room['room_number']); ?> (<?php echo h((string) $room['room_type']); ?>) will be removed. This cannot be undone. A room with bookings cannot be removed.</p>
        <form method="post" action="delete_room.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="room_id" value="<?php echo h((string) $roomId); ?>">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit">Yes, delete the room</button>
        </form>
        <p><a href="view_rooms.php">Cancel</a></p>
    </main>
    <?php
    page_end();
    exit;
}

$delete = db()->prepare('DELETE FROM rooms WHERE room_id = ?');
$delete->bind_param('i', $roomId);

try {
    $delete->execute();
    $removed = $delete->affected_rows === 1;
    $delete->close();
} catch (mysqli_sql_exception $e) {
    $delete->close();
    // 1451 is a foreign-key restriction. The room is still referenced.
    if ((int) $e->getCode() === 1451) {
        flash('That room still has bookings, so it was not deleted.', 'error');
        redirect('view_rooms.php');
    }
    throw $e;
}

audit('room_deleted', ['room_id' => $roomId, 'removed' => $removed]);
flash($removed ? 'Room deleted.' : 'That room was not found.', $removed ? 'success' : 'error');
redirect('view_rooms.php');
