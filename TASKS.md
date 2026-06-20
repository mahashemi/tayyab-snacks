# Tayyab Snacks — Project Tasks

## Status Legend
- `[ ]` Not started
- `[~]` In progress
- `[x]` Complete
- `[!]` Blocked / Needs decision

## Priority
- `P1` Critical / MVP must-have
- `P2` Important but can follow MVP
- `P3` Nice to have / future

---

## Phase 1 — Planning & Design
- [x] Define vision and mission (VISION.md) — P1
- [x] Choose app name: Tayyab Snacks — P1
- [x] Choose domain: tayyabsnacks.com — P1
- [x] Narrow scope to packaged/snack food campaigns — P1
- [x] Define campaign lifecycle (submit → pending → admin review → active/rejected → funded/closed) — P1
- [x] Define contribution flow — P1

## Phase 2 — Backend / Database
- [x] Write database schema (schema.sql) — P1
- [x] Create config.php — P1
- [x] Create db.php (PDO + helpers) — P1
- [x] Implement user registration — P1
- [x] Implement login / logout — P1
- [x] Implement campaign listing (browse + filter + search) — P1
- [x] Implement campaign detail view — P1
- [x] Implement contribute (insert contribution record) — P1
- [x] Update campaign raised_amount on contribution (DB trigger) — P1
- [x] Implement submit campaign form — P1
- [x] Admin review / approve / reject campaigns — P1
- [x] Implement user dashboard (my contributions) — P1
- [x] Implement user dashboard (my campaigns) — P1
- [x] Real-time progress calculation (raised/goal %) — P1
- [x] Admin panel — pending review queue, status management, CSV export — P1
- [ ] Email notification on contribution — P2
- [ ] Social share links (WhatsApp, Facebook) — P2
- [ ] Snack brand directory listing — P2
- [ ] Campaign update posts by creator — P2
- [ ] Withdrawal/payout request system — P3

## Phase 3 — Frontend / UI
- [x] Create style.css (warm green/gold food theme) — P1
- [x] Build index.php (hero + active campaigns grid) — P1
- [x] Build register.php — P1
- [x] Build login.php — P1
- [x] Build campaigns.php (full list with filters) — P1
- [x] Build campaign.php (detail page + contribute form) — P1
- [x] Build submit.php (new campaign form) — P1
- [x] Build dashboard.php (my contributions + campaigns) — P1
- [x] Build admin.php (admin panel) — P1
- [x] Campaign progress bar component — P1
- [x] Campaign card with goal/raised/days left — P1
- [x] Mobile responsive layout — P1
- [ ] Campaign category filter tabs on homepage (currently only on campaigns.php) — P2
- [ ] Success animation on contribute — P2
- [ ] Arabic / Urdu text support — P3

## Phase 4 — Production Readiness
- [x] Remove all demo/seed data — production DB starts with one admin account, zero campaigns — P1
- [x] Fix UTF-8 emoji encoding bug in category icons (was corrupting to `?`) — P1
- [x] Write README.md with setup, admin credentials, and security notes — P1
- [x] Write DEPLOY.md with commit → push → deploy workflow — P1
- [ ] Add a "change password" UI (currently requires direct DB update) — P1
- [ ] Test campaign submission and admin approval end-to-end — P1
- [ ] Test contribution and progress update (verify DB trigger fires) — P1
- [ ] Test dashboard accuracy (total contributed) — P1
- [ ] Test on mobile browsers — P1
- [ ] Security audit — P1
- [ ] Test edge cases (goal reached, campaign expired) — P1

## Phase 5 — Deployment
- [ ] Choose hosting — P1
- [x] Register domain: tayyabsnacks.com — P1
- [ ] Set up MySQL on hosting — P1
- [ ] Upload files via FTP — P1
- [ ] Run schema.sql on production (remember `--default-character-set=utf8mb4`; trigger needs DELIMITER support) — P1
- [ ] Update config.php for production — P1
- [ ] Test on live server — P1
- [ ] Set up SSL — P1
- [ ] Set up SMTP email — P2

## Phase 6 — Launch & Growth
- [x] Seed platform with 9 real-style tayyab snack campaigns (authentic Persian snacks) — P1
- [ ] Announce in local Muslim community groups — P1
- [ ] Partner with local halal certification bodies — P2
- [ ] Add payment gateway (Stripe / PayFast / local) — currently contributions are recorded only, no real payment — P2
- [ ] Create verified Tayyab Snack badge for brands — P2
- [ ] Snack brand map integration (Google Maps) — P3
- [ ] Mobile app — P3
- [ ] Multi-currency support — P3

## Phase 7 — Image Uploads & Profit-Sharing
- [x] Campaign photo upload (JPG/PNG/WEBP, 5MB max, server-validated, `.htaccess`-hardened uploads dir) — P1
- [x] 3-way engagement model at contribution time: Total Dunya / Dunya+Akhira (custom %) / Total Akhira — P1
- [x] `report-profit.php` — owner/admin reports profit per period, auto-distributes via `profit_payouts` — P1
- [x] "Your Share" display on dashboard (total owed/donated) and campaign page (per-campaign + public Profit History) — P1
- [x] Tested distribution math end-to-end (verified exact payout/donation split for all 3 engagement types) — P1
- [ ] Actual fund transfer to contributors for their profit share — currently bookkeeping only — P1
- [ ] Notify contributors (email) when a new profit report affects them — P2
- [ ] Let campaign owner attach a note/receipt to a profit report — P3

---

## Open Questions / Decisions Needed
- [!] Payment processing: How are funds transferred to campaign creators, AND how is profit-share payout actually transferred to contributors?
- [!] Who verifies a reported profit figure is accurate? Currently the campaign owner self-reports with no audit step.
- [!] Currency: which country/currency to start with?
- [!] Is there a minimum/maximum contribution amount? (currently min $100, no max)
- [!] Charity campaigns vs. business campaigns — same or different flows?
- [!] Should "Total Akhira" contributions be excluded from the campaign's public funding goal/progress bar, or counted the same as Dunya contributions?

---

*Last updated:* 2026-06-20
