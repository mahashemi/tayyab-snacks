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
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🥨 <?= e(SITE_NAME) ?></div>
    <div class="nav-links">
        <a href="campaigns.php">Campaigns</a>
        <a href="submit.php">+ Start a Campaign</a>
        <a href="edit-profile.php">Edit Profile</a>
        <?php if (!empty($user['is_admin'])): ?><a href="admin.php">Admin</a><?php endif; ?>
        <a href="logout.php" class="nav-btn">Logout</a>
    </div>
</nav>

<div class="dashboard-wrap">
    <div class="dashboard-header">
        <h2>👋 Welcome, <?= e($user['name']) ?></h2>
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
        <div class="empty-state"><div class="icon">🥨</div><h3>You haven't started a campaign yet</h3></div>
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
                <td style="display:flex;gap:.4rem">
                    <a href="edit-campaign.php?id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                    <a href="report-profit.php?id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline">Report Profit</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <h3 style="font-size:1.1rem;color:var(--green-deep);margin-bottom:1rem">My Contributions</h3>
    <?php if (!$myContributions): ?>
        <div class="empty-state"><div class="icon">🤲</div><h3>You haven't contributed to any campaign yet</h3></div>
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
</body>
</html>
