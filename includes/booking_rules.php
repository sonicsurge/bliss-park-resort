<?php
/**
 * booking_rules.php — business rules for creating a stay.
 *
 * Loaded by booking.php and create_booking.php after security.php.
 * OWASP A04 (Insecure Design): these rules are enforced on the server.
 * Hiding a button, or checking dates only in JavaScript, is not a control,
 * because a client can POST any dates and any room id directly.
 */

declare(strict_types=1);

/**
 * Turn an abandoned "waiting for payment" row into cancelled once it is
 * older than 30 minutes, then insert a new booking if the room is free.
 *
 * Returns ['ok' => true, 'id' => new id] or ['ok' => false, 'error' => text].
 * The text is safe to show to the user: it does not include SQL or paths.
 *
 * $status is chosen by the caller, not by the browser:
 *   pending_payment — guest still has to confirm the simulated payment page
 *   booked          — receptionist created the stay at the desk
 */
function create_booking(mysqli $conn, int $userId, int $roomId, string $checkIn, string $checkOut, string $status): array
{
    $allowed = ['pending_payment', 'booked'];
    if (!in_array($status, $allowed, true) || $userId < 1 || $roomId < 1) {
        return ['ok' => false, 'error' => 'This booking could not be saved.'];
    }

    $in = valid_date($checkIn);
    $out = valid_date($checkOut);
    if ($in === null || $out === null) {
        return ['ok' => false, 'error' => 'Enter a real check-in date and check-out date.'];
    }

    $today = new DateTimeImmutable('today');
    if ($in < $today) {
        return ['ok' => false, 'error' => 'Check-in cannot be in the past.'];
    }
    if ($out <= $in) {
        return ['ok' => false, 'error' => 'Check-out must be after check-in.'];
    }
    if ($in->diff($out)->days > 30) {
        return ['ok' => false, 'error' => 'A stay on this form can be at most 30 nights.'];
    }

    $expiry = (new DateTimeImmutable('-30 minutes'))->format('Y-m-d H:i:s');

    // The transaction makes the "is it free?" check and the insert one unit.
    // Without it, two guests can both pass the check and book the same room.
    $conn->begin_transaction();

    try {
        $expire = $conn->prepare(
            "UPDATE bookings
             SET status = 'cancelled'
             WHERE status = 'pending_payment' AND created_at < ?"
        );
        $expire->bind_param('s', $expiry);
        $expire->execute();
        $expire->close();

        // Lock the room row so the overlap check below cannot race another request.
        // The price stays in this table. This function never reads a price from POST.
        $lock = $conn->prepare('SELECT room_id FROM rooms WHERE room_id = ? FOR UPDATE');
        $lock->bind_param('i', $roomId);
        $lock->execute();
        $room = $lock->get_result()->fetch_assoc();
        $lock->close();

        if ($room === null) {
            $conn->rollback();
            return ['ok' => false, 'error' => 'That room does not exist.'];
        }

        // Overlap test: an existing stay clashes when it starts before we leave
        // AND it ends after we arrive. Price is read here and never taken from POST.
        $clash = $conn->prepare(
            "SELECT booking_id FROM bookings
             WHERE room_id = ?
               AND status IN ('pending_payment', 'booked', 'checked_in')
               AND check_in < ?
               AND check_out > ?
             LIMIT 1"
        );
        $clash->bind_param('iss', $roomId, $checkOut, $checkIn);
        $clash->execute();
        $taken = $clash->get_result()->fetch_assoc();
        $clash->close();

        if ($taken !== null) {
            $conn->rollback();
            return ['ok' => false, 'error' => 'That room is already booked for those dates.'];
        }

        $insert = $conn->prepare(
            'INSERT INTO bookings (user_id, room_id, check_in, check_out, status)
             VALUES (?, ?, ?, ?, ?)'
        );
        $insert->bind_param('iisss', $userId, $roomId, $checkIn, $checkOut, $status);
        $insert->execute();
        $newId = (int) $insert->insert_id;
        $insert->close();

        $conn->commit();
        audit('booking_created', [
            'booking_id' => $newId,
            'room_id' => $roomId,
            'status' => $status,
        ]);

        return ['ok' => true, 'id' => $newId];
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        // Re-throw so the shared handler logs the real database message
        // and the visitor still sees only the generic sentence.
        throw $e;
    }
}
