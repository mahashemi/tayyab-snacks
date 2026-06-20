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
| Email verification required before login (24h token, resend supported) | ✅ |
| Edit your own profile (name, country, phone, password) | ✅ |
| Submit a campaign (goal, category, deadline, description) — held for admin review | ✅ |
| Owners and admins can edit any campaign — "Last edited by" shown on the campaign | ✅ |
| Browse/search active campaigns by category | ✅ |
| Contribute to a campaign (named or anonymous), with live progress bar | ✅ |
| Personal dashboard — my campaigns & my contributions | ✅ |
| Country selector with auto-filled dial code + validated 10-digit phone | ✅ |
| Admin panel — approve/reject pending campaigns, manage status, grant/revoke admin, export CSV | ✅ |
| 9 starter campaigns seeded — authentic Persian snacks (Ardeh, Lavashak, Tokhmeh, Sohan, Gaz, etc.) | ✅ |
| Campaign photo upload (JPG/PNG/WEBP, 5MB max, validated server-side) | ✅ |
| **3-way engagement model**: contributors choose Dunya / Mixed / Akhira profit-sharing per contribution | ✅ |
| **Profit reporting & distribution**: campaign owner reports periodic profit, platform auto-splits it across contributors | ✅ |
| "Your Share" — contributors see profit owed to them and amount donated, per campaign and overall | ✅ |
| Payment gateway integration (currently contributions and profit payouts are recorded, not actually transferred) | 🔜 planned |
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
├── edit-campaign.php               # Edit a campaign (owner or admin)
├── report-profit.php               # Owner/admin reports profit; auto-distributes to contributors
├── edit-profile.php                # Edit your own profile
├── verify.php / verify-pending.php / resend-verification.php   # Email verification flow
├── admin.php                        # Admin panel (pending review, all campaigns, users, privileges, CSV export)
├── uploads/campaigns/                # Uploaded campaign photos (.htaccess blocks script execution)
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
- View, edit, and change the status of any campaign (pending / active / funded / closed / rejected)
- **Grant or revoke admin privileges** for any other user (you cannot demote yourself)
- View and CSV-export users, campaigns, and contributions

> New campaigns submitted via `/submit.php` are **not visible on the site** until an admin approves them from `/admin.php` → Pending tab.

## Email Verification

New accounts must verify their email before logging in. `mail()` is attempted on registration, but **most local environments (XAMPP) have no SMTP configured**, so delivery will silently fail. To make local testing possible, `config.php` has `DEV_SHOW_VERIFY_LINK = true`, which shows the verification link directly on the "check your email" page after registering. **Set this to `false` once real SMTP/email delivery is wired up in production.**

## Editing & Attribution

Campaign creators can edit their own campaigns from `dashboard.php` or the campaign page. Admins can edit *any* campaign the same way (including changing its status). Whenever an admin edits someone else's campaign, the campaign page shows "Last edited by [Admin Name] (Admin)" so changes are always traceable.

## Image Uploads

Campaigns support a photo upload (JPG/PNG/WEBP, max 5MB). Files are validated server-side with `getimagesize()` (not just by extension), renamed to a random filename, and stored in `/uploads/campaigns/`. That folder has a `.htaccess` blocking PHP/script execution. If no photo is uploaded, campaigns fall back to a category icon.

## Profit-Sharing Model (Dunya / Akhira)

This is the platform's core differentiator: contributors aren't just donating — they can be entitled to a real share of the campaign's future profit, and choose how much of that share (if any) they want to keep versus donate.

**At contribution time**, each contributor picks one of three engagement types:

| Engagement | What it means |
|---|---|
| 🌍 **Total Dunya** | Contributor receives 100% of any profit share owed to them. |
| 🌍🕊️ **Dunya + Akhira** | Contributor picks their own split (e.g. donate 40%, keep 60%) of their profit share. |
| 🕊️ **Total Akhira** | Contributor donates 100% of any profit share owed to them — pure sadaqah, for the work of Imam-e-Zamana. |

This choice is stored per-contribution as `akhira_percent` (0–100).

**Reporting profit:** A campaign owner (or admin) periodically visits `/report-profit.php?id=X` and reports actual profit for a period (e.g. "June 2026: $50,000"). The platform then:

1. Calculates each contributor's **ownership fraction** = their contribution amount ÷ total amount contributed to that campaign.
2. Calculates their **raw profit share** = reported profit × ownership fraction.
3. Splits that raw share using their `akhira_percent`: `payout = raw_share × (100 − akhira_percent) / 100`, and the remainder is `donated`.
4. Records one row per contribution in `profit_payouts`, linked to the `profit_reports` entry for that period.

**Where it's shown:**
- The campaign page shows a public **Profit History** table (period, profit reported, total paid out, total donated) and, if you've contributed, **your personal share** for that campaign.
- Your dashboard shows your **total profit share owed** and **total donated on your behalf** across all campaigns, plus a per-contribution breakdown.

**Important — what this is *not* (yet):** Contributions and profit payouts are bookkeeping records only. No real money is transferred to or from contributors automatically. A real payment gateway integration is required before this becomes a live financial product — see Security Notes below.

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
