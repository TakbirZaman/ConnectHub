<?php
// controller/notification.php
// Returns unread admin report messages for the logged-in user as JSON and marks them seen.
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) { echo json_encode([]); exit; }

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/Report.php';

$userId = (int)$_SESSION['user']['id'];
$reportModel = new Report($conn);

// Ensure table exists
$reportModel->ensureTable();

// Fetch unseen alerts for this user
$alerts = $reportModel->getAlertsForTarget($userId);

// Mark them as seen now
if (!empty($alerts)) {
  $reportModel->markSeenForUser($userId);
}

// Return only safe fields
$out = [];
foreach ($alerts as $a){
  $out[] = [
    'id'         => (int)$a['id'],
    'message'    => $a['message'],
    'created_at' => $a['created_at']
  ];
}
echo json_encode($out);
