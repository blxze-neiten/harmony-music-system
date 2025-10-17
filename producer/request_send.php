<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Producer']);
$msg='';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $artist_id = (int)($_POST['artist_id'] ?? 0);
    $music_id = isset($_POST['music_id']) ? (int)$_POST['music_id'] : null;
    $message = trim($_POST['message'] ?? '');

    if (!$artist_id) $error = "Select an artist.";
    else {
        $pdo->prepare("INSERT INTO producer_requests (producer_id, artist_id, music_id, message) VALUES (?,?,?,?)")
            ->execute([$_SESSION['user']['id'],$artist_id,$music_id,$message]);
        $pdo->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)")->execute([$artist_id, "You have a producer request from " . $_SESSION['user']['name']]);
        $msg = "Request sent.";
    }
}

$artists = $pdo->query("SELECT id,name FROM users WHERE role_id = (SELECT id FROM roles WHERE name='Artist')")->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Producer Request</title>
<link rel="stylesheet" href="../assets/bootstrap.min.css"><link rel="stylesheet" href="../assets/style.css"></head>
<body class="container-pad">
<div class="container col-md-6">
  <h3>Send Producer Request</h3>
  <?php if(!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if(!empty($msg)): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <form method="post" class="card p-3">
    <select name="artist_id" class="form-select mb-2" required>
      <option value="">Choose Artist</option>
      <?php foreach($artists as $a): ?><option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option><?php endforeach; ?>
    </select>
    <textarea name="message" class="form-control mb-2" placeholder="Message"></textarea>
    <button class="btn btn-primary">Send</button>
  </form>
</div>
</body></html>
