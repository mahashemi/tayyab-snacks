<?php
require_once __DIR__ . '/db.php';
requireAuth();
$user = auth();
if (empty($user['is_admin'])) {
    http_response_code(403);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">Access denied. Admins only. <a href="index.php">Go back</a></p>');
}

// ── CSV Export ─────────────────────────────────────────────────────────
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    $map = [
        'users'         => ['sql' => 'SELECT id, name, email, phone, city, country, created_at FROM users ORDER BY id', 'file' => 'tayyabsnacks_users.csv'],
        'campaigns'     => ['sql' => "SELECT c.id, c.title, u.name AS creator, cat.name AS category, c.goal_amount, c.raised_amount, c.city, c.deadline, c.status, c.created_at
                                       FROM campaigns c JOIN users u ON u.id = c.user_id LEFT JOIN categories cat ON cat.id = c.category_id ORDER BY c.id", 'file' => 'tayyabsnacks_campaigns.csv'],
        'contributions' => ['sql' => "SELECT co.id, ca.title AS campaign, co.donor_name, co.amount, co.is_anonymous, co.message, co.created_at
                                       FROM contributions co JOIN campaigns ca ON ca.id = co.campaign_id ORDER BY co.id", 'file' => 'tayyabsnacks_contributions.csv'],
    ];
    if (isset($map[$type])) {
        $rows = $pdo->query($map[$type]['sql'])->fetchAll();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $map[$type]['file'] . '"');
        $out = fopen('php://output', 'w');
        if ($rows) fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $r) fputcsv($out, $r);
        fclose($out);
        exit;
    }
}

// ── Actions ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (isset($_POST['toggle_verified'])) {
        $pdo->prepare('UPDATE users SET is_verified = 1, verification_token = NULL, verification_expires = NULL WHERE id = ?')->execute([(int) $_POST['toggle_verified']]);
    } elseif (isset($_POST['toggle_admin'])) {
        $targetId = (int) $_POST['toggle_admin'];
        if ($targetId !== (int) $user['id']) {
            $pdo->prepare('UPDATE users SET is_admin = 1 - is_admin WHERE id = ?')->execute([$targetId]);
        }
    } elseif (isset($_POST['set_status'])) {
        $newStatus = $_POST['set_status'];
        $cid = (int) $_POST['campaign_id'];
        if (in_array($newStatus, ['active', 'rejected', 'closed', 'funded', 'pending'], true)) {
            $pdo->prepare('UPDATE campaigns SET status = ? WHERE id = ?')->execute([$newStatus, $cid]);
        }
    }
    redirect('admin.php?tab=' . ($_GET['tab'] ?? 'pending'));
}

$tab = $_GET['tab'] ?? 'pending';

$stats = $pdo->query(
    "SELECT (SELECT COUNT(*) FROM users) AS total_users,
            (SELECT COUNT(*) FROM campaigns WHERE status='pending') AS pending_count,
            (SELECT COUNT(*) FROM campaigns WHERE status='active') AS active_count,
            (SELECT COALESCE(SUM(raised_amount),0) FROM campaigns) AS total_raised,
            (SELECT COUNT(*) FROM contributions) AS total_contributions"
)->fetch();

$pending = $pdo->query(
    "SELECT c.*, u.name AS creator_name, cat.name AS cat_name FROM campaigns c
     JOIN users u ON u.id = c.user_id LEFT JOIN categories cat ON cat.id = c.category_id
     WHERE c.status = 'pending' ORDER BY c.created_at ASC"
)->fetchAll();

$allCampaigns = $pdo->query(
    "SELECT c.*, u.name AS creator_name, cat.name AS cat_name FROM campaigns c
     JOIN users u ON u.id = c.user_id LEFT JOIN categories cat ON cat.id = c.category_id
     ORDER BY c.created_at DESC"
)->fetchAll();

$users = $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🥨 <?= e(SITE_NAME) ?> <small style="color:var(--gold)">ADMIN</small></div>
    <div class="nav-links">
        <a href="index.php">Site</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php" class="nav-btn">Logout</a>
    </div>
</nav>

<div class="dashboard-wrap" style="max-width:1100px">
    <div class="dashboard-header">
        <h2>🛠️ Admin Panel</h2>
        <p>Review pending campaigns, manage users, and export platform data.</p>
    </div>

    <?php if (flash('success')): ?><div class="alert alert-success"><?= e(flash('success')) ?></div><?php endif; ?>

    <div class="stat-cards">
        <div class="stat-card"><div class="num"><?= (int) $stats['pending_count'] ?></div><div class="lbl">Pending Review</div></div>
        <div class="stat-card"><div class="num"><?= (int) $stats['active_count'] ?></div><div class="lbl">Active Campaigns</div></div>
        <div class="stat-card"><div class="num">Rs <?= number_format((float) $stats['total_raised']) ?></div><div class="lbl">Total Raised</div></div>
        <div class="stat-card"><div class="num"><?= (int) $stats['total_users'] ?></div><div class="lbl">Total Users</div></div>
    </div>

    <div class="tabs">
        <a href="?tab=pending" class="tab-btn <?= $tab === 'pending' ? 'active' : '' ?>" style="text-decoration:none;display:block;text-align:center">⏳ Pending (<?= count($pending) ?>)</a>
        <a href="?tab=campaigns" class="tab-btn <?= $tab === 'campaigns' ? 'active' : '' ?>" style="text-decoration:none;display:block;text-align:center">🥨 All Campaigns (<?= count($allCampaigns) ?>)</a>
        <a href="?tab=users" class="tab-btn <?= $tab === 'users' ? 'active' : '' ?>" style="text-decoration:none;display:block;text-align:center">👥 Users (<?= count($users) ?>)</a>
    </div>

    <?php if ($tab === 'pending'): ?>
        <?php if (!$pending): ?>
            <div class="empty-state"><div class="icon">✅</div><h3>No campaigns awaiting review</h3></div>
        <?php else: ?>
        <?php foreach ($pending as $c): ?>
        <div class="card" style="margin-bottom:1rem">
            <div class="card-body">
                <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:1rem">
                    <div>
                        <div style="font-size:.78rem;color:var(--text-light);margin-bottom:.3rem">by <?= e($c['creator_name']) ?> · <?= e($c['cat_name'] ?? 'Uncategorized') ?> · <?= e($c['city'] ?: 'N/A') ?></div>
                        <div class="card-title" style="font-size:1.1rem"><?= e($c['title']) ?></div>
                        <p style="color:var(--text-mid);font-size:.9rem;margin-top:.4rem"><?= e($c['description']) ?></p>
                        <div style="margin-top:.5rem;font-weight:700;color:var(--green-deep)">Goal: Rs <?= number_format((float) $c['goal_amount']) ?></div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:.5rem;min-width:140px">
                        <a href="edit-campaign.php?id=<?= (int) $c['id'] ?>" class="btn btn-outline btn-full">✏️ Edit First</a>
                        <form method="post"><input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="campaign_id" value="<?= (int) $c['id'] ?>"><button type="submit" name="set_status" value="active" class="btn btn-green btn-full">✓ Approve</button></form>
                        <form method="post" onsubmit="return confirm('Reject this campaign?')"><input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="campaign_id" value="<?= (int) $c['id'] ?>"><button type="submit" name="set_status" value="rejected" class="btn btn-outline btn-full" style="color:#c00;border-color:#c00">✕ Reject</button></form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    <?php elseif ($tab === 'campaigns'): ?>
        <div style="display:flex;justify-content:flex-end;gap:.6rem;margin-bottom:1rem">
            <a href="?export=campaigns" class="btn btn-outline btn-sm">⬇ Campaigns CSV</a>
            <a href="?export=contributions" class="btn btn-outline btn-sm">⬇ Contributions CSV</a>
        </div>
        <table class="table">
            <thead><tr><th>Title</th><th>Creator</th><th>Goal</th><th>Raised</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($allCampaigns as $c): ?>
                <tr>
                    <td><a href="campaign.php?id=<?= (int) $c['id'] ?>" target="_blank"><?= e($c['title']) ?></a></td>
                    <td><?= e($c['creator_name']) ?></td>
                    <td>Rs <?= number_format((float) $c['goal_amount']) ?></td>
                    <td>Rs <?= number_format((float) $c['raised_amount']) ?></td>
                    <td><span class="badge badge-<?= e($c['status']) ?>"><?= e(ucfirst($c['status'])) ?></span></td>
                    <td style="display:flex;gap:.4rem;align-items:center">
                        <a href="edit-campaign.php?id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                            <input type="hidden" name="campaign_id" value="<?= (int) $c['id'] ?>">
                            <select name="set_status" onchange="this.form.submit()" class="form-control" style="padding:.3rem .6rem;font-size:.8rem;width:auto;display:inline-block">
                                <option value="">Change status…</option>
                                <option value="pending" <?= $c['status']==='pending'?'selected':'' ?>>Pending</option>
                                <option value="active" <?= $c['status']==='active'?'selected':'' ?>>Active</option>
                                <option value="funded" <?= $c['status']==='funded'?'selected':'' ?>>Funded</option>
                                <option value="closed" <?= $c['status']==='closed'?'selected':'' ?>>Closed</option>
                                <option value="rejected" <?= $c['status']==='rejected'?'selected':'' ?>>Rejected</option>
                            </select>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php else: ?>
        <div style="display:flex;justify-content:flex-end;margin-bottom:1rem">
            <a href="?export=users" class="btn btn-outline btn-sm">⬇ Download CSV</a>
        </div>
        <table class="table">
            <thead><tr><th>Name</th><th>Email</th><th>City</th><th>Phone</th><th>Verified</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['name']) ?> <?= $u['is_admin'] ? '<span class="badge" style="background:#fff8e1;color:#e65100">Admin</span>' : '' ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['city'] ?: '—') ?></td>
                    <td><?= e($u['phone'] ?: '—') ?></td>
                    <td><?= $u['is_verified'] ? '✓ Verified' : '—' ?></td>
                    <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td style="display:flex;gap:.4rem">
                        <?php if (!$u['is_verified']): ?>
                        <form method="post"><input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><button type="submit" name="toggle_verified" value="<?= (int) $u['id'] ?>" class="btn btn-sm btn-outline">Verify</button></form>
                        <?php endif; ?>
                        <?php if ((int) $u['id'] !== (int) $user['id']): ?>
                        <form method="post" onsubmit="return confirm('<?= $u['is_admin'] ? 'Remove admin privileges from' : 'Grant admin privileges to' ?> <?= e($u['name']) ?>?')">
                            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                            <button type="submit" name="toggle_admin" value="<?= (int) $u['id'] ?>" class="btn btn-sm btn-outline"><?= $u['is_admin'] ? 'Revoke Admin' : 'Make Admin' ?></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
