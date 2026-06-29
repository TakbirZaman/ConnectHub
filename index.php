<?php
// index.php — role-aware router (no footer include needed)
session_start();
include 'config.php';

function role_home($role){
  if ($role === 'user') return 'feed';
  if ($role === 'shopkeeper') return 'marketplace';
  return 'admin';
}

// Resolve target page
$page = $_GET['page'] ?? null;
if ($page === null) {
  if (isset($_SESSION['user'])) {
    $page = role_home($_SESSION['user']['role']);
  } else {
    $page = 'login';
  }
}

// If already logged in, keep users out of login page
if ($page === 'login' && isset($_SESSION['user'])) {
  header('Location: index.php?page=' . role_home($_SESSION['user']['role']));
  exit;
}

// Optional: include header if you have one
if (file_exists('view/header.php')) {
  include 'view/header.php';
}

switch ($page) {
  case 'login':
    include 'view/login.php';
    break;

  case 'register':
    include 'view/register.php';
    break;

  // MVC controllers
  case 'feed':        // user-only page
    include 'controller/FeedController.php';
    break;

  case 'friends':     // user-only page
    include 'controller/FriendsController.php';
    break;

  case 'profile':     // now routed via controller (no SQL in view)
    include 'controller/ProfileController.php';
    break;

  case 'marketplace': // shopkeeper home (users can view listings)
    include 'controller/MarketplaceController.php';
    break;

  case 'messages':
  include 'controller/MessagesController.php';
  break;


  case 'admin':
    include 'view/admin.php';
    break;

  case 'logout':
    session_destroy();
    header('Location: index.php?page=login');
    exit;

  default:
    include 'view/login.php';
    break;
}
?>

