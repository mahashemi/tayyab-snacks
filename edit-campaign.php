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
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">You do not have permission to edit this campaign. <a href="campaign.php?id=' . $id . '">Go back</a></p>');
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $goalAmount  = (float) ($_POST['goal_amount'] ?? 0);
    $categoryId  = (int) ($_POST['category_id'] ?? 0);
    $city        = trim($_POST['city'] ?? '');
    $deadline    = trim($_POST['deadline'] ?? '');
    $status      = $_POST['status'] ?? $campaign['status'];

    if (mb_strlen($title) < 8) $errors[] = 'Title must be at least 8 characters.';
    if (mb_strlen($description) < 30) $errors[] = 'Description must be at least 30 characters.';
    if ($goalAmount < 1000) $errors[] = 'Goal amount must be at least Rs 1,000.';
    if (!$isAdmin) $status = $campaign['status']; // only admin may change status directly here

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE campaigns SET title=?, description=?, goal_amount=?, category_id=?, city=?, deadline=?, status=?, updated_by=?, updated_at=NOW()
             WHERE id=?'
        );
        $stmt->execute([$title, $description, $goalAmount, $categoryId ?: null, $city, $deadline ?: null, $status, $user['id'], $id]);
        flash('success', 'Campaign updated.');
        redirect('campaign.php?id=' . $id);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Campaign — <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🥨 <?= e(SITE_NAME) ?></div>
    <div class="nav-links"><a href="dashboard.php">Dashboard</a><a href="logout.php" class="nav-btn">Logout</a></div>
</nav>

<div class="dashboard-wrap">
    <div class="dashboard-header">
        <h2>✏️ Edit Campaign</h2>
        <p><?= $isAdmin && !$isOwner ? 'You are editing this campaign as an admin.' : 'Update your campaign details below.' ?></p>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="card"><div class="card-body">
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">

            <div class="form-group">
                <label class="form-label">Campaign Title</label>
                <input type="text" name="title" class="form-control" value="<?= e($_POST['title'] ?? $campaign['title']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" required><?= e($_POST['description'] ?? $campaign['description']) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-control">
                        <option value="">Select category</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= $campaign['category_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['icon']) ?> <?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="<?= e($_POST['city'] ?? $campaign['city']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Funding Goal (Rs)</label>
                    <input type="number" name="goal_amount" class="form-control" min="1000" step="100" value="<?= e($_POST['goal_amount'] ?? $campaign['goal_amount']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Deadline</label>
                    <input type="date" name="deadline" class="form-control" value="<?= e($campaign['deadline'] ?? '') ?>">
                </div>
            </div>

            <?php if ($isAdmin): ?>
            <div class="form-group">
                <label class="form-label">Status (admin only)</label>
                <select name="status" class="form-control">
                    <?php foreach (['pending','active','funded','closed','rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $campaign['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div style="display:flex;gap:.8rem">
                <button type="submit" class="btn btn-amber">Save Changes</button>
                <a href="campaign.php?id=<?= $id ?>" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div></div>
</div>
</body>
</html>
