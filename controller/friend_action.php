<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
  header("Location: ../index.php?page=login");
  exit;
}

$uid = (int)$_SESSION['user']['id'];

/** simple notifier */
function notify($conn, $to, $msg){
  $conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    seen TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $to = (int)$to;
  $msg = $conn->real_escape_string($msg);
  @$conn->query("INSERT INTO notifications(user_id,message) VALUES($to,'$msg')");
}

/** helpers */
function getFriendRow($conn, $id){
  $id = (int)$id;
  $res = $conn->query("SELECT * FROM friends WHERE id=$id");
  return $res ? $res->fetch_assoc() : null;
}

function ensureFriendsTable($conn){
  $conn->query("CREATE TABLE IF NOT EXISTS friends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

ensureFriendsTable($conn);

$action = $_GET['a'] ?? '';

if ($action === 'send' && isset($_GET['to'])) {
  $to = (int)$_GET['to'];
  if ($to === $uid) { header("Location: ../index.php?page=friends"); exit; }

  // already friends?
  $q = $conn->query("SELECT id FROM friends 
    WHERE ((sender_id=$uid AND receiver_id=$to) OR (sender_id=$to AND receiver_id=$uid))
      AND status='accepted' LIMIT 1");
  if ($q && $q->num_rows > 0) { header("Location: ../index.php?page=friends"); exit; }

  // pending either direction?
  $q = $conn->query("SELECT id FROM friends 
    WHERE ((sender_id=$uid AND receiver_id=$to) OR (sender_id=$to AND receiver_id=$uid))
      AND status='pending' LIMIT 1");
  if ($q && $q->num_rows > 0) { header("Location: ../index.php?page=friends"); exit; }

  // create request
  $stmt = $conn->prepare("INSERT INTO friends(sender_id,receiver_id,status) VALUES(?,?,'pending')");
  $stmt->bind_param('ii', $uid, $to);
  if ($stmt->execute()) {
    notify($conn, $to, 'You have a new friend request');
  }
  header("Location: ../index.php?page=friends");
  exit;
}

if ($action === 'accept' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  // Only the receiver may accept a pending request
  $row = getFriendRow($conn, $id);
  if ($row && $row['status'] === 'pending' && (int)$row['receiver_id'] === $uid) {
    $conn->query("UPDATE friends SET status='accepted' WHERE id=$id");
    $other = ((int)$row['sender_id'] === $uid) ? (int)$row['receiver_id'] : (int)$row['sender_id'];
    notify($conn, $other, 'Your friend request was accepted');
    notify($conn, $uid, 'You are now friends');
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_end_clean(); }
    if (isset($_GET['stay']) && $_GET['stay'] == '1') {
      header("Location: ../index.php?page=friends");
    } else {
      header("Location: ../index.php?page=messages&to=".$other);
    }
    exit;
  }
  header("Location: ../index.php?page=friends");
  exit;
}

if ($action === 'reject' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $row = getFriendRow($conn, $id);
  if ($row && $row['status'] === 'pending' && (int)$row['receiver_id'] === $uid) {
    $conn->query("UPDATE friends SET status='rejected' WHERE id=$id");
    $other = ((int)$row['sender_id'] === $uid) ? (int)$row['receiver_id'] : (int)$row['sender_id'];
    notify($conn, $other, 'Your friend request was rejected');
    notify($conn, $uid, 'You rejected a friend request');
  }
  header("Location: ../index.php?page=friends");
  exit;
}

if ($action === 'unfriend' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $row = getFriendRow($conn, $id);
  if ($row && ((int)$row['sender_id'] === $uid || (int)$row['receiver_id'] === $uid)) {
    $conn->query("DELETE FROM friends WHERE id=$id");
    notify($conn, (int)$row['sender_id'], 'A friend was removed');
    notify($conn, (int)$row['receiver_id'], 'A friend was removed');
  }
  header("Location: ../index.php?page=friends");
  exit;
}

if ($action === 'cancel' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $row = getFriendRow($conn, $id);
  // Only the sender can cancel a pending request
  if ($row && $row['status'] === 'pending' && (int)$row['sender_id'] === $uid) {
    $conn->query("DELETE FROM friends WHERE id=$id");
  }
  header("Location: ../index.php?page=friends");
  exit;
}

// default: go back
header("Location: ../index.php?page=friends");
exit;
?>
