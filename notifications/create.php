<?php 
require "../config/bootstrap.php"; 
require_roles(['Admin']); // Only Admin can access 
$user = current_user();  

$success = $error = "";  

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target = $_POST['target'] ?? '';
    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        $error = "Please enter a message before sending.";
    } else {
        try {
            switch ($target) {
                case 'all':
                    $stmt = $pdo->query("SELECT id FROM users");
                    break;
                case 'artists':
                    $stmt = $pdo->query("SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE name='Artist')");
                    break;
                case 'producers':
                    $stmt = $pdo->query("SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE name='Producer')");
                    break;
                case 'users':
                    $stmt = $pdo->query("SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE name='User')");
                    break;
                default:
                    throw new Exception("Invalid recipient group selected.");
            }

            $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if ($recipients) {
                $insert = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)");
                foreach ($recipients as $uid) {
                    $insert->execute([$uid, $message]);
                }
                $success = "✅ Notification successfully sent to " . ucfirst($target) . ".";
            } else {
                $error = "No users found in this group.";
            }
        } catch (Exception $e) {
            $error = "Error sending notifications: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Send Notifications - Harmony</title>
  <link rel="stylesheet" href="/harmony/assets/bootstrap.min.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      overflow: hidden;
    }

    .notify-container {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 20px;
      padding: 40px 35px;
      max-width: 480px;
      width: 100%;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
      animation: fadeIn 0.6s ease;
      text-align: center;
      position: relative;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    h1 {
      font-size: 1.8rem;
      font-weight: 700;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 10px;
    }

    .subtitle {
      color: #666;
      margin-bottom: 25px;
      font-size: 14px;
    }

    .welcome {
      font-weight: 600;
      color: #333;
      margin-bottom: 15px;
      font-size: 15px;
    }

    label {
      text-align: left;
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      color: #333;
    }

    select, textarea {
      width: 100%;
      border-radius: 10px;
      border: 2px solid #e0e0e0;
      padding: 12px 15px;
      font-size: 15px;
      outline: none;
      transition: all 0.3s;
    }

    select:focus, textarea:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 4px rgba(102,126,234,0.15);
    }

    textarea {
      resize: none;
      height: 120px;
    }

    .btn-primary {
      width: 100%;
      padding: 12px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border: none;
      color: white;
      font-weight: 600;
      border-radius: 10px;
      margin-top: 15px;
      box-shadow: 0 6px 18px rgba(102,126,234,0.4);
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(102,126,234,0.5);
    }

    .alert {
      padding: 10px 15px;
      border-radius: 10px;
      margin-bottom: 15px;
      font-size: 14px;
    }

    .alert-success { background: #e6ffed; color: #0f5132; border: 1px solid #bcd0c7; }
    .alert-danger { background: #ffe6e6; color: #842029; border: 1px solid #f5c2c7; }

    /* Back button */
    .back-btn {
      display: inline-block;
      position: absolute;
      top: 15px;
      left: 15px;
      background: #eee;
      color: #333;
      font-weight: 600;
      border-radius: 8px;
      padding: 6px 14px;
      text-decoration: none;
      transition: all 0.2s ease;
      font-size: 14px;
    }

    .back-btn:hover {
      background: #ddd;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="notify-container">
    <a href="/harmony/dashboard/dashboard.php" class="back-btn">← Back to Dashboard</a>

    <h1>📢 Send Notification</h1>
    <p class="subtitle">Send updates or announcements to everyone, artists, producers, or users.</p>
    <p class="welcome">👋 Welcome, <strong><?= htmlspecialchars($user['name']) ?></strong> (<?= htmlspecialchars($user['role'] ?? 'Admin') ?>)</p>

    <?php if($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php elseif($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group mb-3">
        <label for="target">Select Recipient Group</label>
        <select name="target" id="target" required>
          <option value="">-- Choose Group --</option>
          <option value="all">🌍 Everyone</option>
          <option value="artists">🎤 All Artists</option>
          <option value="producers">🎧 All Producers</option>
          <option value="users">👥 All Users</option>
        </select>
      </div>

      <div class="form-group mb-3">
        <label for="message">Message</label>
        <textarea name="message" id="message" placeholder="Type your notification..." required></textarea>
      </div>

      <button type="submit" class="btn btn-primary">🚀 Send Notification</button>
    </form>
  </div>
</body>
</html>
