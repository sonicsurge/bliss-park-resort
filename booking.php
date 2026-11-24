<?php
/**
 * booking.php — a signed-in guest books a room for themselves.
 *
 * user id comes from the session (current_user_id), never from a hidden
 * field the browser could change. The room id is checked inside
 * create_booking(), which also ignores any price the browser might add.
 * room_type in the query string only pre-selects a dropdown option, and only
 * when it exactly matches a type loaded from the database.
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/booking_rules.php';
require_once __DIR__ . '/includes/layout.php';
require_role('guest', 'admin', 'receptionist');

$error = '';
$rooms = db()->prepare('SELECT room_id, room_number, room_type, price FROM rooms ORDER BY room_type, room_number');
$rooms->execute();
$roomRows = $rooms->get_result()->fetch_all(MYSQLI_ASSOC);
$rooms->close();

$wanted = $_GET['room_type'] ?? '';
if (!is_string($wanted)) {
    $wanted = '';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post_and_csrf();
    $result = create_booking(
        db(),
        current_user_id(),
        posted_int('room_id') ?? 0,
        posted_string('check_in'),
        posted_string('check_out'),
        'pending_payment'
    );

    if ($result['ok']) {
        redirect('payment.php?booking_id=' . (int) $result['id']);
    }
    $error = (string) $result['error'];
}

page_head('Book a room');
require __DIR__ . '/includes/public_nav.php';
?>
<main class="panel">
    <h1>Book your stay</h1>
    <p class="muted">The price shown is the price stored for that room. This form does not let you send a different price.</p>
    <?php if ($error !== ''): ?><p class="form-error"><?php echo h($error); ?></p><?php endif; ?>
    <form method="post" action="booking.php">
        <?php csrf_field(); ?>
        <label for="room_id">Room</label>
        <select id="room_id" name="room_id" required>
            <?php foreach ($roomRows as $room): ?>
                <?php $selected = $wanted !== '' && $wanted === (string) $room['room_type']; ?>
                <option value="<?php echo h((string) $room['room_id']); ?>"<?php echo $selected ? ' selected' : ''; ?>>
                    <?php echo h((string) $room['room_number'] . ' — ' . $room['room_type'] . ' — KSH ' . $room['price']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="check_in">Check-in</label>
        <input id="check_in" name="check_in" type="date" required value="<?php echo h(posted_string('check_in')); ?>">
        <label for="check_out">Check-out</label>
        <input id="check_out" name="check_out" type="date" required value="<?php echo h(posted_string('check_out')); ?>">
        <p><button type="submit">Continue to simulated payment</button></p>
    </form>
</main>
<?php page_end(); ?>
