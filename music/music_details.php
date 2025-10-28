<?php
require "../config/bootstrap.php";
require_login();

$user = current_user();
$music_id = $_GET['id'] ?? null;

if (!$music_id) {
  header("Location: list.php");
  exit;
}

// 🎵 Fetch song details
$stmt = $pdo->prepare("
  SELECT m.*, u.name AS artist_name, u.id AS artist_id
  FROM music m
  JOIN users u ON m.artist_id = u.id
  WHERE m.id = ?
");
$stmt->execute([$music_id]);
$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
  echo "<h3>Song not found.</h3>";
  exit;
}

// 🎧 Count view
$pdo->prepare("UPDATE music SET views = views + 1 WHERE id = ?")->execute([$music_id]);

// ❤️ Handle Like / Dislike
if (isset($_POST['reaction'])) {
  $reaction = $_POST['reaction'];
  $check = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND music_id = ?");
  $check->execute([$user['id'], $music_id]);
  $existing = $check->fetch();

  if ($existing) {
    $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND music_id = ?")->execute([$user['id'], $music_id]);
    if ($reaction == $existing['reaction']) {
      header("Location: music_details.php?id=$music_id");
      exit;
    }
  }

  $pdo->prepare("INSERT INTO likes (user_id, music_id, reaction) VALUES (?, ?, ?)")->execute([$user['id'], $music_id, $reaction]);
  header("Location: music_details.php?id=$music_id");
  exit;
}

// 💬 Handle comments safely
if (isset($_POST['comment_submit'])) {
  $comment = trim($_POST['comment'] ?? '');
  $parent_id = $_POST['parent_id'] ?? null;
  
  // ✅ FIX: Ensure parent_id is NULL (not empty string)
  if ($parent_id === '' || $parent_id === '0') {
    $parent_id = null;
  }

  if ($comment !== '') {
    $stmt = $pdo->prepare("INSERT INTO comments (user_id, music_id, comment, parent_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['id'], $music_id, $comment, $parent_id]);
  }

  header("Location: music_details.php?id=$music_id");
  exit;
}

// 🗨️ Fetch comments
$comments = $pdo->prepare("
  SELECT c.*, u.name AS commenter
  FROM comments c
  JOIN users u ON c.user_id = u.id
  WHERE c.music_id = ? AND c.parent_id IS NULL
  ORDER BY c.created_at DESC
");
$comments->execute([$music_id]);
$comments = $comments->fetchAll(PDO::FETCH_ASSOC);

// 🧩 Replies fetcher
function getReplies($pdo, $comment_id) {
  $r = $pdo->prepare("
    SELECT c.*, u.name AS commenter
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE parent_id = ?
    ORDER BY c.created_at ASC
  ");
  $r->execute([$comment_id]);
  return $r->fetchAll(PDO::FETCH_ASSOC);
}

// ❤️ Reaction counts
$likeCount = $pdo->query("SELECT COUNT(*) FROM likes WHERE music_id = $music_id AND reaction = 'like'")->fetchColumn();
$dislikeCount = $pdo->query("SELECT COUNT(*) FROM likes WHERE music_id = $music_id AND reaction = 'dislike'")->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($song['title']) ?> - Harmony</title>
<link href="../assets/bootstrap.min.css" rel="stylesheet">
<link href="../assets/style.css" rel="stylesheet">
<style>
body {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #333;
  padding: 120px 20px;
  font-family: 'Segoe UI', sans-serif;
}
.music-card {
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.2);
  padding: 35px;
  max-width: 850px;
  margin: auto;
  animation: fadeIn 0.6s ease;
}
@keyframes fadeIn { from {opacity:0;transform:translateY(20px);} to {opacity:1;transform:translateY(0);} }
h2 {
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 700;
  margin-bottom: 10px;
}
audio {
  width: 100%;
  margin: 15px 0;
}
.reaction-btn {
  border: none;
  padding: 8px 18px;
  border-radius: 25px;
  color: white;
  font-weight: 600;
  transition: 0.3s;
}
.like { background: linear-gradient(135deg, #28a745, #66bb6a); }
.dislike { background: linear-gradient(135deg, #e53935, #ef5350); }
.reaction-btn:hover { transform: scale(1.05); box-shadow: 0 6px 15px rgba(0,0,0,0.2); }
.comment-box { margin-top: 30px; }
.comment {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 12px;
}
.reply {
  margin-left: 40px;
  background: #f3f0ff;
  border-left: 3px solid #764ba2;
}
.comment small { color: #666; }
.comment-form textarea {
  border-radius: 10px;
  padding: 10px;
  width: 100%;
  resize: none;
}
.btn-reply {
  background: transparent;
  border: none;
  color: #667eea;
  cursor: pointer;
  font-size: 0.9rem;
}
a.back {
  color: #764ba2;
  text-decoration: none;
  font-weight: 600;
}
a.back:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="music-card">
  <a href="/harmony/music/list.php" class="back">← Back to Library</a>
  <h2><?= htmlspecialchars($song['title']) ?></h2>
  <p>By <strong><?= htmlspecialchars($song['artist_name']) ?></strong> • <?= htmlspecialchars($song['genre']) ?></p>

  <audio controls>
    <source src="/harmony/music/<?= htmlspecialchars($song['file_path']) ?>" type="audio/mpeg">
  </audio>

  <form method="post" class="mt-3 d-flex gap-2">
    <button type="submit" name="reaction" value="like" class="reaction-btn like">
      👍 Like (<?= $likeCount ?>)
    </button>
    <button type="submit" name="reaction" value="dislike" class="reaction-btn dislike">
      👎 Dislike (<?= $dislikeCount ?>)
    </button>
  </form>

  <hr>

  <div class="comment-box">
    <h4>💬 Comments</h4>
    <form method="post" class="comment-form mb-3">
      <textarea name="comment" rows="3" placeholder="Write a comment..." required></textarea>
      <input type="hidden" name="parent_id" value="">
      <button type="submit" name="comment_submit" class="btn btn-primary mt-2">Post Comment</button>
    </form>

    <?php foreach ($comments as $c): ?>
      <div class="comment">
        <strong><?= htmlspecialchars($c['commenter']) ?></strong>
        <p><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
        <small><?= date("M d, Y H:i", strtotime($c['created_at'])) ?></small><br>

        <button class="btn-reply" onclick="replyTo(<?= $c['id'] ?>)">↩️ Reply</button>

        <?php $replies = getReplies($pdo, $c['id']); ?>
        <?php foreach ($replies as $r): ?>
          <div class="comment reply">
            <strong><?= htmlspecialchars($r['commenter']) ?></strong>
            <p><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
            <small><?= date("M d, Y H:i", strtotime($r['created_at'])) ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
function replyTo(id) {
  document.querySelector("input[name='parent_id']").value = id;
  document.querySelector("textarea[name='comment']").focus();
  window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
}
</script>
</body>
</html>
