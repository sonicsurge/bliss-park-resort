<?php
/**
 * services.php - public list of resort services.
 * All cards are fixed server-owned content. No visitor-controlled string is
 * passed to the browser as HTML, so this page does not create an XSS sink.
 */
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';

page_head('Services at Bliss Park', ['CSS/marketing.css']);
require __DIR__ . '/includes/public_nav.php';

/* The array is application data, not request data. h() remains the output rule. */
$services = [
    ['Laundry and dry cleaning', 'Professional garment care with prompt collection and return.', 'IMAGES/laundry.jpg'],
    ['Housekeeping', 'A clean, comfortable room prepared with care throughout your stay.', 'IMAGES/housekeeping.jpg'],
    ['In room dining', 'Meals and refreshments delivered to your room at any time.', 'IMAGES/in-room-dining.jpg'],
    ['Spa and wellness', 'A quiet place for restorative treatments and relaxation.', 'IMAGES/spa.jpg'],
    ['Concierge assistance', 'Practical help with local tips, transport, and reservations.', 'IMAGES/concierge.jpg'],
    ['Airport transfers', 'Private transfer arrangements for a smoother arrival or departure.', 'IMAGES/transfer.jpg'],
];
?>
<main class="marketing-main">
    <div class="section-heading">
        <h1>Guest services and amenities</h1>
        <p>Comfort is more than a room. These services support an easy and well-organised stay.</p>
    </div>
    <section class="service-grid" aria-label="Resort services">
        <?php foreach ($services as [$title, $description, $image]): ?>
            <article class="service-card">
                <img src="<?php echo h($image); ?>" alt="">
                <h2><?php echo h($title); ?></h2>
                <p><?php echo h($description); ?></p>
            </article>
        <?php endforeach; ?>
    </section>
</main>
<?php require __DIR__ . '/includes/public_footer.php'; page_end(); ?>
