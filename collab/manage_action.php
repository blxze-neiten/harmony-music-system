<?php
require "../config/bootstrap.php";
require_roles(['Artist']); // Only artists can approve/reject
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $status = $action === 'approve' ? 'accepted' : 'rejected';

    // Fetch the collaboration request (ensure artist owns it)
    $stmt = $pdo->prepare("SELECT * FROM producer_requests WHERE id=? AND artist_id=?");
    $stmt->execute([$id, $user['id']]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
        echo "<script>alert('Invalid request or access denied.'); window.location='manage.php';</script>";
        exit;
    }

    // Update the request status
    $update = $pdo->prepare("UPDATE producer_requests SET status=? WHERE id=?");
    $update->execute([$status, $id]);

    // Notify the producer
    $message = "🎧 Your collaboration request for artist {$user['name']} was {$status}.";
    $notify = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $notify->execute([$req['producer_id'], $message]);

    // If accepted, record it in producer_collabs
    if ($status === 'accepted') {
        $insert = $pdo->prepare("
            INSERT INTO producer_collabs (music_id, artist_id, producer_id, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $insert->execute([$req['music_id'], $req['artist_id'], $req['producer_id']]);

        // Optional: also notify artist for confirmation
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
            ->execute([$user['id'], "✅ You have accepted a collaboration with producer ID {$req['producer_id']}"]);
    }

    echo "<script>alert('Request {$status} successfully.'); window.location='manage.php';</script>";
    exit;

} else {
    header("Location: manage.php");
    exit;
}
?>
