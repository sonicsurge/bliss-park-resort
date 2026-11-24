<?php
/**
 * create_booking.php — staff create a stay that is already "booked".
 * The guest id is chosen from a list this page loaded. On POST we check
 * again that the id belongs to a guest. A tampered form must not be able
 * to attach a booking to an admin account, and must not set its own status.
 * Status "booked" is hard-coded in the create_booking() call (A01, A04).
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/booking_rules.php';
require_once __DIR__ . '/includes/layout.php';
require_role('admin', 'receptionist');

$error = '';
$guests = db()->prepare("SELECT user_id, full_name, username FROM users WHERE role = 'guest' ORDER BY full_name");
$guests->execute();
$guestRows = $guests->get_result()->fetch_all(MYSQLI_ASSOC);
$guests->close();

$rooms = db()->prepare('SELECT room_id, room_number, room_type, price FROM rooms ORDER BY room_number');
$rooms->execute();
$roomRows = $rooms->get_result()->fetch_all(MYSQLI_ASSOC);
$rooms->close();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post_and_csrf();

    $guestId = posted_int('user_id');
    $roomId = posted_int('room_id');
    $checkIn = posted_string('check_in');
    $checkOut = posted_string('check_out');

    $guestOk = false;
    foreach ($guestRows as $guest) {
        if ((int) $guest['user_id'] === $guestId) {
            $guestOk = true;
            break;
        }
    }

    if (!$guestOk || $guestId === null) {
        $error = 'Choose a guest account.';
    } else {
        $result = create_booking(db(), $guestId, $roomId ?? 0, $checkIn, $checkOut, 'booked');
        if ($result['ok']) {
            flash('Booking saved.');
            redirect('view_bookings.php');
        }
        $error = (string) $result['error'];
    }
}

page_head('New booking');
require __DIR__ . '/includes/admin_nav.php';
?>
<main class="panel">
    <h1>New desk booking</h1>
    <?php render_flash(); ?>
    <?php if ($error !== ''): ?><p class="form-error"><?php echo h($error); ?></p><?php endif; ?>
    <form method="post" action="create_booking.php">
        <?php csrf_field(); ?>
        <label for="user_id">Guest</label>
        <select id="user_id" name="user_id" required>
            <option value="">Choose a guest</option>
            <?php foreach ($guestRows as $guest): ?>
                <option value="<?php echo h((string) $guest['user_id']); ?>">
                    <?php echo h((string) $guest['full_name'] . ' (' . $guest['username'] . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="room_id">Room</label>
        <select id="room_id" name="room_id" required>
            <?php foreach ($roomRows as $room): ?>
                <option value="<?php echo h((string) $room['room_id']); ?>">
                    <?php echo h((string) $room['room_number'] . ' — ' . $room['room_type'] . ' — KSH ' . $room['price']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="check_in">Check-in</label>
        <input id="check_in" name="check_in" type="date" required>
        <label for="check_out">Check-out</label>
        <input id="check_out" name="check_out" type="date" required>
        <p><button type="submit">Save booking</button></p>
    </form>
    <?php if ($guestRows === []): ?>
        <p class="muted">No guest accounts yet. A guest registers themselves. Staff cannot create an admin from this form.</p>
    <?php endif; ?>
</main>
<?php page_end(); ?>
