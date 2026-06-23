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
    if ($goalAmount < 1000) $errors[] = 'Goal amount must be at least $1,000.';
    if (!$isAdmin) $status = $campaign['status']; // only admin may change status directly here

    if (!$errors) {
        $imagePath = handleImageUpload('image', 'campaigns') ?? $campaign['image_url'];
        $stmt = $pdo->prepare(
            'UPDATE campaigns SET title=?, description=?, goal_amount=?, category_id=?, city=?, deadline=?, image_url=?, status=?, updated_by=?, updated_at=NOW()
             WHERE id=?'
        );
        $stmt->execute([$title, $description, $goalAmount, $categoryId ?: null, $city, $deadline ?: null, $imagePath, $status, $user['id'], $id]);
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
        <h2><i data-lucide="pencil" class="lucide-icon"></i> Edit Campaign</h2>
        <p><?= $isAdmin && !$isOwner ? 'You are editing this campaign as an admin.' : 'Update your campaign details below.' ?></p>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="card"><div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">

            <div class="form-group">
                <label class="form-label">Campaign Photo</label>
                <?php if ($campaign['image_url']): ?>
                    <img src="<?= e($campaign['image_url']) ?>" style="max-width:200px;border-radius:8px;margin-bottom:.6rem;display:block">
                <?php endif; ?>
                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <div class="form-hint">Upload a new photo to replace the current one, or leave blank to keep it.</div>
            </div>

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
                            <option value="<?= (int) $c['id'] ?>" <?= $campaign['category_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
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
                    <label class="form-label">Funding Goal ($)</label>
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
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="app.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
