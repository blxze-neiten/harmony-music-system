<?php
require __DIR__ . '/../config/bootstrap.php';
require_login();

$music_id = (int)($_POST['music_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
if (!$music_id || $comment === '') {
    header("Location: /harmony/music/list.php"); exit;
}
$pdo->prepare("INSERT INTO comments (music_id,user_id,comment) VALUES (?,?,?)")
    ->execute([$music_id, $_SESSION['user']['id'], $comment]);

// notify artist
$artist = $pdo->prepare("SELECT artist_id,title FROM music WHERE id = ?");
$artist->execute([$music_id]);
$row = $artist->fetch();
if ($row) {
    $pdo->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)")
        ->execute([$row['artist_id'], "💬 New comment on '{$row['title']}'"]);
}

header("Location: /harmony/music/play.php?id={$music_id}");
exit;
