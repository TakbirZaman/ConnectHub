<?php
// controller/MessagesController.php
if(!isset($_SESSION['user'])){ header("Location: index.php?page=login"); exit; }
$me = $_SESSION['user'];
$uid = (int)$me['id'];
$role = $me['role'];

require_once __DIR__ . '/../model/Message.php';
$msgModel = new Message($conn);

$partnerId = isset($_GET['to']) ? (int)$_GET['to'] : null;

/* Determine allowed contacts (moved from view — fixes SQL injection) */
$contacts = [];
if ($role === 'user') {
  $stmt = $conn->prepare("
    SELECT u.id, u.name FROM users u
    JOIN friends f ON (u.id = f.sender_id AND f.receiver_id = ?)
                    OR (u.id = f.receiver_id AND f.sender_id = ?)
    WHERE f.status = 'accepted' AND u.role = 'user'
  ");
  $stmt->bind_param("ii", $uid, $uid);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) { $contacts[] = $r; }
} elseif ($role === 'shopkeeper') {
  $stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.name FROM users u
    JOIN messages m ON m.sender_id = u.id
    WHERE m.receiver_id = ?
  ");
  $stmt->bind_param("i", $uid);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) { $contacts[] = $r; }
}

/* Send a new message */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['to'], $_POST['body'])){
  if(!csrf_validate()){ header("Location: index.php?page=messages"); exit; }
  $to   = (int)$_POST['to'];
  $body = trim((string)($_POST['body'] ?? ''));
  if($to && $body !== ''){
    // Permission check (moved from view — uses prepared statements)
    $allowed = false;
    if ($role === 'user') {
      $chk = $conn->prepare("SELECT id FROM friends WHERE ((sender_id=? AND receiver_id=?) OR (receiver_id=? AND sender_id=?)) AND status='accepted'");
      $chk->bind_param("iiii", $uid, $to, $uid, $to);
      $chk->execute();
      if ($chk->get_result()->num_rows > 0) $allowed = true;
    } elseif ($role === 'shopkeeper') {
      $chk = $conn->prepare("SELECT id FROM messages WHERE receiver_id=? AND sender_id=? LIMIT 1");
      $chk->bind_param("ii", $uid, $to);
      $chk->execute();
      if ($chk->get_result()->num_rows > 0) $allowed = true;
    }
    if ($allowed) {
      $msgModel->send($uid, $to, $body);
    }
  }
  header("Location: index.php?page=messages&to=".$to); exit;
}

/* Build inbox list with stable keys */
$rawInbox = $msgModel->inboxFor($uid);
$inbox = [];
foreach($rawInbox as $row){
  $partner_id = ($row['sender_id']==$uid) ? (int)$row['receiver_id'] : (int)$row['sender_id'];

  $nameStmt = $conn->prepare("SELECT name FROM users WHERE id=?");
  $nameStmt->bind_param("i", $partner_id);
  $nameStmt->execute();
  $partnerName = $nameStmt->get_result()->fetch_assoc()['name'] ?? 'User';

  $inbox[] = [
    'partner_id'   => $partner_id,
    'partner_name' => $partnerName,
    'last_body'    => (string)($row['body'] ?? ''),
    'last_at'      => (string)($row['created_at'] ?? ''),
  ];
}

/* Load thread if partner selected, map to stable keys */
$thread = [];
$partner = null;

if ($partnerId){
  $ps = $conn->prepare("SELECT id,name FROM users WHERE id=?");
  $ps->bind_param("i", $partnerId);
  $ps->execute();
  $partner = $ps->get_result()->fetch_assoc();

  $rawThread = $msgModel->threadBetween($uid, $partnerId);
  foreach ($rawThread as $m){
    $thread[] = [
      'sender_id'    => (int)($m['sender_id'] ?? 0),
      'sender_name'  => (string)($m['sender_name'] ?? 'User'),
      'receiver_id'  => (int)($m['receiver_id'] ?? 0),
      'receiver_name'=> (string)($m['receiver_name'] ?? 'User'),
      'body'         => (string)($m['body'] ?? ''),
      'created_at'   => (string)($m['created_at'] ?? ''),
    ];
  }

  $msgModel->markSeen($uid, $partnerId);
}

include __DIR__ . '/../view/messages.php';
