<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Admin']);
$user = current_user();

$roles = $pdo->query("SELECT name FROM roles ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$success = $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target = $_POST['target'] ?? 'all';
    $role = $_POST['role'] ?? '';
    $message = trim($_POST['message'] ?? '');
    if (!$message) { $error = "Message required"; }
    else {
        try {
            $pdo->beginTransaction();
            if ($target === 'all') {
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id,message) SELECT id, ? FROM users");
                $stmt->execute([$message]);
                $count = $stmt->rowCount();
            } else {
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id,message) SELECT u.id, ? FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = ?");
                $stmt->execute([$message, $role]);
                $count = $stmt->rowCount();
            }
            $pdo->prepare("INSERT INTO notification_history (admin_id,message,target_type,target_value,recipient_count) VALUES (?,?,?,?,?)")
                ->execute([$user['id'],$message,$target,$role,$count]);
            $pdo->commit();
            $success = "✅ Successfully sent notification to {$count} users!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "❌ Failed: " . $e->getMessage();
        }
    }
}

// Get recent notification history
$history = $pdo->prepare("SELECT nh.*, u.name AS admin_name FROM notification_history nh JOIN users u ON nh.admin_id = u.id ORDER BY nh.created_at DESC LIMIT 5");
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
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 40px 20px;
  }

  .container {
    max-width: 900px;
    margin: 0 auto;
  }

  .page-header {
    background: white;
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    animation: slideDown 0.5s ease-out;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .page-header h3 {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
  }

  .alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    font-size: 0.95rem;
    font-weight: 500;
    animation: shake 0.4s;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  }

  .alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border: 2px solid #28a745;
  }

  .alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border: 2px solid #dc3545;
  }

  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
  }

  .form-card {
    background: white;
    border-radius: 20px;
    padding: 35px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    animation: fadeInUp 0.5s ease-out 0.2s backwards;
    margin-bottom: 30px;
  }

  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .form-group {
    margin-bottom: 25px;
  }

  label {
    display: block;
    margin-bottom: 10px;
    color: #333;
    font-weight: 600;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .form-select,
  .form-control {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: #fff;
  }

  .form-select:focus,
  .form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
  }

  textarea.form-control {
    min-height: 120px;
    resize: vertical;
  }

  .btn-primary {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    margin-top: 10px;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
  }

  .btn-primary:active {
    transform: translateY(0);
  }

  #roleBox {
    overflow: hidden;
    transition: all 0.3s ease;
  }

  .history-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    animation: fadeInUp 0.5s ease-out 0.4s backwards;
  }

  .history-card h4 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .history-item {
    padding: 15px;
    border-left: 4px solid #667eea;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
  }

  .history-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  }

  .history-meta {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 8px;
  }

  .history-message {
    color: #333;
    font-size: 0.95rem;
    margin-bottom: 8px;
  }

  .history-stats {
    font-size: 0.85rem;
    color: #667eea;
    font-weight: 600;
  }

  .empty-history {
    text-align: center;
    padding: 40px;
    color: #999;
  }

  @media (max-width: 768px) {
    .page-header h3 {
      font-size: 1.5rem;
    }

    .form-card,
    .history-card {
      padding: 25px;
    }
  }
</style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container">
  <div class="page-header">
    <h3>📢 Send Notifications</h3>
  </div>

  <?php if($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
  <?php endif; ?>
  
  <?php if($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
  <?php endif; ?>

  <div class="form-card">
    <form method="post">
      <div class="form-group">
        <label>📍 Target Audience</label>
        <select name="target" id="target" class="form-select">
          <option value="all">All Users</option>
          <option value="role">Specific Role</option>
        </select>
      </div>

      <div id="roleBox" style="display:none;">
        <div class="form-group">
          <label>👥 Select Role</label>
          <select name="role" class="form-select">
            <?php foreach($roles as $r): ?>
              <option value="<?= e($r) ?>"><?= e($r) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>✉️ Message</label>
        <textarea name="message" class="form-control" placeholder="Enter your notification message..." required></textarea>
      </div>

      <button type="submit" class="btn-primary">📤 Send Notification</button>
    </form>
  </div>

  <?php if(!empty($recentNotifications)): ?>
    <div class="history-card">
      <h4>📜 Recent Notifications</h4>
      <?php foreach($recentNotifications as $notif): ?>
        <div class="history-item">
          <div class="history-meta">
            Sent by <strong><?= e($notif['admin_name']) ?></strong> • 
            <?= date('M d, Y g:i A', strtotime($notif['sent_at'])) ?>
          </div>
          <div class="history-message">
            "<?= e($notif['message']) ?>"
          </div>
          <div class="history-stats">
            📊 Sent to <?= $notif['recipient_count'] ?> users 
            (Target: <?= $notif['target_type'] === 'all' ? 'All Users' : e($notif['target_value']) ?>)
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="history-card">
      <h4>📜 Recent Notifications</h4>
      <div class="empty-history">
        <p>No notifications sent yet.</p>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="/harmony/assets/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('target').addEventListener('change', function(){
  const roleBox = document.getElementById('roleBox');
  if (this.value === 'role') {
    roleBox.style.display = 'block';
    roleBox.style.maxHeight = '200px';
  } else {
    roleBox.style.maxHeight = '0';
    setTimeout(() => {
      roleBox.style.display = 'none';
    }, 300);
  }
});
</script>
</body>
</html>