<?php
// controller/FriendsController.php
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='user'){ header("Location: index.php?page=login"); exit; }
$uid = (int)$_SESSION['user']['id'];

require_once __DIR__ . '/../model/Friend.php';
$friendModel = new Friend($conn);

// handle actions (kept for backward compatibility)
if(isset($_GET['action'], $_GET['id'])){
  $id=(int)$_GET['id'];
  switch($_GET['action']){
    case 'accept': $friendModel->updateStatusForReceiver($id, $uid, 'accepted'); break;
    case 'reject': $friendModel->updateStatusForReceiver($id, $uid, 'rejected'); break;
    case 'unfriend': $friendModel->deleteLinkForUser($id, $uid); break;
    case 'cancel': $friendModel->deletePendingForSender($id, $uid); break;
  }
  header("Location: index.php?page=friends"); exit;
}

// Build pending & friends arrays with explicit ids the view can consume
$pending = $friendModel->incomingRequestsList($uid);   // each item: ['id'=>request_id, 'user_id'=>sender, 'name'=>sender_name]
$friends = $friendModel->friendsList($uid);            // each item: ['id'=>link_id, 'friend_id'=>other_user_id, 'name'=>friend_name]


// Normalize $friends shape for the view: expect user_id and friend_link_id
if (is_array($friends)) {
  $norm = [];
  foreach ($friends as $row) {
    // supports either ['id','friend_id','name'] (our model) or already-shaped arrays
    $linkId = isset($row['link_id']) ? (int)$row['link_id'] : (int)($row['id'] ?? 0);
    $userId = isset($row['user_id']) ? (int)$row['user_id'] : (int)($row['friend_id'] ?? 0);
    $norm[] = ['friend_link_id'=>$linkId, 'user_id'=>$userId, 'name'=>$row['name'] ?? ''];
  }
  $friends = $norm;
}

include __DIR__ . '/../view/friends.php';
