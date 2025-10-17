<?php
require "../config/bootstrap.php";
require_login();
$user = current_user();

// Mark all as read when visited
$pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$user['id']]);

// Fetch latest notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();
?>

<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Notifications - Harmony</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <?php include "../includes/navbar.php"; ?>

  <div class="container mt-5">
    <h3 class="mb-4 text-primary fw-bold">🔔 Your Notifications</h3>

    <?php if (count($notifications) === 0): ?>
      <div class="alert alert-info">You have no notifications yet.</div>
    <?php endif; ?>

    <div class="list-group shadow-sm rounded">
      <?php foreach ($notifications as $note): ?>
        <div class="list-group-item d-flex justify-content-between align-items-start <?= $note['is_read'] ? 'bg-light' : 'bg-white' ?>">
          <div>
            <p class="mb-1"><?= htmlspecialchars($note['message']) ?></p>
            <small class="text-muted"><?= date("M d, Y h:i A", strtotime($note['created_at'])) ?></small>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
