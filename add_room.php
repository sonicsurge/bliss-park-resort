<?php
/**
 * add_room.php — admin inserts one room.
 * The three values are validated, then bound to placeholders (A03).
 * A duplicate room number hits UNIQUE in schema.sql and is reported
 * without showing the database error text.
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';
require_role('admin');

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post_and_csrf();

    $number = posted_string('room_number');
    $type = posted_string('room_type');
    $price = posted_int('price');

    if (preg_match('/^[A-Za-z0-9-]{1,10}$/', $number) !== 1) {
        $error = 'Room number must be 1 to 10 letters, digits, or hyphens.';
    } elseif (preg_match('/^[\p{L}0-9 ]{2,40}$/u', $type) !== 1) {
        $error = 'Room type must be 2 to 40 letters, digits, or spaces.';
    } elseif ($price === null || $price < 1 || $price > 1000000) {
        $error = 'Price must be a whole number of Kenyan shillings from 1 to 1000000.';
    } else {
        $stmt = db()->prepare('INSERT INTO rooms (room_number, room_type, price) VALUES (?, ?, ?)');
        // s, s, i — two strings and one integer. The integer binding rejects SQL text.
        $stmt->bind_param('ssi', $number, $type, $price);
        try {
            $stmt->execute();
            $stmt->close();
            audit('room_added', ['room_number' => $number]);
            flash('Room ' . $number . ' was added.');
            redirect('view_rooms.php');
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            if ((int) $e->getCode() === 1062) {
                $error = 'That room number already exists.';
            } else {
                throw $e;
            }
        }
    }
}

page_head('Add room');
require __DIR__ . '/includes/admin_nav.php';
?>
<main class="panel">
    <h1>Add room</h1>
    <?php render_flash(); ?>
    <?php if ($error !== ''): ?><p class="form-error"><?php echo h($error); ?></p><?php endif; ?>
    <form method="post" action="add_room.php">
        <?php csrf_field(); ?>
        <label for="room_number">Room number</label>
        <input id="room_number" name="room_number" required maxlength="10" value="<?php echo h(posted_string('room_number')); ?>">
        <label for="room_type">Room type</label>
        <input id="room_type" name="room_type" required maxlength="40" value="<?php echo h(posted_string('room_type')); ?>">
        <label for="price">Price per night (KSH)</label>
        <input id="price" name="price" type="number" required min="1" max="1000000" step="1">
        <p><button type="submit">Add room</button></p>
    </form>
</main>
<?php page_end(); ?>
