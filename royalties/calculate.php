<?php
require "../config/bootstrap.php";

// Cron simulation — normally this would run daily/weekly
$rate_per_stream = 0.50; // 50 cents per stream
$today = date('Y-m-d');

// Get all songs and count streams
$songs = $pdo->query("
  SELECT m.id, COUNT(s.id) AS streams
  FROM music m
  LEFT JOIN streams s ON m.id = s.music_id
  GROUP BY m.id
")->fetchAll();

foreach ($songs as $song) {
  $music_id = $song['id'];
  $streams = $song['streams'];
  $gross = $streams * $rate_per_stream;

  // Check if royalty exists
  $stmt = $pdo->prepare("SELECT id FROM royalties WHERE music_id = ?");
  $stmt->execute([$music_id]);
  $exists = $stmt->fetch();

  if ($exists) {
    // Update existing royalty record
    $pdo->prepare("UPDATE royalties SET streams_count=?, gross_amount=?, artist_share=? WHERE music_id=?")
        ->execute([$streams, $gross, $gross, $music_id]);
  } else {
    // Insert new record
    $pdo->prepare("
      INSERT INTO royalties (music_id, period_start, period_end, streams_count, gross_amount, artist_share)
      VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([$music_id, $today, $today, $streams, $gross, $gross]);
  }
}

echo "<h3 style='color:green; text-align:center; margin-top:40px;'>✅ Royalties successfully updated based on streams.</h3>";
?>
