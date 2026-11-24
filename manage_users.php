<?php
/**
 * manage_users.php — admin list of accounts.
 *
 * The password hash is deliberately not in the SELECT. A hash shown on screen
 * can be copied and cracked offline (A02). The page also does not need it.
 * Delete is a POST form, same pattern as the rooms list.
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';
require_role('admin');

$stmt = db()->prepare(
    'SELECT user_id, full_name, username, email, role FROM users ORDER BY user_id'
);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

page_head('Manage users');
require __DIR__ . '/includes/admin_nav.php';
?>
<main class="panel">
    <h1>Users</h1>
    <?php render_flash(); ?>
    <table>
        <tr>
            <th>Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Action</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo h((string) $user['full_name']); ?></td>
                <td><?php echo h((string) $user['username']); ?></td>
                <td><?php echo h((string) $user['email']); ?></td>
                <td><?php echo h((string) $user['role']); ?></td>
                <td>
                    <?php if ((int) $user['user_id'] === current_user_id()): ?>
                        <span class="muted">Signed-in account</span>
                    <?php else: ?>
                        <form method="post" action="delete_users.php" class="inline">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="user_id" value="<?php echo h((string) $user['user_id']); ?>">
                            <button type="submit">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</main>
<?php page_end(); ?>
