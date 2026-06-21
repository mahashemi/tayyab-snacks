<?php
require_once __DIR__ . '/db.php';
$user = auth();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT c.*, u.name AS creator_name, u.city AS creator_city, cat.name AS cat_name, cat.icon AS cat_icon,
            e.name AS editor_name, e.is_admin AS editor_is_admin
     FROM campaigns c JOIN users u ON u.id = c.user_id LEFT JOIN categories cat ON cat.id = c.category_id
     LEFT JOIN users e ON e.id = c.updated_by
     WHERE c.id = ?'
);
$stmt->execute([$id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    http_response_code(404);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">Campaign not found. <a href="campaigns.php">Go back</a></p>');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    verifyCsrf();
    $amount      = (float) $_POST['amount'];
    $donorName   = trim($_POST['donor_name'] ?? '');
    $message     = trim($_POST['message'] ?? '');
    $anonymous   = isset($_POST['is_anonymous']) ? 1 : 0;
    $engagement  = $_POST['engagement'] ?? 'dunya';

    if (!in_array($engagement, ['dunya', 'mixed', 'akhira'], true)) $engagement = 'dunya';
    if ($engagement === 'dunya') {
        $akhiraPercent = 0;
    } elseif ($engagement === 'akhira') {
        $akhiraPercent = 100;
    } else {
        $akhiraPercent = (int) ($_POST['akhira_percent'] ?? 50);
        if ($akhiraPercent < 1 || $akhiraPercent > 99) $akhiraPercent = 50;
    }

    if ($amount < 100) $errors[] = 'Minimum contribution is $100.';
    if (!$user && $donorName === '') $errors[] = 'Please enter your name.';

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO contributions (campaign_id, user_id, donor_name, amount, message, is_anonymous, akhira_percent) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $user['id'] ?? null,
            $anonymous ? 'Anonymous' : ($user['name'] ?? $donorName),
            $amount,
            $message,
            $anonymous,
            $akhiraPercent,
        ]);
        flash('success', 'JazakAllah Khair! Your contribution of $' . number_format($amount) . ' has been recorded.');
        redirect('campaign.php?id=' . $id);
    }
}

$contributions = $pdo->prepare('SELECT * FROM contributions WHERE campaign_id = ? ORDER BY created_at DESC LIMIT 20');
$contributions->execute([$id]);
$contributions = $contributions->fetchAll();

$profitReports = $pdo->prepare(
    "SELECT pr.*, (SELECT COALESCE(SUM(payout_amount),0) FROM profit_payouts WHERE profit_report_id = pr.id) AS total_payout,
            (SELECT COALESCE(SUM(donated_amount),0) FROM profit_payouts WHERE profit_report_id = pr.id) AS total_donated
     FROM profit_reports pr WHERE pr.campaign_id = ? ORDER BY pr.created_at DESC"
);
$profitReports->execute([$id]);
$profitReports = $profitReports->fetchAll();

$myShare = null;
if ($user) {
    $myShareStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(pp.payout_amount),0) AS owed, COALESCE(SUM(pp.donated_amount),0) AS donated
         FROM profit_payouts pp JOIN contributions co ON co.id = pp.contribution_id
         WHERE co.campaign_id = ? AND co.user_id = ?"
    );
    $myShareStmt->execute([$id, $user['id']]);
    $myShare = $myShareStmt->fetch();
}

$pct = progressPct((float) $campaign['raised_amount'], (float) $campaign['goal_amount']);
$daysLeft = $campaign['deadline'] ? max(0, (int) ((strtotime($campaign['deadline']) - time()) / 86400)) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($campaign['title']) ?> — <?= e(SITE_NAME) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E%F0%9F%A5%A8%3C/text%3E%3C/svg%3E">
<link rel="stylesheet" href="style.css">
<script>
function toggleAkhiraSlider() {
    const mixed = document.querySelector('input[name="engagement"][value="mixed"]').checked;
    document.getElementById('akhiraSliderWrap').style.display = mixed ? 'block' : 'none';
}
</script>
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🥨 <?= e(SITE_NAME) ?></div>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu">☰</button>
    <div class="nav-scrim" onclick="toggleNav()"></div>
    <div class="nav-links">
        <a href="campaigns.php">Campaigns</a>
        <?php if ($user): ?><a href="dashboard.php">Dashboard</a><a href="logout.php" class="nav-btn">Logout</a>
        <?php else: ?><a href="login.php" class="nav-btn">Login</a><?php endif; ?>
    </div>
</nav>

<div class="dashboard-wrap" style="max-width:900px">
    <?php if (flash('success')): ?><div class="alert alert-success"><?= e(flash('success')) ?></div><?php endif; ?>

    <div class="card">
        <div class="campaign-img" style="height:240px;font-size:5rem">
            <?php if ($campaign['image_url']): ?><img src="<?= e($campaign['image_url']) ?>" alt=""><?php else: ?><?= e($campaign['cat_icon'] ?: '🥨') ?><?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($campaign['cat_name']): ?><span class="badge badge-active" style="margin-bottom:.6rem;display:inline-block"><?= e($campaign['cat_name']) ?></span><?php endif; ?>
            <div style="display:flex;align-items:center;gap:.7rem;flex-wrap:wrap">
                <h1 style="font-size:1.5rem;margin-bottom:.6rem"><?= e($campaign['title']) ?></h1>
                <?php if ($user && ($user['id'] == $campaign['user_id'] || !empty($user['is_admin']))): ?>
                    <a href="edit-campaign.php?id=<?= $id ?>" class="btn btn-sm btn-outline">✏️ Edit</a>
                    <a href="report-profit.php?id=<?= $id ?>" class="btn btn-sm btn-outline">📊 Report Profit</a>
                <?php endif; ?>
            </div>
            <p style="color:var(--text-mid);white-space:pre-line;margin-bottom:1.2rem"><?= e($campaign['description']) ?></p>
            <?php if ($campaign['editor_name']): ?>
                <div style="font-size:.78rem;color:var(--text-light);margin-bottom:1rem">
                    Last edited by <?= e($campaign['editor_name']) ?><?= $campaign['editor_is_admin'] ? ' (Admin)' : '' ?>
                    on <?= date('M j, Y', strtotime($campaign['updated_at'])) ?>
                </div>
            <?php endif; ?>

            <div class="prog-wrap">
                <div class="prog-bar" style="height:14px"><div class="prog-fill" style="width:<?= $pct ?>%"></div></div>
                <div class="prog-labels" style="font-size:.9rem;margin-top:.5rem">
                    <span class="prog-raised">$<?= number_format((float) $campaign['raised_amount']) ?> raised</span>
                    <span class="prog-pct"><?= $pct ?>%</span>
                    <span class="prog-goal">of $<?= number_format((float) $campaign['goal_amount']) ?> goal</span>
                </div>
            </div>

            <div style="display:flex;gap:1.5rem;margin-top:1rem;font-size:.85rem;color:var(--text-light)">
                <span>📍 <?= e($campaign['city'] ?: 'N/A') ?></span>
                <span>⏳ <?= $daysLeft !== null ? $daysLeft . ' days left' : 'No deadline' ?></span>
                <span>👤 by <a href="#"><?= e($campaign['creator_name']) ?></a></span>
            </div>
        </div>
    </div>

    <div class="contribute-section">
        <h3>💝 Contribute to this Campaign</h3>

        <?php if ($errors): ?>
            <div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
        <?php endif; ?>

        <form method="post" id="contribForm">
            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">

            <div class="amount-presets">
                <?php foreach ([500, 1000, 5000, 10000] as $amt): ?>
                    <button type="button" class="preset-btn" onclick="document.getElementById('amountInput').value=<?= $amt ?>">$<?= number_format($amt) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Amount ($)</label>
                <input type="number" name="amount" id="amountInput" class="form-control" min="100" step="50" placeholder="Enter amount" required>
            </div>

            <?php if (!$user): ?>
            <div class="form-group">
                <label class="form-label">Your Name</label>
                <input type="text" name="donor_name" class="form-control" placeholder="Your name" required>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Your Engagement — what happens to your profit share?</label>
                <div style="display:flex;flex-direction:column;gap:.6rem">
                    <label style="display:flex;align-items:start;gap:.6rem;cursor:pointer;padding:.7rem;border:1.5px solid var(--border);border-radius:var(--radius-sm)">
                        <input type="radio" name="engagement" value="dunya" checked onchange="toggleAkhiraSlider()" style="width:auto;margin-top:.2rem">
                        <span><strong>🌍 Total Dunya</strong><br><span style="font-size:.82rem;color:var(--text-light)">I'll receive 100% of any profit share owed to me.</span></span>
                    </label>
                    <label style="display:flex;align-items:start;gap:.6rem;cursor:pointer;padding:.7rem;border:1.5px solid var(--border);border-radius:var(--radius-sm)">
                        <input type="radio" name="engagement" value="mixed" onchange="toggleAkhiraSlider()" style="width:auto;margin-top:.2rem">
                        <span><strong>🌍🕊️ Dunya + Akhira</strong><br><span style="font-size:.82rem;color:var(--text-light)">I'll donate part of my profit share for the work of Imam-e-Zamana, and keep the rest.</span></span>
                    </label>
                    <label style="display:flex;align-items:start;gap:.6rem;cursor:pointer;padding:.7rem;border:1.5px solid var(--border);border-radius:var(--radius-sm)">
                        <input type="radio" name="engagement" value="akhira" onchange="toggleAkhiraSlider()" style="width:auto;margin-top:.2rem">
                        <span><strong>🕊️ Total Akhira</strong><br><span style="font-size:.82rem;color:var(--text-light)">I'll donate 100% of any profit share owed to me.</span></span>
                    </label>
                </div>
                <div id="akhiraSliderWrap" style="display:none;margin-top:.7rem">
                    <label class="form-label">% of my profit share to donate: <strong id="akhiraPercentLabel">50%</strong></label>
                    <input type="range" name="akhira_percent" id="akhiraPercentInput" min="1" max="99" value="50" style="width:100%" oninput="document.getElementById('akhiraPercentLabel').textContent = this.value + '%'">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Message (optional)</label>
                <textarea name="message" class="form-control" placeholder="A word of encouragement..." style="min-height:70px"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                    <input type="checkbox" name="is_anonymous" value="1" style="width:auto">
                    Contribute anonymously
                </label>
            </div>

            <button type="submit" class="btn btn-amber btn-full">Contribute Now</button>
        </form>
    </div>

    <?php if ($myShare && ((float) $myShare['owed'] > 0 || (float) $myShare['donated'] > 0)): ?>
    <div class="alert alert-success" style="margin-top:1.5rem">
        <strong>Your Share from this campaign:</strong>
        $<?= number_format((float) $myShare['owed'], 2) ?> owed to you, $<?= number_format((float) $myShare['donated'], 2) ?> donated on your behalf for the work of Imam-e-Zamana.
    </div>
    <?php endif; ?>

    <h3 style="margin:1.8rem 0 1rem;font-size:1.1rem;color:var(--green-deep)">Recent Contributions (<?= count($contributions) ?>)</h3>
    <div class="card">
        <?php if (!$contributions): ?>
            <div class="empty-state"><div class="icon">🤲</div><h3>Be the first to contribute</h3></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Name</th><th>Amount</th><th>Engagement</th><th>Message</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($contributions as $c): ?>
                <tr>
                    <td><?= e($c['is_anonymous'] ? 'Anonymous' : $c['donor_name']) ?></td>
                    <td>$<?= number_format((float) $c['amount']) ?></td>
                    <td style="font-size:.82rem"><?= e(engagementLabel((int) $c['akhira_percent'])) ?></td>
                    <td style="max-width:250px"><?= e($c['message'] ?: '—') ?></td>
                    <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <h3 style="margin:1.8rem 0 1rem;font-size:1.1rem;color:var(--green-deep)">Profit History (<?= count($profitReports) ?>)</h3>
    <div class="card">
        <?php if (!$profitReports): ?>
            <div class="empty-state"><div class="icon">📊</div><h3>No profit reported yet for this campaign</h3></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Period</th><th>Profit Reported</th><th>Paid to Contributors</th><th>Donated</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($profitReports as $r): ?>
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
    </div>
</div>
<script src="app.js" defer></script>
</body>
</html>
