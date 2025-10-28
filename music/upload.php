<?php
require "../config/bootstrap.php";
require_roles(['Artist']);
$user = current_user();

$success = $error = "";

// Handle music upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $file = $_FILES['file'] ?? null;

    if (empty($title) || !$file || $file['error'] !== UPLOAD_ERR_OK) {
        $error = "Please fill all fields and upload a valid song file.";
    } else {
        $allowed_ext = ['mp3', 'wav', 'ogg', 'm4a'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed_ext)) {
            $error = "Only MP3, WAV, OGG, and M4A files are allowed.";
        } else {
            $upload_dir = __DIR__ . "/uploads/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $filename = uniqid("song_") . "." . $ext;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $stmt = $pdo->prepare("INSERT INTO music (artist_id, title, genre, file_path, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$user['id'], $title, $genre, "uploads/" . $filename]);
                $success = "✅ Your song '{$title}' was uploaded successfully!";
            } else {
                $error = "❌ Failed to upload the song. Please try again.";
            }
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>🎤 Upload Song - Harmony</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="../assets/bootstrap.min.css" rel="stylesheet">
<style>
body {
  font-family: 'Segoe UI', sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
  overflow: hidden;
}

/* Glassy upload container */
.upload-container {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 20px;
  padding: 40px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
  text-align: center;
  animation: fadeIn 0.6s ease;
  position: relative;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(25px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Page Title */
h2 {
  font-weight: 700;
  font-size: 1.8rem;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-bottom: 8px;
}

.subtitle {
  color: #666;
  margin-bottom: 25px;
}

/* Inputs */
input[type="text"], select, input[type="file"] {
  width: 100%;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  padding: 12px 14px;
  font-size: 15px;
  margin-bottom: 15px;
  transition: 0.3s;
}

input:focus, select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102,126,234,0.15);
  outline: none;
}

/* Buttons */
.btn-primary {
  width: 100%;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  font-weight: 600;
  border: none;
  padding: 12px;
  border-radius: 10px;
  transition: all 0.3s ease;
  box-shadow: 0 5px 18px rgba(102, 126, 234, 0.4);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(118, 75, 162, 0.4);
}

.back-btn {
  position: absolute;
  top: 15px;
  left: 15px;
  text-decoration: none;
  background: #eee;
  color: #333;
  padding: 6px 14px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s;
}

.back-btn:hover {
  background: #ddd;
}

/* Alerts */
.alert {
  border-radius: 10px;
  font-size: 14px;
  margin-bottom: 15px;
}

.alert-success {
  background: #e8fff0;
  color: #18794e;
  border: 1px solid #b5e2ca;
}

.alert-danger {
  background: #fff2f2;
  color: #842029;
  border: 1px solid #f5c2c7;
}
</style>
</head>
<body>

<div class="upload-container">
  <a href="/harmony/dashboard/dashboard.php" class="back-btn">← Back to Dashboard</a>
  
  <h2>🎶 Upload Your Song</h2>
  <p class="subtitle">Share your latest track with the Harmony community.</p>

  <p class="mb-3"><strong>Welcome, <?= htmlspecialchars($user['name']) ?></strong></p>

  <?php if($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php elseif($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Enter Song Title" required>
    
    <select name="genre" required>
      <option value="">-- Select Genre --</option>
      <option value="Afrobeat">Afrobeat</option>
      <option value="Hip Hop">Hip Hop</option>
      <option value="Pop">Pop</option>
      <option value="R&B">R&B</option>
      <option value="Gospel">Gospel</option>
      <option value="Reggae">Reggae</option>
      <option value="Other">Other</option>
    </select>

    <input type="file" name="file" accept=".mp3,.wav,.ogg,.m4a" required>

    <button type="submit" class="btn btn-primary">🚀 Upload Song</button>
  </form>
</div>

</body>
</html>
