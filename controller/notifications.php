<?php
// controller/notifications.php
// Returns unread general notifications for the logged-in user as JSON and marks them seen.
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) { echo json_encode([]); exit; }

require_once __DIR__ . '/../config.php';

// Ensure notifications table exists
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  seen TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$uid = (int)$_SESSION['user']['id'];

// Fetch unseen notifications
$res = $conn->query("SELECT id, message, created_at FROM notifications WHERE user_id={$uid} AND seen=0 ORDER BY id DESC");
$out = [];
if ($res) {
  while ($row = $res->fetch_assoc()) { $out[] = $row; }
  // Mark them seen
  $conn->query("UPDATE notifications SET seen=1 WHERE user_id={$uid} AND seen=0");
}
echo json_encode($out);
