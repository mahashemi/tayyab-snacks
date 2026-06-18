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
- [ ] Define campaign lifecycle (submit → review → active → funded/closed) — P1
- [ ] Define contribution flow — P1
- [ ] Wireframe: Home/landing with active campaigns — P1
- [ ] Wireframe: Campaign detail + contribute — P1
- [ ] Wireframe: Dashboard — P1
- [ ] Wireframe: Submit campaign form — P1

## Phase 2 — Backend / Database
- [~] Write database schema (schema.sql) — P1
- [~] Create config.php — P1
- [~] Create db.php (PDO + helpers) — P1
- [ ] Implement user registration — P1
- [ ] Implement login / logout — P1
- [ ] Implement campaign listing (browse + filter) — P1
- [ ] Implement campaign detail view — P1
- [ ] Implement contribute (insert contribution record) — P1
- [ ] Update campaign raised_amount on contribution (DB trigger) — P1
- [ ] Implement submit campaign form — P1
- [ ] Admin review / approve campaigns — P1
- [ ] Implement user dashboard (my contributions) — P1
- [ ] Implement user dashboard (my campaigns) — P1
- [ ] Real-time progress calculation (raised/goal %) — P1
- [ ] Email notification on contribution — P2
- [ ] Social share links (WhatsApp, Facebook) — P2
- [ ] Snack brand directory listing — P2
- [ ] Campaign update posts by creator — P2
- [ ] Withdrawal/payout request system — P3

## Phase 3 — Frontend / UI
- [~] Create style.css (warm green/gold food theme) — P1
- [~] Build index.php (hero + active campaigns grid) — P1
- [~] Build register.php — P1
- [~] Build login.php — P1
- [ ] Build campaigns.php (full list with filters) — P1
- [ ] Build campaign.php (detail page + contribute form) — P1
- [ ] Build submit.php (new campaign form) — P1
- [ ] Build dashboard.php (my contributions + campaigns) — P1
- [ ] Campaign progress bar component — P1
- [ ] Campaign card with goal/raised/days left — P1
- [ ] Mobile responsive layout — P1
- [ ] Campaign category filter tabs — P2
- [ ] Success animation on contribute — P2
- [ ] Arabic / Urdu text support — P3

## Phase 4 — Testing
- [ ] Test campaign submission and admin approval — P1
- [ ] Test contribution and progress update — P1
- [ ] Test dashboard accuracy (total contributed) — P1
- [ ] Test on mobile browsers — P1
- [ ] Security audit — P1
- [ ] Test edge cases (goal reached, campaign expired) — P1

## Phase 5 — Deployment
- [ ] Choose hosting — P1
- [x] Register domain: tayyabsnacks.com — P1
- [ ] Set up MySQL on hosting — P1
- [ ] Upload files via FTP — P1
- [ ] Run schema.sql on production — P1
- [ ] Update config.php for production — P1
- [ ] Test on live server — P1
- [ ] Set up SSL — P1
- [ ] Set up SMTP email — P2

## Phase 6 — Launch & Growth
- [ ] Seed platform with 5 real tayyab snack campaigns — P1
- [ ] Announce in local Muslim community groups — P1
- [ ] Partner with local halal certification bodies — P2
- [ ] Add payment gateway (Stripe / PayFast / local) — P2
- [ ] Create verified Tayyab Snack badge for brands — P2
- [ ] Snack brand map integration (Google Maps) — P3
- [ ] Mobile app — P3
- [ ] Multi-currency support — P3

---

## Open Questions / Decisions Needed
- [!] Payment processing: How are funds transferred to campaign creators?
- [!] Currency: which country/currency to start with?
- [!] Who administers campaign approval and fund release?
- [!] Is there a minimum/maximum contribution amount?
- [!] Should restaurants/food trucks be fully excluded, or allowed as a secondary category?

---

*Last updated:* 2026-06-18
