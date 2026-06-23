<?php
require_once __DIR__ . '/db.php';
requireAuth();
$user = auth();

$stmt = $pdo->prepare(
    "SELECT c.*, cat.name AS cat_name FROM campaigns c LEFT JOIN categories cat ON cat.id = c.category_id
     WHERE c.user_id = ? ORDER BY c.created_at DESC"
);
$stmt->execute([$user['id']]);
$myCampaigns = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT co.*, ca.title AS campaign_title,
            (SELECT COALESCE(SUM(payout_amount),0) FROM profit_payouts WHERE contribution_id = co.id) AS my_payout,
            (SELECT COALESCE(SUM(donated_amount),0) FROM profit_payouts WHERE contribution_id = co.id) AS my_donated
     FROM contributions co
     JOIN campaigns ca ON ca.id = co.campaign_id
     WHERE co.user_id = ? ORDER BY co.created_at DESC"
);
$stmt->execute([$user['id']]);
$myContributions = $stmt->fetchAll();

$totalContributed = array_sum(array_column($myContributions, 'amount'));
$totalRaisedForMe = array_sum(array_column($myCampaigns, 'raised_amount'));
$totalProfitOwed = array_sum(array_column($myContributions, 'my_payout'));
$totalProfitDonated = array_sum(array_column($myContributions, 'my_donated'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — <?= e(SITE_NAME) ?></title>
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

<div class="dashboard-wrap">
    <div class="dashboard-header">
        <h2>Welcome, <?= e($user['name']) ?></h2>
        <p>Track your contributions and your submitted campaigns.</p>
    </div>

    <?php if (flash('success')): ?><div class="alert alert-success"><?= e(flash('success')) ?></div><?php endif; ?>

    <div class="stat-cards">
        <div class="stat-card"><div class="num">$<?= number_format((float) $totalContributed) ?></div><div class="lbl">Total Contributed</div></div>
        <div class="stat-card"><div class="num"><?= count($myContributions) ?></div><div class="lbl">Contributions Made</div></div>
        <div class="stat-card"><div class="num"><?= count($myCampaigns) ?></div><div class="lbl">My Campaigns</div></div>
        <div class="stat-card"><div class="num">$<?= number_format((float) $totalRaisedForMe) ?></div><div class="lbl">Raised For My Campaigns</div></div>
        <div class="stat-card"><div class="num">$<?= number_format($totalProfitOwed, 2) ?></div><div class="lbl">Your Profit Share Owed</div></div>
        <div class="stat-card"><div class="num">$<?= number_format($totalProfitDonated, 2) ?></div><div class="lbl">Your Share Donated (Imam-e-Zamana)</div></div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h3 style="font-size:1.1rem;color:var(--green-deep)">My Campaigns</h3>
        <a href="submit.php" class="btn btn-amber btn-sm">+ Start a Campaign</a>
    </div>

    <?php if (!$myCampaigns): ?>
        <div class="empty-state"><div class="icon"><i data-lucide="cookie" class="lucide-icon"></i></div><h3>You haven't started a campaign yet</h3></div>
    <?php else: ?>
    <table class="table" style="margin-bottom:2rem">
        <thead><tr><th>Title</th><th>Category</th><th>Goal</th><th>Raised</th><th>Status</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($myCampaigns as $c): ?>
            <tr>
                <td><a href="campaign.php?id=<?= (int) $c['id'] ?>"><?= e($c['title']) ?></a></td>
                <td><?= e($c['cat_name'] ?? '—') ?></td>
                <td>$<?= number_format((float) $c['goal_amount']) ?></td>
                <td>$<?= number_format((float) $c['raised_amount']) ?></td>
                <td><span class="badge badge-<?= e($c['status']) ?>"><?= e(ucfirst($c['status'])) ?></span></td>
                <td class="action-row">
                    <a href="edit-campaign.php?id=<?= (int) $c['id'] ?>" class="icon-btn" data-tip="Edit campaign" aria-label="Edit campaign"><i data-lucide="pencil" class="lucide-icon"></i></a>
                    <a href="report-profit.php?id=<?= (int) $c['id'] ?>" class="icon-btn" data-tip="Report profit" aria-label="Report profit"><i data-lucide="bar-chart-3" class="lucide-icon"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <h3 style="font-size:1.1rem;color:var(--green-deep);margin-bottom:1rem">My Contributions</h3>
    <?php if (!$myContributions): ?>
        <div class="empty-state"><div class="icon"><i data-lucide="hand-coins" class="lucide-icon"></i></div><h3>You haven't contributed to any campaign yet</h3></div>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Campaign</th><th>Amount</th><th>Engagement</th><th>Your Share Owed</th><th>Donated</th><th>Date</th></tr></thead>
        <tbody>
            <?php foreach ($myContributions as $c): ?>
            <tr>
                <td><a href="campaign.php?id=<?= (int) $c['campaign_id'] ?>"><?= e($c['campaign_title']) ?></a></td>
                <td>$<?= number_format((float) $c['amount']) ?></td>
                <td style="font-size:.82rem"><?= e(engagementLabel((int) $c['akhira_percent'])) ?></td>
                <td>$<?= number_format((float) $c['my_payout'], 2) ?></td>
                <td>$<?= number_format((float) $c['my_donated'], 2) ?></td>
                <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="app.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
