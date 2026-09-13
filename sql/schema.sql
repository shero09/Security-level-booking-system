-- =====================================================================
-- Security-Level Sports Booking System
-- Database schema (matches Chapter 4 & 6 of the dissertation:
-- Customer, Admin, Sports, Booking and Payment tables)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS sports_booking_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE sports_booking_system;

-- ---------------------------------------------------------------------
-- CUSTOMERS
-- Passwords AND security answers are stored as one-way hashes
-- (password_hash / BCRYPT) rather than "encrypted" reversible text.
-- This is a deliberate improvement over storing reversible ciphertext:
-- the app never needs to read the original answer back, only verify
-- it, so a salted one-way hash gives stronger protection.
-- ---------------------------------------------------------------------
CREATE TABLE customers (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    first_name              VARCHAR(50)  NOT NULL,
    last_name               VARCHAR(50)  NOT NULL,
    username                VARCHAR(50)  NOT NULL UNIQUE,
    email                   VARCHAR(100) NOT NULL UNIQUE,
    password_hash           VARCHAR(255) NOT NULL,
    security_question_1     VARCHAR(255) NOT NULL,
    security_answer_1_hash  VARCHAR(255) NOT NULL,
    security_question_2     VARCHAR(255) NOT NULL,
    security_answer_2_hash  VARCHAR(255) NOT NULL,
    postcode                VARCHAR(10)  NOT NULL,
    failed_attempts         INT NOT NULL DEFAULT 0,
    locked_until            DATETIME NULL,
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ADMIN
-- ---------------------------------------------------------------------
CREATE TABLE admin (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(50)  NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin -> username: admin / password: Admin@12345
-- (hash generated with password_hash(), change this after first login)
INSERT INTO admin (username, password_hash) VALUES
('admin', '$2b$10$UuboieDZtUE1.KS/.u1glOyO3Nx.8Kd80/8SStWUWlK.4z9V5fyPa');

-- ---------------------------------------------------------------------
-- SPORTS / VENUES
-- ---------------------------------------------------------------------
CREATE TABLE sports (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    sport_name     VARCHAR(100) NOT NULL,
    venue_name     VARCHAR(150) NOT NULL,
    venue_number   VARCHAR(20)  NOT NULL,
    venue_postcode VARCHAR(10)  NOT NULL,
    price          DECIMAL(8,2) NOT NULL,
    description    TEXT,
    image          VARCHAR(255) DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO sports (sport_name, venue_name, venue_number, venue_postcode, price, description) VALUES
('Football',   'Greenwich Sports Park',  'V-101', 'SE10 9LS', 25.00, 'Full-size 5-a-side football pitch, floodlit evenings available.'),
('Basketball', 'Riverside Arena',        'V-102', 'SE10 0AG', 20.00, 'Indoor hardwood court, includes scoreboard hire.'),
('Tennis',     'Blackheath Tennis Club', 'V-103', 'SE3 0TG',  15.00, 'Outdoor hard court, rackets available on request.'),
('Badminton',  'Meridian Leisure Centre','V-104', 'SE10 8FA', 12.00, 'Indoor court, shuttlecocks provided.'),
('Swimming',   'Waterfront Pool',        'V-105', 'SE10 0BB', 8.00,  'Olympic-length lane booking, 1-hour slot.');

-- ---------------------------------------------------------------------
-- BOOKINGS
-- ---------------------------------------------------------------------
CREATE TABLE bookings (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    sport_id        INT NOT NULL,
    booking_date    DATE NOT NULL,
    booking_time    TIME NOT NULL,
    venue_number    VARCHAR(20) NOT NULL,
    postcode        VARCHAR(10) NOT NULL,
    status          ENUM('pending_security','pending_payment','confirmed','cancelled') NOT NULL DEFAULT 'pending_security',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PAYMENTS
-- NOTE: Only a masked reference (last 4 digits) and a fake transaction
-- id are stored. Full card numbers / CVV are NEVER persisted -- in a
-- real deployment, payment must go through a PCI-DSS compliant
-- processor (Stripe, PayPal, etc.) which returns a token instead of
-- raw card data ever touching this server. This is safer than, and
-- intentionally deviates from, storing full card details.
-- ---------------------------------------------------------------------
CREATE TABLE payments (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    booking_id        INT NOT NULL,
    customer_id       INT NOT NULL,
    amount            DECIMAL(8,2) NOT NULL,
    cardholder_name   VARCHAR(100) NOT NULL,
    card_last4        CHAR(4) NOT NULL,
    transaction_ref   VARCHAR(40) NOT NULL,
    payment_status    ENUM('success','failed') NOT NULL DEFAULT 'success',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- MESSAGES  (customer <-> admin)
-- ---------------------------------------------------------------------
CREATE TABLE messages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    customer_id  INT NOT NULL,
    sender_type  ENUM('customer','admin') NOT NULL,
    message      TEXT NOT NULL,
    is_read      TINYINT(1) NOT NULL DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;
