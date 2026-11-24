-- Bliss Park Resort schema.
-- Run this as a MariaDB/MySQL administrator. Create the local application
-- account separately with a unique password; README.md gives the commands.
-- The application account should only read and write rows. It must not DROP
-- tables or read files on the server (least privilege, OWASP A05).

CREATE DATABASE IF NOT EXISTS bliss_park
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bliss_park;

-- role is an ENUM so the column itself rejects a role the application does not know.
-- The password column stores a hash, never the password the person typed.
CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(80) NOT NULL,
    username VARCHAR(30) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'receptionist', 'guest') NOT NULL DEFAULT 'guest',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;
-- price is the resort's price. Pages must not trust a price sent by the browser.
CREATE TABLE IF NOT EXISTS rooms (
    room_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_number VARCHAR(10) NOT NULL,
    room_type VARCHAR(40) NOT NULL,
    price INT UNSIGNED NOT NULL,
    PRIMARY KEY (room_id),
    UNIQUE KEY uq_rooms_number (room_number)
) ENGINE=InnoDB;

-- ON DELETE RESTRICT means a room or a guest with a booking cannot be removed
-- by accident. The application must refuse the delete instead of erasing history.
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    status ENUM('pending_payment', 'booked', 'checked_in', 'checked_out', 'cancelled')
        NOT NULL DEFAULT 'pending_payment',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (booking_id),
    KEY idx_bookings_room_dates (room_id, check_in, check_out),
    KEY idx_bookings_user (user_id),
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_room FOREIGN KEY (room_id) REFERENCES rooms (room_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Contact messages are stored. They are not turned into shell commands or SQL text.
CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(80) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    message VARCHAR(1000) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id)
) ENGINE=InnoDB;
