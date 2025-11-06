<?php
require "../config/bootstrap.php";
require_login(); // anyone logged in can generate a stream

$user = current_user();
$music_id = (int)($_GET['id'] ?? 0);

if ($music_id > 0) {
    // ✅ ALWAYS record a stream, even if same user listens multiple times
    $stmt = $pdo->prepare("
        INSERT INTO streams (music_id, user_id, listened_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$music_id, $user['id']]);
}

// Fetch song
$stmt = $pdo->prepare("SELECT * FROM music WHERE id = ?");
$stmt->execute([$music_id]);
$song = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title><?= htmlspecialchars($song['title']) ?></title></head>
<body>
  <h2><?= htmlspecialchars($song['title']) ?></h2>
  <audio controls autoplay>
    <source src="<?= htmlspecialchars($song['file_path']) ?>" type="audio/mpeg">
  </audio>
</body>
</html>
