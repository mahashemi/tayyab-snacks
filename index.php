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
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-brand">🥨 <?= e(SITE_NAME) ?></div>
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
            <div class="hero-stat"><span class="num">Rs <?= number_format((float) $stats['total_raised']) ?></span><span class="lbl">Total Raised</span></div>
            <div class="hero-stat"><span class="num"><?= (int) $stats['contributor_count'] ?></span><span class="lbl">Contributions</span></div>
        </div>
    </div>
</header>

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
                <?= e($c['cat_icon'] ?: '🥨') ?>
                <?php if ($c['cat_name']): ?><span class="campaign-cat-badge"><?= e($c['cat_name']) ?></span><?php endif; ?>
            </div>
            <div class="campaign-body">
                <div class="campaign-title"><?= e($c['title']) ?></div>
                <div class="campaign-desc"><?= e($c['description']) ?></div>
                <div class="prog-wrap">
                    <div class="prog-bar"><div class="prog-fill" style="width:<?= $pct ?>%"></div></div>
                    <div class="prog-labels">
                        <span class="prog-raised">Rs <?= number_format((float) $c['raised_amount']) ?></span>
                        <span class="prog-pct"><?= $pct ?>%</span>
                        <span class="prog-goal">of Rs <?= number_format((float) $c['goal_amount']) ?></span>
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

</body>
</html>
