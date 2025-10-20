<?php
require "../config/bootstrap.php";
require_login();
$user = current_user();

$rows = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC");
$rows->execute([$user['id']]);
$rows = $rows->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>🔔 Notifications</title>
  <link rel="stylesheet" href="../assets/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <h3>🔔 Your Notifications</h3>
  <?php if(empty($rows)): ?>
    <div class="alert alert-info mt-3">No notifications yet.</div>
  <?php else: ?>
    <ul class="list-group mt-3">
      <?php foreach($rows as $n): ?>
      <li class="list-group-item d-flex justify-content-between">
        <?= htmlspecialchars($n['message']) ?>
        <small class="text-muted"><?= date("M d, Y H:i", strtotime($n['created_at'])) ?></small>
      </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
</body>
</html>
