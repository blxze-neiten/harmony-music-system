<?php
require "../config/bootstrap.php";
require_login();

$user = current_user();
$music_id = (int)($_GET['id'] ?? 0);

// --- fetch song + artist info
$stmt = $pdo->prepare("
  SELECT m.*, u.name AS artist_name, u.id AS artist_id
  FROM music m
  JOIN users u ON m.artist_id = u.id
  WHERE m.id = ?
");
$stmt->execute([$music_id]);
$song = $stmt->fetch();

if (!$song) {
  echo "<div class='alert alert-danger text-center mt-5'>Song not found.</div>";
  exit;
}

// CONFIG: per-stream gross rate (KES)
$PER_STREAM_GROSS = 5.00; // change this value as needed

// --- RECORD STREAM (only when someone other than the artist listens)
if ($user['id'] !== $song['artist_id']) {
    // insert stream row
    $pdo->prepare("INSERT INTO streams (music_id, user_id) VALUES (?, ?)")->execute([$music_id, $user['id']]);

    // update royalties for the current billing period (we'll use calendar month)
    $period_start = date('Y-m-01'); // first day of this month
    $period_end   = date('Y-m-t');  // last day of this month
    // Try to find existing royalties row for this music & period_start
    $rstmt = $pdo->prepare("SELECT * FROM royalties WHERE music_id=? AND period_start=?");
    $rstmt->execute([$music_id, $period_start]);
    $r = $rstmt->fetch();

    // compute split among producers if any
    $producers = $pdo->prepare("SELECT * FROM producer_collabs WHERE music_id=?");
    $producers->execute([$music_id]);
    $collabs = $producers->fetchAll();

    $producer_cut_total = 30.0;
    foreach ($collabs as $c) {
        $share_pct = (float)$c['revenue_share']; // e.g. 30.00
        $producer_cut_total += ($share_pct / 100.0) * $PER_STREAM_GROSS;
    }
    // artist gets the remainder
    $artist_cut = $PER_STREAM_GROSS - $producer_cut_total;
    if ($artist_cut < 0) $artist_cut = 0;

    if ($r) {
        // update existing
        $pdo->prepare("UPDATE royalties SET streams_count = streams_count + 1, gross_amount = gross_amount + ?, artist_share = artist_share + ?, producer_share = producer_share + ? WHERE id=?")
            ->execute([$PER_STREAM_GROSS, $artist_cut, $producer_cut_total, $r['id']]);
    } else {
        // insert new royalty row for this month
        $pdo->prepare("INSERT INTO royalties (music_id, period_start, period_end, streams_count, gross_amount, artist_share, producer_share, status)
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')")
            ->execute([$music_id, $period_start, $period_end, 1, $PER_STREAM_GROSS, $artist_cut, $producer_cut_total]);
    }

    // notify artist about the new stream (optional: you may throttle notifications if many streams)
    $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
        ->execute([$song['artist_id'], "🎧 Your song '{$song['title']}' was streamed by {$user['name']} (+Ksh ".number_format($PER_STREAM_GROSS,2).")"]);
}

// --- Handle comment / reply POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['comment'])) {
    $parent = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $comment = trim($_POST['comment']);
    if ($comment !== '') {
        $pdo->prepare("INSERT INTO comments (music_id, user_id, comment, parent_id) VALUES (?, ?, ?, ?)")
            ->execute([$music_id, $user['id'], $comment, $parent]);

        // notify artist if commenter is not artist
        if ($user['id'] != $song['artist_id']) {
            $msg = "💬 {$user['name']} commented on your song '{$song['title']}'.";
            $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$song['artist_id'], $msg]);
        }
    }
    header("Location: play.php?id=$music_id");
    exit;
}

// --- Fetch comments (top-level) + likes/dislikes counts
$comments = $pdo->prepare("
  SELECT c.*, u.name,
    (SELECT COUNT(*) FROM comment_reactions r WHERE r.comment_id=c.id AND r.reaction='like') AS likes,
    (SELECT COUNT(*) FROM comment_reactions r WHERE r.comment_id=c.id AND r.reaction='dislike') AS dislikes
  FROM comments c
  JOIN users u ON c.user_id = u.id
  WHERE c.music_id = ? AND c.parent_id IS NULL
  ORDER BY c.created_at DESC
");
$comments->execute([$music_id]);
$rows = $comments->fetchAll();

// helper: fetch replies
function fetch_replies($pdo, $parent_id) {
    $q = $pdo->prepare("
      SELECT c.*, u.name,
        (SELECT COUNT(*) FROM comment_reactions r WHERE r.comment_id=c.id AND r.reaction='like') AS likes,
        (SELECT COUNT(*) FROM comment_reactions r WHERE r.comment_id=c.id AND r.reaction='dislike') AS dislikes
      FROM comments c
      JOIN users u ON c.user_id=u.id
      WHERE c.parent_id = ?
      ORDER BY c.created_at ASC
    ");
    $q->execute([$parent_id]);
    return $q->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($song['title']) ?> — Harmony</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <div class="card shadow-lg border-0 mb-4" style="background: linear-gradient(135deg,#f8f9ff,#ffe3f0)">
    <div class="card-body text-center">
      <h2 class="fw-bold"><?= htmlspecialchars($song['title']) ?></h2>
      <p class="text-muted">By <strong><?= htmlspecialchars($song['artist_name']) ?></strong></p>
      <span class="badge bg-info text-dark mb-3"><?= htmlspecialchars($song['genre']) ?></span>

      <audio controls class="w-100 mb-3">
        <source src="../uploads/<?= htmlspecialchars($song['file_path']) ?>" type="audio/mpeg">
        Your browser does not support audio playback.
      </audio>
    </div>
  </div>

  <!-- Comments -->
  <div class="card p-4 shadow-sm" style="background: linear-gradient(135deg,#ffffff,#f3f1ff)">
    <h4 class="mb-3">💬 Comments</h4>

    <form method="post" class="mb-3">
      <textarea name="comment" class="form-control mb-2" placeholder="Write your comment..." required></textarea>
      <input type="hidden" name="parent_id" value="">
      <button class="btn btn-primary">Post Comment</button>
    </form>

    <?php if (count($rows) > 0): ?>
      <?php foreach ($rows as $c): ?>
        <div class="comment-box mb-3">
          <div class="d-flex justify-content-between">
            <div><strong><?= htmlspecialchars($c['name']) ?></strong></div>
            <div class="text-muted" style="font-size:0.85rem;"><?= $c['created_at'] ?></div>
          </div>
          <div class="comment-body mt-2"><?= nl2br(htmlspecialchars($c['comment'])) ?></div>

          <div class="comment-actions mt-2">
            <a href="react_comment.php?cid=<?= $c['id'] ?>&react=like&id=<?= $music_id ?>" class="me-3">👍 <span class="badge bg-light text-dark"><?= $c['likes'] ?></span></a>
            <a href="react_comment.php?cid=<?= $c['id'] ?>&react=dislike&id=<?= $music_id ?>">👎 <span class="badge bg-light text-dark"><?= $c['dislikes'] ?></span></a>
          </div>

          <!-- reply form -->
          <form method="post" class="reply-form mt-3">
            <input type="hidden" name="parent_id" value="<?= $c['id'] ?>">
            <textarea name="comment" class="form-control mb-2" placeholder="Reply..." required></textarea>
            <button class="btn btn-sm btn-success">Reply</button>
          </form>

          <?php $replies = fetch_replies($pdo, $c['id']); ?>
          <?php foreach ($replies as $r): ?>
            <div class="reply mt-3 p-2" style="background:#f2f2ff;border-left:4px solid #6C63FF;border-radius:8px">
              <div><strong><?= htmlspecialchars($r['name']) ?></strong> <small class="text-muted"><?= $r['created_at'] ?></small></div>
              <div class="mt-1"><?= nl2br(htmlspecialchars($r['comment'])) ?></div>
              <div class="mt-2">
                <a href="react_comment.php?cid=<?= $r['id'] ?>&react=like&id=<?= $music_id ?>" class="me-3">👍 <span class="badge bg-light text-dark"><?= $r['likes'] ?></span></a>
                <a href="react_comment.php?cid=<?= $r['id'] ?>&react=dislike&id=<?= $music_id ?>">👎 <span class="badge bg-light text-dark"><?= $r['dislikes'] ?></span></a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-muted">No comments yet — be the first to say something!</p>
    <?php endif; ?>

  </div>
</div>

<footer class="mt-5 text-center">
  <p>© <?= date('Y') ?> Harmony Music Industry System</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
