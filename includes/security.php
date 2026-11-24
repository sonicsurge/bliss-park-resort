<?php
/**
 * security.php — shared security library for Bliss Park Resort.
 *
 * Every page that talks to the database or to a signed-in user loads this
 * file first with require_once. The bottom of this file calls bliss_bootstrap(),
 * so the session and the response headers exist before the page does any work.
 *
 * OWASP Top 10:2021 map (short category names used in the project):
 *   A01 Broken Access Control  — require_role(), session user id, no open redirects
 *   A02 Cryptographic Failures — password_hash / password_verify, cookie flags
 *   A03 Injection              — prepared statements, h() for HTML
 *   A04 Insecure Design        — booking rules live in the booking pages; this file
 *                                supplies the shared controls those rules rely on
 *   A05 Security Misconfiguration — headers, cookies, generic errors, env config
 *   A07 Authentication Failures — session fixation defence, idle timeout, rate limit
 *   A09 Logging and Monitoring — audit() writes storage/logs/audit.log
 *
 * A06 (vulnerable components), A08 (integrity) and A10 (SSRF) are covered in
 * README-SECURITY.md because they are not one function in this file.
 */

declare(strict_types=1);

/**
 * A fake password hash used only when the username does not exist.
 * password_verify() still does a full slow check, so "no such user" and
 * "wrong password" take a similar amount of time. That stops a timing clue
 * that would tell an attacker which usernames are real (A07).
 * This hash is not a login. It does not belong to an account.
 */
const DUMMY_PASSWORD_HASH = '$2b$10$eqaQ7b60H5wjxKL3zXZsx.pMr.sJ/heH48ysoPGSARq/FS4u/8m1y';

/**
 * Read KEY=value lines from the project .env file into the process environment.
 * The database password stays in .env, which .htaccess blocks from the web.
 * It is not written in PHP source, so it is not committed as a code secret (A05).
 * A line that starts with # is a comment and is ignored.
 * If the hosting panel has already set the variable, we keep that value.
 */
function load_env(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        // Allow values wrapped in quotes in the .env file.
        $quoted = strlen($value) >= 2
            && (($value[0] === '"' && str_ends_with($value, '"'))
                || ($value[0] === "'" && str_ends_with($value, "'")));
        if ($quoted) {
            $value = substr($value, 1, -1);
        }

        // getenv() is false when the variable was never set. An empty password
        // is a real value, so we only fill gaps.
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Start a hardened session. Must run before any HTML is printed and before
 * session_start(). Each ini / cookie setting is a response to a known failure:
 *
 * use_strict_mode     — PHP will not accept a session id it did not create.
 *                       Stops an attacker from choosing the victim's id (fixation).
 * use_only_cookies    — the id is not accepted from the query string.
 * use_trans_sid       — PHP must not add the session id onto links. Ids in URLs
 *                       leak through browser history and the Referer header (A02).
 * httponly            — JavaScript cannot read the cookie, so an XSS bug cannot
 *                       simply steal document.cookie (A07, defence in depth).
 * samesite=Lax        — the browser will not send this cookie on a cross-site POST.
 *                       That blocks the usual CSRF form posted from another site.
 *                       We still check a CSRF token because Lax is not universal.
 * secure              — when BLISS_SECURE_COOKIES=1 the cookie is HTTPS-only.
 *                       Leave it 0 on http://localhost or the browser will drop
 *                       the cookie and login will look "broken".
 * lifetime 0          — cookie dies when the browser closes (a session cookie).
 * path                — cookie is only sent back to this application folder.
 * domain ''           — current host only. We do not use a leading dot, which
 *                       would share the cookie with every subdomain.
 */
function configure_session(): void
{
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');

    $secure = getenv('BLISS_SECURE_COOKIES') === '1';

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $directory = rtrim(dirname($script), '/');
    if ($directory === '' || $directory === '.') {
        $directory = '/';
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $directory,
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Send the HTTP security headers (A05). These are instructions to the browser.
 * They do not replace input validation. If this function runs after HTML has
 * been printed, header() fails, which is why pages must not echo anything
 * before require of this file.
 *
 * The CSP nonce is a one-time random value. A <script> tag is allowed only
 * when it carries that same nonce. An attacker who injects a script tag cannot
 * know the nonce, so the browser refuses to run it (A03 XSS).
 * Styles also come only from our own CSS files or the pinned Bootstrap file.
 * Keeping both script-src and style-src free of unsafe-inline shrinks the
 * damage an HTML-injection bug could cause.
 */
function send_security_headers(): void
{
    $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    $GLOBALS['csp_nonce'] = $nonce;

    header('Content-Type: text/html; charset=UTF-8');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('Cache-Control: no-store, private');
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net; "
        . "style-src 'self' https://cdn.jsdelivr.net; "
        . "font-src 'self' data:; "
        . "img-src 'self' data:; "
        . "frame-src https://www.google.com; "
        . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'"
    );
}

/** The nonce for this response. Put it on any script tag: nonce="...". */
function csp_nonce(): string
{
    return (string) ($GLOBALS['csp_nonce'] ?? '');
}

/**
 * Drop the session after 30 minutes without a request, and rotate the session
 * id every 15 minutes. Idle timeout limits how long a stolen cookie stays
 * useful on a shared computer (A07). Rotation limits how long one id lives.
 */
function enforce_session_timeout(): void
{
    $idleLimit = 30 * 60;
    $now = time();

    if (isset($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > $idleLimit) {
        $_SESSION = [];
        session_destroy();
        session_start();
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'You were signed out after 30 minutes of inactivity.',
        ];
    }

    $_SESSION['last_activity'] = $now;

    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = $now;
    } elseif (($now - (int) $_SESSION['created_at']) > (15 * 60)) {
        session_regenerate_id(true);
        $_SESSION['created_at'] = $now;
    }
}

/**
 * Escape a string for HTML text or an attribute.
 * ENT_QUOTES escapes both " and ' so the value is safe inside attributes.
 * UTF-8 must match the page charset or the escape can be bypassed (A03 XSS).
 * Use this on every value that came from the user, the database, or the query
 * string when it is printed. Do not use it on text you intentionally want the
 * browser to treat as HTML.
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Create the CSRF token once per session and reuse it until login rotates it.
 * random_bytes() is the cryptographic generator. rand() is not acceptable here
 * because its output can be guessed (A07 / A01).
 * 32 bytes become 64 hex characters. The guide's check expects that length.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Compare the token from the form with the token in the session.
 * hash_equals() compares every character in constant time. A normal === can
 * return faster when the first character is already wrong, which leaks
 * information one character at a time.
 * Missing or wrong token: stop with 403. Do not continue into the delete
 * or the insert.
 */
function csrf_check(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';

    if (!is_string($sent) || !is_string($stored) || $stored === '' || !hash_equals($stored, $sent)) {
        audit('csrf_rejected', ['page' => $_SERVER['SCRIPT_NAME'] ?? '']);
        http_response_code(403);
        exit('Invalid request. Go back, reload the page, and try again.');
    }
}

/** State-changing pages call this first. GET is not allowed to change data. */
function require_post_and_csrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('This action only accepts a form submission.');
    }

    csrf_check();
}

/**
 * Stop the page unless the session has one of the allowed roles.
 * The role was stored at login from the users table. It is not read from
 * the form or the query string. That is the access-control check (A01).
 * A visitor who fails is sent to the login page. We do not tell them
 * whether the page they wanted exists.
 */
function require_role(string ...$roles): void
{
    $role = $_SESSION['role'] ?? '';
    $userId = $_SESSION['user_id'] ?? null;

    if (!is_int($userId) && !(is_string($userId) && ctype_digit((string) $userId))) {
        redirect('login.php');
    }

    if (!is_string($role) || !in_array($role, $roles, true)) {
        audit('access_denied', ['needed' => $roles, 'had' => $role]);
        http_response_code(403);
        exit('You do not have permission to open this page.');
    }
}

/**
 * The id of the signed-in user, taken only from the session.
 * Casting to int means a corrupted session value cannot become SQL text.
 */
function current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

/**
 * Local redirect only. The path must be a simple relative URL we built
 * ourselves. Rejecting "://" and ".." stops an open redirect, where an
 * attacker sends the browser to a fake login page (A01).
 */
function redirect(string $path): void
{
    $safe = preg_match('#^[A-Za-z0-9_./?=&%+-]+$#', $path) === 1
        && !str_contains($path, '..')
        && !str_contains($path, '//');

    if (!$safe) {
        $path = 'homepage.php';
    }

    header('Location: ' . $path);
    exit;
}

/**
 * Remember a message for the next page, then the next page should redirect.
 * That pattern is Post/Redirect/Get: refreshing the result page does not
 * submit the form a second time.
 */
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/** Read and remove the flash message. Returns null when there is none. */
function get_flash(): ?array
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/** Print the flash message, escaped. Call this inside the page body. */
function render_flash(): void
{
    $flash = get_flash();
    if ($flash === null) {
        return;
    }

    $type = ($flash['type'] ?? '') === 'error' ? 'error' : 'success';
    echo '<p class="flash flash-' . $type . '" role="status">' . h((string) ($flash['message'] ?? '')) . '</p>';
}

/**
 * End the session completely. Used by logout and by a rejected login role.
 * Clearing the cookie uses the same path, Secure, and HttpOnly flags the
 * session was created with, or the browser will keep the old cookie.
 */
function logout_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();
}

/**
 * Read one text field from POST.
 * If the field is an array (username[]=x), PHP would otherwise hand the
 * page an array. String functions and SQL bindings must not receive that.
 * trim() removes accidental spaces. The caller still checks length and shape.
 */
function posted_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }

    return trim($value);
}

/**
 * Read an integer from POST. Returns null when the value is missing, an
 * array, or not a whole number. filter_var does not accept "1 OR 1=1".
 * That is the first half of stopping the old SQL injection (A03). The
 * prepared statement is the second half.
 */
function posted_int(string $key): ?int
{
    $value = $_POST[$key] ?? null;
    if ($value === null || is_array($value)) {
        return null;
    }

    $filtered = filter_var($value, FILTER_VALIDATE_INT);
    if ($filtered === false) {
        return null;
    }

    return (int) $filtered;
}

/**
 * Accept only a real calendar date in the form 2026-11-24.
 * createFromFormat would otherwise roll 31 February into March. The '!'
 * starts the clock at midnight so a timezone cannot change the day.
 */
function valid_date(string $value): ?DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    $warnings = is_array($errors) ? (int) $errors['warning_count'] : 0;
    $problems = is_array($errors) ? (int) $errors['error_count'] : 0;

    if ($date === false || $warnings > 0 || $problems > 0) {
        return null;
    }

    if ($date->format('Y-m-d') !== $value) {
        return null;
    }

    return $date;
}

/**
 * Open the database using the .env account, not a root account hard-coded
 * in the page (A05). utf8mb4 matches schema.sql. Strict SQL mode rejects
 * values that do not fit instead of silently cutting them off.
 * The visitor never sees $e->getMessage(): it can contain table names,
 * SQL, or paths (A05 and A09). The detail goes to the audit log.
 */
function db(): mysqli
{
    static $conn = null;

    if ($conn instanceof mysqli) {
        return $conn;
    }

    $host = getenv('BLISS_DB_HOST') ?: '';
    $user = getenv('BLISS_DB_USER') ?: '';
    $pass = getenv('BLISS_DB_PASS');
    $name = getenv('BLISS_DB_NAME') ?: '';
    $port = (int) (getenv('BLISS_DB_PORT') ?: 3306);

    if ($host === '' || $user === '' || $pass === false || $name === '') {
        audit('db_config_missing', []);
        fail(500, 'The application is not configured. Copy .env.example to .env and set the database account.');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = new mysqli($host, $user, $pass, $name, $port);
        $conn->set_charset('utf8mb4');
        // Kenya is UTC+3 all year. NOW() in SQL then matches the PHP clock
        // used for "today" and for the 30-minute payment hold.
        $conn->query("SET time_zone = '+03:00'");
        $conn->query(
            "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'"
        );
    } catch (mysqli_sql_exception $e) {
        // Do not put $pass in this log line.
        audit('db_connect_failed', ['error' => $e->getMessage()]);
        fail(500, 'The service is temporarily unavailable. Please try again later.');
    }

    return $conn;
}

/**
 * Append one JSON line to storage/logs/audit.log.
 * Never pass a password, a card number, a CVV, or a session token in $context.
 * Those keys are removed here as a safety net (A09 done without creating an A02 leak).
 * The web server is told, in .htaccess, not to serve the storage directory.
 */
function audit(string $event, array $context = []): void
{
    foreach (['password', 'confirmPassword', 'cvv', 'card_number', 'csrf_token', 'BLISS_DB_PASS'] as $secret) {
        unset($context[$secret]);
    }

    $dir = __DIR__ . '/../storage/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    $record = [
        'time' => date('c'),
        'event' => $event,
        'user_id' => $_SESSION['user_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'context' => $context,
    ];

    $line = json_encode($record, JSON_UNESCAPED_SLASHES);
    if ($line === false) {
        $line = '{"event":"log_encode_failed"}';
    }

    file_put_contents($dir . '/audit.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

/**
 * Stop the request with a status code and a short public sentence.
 * Pages use this for expected refusals (400, 403, 405). Unexpected crashes
 * are handled by the exception handler below, which also writes the audit log.
 */
function fail(int $status, string $publicMessage): void
{
    if (!headers_sent()) {
        http_response_code($status);
    }

    exit($publicMessage);
}

/**
 * Rate-limit file for this client. The file name is a hash of the IP so a
 * strange IP value cannot change the path (path traversal).
 * We use REMOTE_ADDR only. X-Forwarded-For is sent by the client unless the
 * web server replaces it, so trusting it would let an attacker reset the
 * counter by typing a new header (A07).
 */
function rate_limit_path(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $dir = __DIR__ . '/../storage/ratelimit';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    return $dir . '/' . hash('sha256', $ip) . '.json';
}

/** True when this client is inside a lockout window after repeated failures. */
function login_is_locked(): bool
{
    $path = rate_limit_path();
    if (!is_file($path)) {
        return false;
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        return false;
    }

    return (int) ($data['locked_until'] ?? 0) > time();
}

/**
 * Count a failed login. Five failures inside 15 minutes lock the client for
 * 15 minutes. The numbers are a design choice (A04): a person can mistype,
 * a script cannot try a whole dictionary online at full speed.
 * This does not replace password hashing. Hashing protects a stolen database.
 * The lock only slows guessing through the website.
 */
function login_register_failure(): void
{
    $path = rate_limit_path();
    $now = time();
    $window = 15 * 60;
    $data = ['failures' => 0, 'first' => $now, 'locked_until' => 0];

    if (is_file($path)) {
        $loaded = json_decode((string) file_get_contents($path), true);
        if (is_array($loaded)) {
            $data = $loaded;
        }
    }

    if ($now - (int) ($data['first'] ?? $now) > $window) {
        $data = ['failures' => 0, 'first' => $now, 'locked_until' => 0];
    }

    $data['failures'] = (int) ($data['failures'] ?? 0) + 1;
    if ($data['failures'] >= 5) {
        $data['locked_until'] = $now + $window;
    }

    file_put_contents($path, (string) json_encode($data), LOCK_EX);
}

/** A successful login clears the failure counter for this client. */
function login_clear_failures(): void
{
    $path = rate_limit_path();
    if (is_file($path)) {
        unlink($path);
    }
}

/**
 * Last-resort handler. Any exception we did not catch on purpose ends here.
 * The browser gets one generic sentence. The class and message go to the log
 * so we can investigate (A09) without briefing the attacker (A05).
 */
set_exception_handler(function (Throwable $e): void {
    audit('exception', [
        'type' => get_class($e),
        'message' => $e->getMessage(),
    ]);

    if (!headers_sent()) {
        http_response_code(500);
    }

    echo 'Something went wrong. Please try again later.';
    exit;
});

/**
 * Run on include. Order matters:
 *   1. Hide PHP warnings from the visitor even if php.ini is loose.
 *   2. Load .env before the database is opened.
 *   3. Kenya timezone because the resort is in Naivasha. "Today" for a
 *      booking uses this clock, not the server's default UTC.
 *   4. Cookie settings, then session_start(), then the idle check.
 *   5. Security headers before the page prints HTML.
 */
function bliss_bootstrap(): void
{
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    // PHP's development server may add this version banner even when the
    // production php.ini has expose_php disabled. Remove it from responses
    // here as a second layer so an attacker gets less version information.
    header_remove('X-Powered-By');

    load_env(__DIR__ . '/../.env');
    date_default_timezone_set('Africa/Nairobi');
    configure_session();
    session_start();
    enforce_session_timeout();
    send_security_headers();
}

require_once __DIR__ . '/../csrf.php';

bliss_bootstrap();
