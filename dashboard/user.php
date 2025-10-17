<?php
require "../config/bootstrap.php";
require_roles(['User']);
$user = current_user();

// Fetch all songs (with artist names)
$songs = $pdo->query("
  SELECT m.*, u.name AS artist_name 
  FROM music m 
  JOIN users u ON m.artist_id = u.id 
  ORDER BY m.created_at DESC
")->fetchAll();

// Get all distinct genres for filter dropdown
$genres = $pdo->query("SELECT DISTINCT genre FROM music ORDER BY genre ASC")->fetchAll();

// Count notifications
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=?");
$stmt->execute([$user['id']]);
$notif_count = $stmt->fetchColumn();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>🎧 User Dashboard - Harmony</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <h2 class="mb-4 text-center fw-bold text-gradient">🎶 Welcome, <?= htmlspecialchars($user['name']) ?>!</h2>
  <p class="lead text-center">Explore music, leave comments, and discover your favorite genres 🎧</p>

  <!-- Genre Filter -->
  <div class="d-flex justify-content-center my-4">
    <form method="get" class="d-flex">
      <select name="genre" class="form-select me-2" style="border-radius: 25px; width: 200px;">
        <option value="">🎵 All Genres</option>
        <?php foreach ($genres as $g): ?>
          <option value="<?= htmlspecialchars($g['genre']) ?>" 
            <?= (($_GET['genre'] ?? '') == $g['genre']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($g['genre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary" style="border-radius: 25px;">Filter</button>
    </form>
  </div>

  <!-- Songs List -->
  <div class="row">
    <?php
    $selected_genre = $_GET['genre'] ?? '';
    $filtered_songs = array_filter($songs, function($s) use ($selected_genre) {
      return $selected_genre === '' || $s['genre'] === $selected_genre;
    });

    if ($filtered_songs):
      foreach ($filtered_songs as $song):
    ?>
    <div class="col-md-4 mb-4">
      <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #f8f9ff, #ffe3f0);">
        <div class="card-body text-center">
          <h5 class="card-title fw-bold"><?= htmlspecialchars($song['title']) ?></h5>
          <p class="text-muted mb-2"><em>By <?= htmlspecialchars($song['artist_name']) ?></em></p>
          <span class="badge bg-info text-dark mb-2"><?= htmlspecialchars($song['genre']) ?></span>
          <audio controls class="w-100 mb-3">
            <source src="../uploads/<?= htmlspecialchars($song['file_path']) ?>" type="audio/mpeg">
          </audio>
          <a href="../music/play.php?id=<?= $song['id'] ?>" class="btn btn-success btn-sm">▶ View & Comment</a>
        </div>
      </div>
    </div>
    <?php
      endforeach;
    else:
      echo "<p class='text-center text-muted'>No songs available in this genre yet.</p>";
    endif;
    ?>
  </div>
</div>

<footer class="mt-5">
  <p>© <?= date('Y') ?> Harmony Music Industry System</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
