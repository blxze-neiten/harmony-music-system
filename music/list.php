<?php
require "../config/bootstrap.php";
require_login();

$user = current_user();

// 🎧 Fetch songs depending on role
if ($user['role'] === 'Artist') {
    // Artist: show only their songs
    $stmt = $pdo->prepare("
        SELECT 
            m.id, m.title, m.genre, m.views, m.file_path, m.created_at,
            (SELECT COUNT(*) FROM likes l WHERE l.music_id = m.id AND l.reaction = 'like') AS like_count,
            (SELECT COUNT(*) FROM likes l WHERE l.music_id = m.id AND l.reaction = 'dislike') AS dislike_count,
            (SELECT COUNT(*) FROM comments c WHERE c.music_id = m.id) AS comment_count
        FROM music m
        WHERE m.artist_id = ?
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $heading = "🎧 My Music Library";
    $subtitle = "Manage, preview, and monitor your uploaded songs.";
} else {
    // Admin / Producer: show all songs
    $stmt = $pdo->query("
        SELECT 
            m.*, u.name AS artist_name,
            (SELECT COUNT(*) FROM likes l WHERE l.music_id = m.id AND l.reaction = 'like') AS like_count,
            (SELECT COUNT(*) FROM likes l WHERE l.music_id = m.id AND l.reaction = 'dislike') AS dislike_count,
            (SELECT COUNT(*) FROM comments c WHERE c.music_id = m.id) AS comment_count
        FROM music m
        JOIN users u ON m.artist_id = u.id
        ORDER BY m.created_at DESC
    ");
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $heading = "🎵 All Uploaded Music";
    $subtitle = "Explore and manage songs uploaded by artists on Harmony.";
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($heading) ?> - Harmony</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="../assets/bootstrap.min.css" rel="stylesheet">
<link href="../assets/style.css" rel="stylesheet">

<style>
body {
  font-family: 'Poppins', 'Segoe UI', sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  padding: 140px 15px;
  display: flex;
  justify-content: center;
  align-items: flex-start;
}
.music-container {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 25px;
  padding: 50px;
  max-width: 1200px;
  width: 100%;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
  backdrop-filter: blur(15px);
  animation: fadeIn 0.7s ease;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}
h2 {
  font-weight: 800;
  font-size: 2.3rem;
  background: linear-gradient(135deg, #5b4bcc, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-align: center;
  margin-bottom: 10px;
}
.subtitle {
  color: #555;
  text-align: center;
  margin-bottom: 40px;
}
.back-link {
  font-weight: 600;
  color: #5b4bcc;
  text-decoration: none;
  display: inline-block;
  margin-bottom: 15px;
  transition: 0.3s;
}
.back-link:hover {
  color: #764ba2;
  text-decoration: underline;
}
.btn-primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  color: white;
  border-radius: 12px;
  padding: 12px 24px;
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
  transition: all 0.3s;
}
.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.5);
}
.table {
  border-radius: 15px;
  background: white;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}
.table thead {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}
.table th, .table td {
  text-align: center;
  vertical-align: middle;
  padding: 15px;
  font-size: 15px;
}
.table-hover tbody tr:hover {
  background: rgba(102, 126, 234, 0.08);
}
a.fw-bold {
  color: #5b4bcc;
  font-weight: 700;
  transition: 0.3s;
}
a.fw-bold:hover {
  color: #764ba2;
  text-decoration: underline;
}
audio {
  width: 220px;
  height: 35px;
}
.empty-state {
  text-align: center;
  padding: 80px 20px;
  color: #666;
}
.empty-state i {
  font-size: 3.8rem;
  color: #764ba2;
  display: block;
  margin-bottom: 12px;
  animation: pulse 2s infinite;
}
@keyframes pulse {
  0%,100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.1); opacity: 0.9; }
}
</style>
</head>
<body>

<div class="music-container">
  <a href="/harmony/dashboard/dashboard.php" class="back-link">← Back to Dashboard</a>

  <h2><?= htmlspecialchars($heading) ?></h2>
  <p class="subtitle"><?= htmlspecialchars($subtitle) ?></p>

  <?php if ($user['role'] === 'Artist'): ?>
    <div class="text-end mb-3">
      <a href="/harmony/music/upload.php" class="btn btn-primary">🎵 Upload New Song</a>
    </div>
  <?php endif; ?>

  <?php if (empty($songs)): ?>
    <div class="empty-state">
      <i>🎼</i>
      <p>No songs uploaded yet.</p>
      <?php if ($user['role'] === 'Artist'): ?>
      <a href="/harmony/music/upload.php" class="btn btn-primary mt-3">Upload Now</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-responsive mt-4">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Title</th>
            <?php if ($user['role'] !== 'Artist'): ?><th>Artist</th><?php endif; ?>
            <th>Genre</th>
            <th>Preview</th>
            <th>Views</th>
            <th>Likes</th>
            <th>Dislikes</th>
            <th>Comments</th>
            <th>Date Uploaded</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($songs as $index => $song): ?>
          <tr>
            <td><?= $index + 1 ?></td>
            <td>
              <a href="/harmony/music/music_details.php?id=<?= $song['id'] ?>" class="fw-bold text-decoration-none">
                <?= htmlspecialchars($song['title']) ?>
              </a>
            </td>
            <?php if ($user['role'] !== 'Artist'): ?>
              <td><?= htmlspecialchars($song['artist_name'] ?? '-') ?></td>
            <?php endif; ?>
            <td><?= htmlspecialchars($song['genre'] ?: '-') ?></td>
            <td>
              <?php if (!empty($song['file_path'])): ?>
                <audio controls preload="none">
  <source src="<?= htmlspecialchars($song['file_path']) ?>" type="audio/mpeg">
  Your browser does not support audio playback.
</audio>

              <?php else: ?>
                <span class="text-muted">No file</span>
              <?php endif; ?>
            </td>
            <td><?= number_format($song['views'] ?? 0) ?></td>
            <td>❤️ <?= number_format($song['like_count'] ?? 0) ?></td>
            <td>👎 <?= number_format($song['dislike_count'] ?? 0) ?></td>
            <td>💬 <?= number_format($song['comment_count'] ?? 0) ?></td>
            <td><small><?= date("M d, Y", strtotime($song['created_at'])) ?></small></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
