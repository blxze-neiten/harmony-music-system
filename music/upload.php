<?php
require "../config/bootstrap.php";
require_roles(['Artist']);

$user = current_user();
$msg = "";

// Handle song upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $file = $_FILES['file'] ?? null;

    if ($title && $genre && $file && $file['error'] === UPLOAD_ERR_OK) {
        // Create uploads folder if not exists
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        // Validate audio type
        $allowed_types = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'];
        if (!in_array($file['type'], $allowed_types)) {
            $msg = "❌ Invalid file type. Please upload MP3, WAV, or OGG only.";
        } else {
            // Generate unique filename
            $filename = time() . '_' . basename($file['name']);
            $file_path = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Save to database
                $stmt = $pdo->prepare("INSERT INTO music (artist_id, title, genre, file_path) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user['id'], $title, $genre, $filename]);

                // Notify all users (except artists)
                $note = $pdo->query("SELECT id FROM users WHERE id != {$user['id']} AND id IN (SELECT id FROM users WHERE role_id != (SELECT id FROM roles WHERE name='Artist'))");
                while ($n = $note->fetch()) {
                    $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
                        ->execute([$n['id'], "🎵 New song uploaded: '{$title}' by {$user['name']}"]);
                }

                $msg = "✅ Song uploaded successfully!";
            } else {
                $msg = "❌ Failed to upload file.";
            }
        }
    } else {
        $msg = "❌ Please fill in all fields and choose a valid audio file.";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>🎵 Upload Music - Harmony</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <div class="card p-4 shadow-lg border-0" style="background: linear-gradient(135deg, #f8f9ff, #ffe3f0);">
    <h2 class="text-center mb-3">🎶 Upload New Song</h2>

    <?php if ($msg): ?>
      <div class="alert alert-info text-center"><?= $msg ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="row g-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Song Title</label>
        <input type="text" name="title" class="form-control" placeholder="Enter song title" required>
      </div>

      <div class="col-md-6">
        <label class="form-label fw-semibold">Genre</label>
        <select name="genre" class="form-select" required>
          <option value="">-- Select Genre --</option>
          <option value="Pop">Pop</option>
          <option value="Hip Hop">Hip Hop</option>
          <option value="Afrobeat">Afrobeat</option>
          <option value="Gospel">Gospel</option>
          <option value="RnB">R&B</option>
          <option value="Reggae">Reggae</option>
          <option value="Classical">Classical</option>
          <option value="Bongo">Bongo</option>
        </select>
      </div>

      <div class="col-12">
        <label class="form-label fw-semibold">Upload Audio File</label>
        <input type="file" name="file" class="form-control" accept="audio/*" required>
      </div>

      <div class="col-12 text-center mt-3">
        <button class="btn btn-primary px-4 py-2">⬆ Upload Song</button>
      </div>
    </form>
  </div>
</div>

<footer class="mt-5">
  <p>© <?= date('Y') ?> Harmony Music Industry System</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
