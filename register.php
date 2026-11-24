<?php
/**
 * register.php — create a guest account.
 *
 * The role is not a form field. It is hard-coded to "guest" below.
 * If a visitor adds role=admin to the POST body, posted_string('role') is
 * never read, so the extra field does nothing (A01, privilege escalation).
 *
 * Duplicate username or email returns one generic sentence. A specific
 * "that username is taken" message would let someone map the user list (A07).
 * The UNIQUE keys in schema.sql are what actually stop the duplicate if two
 * requests arrive at the same moment.
 */

require_once __DIR__ . '/includes/security.php';

$error = '';
$saved = [
    'full_name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post_and_csrf();

    $saved['full_name'] = posted_string('full_name');
    $saved['username'] = posted_string('username');
    $saved['email'] = posted_string('email');
    $saved['phone'] = posted_string('phone');
    $password = posted_string('password');
    $confirm = posted_string('confirmPassword');

    // Format errors are safe to explain: they do not reveal an existing account.
    if (preg_match('/^[\p{L} .\'-]{2,80}$/u', $saved['full_name']) !== 1) {
        $error = 'Enter a full name using letters, spaces, apostrophes, or hyphens.';
    } elseif (preg_match('/^[A-Za-z0-9_]{3,30}$/', $saved['username']) !== 1) {
        $error = 'Username must be 3 to 30 letters, digits, or underscores.';
    } elseif (filter_var($saved['email'], FILTER_VALIDATE_EMAIL) === false || strlen($saved['email']) > 100) {
        $error = 'Enter a valid email address.';
    } elseif (preg_match('/^\+?[0-9]{9,15}$/', $saved['phone']) !== 1) {
        $error = 'Enter a phone number of 9 to 15 digits.';
    } elseif ($password !== $confirm) {
        $error = 'The two passwords do not match.';
    } elseif (strlen($password) < 8 || strlen($password) > 72
        || preg_match('/[A-Za-z]/', $password) !== 1
        || preg_match('/[0-9]/', $password) !== 1) {
        // bcrypt only uses the first 72 bytes. Longer passwords are rejected
        // so two different passwords cannot collapse into one hash (A02).
        $error = 'Password must be 8 to 72 characters and include a letter and a digit.';
    } elseif (strcasecmp($password, $saved['username']) === 0) {
        $error = 'Password must not be the same as the username.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'guest';

        $stmt = db()->prepare(
            'INSERT INTO users (full_name, username, email, phone, password, role)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'ssssss',
            $saved['full_name'],
            $saved['username'],
            $saved['email'],
            $saved['phone'],
            $hash,
            $role
        );

        try {
            $stmt->execute();
            $stmt->close();
            audit('user_registered', ['username' => $saved['username']]);
            flash('Account created. Sign in with your new username.');
            redirect('login.php');
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            // 1062 is MySQL's duplicate-key error. Anything else is unexpected.
            if ((int) $e->getCode() === 1062) {
                $error = 'Could not create the account. Try a different username and email.';
            } else {
                throw $e;
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
    <title>Register — Bliss Park</title>
    <link rel="stylesheet" href="CSS/register.css">
</head>
<body>
    <div class="registration-container">
        <h2>Sign Up</h2>
        <?php if ($error !== ''): ?>
            <p class="form-error"><?php echo h($error); ?></p>
        <?php endif; ?>
        <form method="post" action="register.php" autocomplete="on">
            <?php csrf_field(); ?>
            <div class="input-group">
                <!-- Local text icon avoids loading an unpinned third-party icon sheet. -->
                <span class="icon" aria-hidden="true">&#9679;</span>
                <input type="text" id="fullName" name="full_name" required maxlength="80" autocomplete="name" value="<?php echo h($saved['full_name']); ?>">
                <label for="fullName">Full Name</label>
            </div>
            <div class="input-group">
                <span class="icon" aria-hidden="true">&#9679;</span>
                <input type="text" id="username" name="username" required maxlength="30" autocomplete="username" value="<?php echo h($saved['username']); ?>">
                <label for="username">Username</label>
            </div>
            <div class="input-group">
                <span class="icon" aria-hidden="true">&#9679;</span>
                <input type="email" id="email" name="email" required maxlength="100" autocomplete="email" value="<?php echo h($saved['email']); ?>">
                <label for="email">Email</label>
            </div>
            <div class="input-group">
                <span class="icon" aria-hidden="true">&#9679;</span>
                <input type="tel" id="phone" name="phone" required maxlength="16" autocomplete="tel" value="<?php echo h($saved['phone']); ?>">
                <label for="phone">Phone Number</label>
            </div>
            <div class="input-group">
                <span class="icon" aria-hidden="true">&#9679;</span>
                <input type="password" id="password" name="password" required maxlength="72" autocomplete="new-password">
                <label for="password">Password</label>
            </div>
            <div class="input-group">
                <span class="icon" aria-hidden="true">&#9679;</span>
                <input type="password" id="confirmPassword" name="confirmPassword" required maxlength="72" autocomplete="new-password">
                <label for="confirmPassword">Confirm Password</label>
            </div>
            <button type="submit" class="submit-button">Register</button>
            <p>Already registered? <a href="login.php">Sign in</a></p>
        </form>
    </div>
</body>
</html>
