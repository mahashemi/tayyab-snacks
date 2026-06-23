<?php
require_once __DIR__ . '/db.php';
requireAuth();
$user = auth();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM campaigns WHERE id = ?');
$stmt->execute([$id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    http_response_code(404);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">Campaign not found. <a href="campaigns.php">Go back</a></p>');
}

$isOwner = $campaign['user_id'] == $user['id'];
$isAdmin = !empty($user['is_admin']);
if (!$isOwner && !$isAdmin) {
    http_response_code(403);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">You do not have permission to report profit for this campaign. <a href="campaign.php?id=' . $id . '">Go back</a></p>');
}

$errors = [];
$summary = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $periodLabel  = trim($_POST['period_label'] ?? '');
    $profitAmount = (float) ($_POST['profit_amount'] ?? -1);

    if (mb_strlen($periodLabel) < 2) $errors[] = 'Please enter a period label, e.g. "June 2026".';
    if ($profitAmount < 0) $errors[] = 'Profit amount cannot be negative.';

    if (!$errors) {
        $contribStmt = $pdo->prepare('SELECT id, user_id, amount, akhira_percent FROM contributions WHERE campaign_id = ?');
        $contribStmt->execute([$id]);
        $contributions = $contribStmt->fetchAll();
        $totalContributed = array_sum(array_column($contributions, 'amount'));

        if ($totalContributed <= 0) {
            $errors[] = 'This campaign has no contributions yet — nothing to distribute profit against.';
        } else {
            $pdo->beginTransaction();
            try {
                $reportStmt = $pdo->prepare(
                    'INSERT INTO profit_reports (campaign_id, reported_by, period_label, profit_amount) VALUES (?, ?, ?, ?)'
                );
                $reportStmt->execute([$id, $user['id'], $periodLabel, $profitAmount]);
                $reportId = (int) $pdo->lastInsertId();

                $payoutStmt = $pdo->prepare(
                    'INSERT INTO profit_payouts (profit_report_id, contribution_id, user_id, payout_amount, donated_amount) VALUES (?, ?, ?, ?, ?)'
                );

                $totalPayout = 0;
                $totalDonated = 0;
                foreach ($contributions as $c) {
                    $fraction = (float) $c['amount'] / $totalContributed;
                    $rawShare = $profitAmount * $fraction;
                    $donated = $rawShare * ((int) $c['akhira_percent'] / 100);
                    $payout = $rawShare - $donated;
                    $payoutStmt->execute([$reportId, $c['id'], $c['user_id'], $payout, $donated]);
                    $totalPayout += $payout;
                    $totalDonated += $donated;
                }

                $pdo->commit();
                $summary = ['period' => $periodLabel, 'profit' => $profitAmount, 'payout' => $totalPayout, 'donated' => $totalDonated, 'contributors' => count($contributions)];
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Something went wrong while distributing profit. Please try again.';
            }
        }
    }
}

$reports = $pdo->prepare(
    "SELECT pr.*, (SELECT COALESCE(SUM(payout_amount),0) FROM profit_payouts WHERE profit_report_id = pr.id) AS total_payout,
            (SELECT COALESCE(SUM(donated_amount),0) FROM profit_payouts WHERE profit_report_id = pr.id) AS total_donated
     FROM profit_reports pr WHERE pr.campaign_id = ? ORDER BY pr.created_at DESC"
);
$reports->execute([$id]);
$reports = $reports->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report Profit — <?= e(SITE_NAME) ?></title>
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
        <h2><i data-lucide="bar-chart-3" class="lucide-icon"></i> Report Profit — <?= e($campaign['title']) ?></h2>
        <p>Report this campaign's profit for a period. It will be split across all contributors based on their contribution share and chosen engagement type (Dunya / Mixed / Akhira).</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <?php if ($summary): ?>
        <div class="alert alert-success">
            <strong><?= e($summary['period']) ?>:</strong> $<?= number_format($summary['profit']) ?> profit distributed across <?= $summary['contributors'] ?> contribution(s) —
            $<?= number_format($summary['payout'], 2) ?> owed to contributors, $<?= number_format($summary['donated'], 2) ?> donated for the work of Imam-e-Zamana.
        </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:1.5rem"><div class="card-body">
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Period Label</label>
                    <input type="text" name="period_label" class="form-control" placeholder="e.g. June 2026" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Profit Amount ($)</label>
                    <input type="number" name="profit_amount" class="form-control" min="0" step="0.01" placeholder="0" required>
                </div>
            </div>

            <button type="submit" class="btn btn-amber">Report &amp; Distribute Profit</button>
        </form>
    </div></div>

    <h3 style="margin-bottom:1rem;font-size:1.1rem;color:var(--green-deep)">Profit History (<?= count($reports) ?>)</h3>
    <?php if (!$reports): ?>
        <div class="empty-state"><div class="icon"><i data-lucide="bar-chart-3" class="lucide-icon"></i></div><h3>No profit reported yet</h3></div>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Period</th><th>Profit Reported</th><th>Paid to Contributors</th><th>Donated</th><th>Date</th></tr></thead>
        <tbody>
            <?php foreach ($reports as $r): ?>
            <tr>
                <td><?= e($r['period_label']) ?></td>
                <td>$<?= number_format((float) $r['profit_amount'], 2) ?></td>
                <td>$<?= number_format((float) $r['total_payout'], 2) ?></td>
                <td>$<?= number_format((float) $r['total_donated'], 2) ?></td>
                <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <p style="margin-top:1.5rem"><a href="campaign.php?id=<?= $id ?>" class="btn btn-outline"><i data-lucide="arrow-left" class="lucide-icon"></i> Back to Campaign</a></p>
</div>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="app.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
