<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Producer']);
$user = current_user();

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $file = $_FILES['file'] ?? null;

    if (!$title || !$file || $file['error'] !== UPLOAD_ERR_OK) {
        $error = "❌ Please provide a beat title and select a valid file.";
    } else {
        $allowed = ['mp3', 'wav', 'flp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = "❌ Only MP3, WAV or FLP formats are allowed.";
        } else {
            $uploadDir = __DIR__ . '/../uploads/beats/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileName = uniqid('beat_', true) . '.' . $ext;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {

                // ✅ Correct public path
                $relativePath = '/harmony/uploads/beats/' . $fileName;

                // ✅ Save beat in music table
                $stmt = $pdo->prepare("
                    INSERT INTO music (artist_id, title, genre, file_path)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$user['id'], $title, $genre, $relativePath]);

                $success = "✅ Beat uploaded successfully!";
            } else {
                $error = "❌ File upload failed.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Upload Beat - Harmony</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<style>
body {
  background: linear-gradient(135deg, #667eea, #764ba2);
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 30px;
  font-family: 'Segoe UI', sans-serif;
}
.upload-box {
  background: white;
  padding: 35px;
  border-radius: 18px;
  width: 100%;
  max-width: 520px;
  box-shadow: 0 15px 45px rgba(0,0,0,0.25);
  animation: fadeIn 0.6s ease-in-out;
}
h3 {
  text-align: center;
  font-weight: 700;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-bottom: 25px;
}
.btn-upload {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  padding: 14px;
  border-radius: 10px;
  color: white;
  font-weight: 600;
  width: 100%;
  transition: 0.3s;
}
.btn-upload:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(102,126,234,0.5);
}
@keyframes fadeIn { from {opacity: 0; transform: translateY(20px);} to {opacity: 1; transform: translateY(0);} }
</style>
</head>

<body>
<div class="upload-box">
  <h3>🎧 Upload Your Beat</h3>

  <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <label class="form-label">Beat Title</label>
    <input type="text" name="title" class="form-control mb-3" required>

    <label class="form-label">Genre</label>
    <input type="text" name="genre" class="form-control mb-3" placeholder="Hip Hop, Trap, Afrobeats">

    <label class="form-label">Beat File</label>
    <input type="file" name="file" class="form-control mb-3" accept=".mp3,.wav,.flp" required>

    <button type="submit" class="btn-upload">⬆️ Upload Beat</button>
  </form>

  <div class="text-center mt-3">
    <a href="/harmony/producer/my_beats.php" class="text-light">← View My Beats</a>
  </div>
</div>
</body>
</html>
