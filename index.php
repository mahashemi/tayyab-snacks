<?php
require_once __DIR__ . '/db.php';
$user = auth();

$campaigns = $pdo->query(
    "SELECT c.*, u.name AS creator_name, cat.name AS cat_name, cat.icon AS cat_icon
     FROM campaigns c
     JOIN users u ON u.id = c.user_id
     LEFT JOIN categories cat ON cat.id = c.category_id
     WHERE c.status = 'active'
     ORDER BY c.created_at DESC LIMIT 12"
)->fetchAll();

$stats = $pdo->query(
    "SELECT (SELECT COUNT(*) FROM campaigns WHERE status='active') AS active_count,
            (SELECT COALESCE(SUM(raised_amount),0) FROM campaigns) AS total_raised,
            (SELECT COUNT(*) FROM contributions) AS contributor_count"
)->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(SITE_NAME) ?> — <?= e(SITE_TAGLINE) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E%F0%9F%A5%A8%3C/text%3E%3C/svg%3E">
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-brand">🥨 <?= e(SITE_NAME) ?></div>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu">☰</button>
    <div class="nav-scrim" onclick="toggleNav()"></div>
    <div class="nav-links">
        <a href="campaigns.php">Campaigns</a>
        <?php if ($user): ?>
            <a href="submit.php">+ Start a Campaign</a>
            <a href="dashboard.php">Dashboard</a>
            <?php if (!empty($user['is_admin'])): ?><a href="admin.php">Admin</a><?php endif; ?>
            <a href="logout.php" class="nav-btn">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="nav-btn">Join Free</a>
        <?php endif; ?>
    </div>
</nav>

<header class="hero">
    <div class="hero-content">
        <div class="hero-badge">🌿 Halal &amp; Tayyab Certified Campaigns</div>
        <h1>Pure Snacks. <span>Pure Community.</span></h1>
        <p>Fund halal, additive-free snack and packaged food makers. Every contribution helps a Muslim entrepreneur bring tayyab food to your table.</p>
        <div class="hero-actions">
            <?php if ($user): ?>
                <a href="submit.php" class="btn btn-primary">+ Start a Campaign</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary">Join Free</a>
            <?php endif; ?>
            <a href="#campaigns" class="btn btn-secondary">Browse Campaigns</a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat"><span class="num"><?= (int) $stats['active_count'] ?></span><span class="lbl">Active Campaigns</span></div>
            <div class="hero-stat"><span class="num">$<?= number_format((float) $stats['total_raised']) ?></span><span class="lbl">Total Raised</span></div>
            <div class="hero-stat"><span class="num"><?= (int) $stats['contributor_count'] ?></span><span class="lbl">Contributions</span></div>
        </div>
    </div>
</header>

<?php if (!$user): ?>
<section class="mission-band">
    <div class="mission-grid">
        <div>
            <h3>🎯 Our Vision</h3>
            <p>To build the go-to community-funded platform for halal, additive-free snack and packaged food entrepreneurs — helping Muslim families trust what they feed their children, and helping small tayyab snack makers get the capital to grow.</p>
        </div>
        <div>
            <h3>🌍 Our Mission</h3>
            <p>A contribution (crowdfunding) platform focused specifically on packaged and snack food campaigns — from home-based snack makers launching their first product, to small halal brands scaling production — funded directly by the Muslim community.</p>
        </div>
    </div>
    <div class="mission-cta">
        <p>Already have an account?</p>
        <div class="hero-actions" style="justify-content:center">
            <a href="login.php" class="btn btn-primary">Log In</a>
            <a href="register.php" class="btn btn-outline">Create Free Account</a>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="container section" id="campaigns">
    <h2 class="section-title">Active <span>Campaigns</span></h2>
    <p class="section-sub">Support these halal snack and packaged food initiatives</p>

    <?php if (!$campaigns): ?>
        <div class="empty-state"><div class="icon">📭</div><h3>No active campaigns right now</h3></div>
    <?php else: ?>
    <div class="grid-3">
        <?php foreach ($campaigns as $c):
            $pct = progressPct((float) $c['raised_amount'], (float) $c['goal_amount']);
            $daysLeft = $c['deadline'] ? max(0, (int) ((strtotime($c['deadline']) - time()) / 86400)) : null;
        ?>
        <a href="campaign.php?id=<?= (int) $c['id'] ?>" class="campaign-card" style="text-decoration:none;color:inherit">
            <div class="campaign-img">
                <?php if ($c['image_url']): ?><img src="<?= e($c['image_url']) ?>" alt=""><?php else: ?><?= e($c['cat_icon'] ?: '🥨') ?><?php endif; ?>
                <?php if ($c['cat_name']): ?><span class="campaign-cat-badge"><?= e($c['cat_name']) ?></span><?php endif; ?>
            </div>
            <div class="campaign-body">
                <div class="campaign-title"><?= e($c['title']) ?></div>
                <div class="campaign-desc"><?= e($c['description']) ?></div>
                <div class="prog-wrap">
                    <div class="prog-bar"><div class="prog-fill" style="width:<?= $pct ?>%"></div></div>
                    <div class="prog-labels">
                        <span class="prog-raised">$<?= number_format((float) $c['raised_amount']) ?></span>
                        <span class="prog-pct"><?= $pct ?>%</span>
                        <span class="prog-goal">of $<?= number_format((float) $c['goal_amount']) ?></span>
                    </div>
                </div>
            </div>
            <div class="campaign-footer">
                <div class="campaign-meta">
                    <span>📍 <?= e($c['city'] ?: 'N/A') ?></span>
                    <span><?= $daysLeft !== null ? $daysLeft . ' days left' : 'No deadline' ?></span>
                </div>
                <span class="btn btn-outline btn-sm">Contribute →</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-brand">🥨 <?= e(SITE_NAME) ?></div>
            <p>Pure Snacks. Pure Intentions. Pure Community.</p>
        </div>
        <div>
            <div class="footer-heading">Explore</div>
            <ul class="footer-links">
                <li><a href="campaigns.php">All Campaigns</a></li>
                <li><a href="register.php">Join Free</a></li>
            </ul>
        </div>
        <div>
            <div class="footer-heading">Account</div>
            <ul class="footer-links">
                <li><a href="login.php">Login</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. Built with ❤️ for the Ummah.</div>
</footer>

<script src="app.js" defer></script>
</body>
</html>
