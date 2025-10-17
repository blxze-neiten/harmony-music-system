<?php
require "../config/bootstrap.php";
require_login();
$user = current_user();

$collab_id = $_GET['id'] ?? null;
if (!$collab_id) die("Invalid collaboration ID");

// Verify user is part of this collaboration
$stmt = $pdo->prepare("SELECT * FROM producer_collabs WHERE id=? AND (artist_id=? OR producer_id=?)");
$stmt->execute([$collab_id, $user['id'], $user['id']]);
$collab = $stmt->fetch();

if (!$collab) {
  die("Access denied");
}

// Handle message submission (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
  $msg = trim($_POST['message']);
  if ($msg !== '') {
    $stmt = $pdo->prepare("INSERT INTO collab_messages (collab_id, sender_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$collab_id, $user['id'], $msg]);
  }
  exit;
}

// Fetch messages
$messages = $pdo->prepare("
  SELECT cm.*, u.name FROM collab_messages cm 
  JOIN users u ON cm.sender_id=u.id 
  WHERE cm.collab_id=? ORDER BY cm.created_at ASC
");
$messages->execute([$collab_id]);
$data = $messages->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>🎤 Collaboration Chat - Harmony</title>
  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/style.css" rel="stylesheet">
  <style>
    body { background: #f8f9fc; }
    .chat-box { max-height: 65vh; overflow-y: auto; padding: 15px; border-radius: 12px; background: #fff; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
    .chat-bubble { padding: 10px 15px; border-radius: 15px; margin: 5px 0; display: inline-block; max-width: 75%; }
    .chat-left { background: #e0e0ff; align-self: flex-start; }
    .chat-right { background: #ffe1ec; align-self: flex-end; text-align: right; }
    .chat-container { display: flex; flex-direction: column; }
    textarea { resize: none; border-radius: 10px; }
  </style>
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-4">
  <h3>💬 Chat with <?= $user['id'] == $collab['artist_id'] ? "Producer" : "Artist" ?></h3>

  <div class="chat-box mb-3" id="chatBox">
    <?php foreach ($data as $m): ?>
      <div class="chat-container <?= $m['sender_id'] == $user['id'] ? 'align-items-end' : 'align-items-start' ?>">
        <div class="chat-bubble <?= $m['sender_id'] == $user['id'] ? 'chat-right' : 'chat-left' ?>">
          <strong><?= htmlspecialchars($m['name']) ?>:</strong> <?= nl2br(htmlspecialchars($m['message'])) ?><br>
          <small class="text-muted"><?= $m['created_at'] ?></small>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <form id="chatForm" class="d-flex gap-2">
    <textarea id="message" class="form-control" rows="2" placeholder="Type your message..." required></textarea>
    <button class="btn btn-primary">Send</button>
  </form>
</div>

<script src="../assets/bootstrap.bundle.min.js"></script>
<script>
// Handle chat submission (AJAX)
document.getElementById('chatForm').addEventListener('submit', async e => {
  e.preventDefault();
  const msg = document.getElementById('message').value.trim();
  if (!msg) return;

  await fetch('', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ message: msg })
  });

  document.getElementById('message').value = '';
  loadChat(); // Refresh chat
});

// Auto-refresh messages every 3 seconds
async function loadChat() {
  const res = await fetch(window.location.href);
  const text = await res.text();
  const parser = new DOMParser();
  const htmlDoc = parser.parseFromString(text, 'text/html');
  document.getElementById('chatBox').innerHTML = htmlDoc.getElementById('chatBox').innerHTML;
  document.getElementById('chatBox').scrollTop = document.getElementById('chatBox').scrollHeight;
}
setInterval(loadChat, 3000);
</script>
</body>
</html>
