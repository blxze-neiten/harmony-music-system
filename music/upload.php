<?php
require "../config/bootstrap.php";
require_roles(['Artist']);

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = $_POST['title'];
  $genre = $_POST['genre'];
  $file = $_FILES['file_path']['name'];
  $target = "../uploads/" . basename($file);
  if (move_uploaded_file($_FILES['file_path']['tmp_name'], $target)) {
    $stmt = $pdo->prepare("INSERT INTO music (artist_id, title, genre, file_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([current_user()['id'], $title, $genre, $file]);
    $msg = "✅ Song uploaded successfully!";
  } else {
    $msg = "❌ Upload failed.";
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Upload Music - Harmony</title>
  <link rel="stylesheet" href="../assets/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <div class="card p-4 shadow-lg col-md-6 mx-auto">
    <h3 class="mb-3 text-center text-primary">🎵 Upload Your Song</h3>
    <?php if($msg): ?><div class="alert alert-info"><?= $msg ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <input type="text" name="title" class="form-control mb-3" placeholder="Song Title" required>
      <input type="text" name="genre" class="form-control mb-3" placeholder="Genre" required>
      <input type="file" name="file_path" class="form-control mb-3" accept=".mp3,.wav" required>
      <button class="btn btn-primary w-100">Upload</button>
    </form>
  </div>
</div>
</body>
</html>
