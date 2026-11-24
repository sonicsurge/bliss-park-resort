<?php
/**
 * about.php - server-rendered public information page.
 * It has no form inputs, but it still includes security.php so response
 * headers do not depend on whether Apache happens to process .htaccess.
 */
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';

page_head('About Bliss Park Resort', ['CSS/marketing.css']);
require __DIR__ . '/includes/public_nav.php';
?>
<main class="marketing-main">
    <div class="section-heading">
        <h1>Welcome to Bliss Park Resort</h1>
        <p>A relaxed place to stay, work, celebrate, or explore Naivasha at your own pace.</p>
    </div>
    <section class="about-block" aria-label="About Bliss Park Resort">
        <div class="about-copy">
            <p>Bliss Park Resort offers well-appointed accommodation, dining, and guest support in a setting built for rest and connection. Our team focuses on clear communication from room selection through check-out.</p>
            <p>The guest experience is paired with a secure application design: only the server creates reservations, prices come from the room record, and guests can view only their own bookings.</p>
            <a class="primary-link" href="room.php">Explore rooms</a>
        </div>
        <img class="feature-image" src="IMAGES/hotel.jpg" alt="Hotel interior at Bliss Park Resort">
    </section>
</main>
<?php require __DIR__ . '/includes/public_footer.php'; page_end(); ?>
