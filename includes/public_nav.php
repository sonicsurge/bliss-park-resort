<?php
/**
 * Public navigation. Signed-in guests get My bookings and a POST logout.
 * Anonymous visitors get Login and Register instead. The branch reads the
 * session that security.php already started. It does not trust a cookie
 * value printed into the link.
 */
$signedIn = isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand" href="homepage.php">Bliss Park Resort</a>
        <div class="navbar-nav">
            <a class="nav-link" href="homepage.php">Home</a>
            <a class="nav-link" href="about.php">About</a>
            <a class="nav-link" href="room.php">Rooms</a>
            <a class="nav-link" href="services.php">Services</a>
            <a class="nav-link" href="booking.php">Book</a>
            <a class="nav-link" href="contact.php">Contact</a>
            <?php if ($signedIn): ?>
                <a class="nav-link" href="my_bookings.php">My bookings</a>
            <?php endif; ?>
        </div>
        <?php if ($signedIn): ?>
            <form method="post" action="logout.php" class="ms-auto">
                <?php csrf_field(); ?>
                <button type="submit" class="btn btn-logout">Logout</button>
            </form>
        <?php else: ?>
            <div class="ms-auto">
                <a class="btn btn-logout" href="login.php">Login</a>
                <a class="btn btn-logout" href="register.php">Register</a>
            </div>
        <?php endif; ?>
    </div>
</nav>
