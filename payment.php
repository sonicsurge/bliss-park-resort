<?php
/**
 * payment.php — local demonstration stand-in for a payment page.
 *
 * OWASP A02 and A04: this site is not a payment processor. The old
 * "payment details.html" page asked for a card number, a CVV, and a
 * national ID, then showed "success" in JavaScript without saving a booking.
 * Collecting those values would be card data we are not allowed to store,
 * and a national ID we do not need.
 *
 * This page does not have those fields. Confirming the form only changes
 * this user's booking from pending_payment to booked. If a client posts
 * card_number or cvv anyway, the script never reads those keys, and audit()
 * would drop them if they were passed into a log context.
 *
 * The booking id in the URL is an identifier, not permission. Permission is
 * "this row's user_id equals the session" (or the viewer is staff). Using the
 * id alone would be an insecure direct object reference (A01). A guest who
 * changes the number sees the same "not found" message as a missing booking,
 * so they cannot tell which numbers exist.
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';
require_role('guest', 'admin', 'receptionist');

$bookingId = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post_and_csrf();
    $bookingId = posted_int('booking_id');
}

if ($bookingId === false || $bookingId === null || $bookingId < 1) {
    fail(400, 'No booking was selected.');
}

$stmt = db()->prepare(
    'SELECT b.booking_id, b.user_id, b.status, b.check_in, b.check_out,
            r.room_number, r.room_type, r.price
     FROM bookings b
     JOIN rooms r ON r.room_id = b.room_id
     WHERE b.booking_id = ?'
);
$stmt->bind_param('i', $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

$role = (string) ($_SESSION['role'] ?? '');
$ownsIt = is_array($booking) && (int) $booking['user_id'] === current_user_id();
$staff = $role === 'admin' || $role === 'receptionist';

if (!is_array($booking) || (!$ownsIt && !$staff)) {
    fail(404, 'That booking was not found.');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if ($booking['status'] !== 'pending_payment') {
        flash('This booking is not waiting for payment.', 'error');
        redirect('my_bookings.php');
    }

    $update = db()->prepare(
        "UPDATE bookings SET status = 'booked'
         WHERE booking_id = ? AND status = 'pending_payment' AND user_id = ?"
    );
    // Staff may confirm, but the row still has to belong to its guest.
    // We bind the booking's own user_id, not a user id from the form.
    $owner = (int) $booking['user_id'];
    $update->bind_param('ii', $bookingId, $owner);
    $update->execute();
    $changed = $update->affected_rows === 1;
    $update->close();

    audit('payment_simulated', ['booking_id' => $bookingId, 'changed' => $changed]);
    flash($changed ? 'Payment simulation recorded. The room is booked. No card data was sent.' : 'The booking could not be confirmed.', $changed ? 'success' : 'error');
    redirect('my_bookings.php');
}

page_head('Simulated payment');
require __DIR__ . '/includes/public_nav.php';
?>
<main class="panel">
    <h1>Simulated payment</h1>
    <p class="form-error">Do not type a real card number, CVV, PIN, or national ID. This demonstration does not collect or process payment data.</p>
    <p>Room <?php echo h((string) $booking['room_number']); ?> — <?php echo h((string) $booking['room_type']); ?></p>
    <p><?php echo h((string) $booking['check_in']); ?> to <?php echo h((string) $booking['check_out']); ?></p>
    <p>Price stored for this room: KSH <?php echo h((string) $booking['price']); ?> per night.</p>
    <p>Status: <?php echo h((string) $booking['status']); ?></p>
    <?php if ($booking['status'] === 'pending_payment'): ?>
        <form method="post" action="payment.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="booking_id" value="<?php echo h((string) $booking['booking_id']); ?>">
            <button type="submit">Confirm simulated payment</button>
        </form>
    <?php else: ?>
        <p>This booking is already <?php echo h((string) $booking['status']); ?>.</p>
    <?php endif; ?>
</main>
<?php page_end(); ?>
