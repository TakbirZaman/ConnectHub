<?php
if(!isset($_SESSION['user'])){ header("Location: index.php?page=login"); exit; }
$me = $_SESSION['user'];
$view_id = (int)($_GET['user'] ?? $me['id']);

require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../model/Post.php';
$userModel = new User($conn);
$postModel = new Post($conn);

/* Handle profile update (self only) */
if($view_id===$me['id'] && $_SERVER['REQUEST_METHOD']==='POST'){
  if(!csrf_validate()){ header("Location: index.php?page=profile"); exit; }
  $bio = trim($_POST['bio'] ?? '');
  $pic = $me['profile_pic'];
  if(!empty($_FILES['pic']['name'])){
    $ext = strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if(in_array($ext,$allowed,true)){
      if(!is_dir('uploads')){ @mkdir('uploads',0777,true); }
      $pic = 'uploads/p_'.time().'_'.rand(100,999).'.'.$ext;
      move_uploaded_file($_FILES['pic']['tmp_name'],$pic);
    }
  }
  $userModel->updateProfile($view_id, $bio, $pic);
  $_SESSION['user'] = $userModel->getById($view_id);
  $_SESSION['flash_good'] = 'Profile updated.';
  header("Location: index.php?page=profile"); exit;
}

/* Data for view */
$u = $userModel->getById($view_id);
$posts = $postModel->getByUser($view_id);

/* Pre-compute like/comment counts (keeps view dumb) */
$counts = [];
foreach($posts as $p){
  $pid = (int)$p['id'];
  $counts[$pid] = [
    'like'    => $postModel->likeCount($pid),
    'comment' => $postModel->commentCount($pid),
  ];
}

include __DIR__ . '/../view/profile.php';
