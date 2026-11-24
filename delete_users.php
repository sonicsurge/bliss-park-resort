<?php
/**
 * delete_users.php — admin confirms, then deletes one account.
 *
 * Guards, in order:
 *   1. require_role('admin')          — a guest cannot open this URL (A01)
 *   2. require_post_and_csrf()        — not a GET, and the token must match
 *   3. posted_int()                   — the id has to be a whole number (A03)
 *   4. not the signed-in admin        — do not lock yourself out
 *   5. not the last admin             — the site must keep one administrator
 *   6. prepared DELETE                — the id is data, not SQL
 *   7. foreign key                    — a guest with bookings is not removed
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';
require_role('admin');
require_post_and_csrf();

$userId = posted_int('user_id');
if ($userId === null || $userId < 1) {
    fail(400, 'No valid user was selected.');
}

if ($userId === current_user_id()) {
    flash('You cannot delete the account you are signed in with.', 'error');
    redirect('manage_users.php');
}

$lookup = db()->prepare('SELECT full_name, username, role FROM users WHERE user_id = ?');
$lookup->bind_param('i', $userId);
$lookup->execute();
$user = $lookup->get_result()->fetch_assoc();
$lookup->close();

if ($user === null) {
    flash('That user was not found.', 'error');
    redirect('manage_users.php');
}

if ($user['role'] === 'admin') {
    $count = db()->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
    $admins = (int) $count->fetch_assoc()['total'];
    $count->free();
    if ($admins <= 1) {
        flash('The last administrator cannot be deleted.', 'error');
        redirect('manage_users.php');
    }
}

if (posted_string('confirm') !== 'yes') {
    page_head('Confirm delete');
    require __DIR__ . '/includes/admin_nav.php';
    ?>
    <main class="panel">
        <h1>Delete this user?</h1>
        <p><?php echo h((string) $user['full_name']); ?> (<?php echo h((string) $user['username']); ?>, <?php echo h((string) $user['role']); ?>) will be removed. An account that still has bookings cannot be removed.</p>
        <form method="post" action="delete_users.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="user_id" value="<?php echo h((string) $userId); ?>">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit">Yes, delete the user</button>
        </form>
        <p><a href="manage_users.php">Cancel</a></p>
    </main>
    <?php
    page_end();
    exit;
}

$delete = db()->prepare('DELETE FROM users WHERE user_id = ? AND user_id <> ?');
$me = current_user_id();
// The second placeholder repeats the "not me" rule inside SQL, in case the
// session check above is ever removed by mistake.
$delete->bind_param('ii', $userId, $me);

try {
    $delete->execute();
    $removed = $delete->affected_rows === 1;
    $delete->close();
} catch (mysqli_sql_exception $e) {
    $delete->close();
    if ((int) $e->getCode() === 1451) {
        flash('That account still has bookings, so it was not deleted.', 'error');
        redirect('manage_users.php');
    }
    throw $e;
}

audit('user_deleted', ['target' => $userId, 'removed' => $removed]);
flash($removed ? 'User deleted.' : 'That user was not deleted.', $removed ? 'success' : 'error');
redirect('manage_users.php');
