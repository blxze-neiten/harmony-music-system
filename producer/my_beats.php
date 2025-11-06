<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Producer']); // only producers can access
$user = current_user();

$success = $error = "";

// ✅ Handle delete
if (isset($_GET['delete'])) {
    $beatId = (int) $_GET['delete'];

    // Producers are stored as 'artist_id' in the 'music' table for ownership
    $stmt = $pdo->prepare("SELECT file_path FROM music WHERE id = ? AND artist_id = ?");
    $stmt->execute([$beatId, $user['id']]);
    $beat = $stmt->fetch();

    if ($beat) {
        $filePath = __DIR__ . '/../' . ltrim($beat['file_path'], '/');
        if (file_exists($filePath)) unlink($filePath);

        $pdo->prepare("DELETE FROM music WHERE id = ? AND artist_id = ?")->execute([$beatId, $user['id']]);
        $success = "✅ Beat deleted successfully.";
    } else {
        $error = "❌ Beat not found or permission denied.";
    }
}

// ✅ Fetch uploaded beats (using artist_id)
$stmt = $pdo->prepare("SELECT * FROM music WHERE artist_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$beats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Uploaded Beats - Harmony</title>
<link rel="stylesheet" href="/harmony/assets/bootstrap.min.css">
<style>
  body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #667eea, #764ba2);
    min-height: 100vh;
    padding: 40px 20px;
  }
  .container {
    max-width: 1100px;
    margin: auto;
  }
  .page-header {
    background: white;
    border-radius: 20px;
    padding: 25px 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
  }
  .page-header h3 {
    font-weight: 700;
    font-size: 1.8rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .upload-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 10px 18px;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    transition: 0.3s;
  }
  .upload-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102,126,234,0.5);
  }
  .alert {
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 0.95rem;
  }
  .alert-success { background: #e7f9ee; color: #257a3e; border: 1px solid #bfecc8; }
  .alert-danger { background: #fdecea; color: #b41d1d; border: 1px solid #f5c2c2; }
  .table-container {
    background: white;
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
  }
  table {
    width: 100%;
    border-collapse: collapse;
  }
  th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px;
    text-align: left;
    border-radius: 8px 8px 0 0;
  }
  td {
    padding: 10px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
  }
  audio {
    width: 180px;
    height: 32px;
  }
  .btn-delete {
    background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 5px 12px;
    font-weight: 600;
    text-decoration: none;
  }
  .btn-delete:hover {
    box-shadow: 0 4px 12px rgba(238,90,111,0.5);
    transform: scale(1.05);
  }
  .no-beats {
    text-align: center;
    color: #777;
    padding: 40px;
  }
</style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container">
  <div class="page-header">
    <h3>🎵 My Uploaded Beats</h3>
    <a href="/harmony/producer/upload_beat.php" class="upload-btn">⬆️ Upload New Beat</a>
  </div>

  <?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="table-container">
    <?php if(empty($beats)): ?>
      <div class="no-beats">
        <p>You haven't uploaded any beats yet.</p>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Title</th>
            <th>Genre</th>
            <th>Preview</th>
            <th>Uploaded</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($beats as $i => $beat): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($beat['title']) ?></td>
              <td><?= htmlspecialchars($beat['genre'] ?? '-') ?></td>
              <td>
                <audio controls>
                  <source src="<?= htmlspecialchars($beat['file_path']) ?>" type="audio/mpeg">
                  Your browser does not support audio playback.
                </audio>
              </td>
              <td><?= date('M d, Y', strtotime($beat['created_at'])) ?></td>
              <td>
                <a href="?delete=<?= $beat['id'] ?>" 
                   onclick="return confirm('Are you sure you want to delete this beat?')"
                   class="btn-delete">🗑️ Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<script src="/harmony/assets/bootstrap.bundle.min.js"></script>
</body>
</html>
