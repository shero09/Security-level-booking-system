# Security-Level Sports Booking System

A PHP/MySQL sports events booking system built with **two-level security question
verification** as a gate before payment — matching the design in *"Mitigating Security
Threats with Security Level Techniques for Sports Events Booking System"*
(Chapters 4–6: Requirement Analysis, System Design, System Implementation).

This has been fully built and tested end-to-end (registration → login → booking →
security level 1 → security level 2 → payment → confirmation, plus the admin panel)
against PHP 8.3 and MariaDB.

## Features

- **Customer registration** with two distinct security questions/answers (hashed, never stored in plain text)
- **Login** with bcrypt password hashing
- **Sports/venue browsing** pulled from the database
- **Booking flow** (date, time, venue, postcode)
- **Two-level security question verification**, required before any payment is possible
- **Account lockout**: 3 failed attempts on either level locks the account for 15 minutes and forces re-login (brute-force protection)
- **Simulated payment** — only the card's last 4 digits + a transaction reference are stored; the full card number/CVV are never persisted (see "Security notes" below)
- **Customer ↔ Admin messaging** thread
- **Admin dashboard**: view all bookings, delete a booking on customer request
- CSRF tokens on every form, PDO prepared statements everywhere (no SQL injection surface), output escaping on every echoed value, hardened session cookies

## Requirements

- PHP 8.1+ with the following extensions: `pdo_mysql`, `mbstring`
- MySQL 5.7+ or MariaDB 10.4+
- A local web server (Apache/Nginx via XAMPP/WAMP/MAMP, or PHP's built-in server for quick testing)

## Setup

1. **Create the database**
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
   This creates the `sports_booking_system` database, all tables, 5 sample sports/venues,
   and a default admin account.

2. **Configure the DB connection**
   Edit `includes/db.php` and set your MySQL username/password:
   ```php
   $DB_USER = 'root';
   $DB_PASS = 'your_password';
   ```

3. **Run it**
   - **Quick test** (no Apache needed):
     ```bash
     php -S localhost:8000
     ```
     then open `http://localhost:8000`
   - **XAMPP/WAMP**: copy this folder into `htdocs`/`www` and open `http://localhost/sports-booking-system`

4. **Default admin login**
   - URL: `/admin/login.php`
   - Username: `admin`
   - Password: `Admin@12345`
   - **Change this password before any real deployment** — update the hash in the `admin` table using `password_hash('newpassword', PASSWORD_BCRYPT)`.

5. **Try the customer flow**
   - Register a new account (you'll set two security questions/answers).
   - Log in, pick a sport, fill in the booking form.
   - Answer Security Level 1, then Security Level 2.
   - Enter any 16-digit number for the demo card (e.g. `4242 4242 4242 4242`), any future expiry, any 3-digit CVV — this is a **simulated** payment, not a real gateway.
   - See your confirmation, then try Messages to contact the admin.

## Project structure

```
sports-booking-system/
├── index.php                 Home page
├── register.php              Registration with 2 security questions
├── login.php                 Customer login + lockout handling
├── logout.php
├── sports.php                Sport/venue listing
├── booking.php                Booking form (stages booking in session)
├── security_level1.php        Security question 1 gate
├── security_level2.php        Security question 2 gate
├── payment.php                Simulated payment, commits booking + payment
├── confirmation.php
├── messages.php                Customer messaging
├── admin/
│   ├── login.php
│   ├── dashboard.php           View & delete bookings
│   └── messages.php            Reply to customer messages
├── includes/
│   ├── db.php                  PDO connection
│   ├── functions.php           CSRF, session, auth guards, lockout logic
│   ├── header.php / footer.php
├── css/style.css
└── sql/schema.sql
```

## Security notes (for your dissertation write-up / viva)

A few deliberate choices worth mentioning if you're using this alongside the report:

- **Hashing vs. encryption for security answers**: the original design mentions
  "encrypting" security answers. This build uses one-way **bcrypt hashing**
  (`password_hash`/`password_verify`) instead of reversible encryption — the
  system only ever needs to *verify* an answer, never read it back, so hashing
  is the stronger choice (nobody, including an attacker with full DB access,
  can recover the original answer).
- **Payment data**: full card numbers and CVVs are **never written to the
  database** — only the last 4 digits and a transaction reference are kept, and
  the full number is discarded from memory immediately after the (simulated)
  charge. This mirrors how a real integration with a PCI-DSS compliant gateway
  (e.g. Stripe, PayPal) would work: raw card data lands with the gateway, not
  your server.
- **CSRF tokens** are checked on every state-changing POST request.
- **SQL injection**: every query uses PDO prepared statements with bound
  parameters — no user input is ever concatenated into SQL.
- **Session fixation**: the session ID is regenerated on login (both customer
  and admin) to prevent session fixation attacks.
- **Account lockout**: 3 incorrect attempts at either security level (or at
  login) locks the account for 15 minutes, mitigating brute-force guessing —
  this extends the dissertation's "number of attempts" requirement (Ch.4) into
  a concrete, testable control.

## Suggested extensions (if you want to go further for the write-up)

- Rate-limit by IP address in addition to per-account lockout
- Add email verification on registration
- Replace the simulated payment with a real Stripe test-mode integration
- Add HTTPS enforcement + `session.cookie_secure = 1` for production
