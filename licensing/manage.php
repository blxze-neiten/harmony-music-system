<?php
require "../config/bootstrap.php";
require_roles(['Artist']);
$user = current_user();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'] ?? 'reject';
    $status = ($action === 'approve') ? 'approved' : 'rejected';

    // Update status
    $pdo->prepare("UPDATE licensing_requests SET status=? WHERE id=?")->execute([$status, $id]);

    // Notify requester
    $stmt = $pdo->prepare("
        SELECT lr.requester_id, m.title 
        FROM licensing_requests lr 
        JOIN music m ON lr.music_id = m.id 
        WHERE lr.id = ?
    ");
    $stmt->execute([$id]);
    if ($info = $stmt->fetch()) {
        $message = "📜 Your licensing request for '{$info['title']}' has been {$status}.";
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$info['requester_id'], $message]);
    }

    header("Location: manage.php?msg=" . urlencode("Request {$status} successfully!"));
    exit;
}

// Fetch artist’s licensing requests
$stmt = $pdo->prepare("
  SELECT lr.*, m.title, u.name AS requester_name
  FROM licensing_requests lr
  JOIN music m ON lr.music_id = m.id
  JOIN users u ON lr.requester_id = u.id
  WHERE m.artist_id = ?
  ORDER BY lr.created_at DESC
");
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>🎼 Licensing Requests - Harmony</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="../assets/bootstrap.min.css" rel="stylesheet">
<style>
body {
  font-family: 'Segoe UI', sans-serif;
  background: linear-gradient(135deg, #667eea, #764ba2);
  min-height: 100vh;
  padding-top: 80px;
}

/* Container */
.container {
  max-width: 900px;
}

/* Header */
.header-box {
  background: rgba(255,255,255,0.9);
  border-radius: 15px;
  padding: 25px 30px;
  text-align: center;
  box-shadow: 0 5px 20px rgba(0,0,0,0.15);
  margin-bottom: 30px;
  backdrop-filter: blur(10px);
}
.header-box h2 {
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 700;
}
.header-box p {
  color: #555;
  font-size: 15px;
}

/* Cards */
.request-card {
  background: rgba(255,255,255,0.97);
  border-radius: 15px;
  padding: 20px 25px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
}
.request-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
.request-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.request-header strong {
  font-size: 16px;
  color: #333;
}
.request-header em {
  color: #764ba2;
  font-weight: 600;
}
.details {
  font-size: 14px;
  color: #555;
  margin-top: 10px;
}
.badge {
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 13px;
}
form button {
  border-radius: 8px;
  font-weight: 500;
  padding: 6px 14px;
  transition: 0.3s;
}
form button:hover {
  transform: translateY(-2px);
}

/* Alerts */
.alert {
  border-radius: 10px;
  margin-bottom: 20px;
}

/* Back button */
.back-btn {
  display: inline-block;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  padding: 8px 20px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  transition: 0.3s;
}
.back-btn:hover {
  background: linear-gradient(135deg, #5a67d8, #6b46c1);
  transform: translateY(-2px);
}
</style>
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container">
  <div class="header-box">
    <h2>📜 Licensing Requests</h2>
    <p>Manage and respond to licensing requests for your uploaded songs.</p>
    <a href="/harmony/dashboard/dashboard.php" class="back-btn mt-2">← Back to Dashboard</a>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success text-center"><?= htmlspecialchars($_GET['msg']) ?></div>
  <?php endif; ?>

  <?php if (empty($requests)): ?>
    <div class="alert alert-secondary text-center">No licensing requests yet.</div>
  <?php else: ?>
    <?php foreach ($requests as $r): ?>
      <div class="request-card mb-3">
        <div class="request-header">
          <div>
            <strong><?= htmlspecialchars($r['requester_name']) ?></strong>
            <span>requested a license for</span>
            <em><?= htmlspecialchars($r['title']) ?></em>
          </div>
          <div>
            <?php if ($r['status'] === 'pending'): ?>
              <span class="badge bg-warning text-dark">Pending</span>
            <?php elseif ($r['status'] === 'approved'): ?>
              <span class="badge bg-success">Approved</span>
            <?php else: ?>
              <span class="badge bg-danger">Rejected</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="details">
          <p><strong>Usage:</strong> <?= nl2br(htmlspecialchars($r['usage_description'])) ?></p>
          <p><strong>Fee Offered:</strong> Ksh <?= number_format($r['fee_offered'], 2) ?></p>
          <p><small class="text-muted">Requested on <?= date("M d, Y h:i A", strtotime($r['created_at'])) ?></small></p>
        </div>

        <?php if ($r['status'] === 'pending'): ?>
          <form method="post" class="mt-2 text-end">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button name="action" value="approve" class="btn btn-success btn-sm me-2">✅ Approve</button>
            <button name="action" value="reject" class="btn btn-danger btn-sm">❌ Reject</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script src="../assets/bootstrap.bundle.min.js"></script>
</body>
</html>
