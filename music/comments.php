<?php 
require "../config/bootstrap.php"; 
require_login();

$music_id = $_GET['id'] ?? 0;

// Fetch song
$stmt = $pdo->prepare("SELECT m.*, u.name as artist, u.id as artist_id 
                       FROM music m 
                       JOIN users u ON m.artist_id=u.id 
                       WHERE m.id=?");
$stmt->execute([$music_id]); 
$song = $stmt->fetch();
if(!$song){ echo "Song not found."; exit; }

$user = current_user();

// Handle comment or reply submission
if($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['comment'])){
  $parent = !empty($_POST['parent_id']) ? $_POST['parent_id'] : NULL;

  // Insert new comment
  $pdo->prepare("INSERT INTO comments(music_id,user_id,comment,parent_id) VALUES (?,?,?,?)")
      ->execute([$music_id,$user['id'],$_POST['comment'],$parent]);

  // --- Notifications ---
  if($parent === NULL){
    // Normal comment on a song → notify Artist
    if($song['artist_id'] != $user['id']){ // don’t notify self
      $msg = $user['name']." commented on your song '".$song['title']."'";
      $pdo->prepare("INSERT INTO notifications(user_id,message) VALUES (?,?)")
          ->execute([$song['artist_id'],$msg]);
    }
  } else {
    // Reply to another comment → notify the comment owner
    $q=$pdo->prepare("SELECT user_id FROM comments WHERE id=?");
    $q->execute([$parent]);
    $original=$q->fetch();
    if($original && $original['user_id'] != $user['id']){
      $msg = $user['name']." replied to your comment on '".$song['title']."'";
      $pdo->prepare("INSERT INTO notifications(user_id,message) VALUES (?,?)")
          ->execute([$original['user_id'],$msg]);
    }
  }

  header("Location: comments.php?id=".$music_id); // Refresh to show new comment
  exit;
}

// Fetch top-level comments
$comments = $pdo->prepare("
  SELECT c.*, u.name 
  FROM comments c 
  JOIN users u ON c.user_id=u.id 
  WHERE c.music_id=? AND c.parent_id IS NULL 
  ORDER BY c.created_at DESC
");
$comments->execute([$music_id]); 
$rows = $comments->fetchAll();

// Function to fetch replies
function fetch_replies($pdo,$parent_id){
  $q=$pdo->prepare("
    SELECT c.*, u.name 
    FROM comments c 
    JOIN users u ON c.user_id=u.id 
    WHERE parent_id=? ORDER BY c.created_at ASC
  ");
  $q->execute([$parent_id]);
  return $q->fetchAll();
}
?>
<!doctype html>
<html>
<head>
  <link rel="stylesheet" href="../assets/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container mt-5">
  <h3>💬 Comments for "<?= htmlspecialchars($song['title']) ?>"</h3>

  <!-- New Comment Form -->
  <form method="post" class="mb-3">
    <textarea name="comment" class="form-control mb-2" placeholder="Write a comment..." required></textarea>
    <input type="hidden" name="parent_id" value="">
    <button class="btn btn-primary">Post Comment</button>
  </form>

  <!-- Comment List -->
  <div class="list-group">
    <?php foreach($rows as $c): ?>
      <div class="list-group-item">
        <strong><?= htmlspecialchars($c['name']) ?>:</strong> 
        <?= htmlspecialchars($c['comment']) ?>
        <br><small class="text-muted"><?= $c['created_at'] ?></small>

        <!-- Reply Form -->
        <?php if(current_user()['role']!=='Admin'): ?>
        <form method="post" class="mt-2">
          <input type="hidden" name="parent_id" value="<?= $c['id'] ?>">
          <textarea name="comment" class="form-control mb-2" placeholder="Reply..." required></textarea>
          <button class="btn btn-sm btn-success">Reply</button>
        </form>
        <?php endif; ?>

        <!-- Replies -->
        <?php $replies=fetch_replies($pdo,$c['id']); ?>
        <?php foreach($replies as $r): ?>
          <div class="ms-4 mt-2 p-2 border-start border-2">
            <strong><?= htmlspecialchars($r['name']) ?>:</strong> 
            <?= htmlspecialchars($r['comment']) ?>
            <br><small class="text-muted"><?= $r['created_at'] ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
