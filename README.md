# Varsity Athlete ID Card & Visitor Monitoring System

A complete PHP + MySQL system built around 5 features:

1. **Athlete Registration & ID Issuance** — centralized database, unique auto-generated Card ID (e.g. `VAR-2026-0001`) with a printable ID card (photo + QR code).
2. **Card Scanning Verification** — scan or type a Card ID to instantly check whether it's registered and active. Every attempt is logged.
3. **Identity Verification** — after a valid scan, staff compares the person against the stored athlete photo/details and confirms Match / No Match before access is granted.
4. **Visitor Access Monitoring** — once identity is verified, staff logs the visitor's name, ID presented, and purpose, tied to that athlete. Visitors can later be checked out.
5. **Access Logs & Reports** — filterable reports for card scans and visitor transactions, with summary counts, ready to print.

## Requirements
- PHP 8.0+ with the `pdo_mysql` extension
- MySQL 5.7+ / MariaDB 10.3+
- Any web server (Apache/XAMPP/WAMP) or PHP's built-in server

## Setup

1. **Create the database**
   Import `database.sql` into MySQL (via phpMyAdmin, or CLI):
   ```bash
   mysql -u root -p < database.sql
   ```
   This creates the `varsity_id_system` database, all tables, and a default admin account:
   - **Username:** `admin`
   - **Password:** `admin123`

   ⚠️ Change this password after first login (update it directly in the `users` table using `password_hash()`, or add an account-settings page).

2. **Configure the database connection**
   Edit `config/database.php` and set your MySQL host/username/password if different from defaults:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'varsity_id_system');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. **Run the app**
   - **XAMPP/WAMP:** place the `varsity_id_system` folder inside `htdocs`/`www`, then visit `http://localhost/varsity_id_system/login.php`
   - **PHP built-in server (quick test):**
     ```bash
     php -S localhost:8000
     ```
     then open `http://localhost:8000/login.php`

4. Make sure the `uploads/` folder is writable (used for athlete photos):
   ```bash
   chmod 755 uploads
   ```

## Folder structure

```
varsity_id_system/
├── database.sql                # full schema + default admin
├── config/database.php         # DB connection settings
├── includes/                   # auth guard, helper functions, header/footer
├── login.php / logout.php
├── index.php                   # dashboard with live stats
├── athletes/
│   ├── register.php            # Feature 1: register + issue card
│   ├── list.php                # search/manage records
│   ├── edit.php
│   ├── delete.php
│   └── view_card.php           # printable ID card with QR code
├── scan/
│   ├── scan.php                 # Feature 2: card scan verification
│   └── verify_identity.php      # Feature 3: identity confirmation
├── visitors/
│   ├── log_visitor.php          # Feature 4: record visitor transaction
│   ├── checkout.php             # mark visitor as checked out
│   └── list.php                 # visitor log with filters
├── reports/
│   ├── access_reports.php       # Feature 5: scan/identity report
│   └── visitor_reports.php      # Feature 5: visitor transaction report
├── assets/css/style.css
└── uploads/                     # athlete photo storage
```

## How the workflow ties together

1. Staff registers an athlete → system issues a unique `card_id` and prints an ID card with a QR code.
2. At the dorm entrance, staff scans/types the `card_id` on the **Scan Card** page → system checks the centralized database and logs `Valid`/`Invalid`.
3. If valid, staff is taken to **Identity Verification** to visually compare the cardholder against the stored photo, then confirms **Match** or **No Match**. This result is saved in the same access log record.
4. If identity is verified, staff is taken straight to **Log Visitor** to record who the athlete is receiving, their ID, and purpose — creating a visitor transaction tied to that athlete and access log.
5. All scans and visitor transactions are timestamped and available under **Reports**, filterable by date range, ready to print for record-keeping.

## Security notes
- All queries use PDO prepared statements (no SQL injection).
- Passwords are stored with `password_hash()` / verified with `password_verify()`.
- Sessions gate every page via `require_login()`.
- Uploaded photos are validated by extension and size before being saved.
