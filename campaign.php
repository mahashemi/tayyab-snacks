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
    $amount     = (float) $_POST['amount'];
    $donorName  = trim($_POST['donor_name'] ?? '');
    $message    = trim($_POST['message'] ?? '');
    $anonymous  = isset($_POST['is_anonymous']) ? 1 : 0;

    if ($amount < 100) $errors[] = 'Minimum contribution is Rs 100.';
    if (!$user && $donorName === '') $errors[] = 'Please enter your name.';

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO contributions (campaign_id, user_id, donor_name, amount, message, is_anonymous) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $user['id'] ?? null,
            $anonymous ? 'Anonymous' : ($user['name'] ?? $donorName),
            $amount,
            $message,
            $anonymous,
        ]);
        flash('success', 'JazakAllah Khair! Your contribution of Rs ' . number_format($amount) . ' has been recorded.');
        redirect('campaign.php?id=' . $id);
    }
}

$contributions = $pdo->prepare('SELECT * FROM contributions WHERE campaign_id = ? ORDER BY created_at DESC LIMIT 20');
$contributions->execute([$id]);
$contributions = $contributions->fetchAll();

$pct = progressPct((float) $campaign['raised_amount'], (float) $campaign['goal_amount']);
$daysLeft = $campaign['deadline'] ? max(0, (int) ((strtotime($campaign['deadline']) - time()) / 86400)) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($campaign['title']) ?> — <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🥨 <?= e(SITE_NAME) ?></div>
    <div class="nav-links">
        <a href="campaigns.php">Campaigns</a>
        <?php if ($user): ?><a href="dashboard.php">Dashboard</a><a href="logout.php" class="nav-btn">Logout</a>
        <?php else: ?><a href="login.php" class="nav-btn">Login</a><?php endif; ?>
    </div>
</nav>

<div class="dashboard-wrap" style="max-width:900px">
    <?php if (flash('success')): ?><div class="alert alert-success"><?= e(flash('success')) ?></div><?php endif; ?>

    <div class="card">
        <div class="campaign-img" style="height:240px;font-size:5rem"><?= e($campaign['cat_icon'] ?: '🥨') ?></div>
        <div class="card-body">
            <?php if ($campaign['cat_name']): ?><span class="badge badge-active" style="margin-bottom:.6rem;display:inline-block"><?= e($campaign['cat_name']) ?></span><?php endif; ?>
            <div style="display:flex;align-items:center;gap:.7rem;flex-wrap:wrap">
                <h1 style="font-size:1.5rem;margin-bottom:.6rem"><?= e($campaign['title']) ?></h1>
                <?php if ($user && ($user['id'] == $campaign['user_id'] || !empty($user['is_admin']))): ?>
                    <a href="edit-campaign.php?id=<?= $id ?>" class="btn btn-sm btn-outline">✏️ Edit</a>
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
                    <span class="prog-raised">Rs <?= number_format((float) $campaign['raised_amount']) ?> raised</span>
                    <span class="prog-pct"><?= $pct ?>%</span>
                    <span class="prog-goal">of Rs <?= number_format((float) $campaign['goal_amount']) ?> goal</span>
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
                    <button type="button" class="preset-btn" onclick="document.getElementById('amountInput').value=<?= $amt ?>">Rs <?= number_format($amt) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Amount (Rs)</label>
                <input type="number" name="amount" id="amountInput" class="form-control" min="100" step="50" placeholder="Enter amount" required>
            </div>

            <?php if (!$user): ?>
            <div class="form-group">
                <label class="form-label">Your Name</label>
                <input type="text" name="donor_name" class="form-control" placeholder="Your name" required>
            </div>
            <?php endif; ?>

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

    <h3 style="margin:1.8rem 0 1rem;font-size:1.1rem;color:var(--green-deep)">Recent Contributions (<?= count($contributions) ?>)</h3>
    <div class="card">
        <?php if (!$contributions): ?>
            <div class="empty-state"><div class="icon">🤲</div><h3>Be the first to contribute</h3></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Name</th><th>Amount</th><th>Message</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($contributions as $c): ?>
                <tr>
                    <td><?= e($c['is_anonymous'] ? 'Anonymous' : $c['donor_name']) ?></td>
                    <td>Rs <?= number_format((float) $c['amount']) ?></td>
                    <td style="max-width:300px"><?= e($c['message'] ?: '—') ?></td>
                    <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
