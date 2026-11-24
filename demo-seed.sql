-- Synthetic local-development data for Bliss Park Resort.
-- Run after schema.sql. This script is idempotent and never deletes data.
-- The accounts below are intentionally public demo accounts: never use them
-- on an internet-facing server.

USE bliss_park;

START TRANSACTION;

-- Synthetic staff accounts.
-- admin / BlissAdmin#2026
-- receptionist / FrontDesk#2026
INSERT INTO users (full_name, username, email, phone, password, role)
SELECT 'Bliss Administrator', 'admin', 'admin@blisspark.local', '+254700000001',
       '$2b$10$1COnvHoe7Pu9UNcfjeL.uOw2V0sa2JRSu8bs6GBPwzxSwqXdtQZka', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');

INSERT INTO users (full_name, username, email, phone, password, role)
SELECT 'Front Desk', 'receptionist', 'desk@blisspark.local', '+254700000002',
       '$2b$10$9C4/SxsA9KmZEXGvGX4z0uJkqkAhx9p.Peh6K6Tt453pXoQwXTH66', 'receptionist'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'receptionist');

-- Synthetic rooms.
INSERT INTO rooms (room_number, room_type, price)
SELECT '101', 'Premier Room', 10000
WHERE NOT EXISTS (SELECT 1 FROM rooms WHERE room_number = '101');
INSERT INTO rooms (room_number, room_type, price)
SELECT '102', 'Premier Twin Room', 15000
WHERE NOT EXISTS (SELECT 1 FROM rooms WHERE room_number = '102');
INSERT INTO rooms (room_number, room_type, price)
SELECT '103', 'Premier Deluxe Room', 20000
WHERE NOT EXISTS (SELECT 1 FROM rooms WHERE room_number = '103');
INSERT INTO rooms (room_number, room_type, price)
SELECT '104', 'Premier Club Room', 9500
WHERE NOT EXISTS (SELECT 1 FROM rooms WHERE room_number = '104');
INSERT INTO rooms (room_number, room_type, price)
SELECT '105', 'Premier Club Pool View', 18000
WHERE NOT EXISTS (SELECT 1 FROM rooms WHERE room_number = '105');
INSERT INTO rooms (room_number, room_type, price)
SELECT '106', 'Premier Club Deluxe Room', 25000
WHERE NOT EXISTS (SELECT 1 FROM rooms WHERE room_number = '106');

-- Synthetic guest account: demo_guest / DemoGuest#2026.
INSERT INTO users (full_name, username, email, phone, password, role)
SELECT 'Demo Guest', 'demo_guest', 'demo.guest@blisspark.local', '+254700000101',
       '$2y$12$hsUoS6OSnTLFikYJJ6R6netUTRR2YXGi7EvnNf8Uu/gYVva.KACka', 'guest'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'demo_guest');

SELECT user_id INTO @demo_guest_id FROM users WHERE username = 'demo_guest' LIMIT 1;

-- Sample records cover pending payment, booked, and checked-in states.
INSERT INTO bookings (user_id, room_id, check_in, check_out, status)
SELECT @demo_guest_id, room_id, '2026-12-03', '2026-12-05', 'pending_payment'
FROM rooms WHERE room_number = '101'
  AND NOT EXISTS (SELECT 1 FROM bookings WHERE user_id = @demo_guest_id AND room_id = rooms.room_id
                  AND check_in = '2026-12-03' AND check_out = '2026-12-05');

INSERT INTO bookings (user_id, room_id, check_in, check_out, status)
SELECT @demo_guest_id, room_id, '2026-12-07', '2026-12-10', 'booked'
FROM rooms WHERE room_number = '102'
  AND NOT EXISTS (SELECT 1 FROM bookings WHERE user_id = @demo_guest_id AND room_id = rooms.room_id
                  AND check_in = '2026-12-07' AND check_out = '2026-12-10');

INSERT INTO bookings (user_id, room_id, check_in, check_out, status)
SELECT @demo_guest_id, room_id, '2026-11-24', '2026-11-26', 'checked_in'
FROM rooms WHERE room_number = '103'
  AND NOT EXISTS (SELECT 1 FROM bookings WHERE user_id = @demo_guest_id AND room_id = rooms.room_id
                  AND check_in = '2026-11-24' AND check_out = '2026-11-26');

COMMIT;
