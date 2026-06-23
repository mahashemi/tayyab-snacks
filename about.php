<?php
require_once __DIR__ . '/db.php';
$user = auth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us — <?= e(SITE_NAME) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E%F0%9F%A5%A8%3C/text%3E%3C/svg%3E">
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <a class="nav-brand" href="index.php"><i data-lucide="cookie" class="lucide-icon"></i> <?= e(SITE_NAME) ?></a>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu"><i data-lucide="menu" class="lucide-icon"></i></button>
    <div class="nav-scrim" onclick="toggleNav()"></div>
    <div class="nav-links">
        <a href="campaigns.php">Campaigns</a>
        <a href="about.php">About</a>
        <a href="feedback.php">Feedback</a>
        <?php if ($user): ?>
            <a href="submit.php">+ Start a Campaign</a>
            <div class="nav-account">
                <button class="nav-account-trigger" type="button" onclick="toggleAccountMenu(event)" aria-label="Account menu">
                    <span class="nav-avatar"><?= e(mb_substr($user['name'], 0, 1)) ?></span>
                    <i data-lucide="chevron-down" class="lucide-icon"></i>
                </button>
                <div class="nav-account-menu">
                    <div class="nav-account-header">
                        <span class="nav-avatar"><?= e(mb_substr($user['name'], 0, 1)) ?></span>
                        <div>
                            <div class="nav-account-name"><?= e($user['name']) ?></div>
                            <div class="nav-account-email"><?= e($user['email']) ?></div>
                        </div>
                    </div>
                    <div class="nav-menu-divider"></div>
                    <a href="dashboard.php"><i data-lucide="layout-dashboard" class="lucide-icon"></i> Dashboard</a>
                    <?php if (!empty($user['is_admin'])): ?><a href="admin.php"><i data-lucide="shield-check" class="lucide-icon"></i> Admin Panel</a><?php endif; ?>
                    <div class="nav-menu-divider"></div>
                    <a href="logout.php"><i data-lucide="log-out" class="lucide-icon"></i> Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="nav-btn">Join Free</a>
        <?php endif; ?>
    </div>
</nav>

<section class="mission-band">
    <div class="mission-grid">
        <div>
            <h3><i data-lucide="target" class="lucide-icon"></i> Our Vision</h3>
            <p>To build the go-to community-funded platform for halal, additive-free snack and packaged food entrepreneurs — helping Muslim families trust what they feed their children, and helping small tayyab snack makers get the capital to grow.</p>
        </div>
        <div>
            <h3><i data-lucide="globe" class="lucide-icon"></i> Our Mission</h3>
            <p>A contribution (crowdfunding) platform focused specifically on packaged and snack food campaigns — from home-based snack makers launching their first product, to small halal brands scaling production — funded directly by the Muslim community.</p>
        </div>
    </div>
</section>

<div class="container section">
    <h2 class="section-title">What Makes Us <span>Different</span></h2>
    <div class="grid-3">
        <div class="card"><div class="card-body">
            <h3 style="font-size:1.05rem;margin-bottom:.5rem;color:var(--green-deep)"><i data-lucide="heart-handshake" class="lucide-icon"></i> Dunya & Akhira</h3>
            <p style="color:var(--text-mid);font-size:.92rem">Contributors aren't just donating — they earn a real profit share, and choose how much to keep versus give as sadaqah.</p>
        </div></div>
        <div class="card"><div class="card-body">
            <h3 style="font-size:1.05rem;margin-bottom:.5rem;color:var(--green-deep)"><i data-lucide="cookie" class="lucide-icon"></i> Snacks Only</h3>
            <p style="color:var(--text-mid);font-size:.92rem">We focus exclusively on packaged and snack foods — a niche where trust in halal sourcing matters most.</p>
        </div></div>
        <div class="card"><div class="card-body">
            <h3 style="font-size:1.05rem;margin-bottom:.5rem;color:var(--green-deep)"><i data-lucide="bar-chart-3" class="lucide-icon"></i> Transparent Reporting</h3>
            <p style="color:var(--text-mid);font-size:.92rem">Campaign owners report real profit periodically, and every contributor sees exactly what they're owed.</p>
        </div></div>
    </div>
    <div style="text-align:center;margin-top:2.5rem">
        <p style="color:var(--text-mid);margin-bottom:1rem">Have a question or suggestion?</p>
        <a href="feedback.php" class="btn btn-amber">Send Us Feedback</a>
    </div>
</div>

<footer>
    <div class="footer-bottom">&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. Pure Snacks. Pure Intentions. Pure Community.</div>
</footer>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="app.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
