<?php
require "../config/bootstrap.php";
require_login();
$user = current_user();

try {
    $stmt = $pdo->prepare("
        SELECT id, message, created_at, is_read 
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark all as read
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user['id']]);
} catch (PDOException $e) {
    $notifications = [];
    error_log("Notifications fetch error: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>🔔 Notifications - Harmony</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="../assets/bootstrap.min.css" rel="stylesheet">
<link href="../assets/style.css" rel="stylesheet">

<style>
/* ================================
   🎶 HARMONY NOTIFICATIONS PAGE
================================ */
body {
  font-family: 'Poppins', 'Segoe UI', sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 120px 20px;
}

/* Container */
.notify-container {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 25px;
  padding: 40px 35px;
  width: 100%;
  max-width: 900px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
  backdrop-filter: blur(12px);
  animation: fadeIn 0.7s ease;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Header */
h2 {
  font-weight: 800;
  font-size: 2rem;
  background: linear-gradient(135deg, #5b4bcc, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-align: center;
  margin-bottom: 8px;
}
.subtitle {
  text-align: center;
  color: #555;
  margin-bottom: 30px;
  font-size: 15px;
}

/* Back button */
.back-link {
  display: inline-block;
  font-weight: 600;
  color: #5b4bcc;
  text-decoration: none;
  margin-bottom: 10px;
  transition: 0.3s;
}
.back-link:hover {
  color: #764ba2;
  text-decoration: underline;
}

/* Notification items */
.notification-item {
  background: rgba(255, 255, 255, 0.98);
  border-radius: 15px;
  padding: 15px 20px;
  margin-bottom: 15px;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
  border-left: 5px solid transparent;
  transition: all 0.3s ease;
}
.notification-item.unread {
  background: #f5f3ff;
  border-left-color: #764ba2;
}
.notification-item:hover {
  transform: scale(1.02);
  background: #ffffff;
}
.notification-message {
  font-size: 15px;
  color: #333;
  margin-bottom: 6px;
}
.notification-time {
  font-size: 13px;
  color: #777;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 70px 20px;
  color: #666;
}
.empty-state i {
  font-size: 3.5rem;
  color: #764ba2;
  display: block;
  margin-bottom: 10px;
  animation: pulse 2s infinite;
}
@keyframes pulse {
  0%,100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.1); opacity: 0.9; }
}

/* Buttons */
.btn-outline-light {
  border: 2px solid #764ba2;
  color: #764ba2;
  font-weight: 600;
  border-radius: 12px;
  padding: 10px 20px;
  transition: all 0.3s ease;
}
.btn-outline-light:hover {
  background: #764ba2;
  color: white;
  box-shadow: 0 8px 20px rgba(118, 75, 162, 0.4);
}
</style>
</head>
<body>

<div class="notify-container">
  <a href="/harmony/dashboard/dashboard.php" class="back-link">← Back to Dashboard</a>

  <h2>🔔 Notifications</h2>
  <p class="subtitle">Stay informed with Harmony system updates and alerts.</p>

  <?php if (empty($notifications)): ?>
    <div class="empty-state">
      <i>📭</i>
      <p>No notifications yet. We’ll keep you updated!</p>
    </div>
  <?php else: ?>
    <?php foreach ($notifications as $n): ?>
      <div class="notification-item <?= !$n['is_read'] ? 'unread' : '' ?>">
        <div class="notification-message"><?= htmlspecialchars($n['message']) ?></div>
        <div class="notification-time"><?= date("M d, Y • h:i A", strtotime($n['created_at'])) ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

<script src="../assets/bootstrap.bundle.min.js"></script>
</body>
</html>
