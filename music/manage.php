<?php
require "../config/bootstrap.php";
require_login();
require_roles(['Admin', 'Producer']); // Restrict access

$user = current_user();

// Handle actions (Approve, Feature, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['song_id'])) {
    $song_id = intval($_POST['song_id']);
    $action = $_POST['action'];
    $msg = "";

    switch ($action) {
        case 'approve':
            $stmt = $pdo->prepare("UPDATE music SET status = 'approved' WHERE id = ?");
            $stmt->execute([$song_id]);
            $msg = "✅ Song approved successfully!";
            break;

        case 'feature':
            $stmt = $pdo->prepare("UPDATE music SET featured = 1 WHERE id = ?");
            $stmt->execute([$song_id]);
            $msg = "🌟 Song featured successfully!";
            break;

        case 'delete':
            // Optional: delete song file from server
            $stmt = $pdo->prepare("SELECT file_path FROM music WHERE id = ?");
            $stmt->execute([$song_id]);
            $file = $stmt->fetchColumn();
            if ($file && file_exists("../music/" . $file)) {
                unlink("../music/" . $file);
            }

            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM music WHERE id = ?");
            $stmt->execute([$song_id]);
            $msg = "🗑️ Song deleted successfully!";
            break;
    }

    // Redirect to avoid form re-submission
    header("Location: manage.php?msg=" . urlencode($msg));
    exit;
}

// Fetch all songs with artist details
$stmt = $pdo->query("
  SELECT m.*, u.name AS artist_name, u.email
  FROM music m
  JOIN users u ON m.artist_id = u.id
  ORDER BY m.created_at DESC
");
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>🎛️ Manage Music - Harmony</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="../assets/bootstrap.min.css" rel="stylesheet">
<style>
body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  padding: 120px 20px;
  display: flex;
  justify-content: center;
  align-items: flex-start;
}
.container-box {
  background: rgba(255,255,255,0.95);
  border-radius: 25px;
  padding: 40px;
  width: 100%;
  max-width: 1100px;
  box-shadow: 0 20px 45px rgba(0,0,0,0.25);
  backdrop-filter: blur(15px);
  animation: fadeIn 0.6s ease;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}
h2 {
  font-weight: 800;
  font-size: 2rem;
  background: linear-gradient(135deg, #5b4bcc, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-align: center;
  margin-bottom: 15px;
}
.subtitle {
  color: #555;
  text-align: center;
  margin-bottom: 30px;
}
.table {
  background: white;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}
.table thead {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  text-transform: uppercase;
  font-size: 0.9rem;
}
.table-hover tbody tr:hover {
  background: rgba(118, 75, 162, 0.1);
  transform: scale(1.01);
  transition: all 0.2s ease;
}
audio {
  width: 140px;
  height: 30px;
}
.btn-sm {
  font-size: 0.8rem;
  border-radius: 8px;
  padding: 4px 10px;
}
.btn-approve {
  background: linear-gradient(135deg, #4CAF50, #66BB6A);
  color: #fff;
  border: none;
}
.btn-feature {
  background: linear-gradient(135deg, #ff9800, #ffc107);
  color: #fff;
  border: none;
}
.btn-delete {
  background: linear-gradient(135deg, #e53935, #ef5350);
  color: #fff;
  border: none;
}
.back-link {
  color: #5b4bcc;
  text-decoration: none;
  font-weight: 600;
}
.back-link:hover {
  color: #764ba2;
  text-decoration: underline;
}
.alert {
  border-radius: 10px;
  padding: 12px 20px;
  background: #e3f2fd;
  color: #333;
  font-weight: 500;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  margin-bottom: 20px;
}
</style>
</head>
<body>

<div class="container-box">
  <a href="/harmony/dashboard/dashboard.php" class="back-link mb-3 d-inline-block">← Back to Dashboard</a>
  <h2>🎛️ Manage Uploaded Music</h2>
  <p class="subtitle">Review, approve, feature, or delete songs uploaded by artists.</p>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert"><?= htmlspecialchars($_GET['msg']) ?></div>
  <?php endif; ?>

  <?php if (empty($songs)): ?>
    <p class="text-center text-muted">No songs have been uploaded yet.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Title</th>
            <th>Artist</th>
            <th>Email</th>
            <th>Preview</th>
            <th>Status</th>
            <th>Views</th>
            <th>Likes</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($songs as $i => $song): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><strong><?= htmlspecialchars($song['title']) ?></strong></td>
            <td><?= htmlspecialchars($song['artist_name']) ?></td>
            <td><?= htmlspecialchars($song['email']) ?></td>
            <td>
              <?php if ($song['file_path']): ?>
                <audio controls preload="none">
                  <source src="/harmony/music/<?= htmlspecialchars($song['file_path']) ?>" type="audio/mpeg">
                </audio>
              <?php else: ?>
                <span class="text-muted">No file</span>
              <?php endif; ?>
            </td>
            <td>
              <?= htmlspecialchars($song['status'] ?? 'pending') ?>
              <?= ($song['featured'] ?? 0) ? '🌟' : '' ?>
            </td>
            <td><?= number_format($song['views'] ?? 0) ?></td>
            <td>❤️ <?= number_format($song['likes'] ?? 0) ?></td>
            <td><small><?= date("M d, Y", strtotime($song['created_at'])) ?></small></td>
            <td>
              <form method="post" style="display:inline;">
                <input type="hidden" name="song_id" value="<?= $song['id'] ?>">
                <button type="submit" name="action" value="approve" class="btn btn-sm btn-approve">Approve</button>
              </form>
              <form method="post" style="display:inline;">
                <input type="hidden" name="song_id" value="<?= $song['id'] ?>">
                <button type="submit" name="action" value="feature" class="btn btn-sm btn-feature">Feature</button>
              </form>
              <form method="post" style="display:inline;" onsubmit="return confirm('Delete this song?');">
                <input type="hidden" name="song_id" value="<?= $song['id'] ?>">
                <button type="submit" name="action" value="delete" class="btn btn-sm btn-delete">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
