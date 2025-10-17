<?php
require "../config/bootstrap.php";
require_login();

$user = current_user();
if ($user['role'] !== 'Admin') {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']);
    if ($message !== '') {
        $stmt = $pdo->query("SELECT id FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $insert = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)");
        foreach ($users as $uid) {
            $insert->execute([$uid, $message]);
        }
        $success = true;
    }
}
?>
<!doctype html>
<html>
<head>
  <link rel="stylesheet" href="../assets/bootstrap.min.css">
</head>
<body class="p-4">
  <h2>📢 Send Notification</h2>
  <?php if (!empty($success)): ?>
    <div class="alert alert-success">Notification sent to all users!</div>
  <?php endif; ?>
  <form method="post">
    <textarea name="message" class="form-control mb-3" placeholder="Enter message..." required></textarea>
    <button type="submit" class="btn btn-primary">Send</button>
  </form>
</body>
</html>