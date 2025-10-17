<?php
require "../config/bootstrap.php";
require_roles(['Producer']);
$user = current_user();

// Fetch artists and their songs
$artists = $pdo->query("
    SELECT id, name 
    FROM users 
    WHERE role_id = (SELECT id FROM roles WHERE name='Artist') 
    ORDER BY name ASC
")->fetchAll();

$songs = $pdo->query("
    SELECT m.id, m.title, u.name AS artist 
    FROM music m 
    JOIN users u ON m.artist_id = u.id 
    ORDER BY m.created_at DESC
")->fetchAll();

// Handle request submission
$success = $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $artist_id = $_POST['artist_id'] ?? null;
    $music_id = $_POST['music_id'] ?: null;
    $message = trim($_POST['message'] ?? '');

    if (!$artist_id || !$message) {
        $error = "Please select an artist and write a message.";
    } else {
        try {
            // Insert collaboration request
            $stmt = $pdo->prepare("
                INSERT INTO producer_requests (producer_id, artist_id, music_id, message)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user['id'], $artist_id, $music_id, $message]);

            // Notify artist
            $note = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $note->execute([$artist_id, "🎧 New collaboration request from {$user['name']}"]);

            $success = "Request sent successfully!";
        } catch (PDOException $e) {
            $error = "Error sending request: " . $e->getMessage();
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Send Collaboration Request - Harmony</title>
  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/style.css" rel="stylesheet">
  <style>
    body { background-color: #f0f2f5; }
    .card { border-radius: 12px; }
    .form-control, .form-select { border-radius: 8px; }
    h2 { font-weight: 700; }
    .alert { border-radius: 10px; }
    .btn-primary { border-radius: 8px; padding: 10px 20px; }
  </style>
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <h2>🤝 Send Collaboration Request</h2>
  <p class="text-muted mb-4">Producers can send collaboration requests to artists, optionally linking a song.</p>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" class="card p-4 shadow-sm">
    <div class="mb-3">
      <label class="form-label">Select Artist</label>
      <select name="artist_id" class="form-select" required>
        <option value="">-- Choose Artist --</option>
        <?php foreach($artists as $a): ?>
          <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Select Song (Optional)</label>
      <select name="music_id" class="form-select">
        <option value="">-- None --</option>
        <?php foreach($songs as $s): ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['title']) ?> (<?= htmlspecialchars($s['artist']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Message</label>
      <textarea name="message" class="form-control" placeholder="Describe your collaboration idea..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Send Request</button>
  </form>
</div>

<script src="../assets/bootstrap.bundle.min.js"></script>
</body>
</html>
