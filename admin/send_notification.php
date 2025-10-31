<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Admin']);
$user = current_user();

$success = $error = null;

// Handle notification form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target = $_POST['target'] ?? 'all';
    $message = trim($_POST['message'] ?? '');

    if (!$message) {
        $error = "❌ Please enter a notification message.";
    } else {
        try {
            $pdo->beginTransaction();

            switch ($target) {
                case 'all':
                    // Everyone
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message)
                                           SELECT id, ? FROM users");
                    $stmt->execute([$message]);
                    $count = $stmt->rowCount();
                    $targetLabel = "Everyone";
                    break;

                case 'artists':
                    // All artists
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message)
                                           SELECT u.id, ? FROM users u
                                           JOIN roles r ON u.role_id = r.id
                                           WHERE LOWER(r.name) = 'artist'");
                    $stmt->execute([$message]);
                    $count = $stmt->rowCount();
                    $targetLabel = "Artists";
                    break;

                case 'producers':
                    // All producers
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message)
                                           SELECT u.id, ? FROM users u
                                           JOIN roles r ON u.role_id = r.id
                                           WHERE LOWER(r.name) = 'producer'");
                    $stmt->execute([$message]);
                    $count = $stmt->rowCount();
                    $targetLabel = "Producers";
                    break;

                case 'users':
                    // All normal users
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message)
                                           SELECT u.id, ? FROM users u
                                           JOIN roles r ON u.role_id = r.id
                                           WHERE LOWER(r.name) = 'user'");
                    $stmt->execute([$message]);
                    $count = $stmt->rowCount();
                    $targetLabel = "Regular Users";
                    break;

                default:
                    throw new Exception("Invalid target selection.");
            }

            // Save notification history
            $pdo->prepare("INSERT INTO notification_history 
                (admin_id, message, target_type, target_value, recipient_count)
                VALUES (?, ?, ?, ?, ?)")
                ->execute([$user['id'], $message, 'group', $targetLabel, $count]);

            $pdo->commit();
            $success = "✅ Notification successfully sent to {$count} {$targetLabel}!";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "❌ Failed to send notification: " . $e->getMessage();
        }
    }
}

// Fetch recent notifications
$history = $pdo->prepare("SELECT nh.*, u.name AS admin_name 
                          FROM notification_history nh 
                          JOIN users u ON nh.admin_id = u.id 
                          ORDER BY nh.created_at DESC LIMIT 6");
$history->execute();
$recentNotifications = $history->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Send Notifications - Harmony Admin</title>
<link rel="stylesheet" href="/harmony/assets/bootstrap.min.css">
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 40px 20px;
  }
  .container { max-width: 900px; margin: 0 auto; }
  .page-header {
    background: white; border-radius: 20px; padding: 30px;
    margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  }
  .page-header h3 {
    font-size: 2rem; font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  }
  .alert {
    padding: 15px 20px; border-radius: 12px; margin-bottom: 25px;
    font-size: 0.95rem; font-weight: 500;
  }
  .alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724; border: 2px solid #28a745;
  }
  .alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24; border: 2px solid #dc3545;
  }
  .form-card {
    background: white; border-radius: 20px; padding: 35px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    margin-bottom: 30px;
  }
  label { font-weight: 600; color: #333; }
  .form-select, .form-control {
    width: 100%; padding: 14px 16px; border: 2px solid #e0e0e0;
    border-radius: 10px; font-size: 15px;
    transition: all 0.3s ease;
  }
  .form-select:focus, .form-control:focus {
    border-color: #667eea; box-shadow: 0 0 0 4px rgba(102,126,234,0.1);
  }
  textarea.form-control { min-height: 120px; resize: vertical; }
  .btn-primary {
    width: 100%; padding: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white; border: none; border-radius: 10px;
    font-weight: 600; box-shadow: 0 4px 15px rgba(102,126,234,0.4);
  }
  .btn-primary:hover { transform: translateY(-2px); }
  .history-card {
    background: white; border-radius: 20px; padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  }
  .history-item {
    padding: 15px; border-left: 4px solid #667eea;
    background: #f8f9fa; border-radius: 8px; margin-bottom: 15px;
  }
  .history-meta { font-size: 0.85rem; color: #666; }
  .history-message { color: #333; margin-bottom: 8px; }
  .history-stats { font-size: 0.85rem; color: #667eea; font-weight: 600; }
</style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container">
  <div class="page-header">
    <h3>📢 Send Notifications</h3>
  </div>

  <?php if($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
  <?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

  <div class="form-card">
    <form method="post">
      <div class="form-group mb-3">
        <label>📍 Select Target Group</label>
        <select name="target" class="form-select" required>
          <option value="all">Everyone </option>
          <option value="artists">All Artists</option>
          <option value="producers">All Producers</option>
          <option value="users">All Regular Users</option>
        </select>
      </div>

      <div class="form-group mb-3">
        <label>✉️ Notification Message</label>
        <textarea name="message" class="form-control" placeholder="Enter your message..." required></textarea>
      </div>

      <button type="submit" class="btn-primary">📤 Send Notification</button>
    </form>
  </div>

  <div class="history-card">
    <h4>📜 Recent Notifications</h4>
    <?php if(empty($recentNotifications)): ?>
      <p class="text-center text-muted">No notifications sent yet.</p>
    <?php else: ?>
      <?php foreach($recentNotifications as $notif): ?>
        <div class="history-item">
          <div class="history-meta">
            Sent by <strong><?= e($notif['admin_name']) ?></strong> • <?= date('M d, Y g:i A', strtotime($notif['created_at'])) ?>
          </div>
          <div class="history-message">"<?= e($notif['message']) ?>"</div>
          <div class="history-stats">📊 Target: <?= e($notif['target_value']) ?> • Sent to <?= $notif['recipient_count'] ?> users</div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script src="/harmony/assets/bootstrap.bundle.min.js"></script>
</body>
</html>
