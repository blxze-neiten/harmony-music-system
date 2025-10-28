<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Artist']);
$user = current_user();

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'] === 'approve' ? 'accepted' : 'rejected';

    // Validate the collaboration
    $stmt = $pdo->prepare("SELECT * FROM producer_collabs WHERE id = ? AND artist_id = ?");
    $stmt->execute([$id, $user['id']]);
    $collab = $stmt->fetch();

    if ($collab) {
        // Update status
        $pdo->prepare("UPDATE producer_collabs SET status = ? WHERE id = ?")->execute([$action, $id]);

        // Notify producer
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
            ->execute([$collab['producer_id'], "🎶 Your collaboration request was {$action} by {$user['name']}"]);

        $msg = "Collaboration {$action} successfully!";
        header("Location: manage.php?msg=" . urlencode($msg));
        exit;
    } else {
        header("Location: manage.php?msg=Invalid collaboration request.");
        exit;
    }
}

// Fetch collaborations for this artist
$stmt = $pdo->prepare("
  SELECT pc.*, u.name AS producer_name, m.title AS song_title
  FROM producer_collabs pc
  JOIN users u ON pc.producer_id = u.id
  JOIN music m ON pc.music_id = m.id
  WHERE pc.artist_id = ?
  ORDER BY pc.created_at DESC
");
$stmt->execute([$user['id']]);
$collabs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>🤝 Manage Collaborations - Harmony</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="/harmony/assets/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #667eea, #764ba2);
      min-height: 100vh;
      padding-top: 80px;
    }

    .container { max-width: 900px; }

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
    .header-box p { color: #555; font-size: 15px; }

    .collab-card {
      background: rgba(255,255,255,0.97);
      border-radius: 15px;
      padding: 20px 25px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }
    .collab-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .collab-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .collab-header strong { font-size: 16px; color: #333; }
    .collab-header em { color: #764ba2; font-weight: 600; }

    .details { font-size: 14px; color: #555; margin-top: 10px; }

    .badge { padding: 6px 10px; border-radius: 8px; font-size: 13px; }

    form button {
      border-radius: 8px;
      font-weight: 500;
      padding: 6px 14px;
      transition: 0.3s;
    }

    form button:hover { transform: translateY(-2px); }

    .alert { border-radius: 10px; margin-bottom: 20px; }

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
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container">
  <div class="header-box">
    <h2>🤝 Collaboration Requests</h2>
    <p>Review, approve, or reject collaboration requests from producers.</p>
    <a href="/harmony/dashboard/dashboard.php" class="back-btn mt-2">← Back to Dashboard</a>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success text-center"><?= htmlspecialchars($_GET['msg']) ?></div>
  <?php endif; ?>

  <?php if (empty($collabs)): ?>
    <div class="alert alert-secondary text-center">No collaboration requests yet.</div>
  <?php else: ?>
    <?php foreach ($collabs as $r): 
      $status = $r['status'] ?? 'pending'; // ✅ Prevent undefined array key
    ?>
      <div class="collab-card mb-3">
        <div class="collab-header">
          <div>
            <strong><?= htmlspecialchars($r['producer_name']) ?></strong>
            <span>wants to collaborate on</span>
            <em><?= htmlspecialchars($r['song_title']) ?></em>
          </div>
          <div>
            <?php if ($status === 'accepted'): ?>
              <span class="badge bg-success">Accepted</span>
            <?php elseif ($status === 'rejected'): ?>
              <span class="badge bg-danger">Rejected</span>
            <?php else: ?>
              <span class="badge bg-warning text-dark">Pending</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="details">
          <p><strong>Revenue Share:</strong> <?= htmlspecialchars($r['revenue_share']) ?>%</p>
          <p><small class="text-muted">Requested on <?= date("M d, Y h:i A", strtotime($r['created_at'])) ?></small></p>
        </div>

        <?php if ($status === 'pending'): ?>
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

<script src="/harmony/assets/bootstrap.bundle.min.js"></script>
</body>
</html>
