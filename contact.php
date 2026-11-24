<?php
/**
 * contact.php — store a message from the public contact form.
 *
 * Anyone may open this page. The session still exists so the CSRF token has
 * somewhere to live. A session without user_id is not a login.
 *
 * The message is inserted with a prepared statement and length limits (A03).
 * We do not pass the message to mail() or to a shell command. Building mail
 * headers from the visitor's name is a classic header-injection bug, and
 * building a command line from it would be command injection (also A03).
 * Storing the row is the whole side effect.
 *
 * The map iframe uses a fixed Google URL written in this file. Our server
 * does not request a URL the visitor supplies, so there is no SSRF sink (A10).
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';

$error = '';
$saved = ['full_name' => '', 'email' => '', 'phone' => '', 'message' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post_and_csrf();
    $saved['full_name'] = posted_string('full_name');
    $saved['email'] = posted_string('email');
    $saved['phone'] = posted_string('phone');
    $saved['message'] = posted_string('message');

    if (preg_match('/^[\p{L} .\'-]{2,80}$/u', $saved['full_name']) !== 1) {
        $error = 'Enter your name using letters, spaces, apostrophes, or hyphens.';
    } elseif (filter_var($saved['email'], FILTER_VALIDATE_EMAIL) === false || strlen($saved['email']) > 100) {
        $error = 'Enter a valid email address.';
    } elseif (preg_match('/^\+?[0-9]{9,15}$/', $saved['phone']) !== 1) {
        $error = 'Enter a phone number of 9 to 15 digits.';
    } elseif (strlen($saved['message']) < 5 || strlen($saved['message']) > 1000) {
        $error = 'The message must be between 5 and 1000 characters.';
    } else {
        $stmt = db()->prepare(
            'INSERT INTO contact_messages (full_name, email, phone, message) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $saved['full_name'], $saved['email'], $saved['phone'], $saved['message']);
        $stmt->execute();
        $stmt->close();
        audit('contact_message', ['email' => $saved['email']]);
        flash('Message received. The resort will read it from the database.');
        redirect('contact.php');
    }
}

page_head('Contact');
require __DIR__ . '/includes/public_nav.php';
?>
<main class="panel">
    <h1>Contact us</h1>
    <?php render_flash(); ?>
    <?php if ($error !== ''): ?><p class="form-error"><?php echo h($error); ?></p><?php endif; ?>
    <form method="post" action="contact.php">
        <?php csrf_field(); ?>
        <label for="full_name">Name</label>
        <input id="full_name" name="full_name" required maxlength="80" value="<?php echo h($saved['full_name']); ?>">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required maxlength="100" value="<?php echo h($saved['email']); ?>">
        <label for="phone">Phone</label>
        <input id="phone" name="phone" type="tel" required maxlength="16" value="<?php echo h($saved['phone']); ?>">
        <label for="message">Message</label>
        <textarea id="message" name="message" rows="5" required maxlength="1000"><?php echo h($saved['message']); ?></textarea>
        <p><button type="submit">Send message</button></p>
    </form>
    <p class="muted">Bliss Park Resort, Naivasha, Kenya. blissparkresort@gmail.com. +254 46154039.</p>
    <iframe class="map-frame"
        title="Map of Naivasha, Kenya"
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d127608.68065038562!2d36.31460592797664!3d-0.7818449013697926!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1828a2a07530e381%3A0x8979a4c07936a798!2sNaivasha%2C%20Kenya!5e0!3m2!1sen!2ske!4v1703683281084!5m2!1sen!2ske"
        width="100%" height="360" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
</main>
<?php page_end(); ?>
