<?php
require_once __DIR__ . '/db.php';
$user = auth();

$catId = (int) ($_GET['cat'] ?? 0);
$q = trim($_GET['q'] ?? '');
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$sql = "SELECT c.*, u.name AS creator_name, cat.name AS cat_name, cat.icon AS cat_icon
        FROM campaigns c
        JOIN users u ON u.id = c.user_id
        LEFT JOIN categories cat ON cat.id = c.category_id
        WHERE c.status = 'active'";
$params = [];
if ($catId > 0) { $sql .= ' AND c.category_id = ?'; $params[] = $catId; }
if ($q !== '') { $sql .= ' AND (c.title LIKE ? OR c.description LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
$sql .= ' ORDER BY c.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$campaigns = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Campaigns — <?= e(SITE_NAME) ?></title>
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
        <?php if ($user): ?><a href="submit.php">+ Start a Campaign</a><a href="dashboard.php">Dashboard</a><a href="logout.php" class="nav-btn">Logout</a>
        <?php else: ?><a href="login.php" class="nav-btn">Login</a><?php endif; ?>
    </div>
</nav>

<div class="container section">
    <h2 class="section-title">All <span>Campaigns</span></h2>

    <form method="get" style="display:flex;gap:.6rem;margin-bottom:1.5rem;max-width:500px">
        <input type="text" name="q" class="form-control" placeholder="Search campaigns..." value="<?= e($q) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <div class="chip-row">
        <a href="campaigns.php" class="cat-chip <?= $catId === 0 ? 'active' : '' ?>">🍽️ All Categories</a>
        <?php foreach ($categories as $c): ?>
            <a href="?cat=<?= (int) $c['id'] ?>" class="cat-chip <?= $catId === (int) $c['id'] ? 'active' : '' ?>"><?= e($c['icon']) ?> <?= e($c['name']) ?></a>
        <?php endforeach; ?>
    </div>

    <p class="section-sub"><?= count($campaigns) ?> campaign(s) found</p>

    <?php if (!$campaigns): ?>
        <div class="empty-state"><div class="icon">📭</div><h3>No campaigns found</h3></div>
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
<script src="app.js" defer></script>
</body>
</html>
