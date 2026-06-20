<?php
require_once __DIR__ . '/db.php';
requireAuth();
$user = auth();

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

    if (mb_strlen($title) < 8) $errors[] = 'Title must be at least 8 characters.';
    if (mb_strlen($description) < 30) $errors[] = 'Description must be at least 30 characters — explain your campaign clearly.';
    if ($goalAmount < 1000) $errors[] = 'Goal amount must be at least Rs 1,000.';
    if ($deadline !== '' && strtotime($deadline) <= time()) $errors[] = 'Deadline must be a future date.';

    if (!$errors) {
        $imagePath = handleImageUpload('image', 'campaigns');
        $stmt = $pdo->prepare(
            'INSERT INTO campaigns (user_id, category_id, title, description, goal_amount, city, deadline, image_url, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], $categoryId ?: null, $title, $description, $goalAmount, $city, $deadline ?: null, $imagePath, 'pending']);
        $newId = (int) $pdo->lastInsertId();
        flash('success', 'Your campaign has been submitted for review! We will activate it shortly.');
        redirect('dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Start a Campaign — <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🥨 <?= e(SITE_NAME) ?></div>
    <div class="nav-links"><a href="campaigns.php">Campaigns</a><a href="dashboard.php">Dashboard</a><a href="logout.php" class="nav-btn">Logout</a></div>
</nav>

<div class="dashboard-wrap">
    <div class="dashboard-header">
        <h2>🚀 Start a Tayyab Snack Campaign</h2>
        <p>Tell the community about your halal snack idea — every campaign is reviewed before going live.</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="card"><div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">

            <div class="form-group">
                <label class="form-label">Campaign Photo (optional)</label>
                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <div class="form-hint">JPG, PNG, or WEBP. Max 5MB. Leave blank to use a category icon instead.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Campaign Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Help Us Launch Our Tayyab Kids Snack Line" value="<?= e($_POST['title'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" placeholder="Tell your story: what are you making, why it's tayyab, what the funds will be used for..." required><?= e($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-control">
                        <option value="">Select category</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= e($c['icon']) ?> <?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" placeholder="Karachi" value="<?= e($_POST['city'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Funding Goal (Rs)</label>
                    <input type="number" name="goal_amount" class="form-control" min="1000" step="100" placeholder="150000" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Deadline (optional)</label>
                    <input type="date" name="deadline" class="form-control">
                </div>
            </div>

            <button type="submit" class="btn btn-amber btn-full">Submit for Review</button>
        </form>
    </div></div>
</div>
</body>
</html>
