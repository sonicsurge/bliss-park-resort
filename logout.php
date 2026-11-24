<?php
/**
 * logout.php — end the session.
 *
 * GET is refused. A logout link in an <img> or on another website would
 * otherwise sign the person out without them choosing to (logout CSRF).
 * POST plus csrf_check() means the form had to be rendered by our own site.
 */

require_once __DIR__ . '/includes/security.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Use the logout button.');
}

csrf_check();
audit('logout', []);

// Wipe the session contents, then change the id, so the old cookie is dead
// and a new empty session can carry the signed-out message to login.php.
$_SESSION = [];
session_regenerate_id(true);
flash('You have signed out.');
redirect('login.php');
