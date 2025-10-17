<?php
require "../config/bootstrap.php";
require_login();

$user = current_user();
$cid = (int)($_GET['cid'] ?? 0);
$react = $_GET['react'] ?? 'like';
$music_id = (int)($_GET['id'] ?? 0);

// validate react
$react = ($react === 'dislike') ? 'dislike' : 'like';

// check existing reaction by this user
$stmt = $pdo->prepare("SELECT * FROM comment_reactions WHERE comment_id=? AND user_id=?");
$stmt->execute([$cid, $user['id']]);
$existing = $stmt->fetch();

if ($existing) {
    // if same reaction, remove (toggle off)
    if ($existing['reaction'] === $react) {
        $pdo->prepare("DELETE FROM comment_reactions WHERE id=?")->execute([$existing['id']]);
    } else {
        // update to new reaction
        $pdo->prepare("UPDATE comment_reactions SET reaction=?, created_at=NOW() WHERE id=?")->execute([$react, $existing['id']]);
    }
} else {
    // insert new reaction
    $pdo->prepare("INSERT INTO comment_reactions (comment_id, user_id, reaction) VALUES (?, ?, ?)")->execute([$cid, $user['id'], $react]);
}

$redirect = "play.php?id=" . ($music_id ?: $_SERVER['HTTP_REFERER'] ?? '');
header("Location: $redirect");
exit;
