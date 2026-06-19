# Tayyab Snacks

**Pure Snacks. Pure Intentions. Pure Community.** A crowdfunding platform connecting halal, additive-free snack and packaged-food makers with community contributors.

🌐 Domain: **tayyabsnacks.com**

---

## What It Does

Tayyab Snacks is a focused crowdfunding platform — not a general marketplace — for **packaged and snack food** campaigns:

- Home bakers, small snack brands, dried-fruit packers, and halal beverage makers submit a campaign
- An admin reviews and approves it before it goes live
- The community contributes funds, tracked transparently with a live progress bar

## Tech Stack

- **PHP 7.4+** (no framework — plain PHP with PDO)
- **MySQL / MariaDB** (uses a DB trigger to auto-update campaign totals on each contribution)
- **Vanilla HTML/CSS/JS** — no build step, deploy by copying files

## Features

| Feature | Status |
|---|---|
| User registration & login | ✅ |
| Submit a campaign (goal, category, deadline, description) — held for admin review | ✅ |
| Browse/search active campaigns by category | ✅ |
| Contribute to a campaign (named or anonymous), with live progress bar | ✅ |
| Personal dashboard — my campaigns & my contributions | ✅ |
| Admin panel — approve/reject pending campaigns, manage status, export CSV | ✅ |
| Payment gateway integration (currently contributions are recorded, not charged) | 🔜 planned |
| Verified "Tayyab Snack" badge for brands | 🔜 planned |

## Project Structure

```
tayyab_snacks/
├── config.php           # DB credentials & site settings — EDIT THIS for your environment
├── db.php                # PDO connection + shared helper functions (incl. progressPct())
├── schema.sql              # Database schema + trigger + one starter admin account
├── style.css                # Design system (warm green/gold/amber food theme)
├── index.php                 # Homepage / active campaigns
├── register.php / login.php / logout.php
├── campaigns.php               # Browse all active campaigns
├── campaign.php                  # Campaign detail + contribute form
├── submit.php                     # Submit a new campaign (goes to "pending" review)
├── dashboard.php                   # My campaigns & my contributions
├── admin.php                        # Admin panel (pending review, all campaigns, users, CSV export)
├── VISION.md                         # Product vision & mission
└── TASKS.md                           # Project task tracker
```

## Setup (Local — XAMPP)

1. Copy this folder into `C:\xampp\htdocs\tayyab_snacks`
2. Start Apache + MySQL in the XAMPP Control Panel
3. Import the schema:
   ```
   C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root < schema.sql
   ```
   > **Important:** always import with `--default-character-set=utf8mb4` — without it, the emoji category icons get corrupted into `?` characters. The schema also creates a `DELIMITER`-based trigger; if your host's phpMyAdmin import rejects triggers, run that block separately via the SQL tab.
4. Visit `http://localhost/tayyab_snacks/`

## First Login

A single admin account is seeded by `schema.sql`:

- **Email:** `admin@tayyabsnacks.com`
- **Password:** `Admin@123`

**Change this password immediately after your first login.** There is no "change password" UI yet — update it directly in the database:

```sql
UPDATE users SET password = '<new bcrypt hash>' WHERE email = 'admin@tayyabsnacks.com';
```
(Generate a hash with PHP: `php -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT);"`)

## Admin Panel

Visit `/admin.php` while logged in as an admin (`is_admin = 1`) to:
- **Review pending campaigns** — approve (makes it live) or reject, one click each
- View and change the status of any campaign (pending / active / funded / closed / rejected)
- View and CSV-export users, campaigns, and contributions

> New campaigns submitted via `/submit.php` are **not visible on the site** until an admin approves them from `/admin.php` → Pending tab.

## Deployment

See [DEPLOY.md](DEPLOY.md) for the full commit → push → deploy workflow, including both shared-hosting (cPanel/FTP) and VPS (SSH + git pull) paths.

## Security Notes

- Passwords are hashed with `password_hash()` (bcrypt)
- All database queries use PDO prepared statements
- All forms are CSRF-protected
- `config.php` ships with local XAMPP defaults (`root` / no password) — **you must change these before deploying to production**
- Contributions are currently bookkeeping records only — no real payment is processed. Wire up a gateway (Stripe/PayFast/local) before accepting real money.

## License

Private project. All rights reserved.
