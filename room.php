<?php
/**
 * room.php - public room catalogue.
 *
 * The booking link supplies only a convenience room_type label. booking.php
 * never trusts that label for a price or a reservation: it reloads the real
 * room row and applies server-side booking rules.
 */
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/layout.php';

page_head('Rooms at Bliss Park', ['CSS/marketing.css']);
require __DIR__ . '/includes/public_nav.php';

$rooms = [
    ['Premier Room', 'KSH 10,000 per night', 'IMAGES/primier.jpg', ['Kenyan coffee or tea', 'Morning pastries', 'Seasonal fruit juice']],
    ['Premier Twin Room', 'KSH 15,000 per night', 'IMAGES/tw.jpg', ['Gourmet sandwiches', 'Local snacks and nuts', 'Fresh fruit cup']],
    ['Premier Deluxe Room', 'KSH 20,000 per night', 'IMAGES/deluxe.jpg', ['House wine or premium juice', 'Artisan chocolates', 'Seasonal fruit basket']],
    ['Premier Club Room', 'KSH 9,500 per night', 'IMAGES/club.jpg', ['Coffee or tea selection', 'Fresh croissant', 'Chilled fruit juice']],
    ['Premier Club Pool View', 'KSH 18,000 per night', 'IMAGES/poolview.jpg', ['Sparkling juice', 'Cheese and crackers', 'Nuts and dried fruits']],
    ['Premier Club Deluxe Room', 'KSH 25,000 per night', 'IMAGES/club deluxe.jpg', ['Artisanal juice blend', 'French macarons', 'Fruit platter']],
];
?>
<main class="marketing-main">
    <div class="section-heading">
        <h1>Rooms and suites</h1>
        <p>Choose the setting that fits your visit. The displayed prices are informative; the server remains the source of truth during booking.</p>
    </div>
    <section class="room-grid" aria-label="Room catalogue">
        <?php foreach ($rooms as [$type, $price, $image, $extras]): ?>
            <article class="room-card">
                <img src="<?php echo h($image); ?>" alt="<?php echo h($type); ?>">
                <h2><?php echo h($type); ?></h2>
                <p><?php echo h($price); ?></p>
                <ul>
                    <?php foreach ($extras as $extra): ?><li><?php echo h($extra); ?></li><?php endforeach; ?>
                </ul>
                <a href="booking.php?room_type=<?php echo rawurlencode($type); ?>">Book this room</a>
            </article>
        <?php endforeach; ?>
    </section>
</main>
<?php require __DIR__ . '/includes/public_footer.php'; page_end(); ?>
