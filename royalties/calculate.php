<?php
require "../config/bootstrap.php";

$rate_per_stream = 0.50; // KES per play

// Get all songs and total stream counts
$songs = $pdo->query("
  SELECT 
      m.id,
      COUNT(s.id) AS total_streams
  FROM music m
  LEFT JOIN streams s ON m.id = s.music_id
  GROUP BY m.id
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($songs as $song) {
    $music_id = $song['id'];
    $streams = $song['total_streams'];
    $gross = $streams * $rate_per_stream;

    $artist_share = $gross * 0.7;
    $producer_share = $gross * 0.3;

    // Check if royalty record exists
    $stmt = $pdo->prepare("SELECT id FROM royalties WHERE music_id = ?");
    $stmt->execute([$music_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        // ✅ Update Existing Row
        $pdo->prepare("
            UPDATE royalties 
            SET streams_count = ?, gross_amount = ?, artist_share = ?, producer_share = ?
            WHERE music_id = ?
        ")->execute([$streams, $gross, $artist_share, $producer_share, $music_id]);

    } else {
        // ✅ Insert New Row (NO period columns)
        $pdo->prepare("
            INSERT INTO royalties (music_id, streams_count, gross_amount, artist_share, producer_share, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ")->execute([$music_id, $streams, $gross, $artist_share, $producer_share]);
    }
}

echo "<h3 style='color:green; text-align:center;margin-top:40px;'>✅ Royalties Successfully Calculated & Updated</h3>";
