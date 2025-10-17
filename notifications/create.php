<?php
require __DIR__ . '/../config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$user_id = (int)($_POST['user_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
if (!$user_id || $message === '') { echo json_encode(['error'=>'missing']); exit; }

$pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")->execute([$user_id, $message]);
echo json_encode(['ok'=>1]);
