<?php
/**
 * homepage.php - public landing page.
 *
 * This replaces the old static page. Loading security.php means this response
 * receives the same UTF-8, anti-framing, CSP, and no-cache headers as every
 * other application page. The navigation is a PHP include, not a fetch() plus
 * innerHTML operation, which removes an unnecessary client-side injection path.
 */
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';

page_head('Bliss Park Resort', ['CSS/marketing.css']);
require __DIR__ . '/includes/public_nav.php';
?>
<main class="marketing-main">
    <section class="hero" aria-labelledby="welcome-title">
        <div class="hero-copy">
            <h1 id="welcome-title">A calm, secure stay in Naivasha.</h1>
            <p>Bliss Park Resort combines comfortable rooms, thoughtful service, and a booking journey designed to protect guest information.</p>
            <a class="primary-link" href="booking.php">Choose a room</a>
        </div>
        <img class="hero-image" src="IMAGES/resort.jpg" alt="Exterior view of Bliss Park Resort">
    </section>

    <section aria-labelledby="highlights-title">
        <div class="section-heading">
            <h2 id="highlights-title">Designed for comfort</h2>
            <p>Every stay starts with transparent room choices and ends with a human reception team ready to help.</p>
        </div>
        <div class="feature-grid">
            <article class="feature-card"><h2>Thoughtful rooms</h2><p>Choose from six room types, each with a clear nightly price set by the resort rather than by the browser.</p></article>
            <article class="feature-card"><h2>Guest services</h2><p>Enjoy housekeeping, laundry, dining, wellness, concierge help, and airport transfers.</p></article>
            <article class="feature-card"><h2>Secure booking</h2><p>Bookings use server-side validation, account ownership checks, and a safe payment confirmation flow.</p></article>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/public_footer.php'; page_end(); ?>
