<?php
/**
 * login.php — show the sign-in form (GET) and check the password (POST).
 *
 * The old login.html posted here with no CSRF token, and the old script said
 * "User not found" or "Incorrect password". Those two sentences tell an
 * attacker which usernames exist (account enumeration, A07). Both failures
 * now produce the same sentence.
 *
 * The form is printed by PHP so the hidden CSRF token can be different for
 * each session. A static HTML file cannot do that.
 */

require_once __DIR__ . '/includes/security.php';

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // A login changes who the session belongs to, so it needs the token.
    require_post_and_csrf();

    if (login_is_locked()) {
        $error = 'Too many attempts. Wait 15 minutes and try again.';
        audit('login_locked', []);
    } else {
        $username = posted_string('username');
        $password = posted_string('password');

        // Reject a shape we will never store. Still use the same public error
        // so the message does not become an oracle for "valid username format".
        $shapeOk = preg_match('/^[A-Za-z0-9_]{3,30}$/', $username) === 1
            && $password !== ''
            && strlen($password) <= 72;

        $user = null;
        if ($shapeOk) {
            $stmt = db()->prepare(
                'SELECT user_id, password, role FROM users WHERE username = ? LIMIT 1'
            );
            // "s" means the username is bound as a string, not pasted into the SQL.
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc() ?: null;
            $stmt->close();
        }

        // Always run password_verify. The dummy hash is used when no row exists.
        $hash = is_array($user) ? (string) $user['password'] : DUMMY_PASSWORD_HASH;
        $passwordOk = $shapeOk && password_verify($password, $hash);

        if (!$passwordOk || !is_array($user)) {
            login_register_failure();
            audit('login_failed', ['username' => $username]);
            $error = 'Invalid username or password.';
        } else {
            $role = (string) $user['role'];
            if (!in_array($role, ['admin', 'receptionist', 'guest'], true)) {
                audit('login_rejected_role', ['user_id' => (int) $user['user_id']]);
                $error = 'Invalid username or password.';
            } else {
                // New session id before we store the user. Blocks session fixation.
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['role'] = $role;
                $_SESSION['username'] = $username;
                $_SESSION['created_at'] = time();
                $_SESSION['last_activity'] = time();
                // The anonymous CSRF token must not survive the privilege change.
                unset($_SESSION['csrf_token']);

                // If PHP's preferred algorithm has changed, upgrade the stored hash
                // now, while we still have the password in memory. We do not log it.
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    $upgraded = password_hash($password, PASSWORD_DEFAULT);
                    $update = db()->prepare('UPDATE users SET password = ? WHERE user_id = ?');
                    $id = (int) $user['user_id'];
                    $update->bind_param('si', $upgraded, $id);
                    $update->execute();
                    $update->close();
                }

                login_clear_failures();
                audit('login_success', ['role' => $role]);

                // Destinations are fixed. We never redirect to a URL from the query string.
                if ($role === 'admin') {
                    redirect('admin_dashboard.php');
                }
                if ($role === 'receptionist') {
                    redirect('receptionist_dashboard.php');
                }
                redirect('my_bookings.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in — Bliss Park</title>
    <link rel="stylesheet" href="CSS/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Sign In</h2>
        <?php render_flash(); ?>
        <?php if ($error !== ''): ?>
            <p class="form-error"><?php echo h($error); ?></p>
        <?php endif; ?>
        <!-- autocomplete helps a password manager fill the real fields. -->
        <!-- The token field sits outside .input-group so it does not break the CSS sibling labels. -->
        <form method="post" action="login.php" autocomplete="on">
            <?php csrf_field(); ?>
            <div class="input-group">
                <!-- Self-hosted text icon: no unpinned icon CDN dependency (A06/A08). -->
                <span class="icon" aria-hidden="true">&#9679;</span>
                <input type="text" id="username" name="username" required maxlength="30" autocomplete="username">
                <label for="username">Username</label>
            </div>
            <div class="input-group">
                <span class="icon" aria-hidden="true">&#9679;</span>
                <input type="password" id="password" name="password" required maxlength="72" autocomplete="current-password">
                <label for="password">Password</label>
            </div>
            <button type="submit" class="submit-button">Login</button>
            <p>Not registered? <a href="register.php">Sign up here</a></p>
        </form>
    </div>
</body>
</html>
