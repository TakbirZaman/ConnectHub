<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$logged = isset($_SESSION['user']);
$role   = $logged ? $_SESSION['user']['role'] : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>ConnectHub</title>
  <link rel="icon" type="image/png" href="uploads/favicon.png">
  <link rel="stylesheet" href="view/style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
<header class="topbar">
  <div class="brand">
    <img src="uploads/logo.png" alt="logo" class="logo">
    <span class="brand-title">ConnectHub</span>
  </div>

  <?php if ($logged): ?>
    <nav class="nav">
      <?php if ($role === 'user'): ?>
        <a href="index.php?page=feed">Feed</a>
        <a href="index.php?page=friends">Friends</a>
        <a href="index.php?page=messages">Messages</a>
        <a href="index.php?page=profile">Profile</a>
        <a href="index.php?page=marketplace">Marketplace</a>
      <?php elseif ($role === 'shopkeeper'): ?>
        <a href="index.php?page=marketplace">Marketplace</a>
        <a href="index.php?page=messages">Messages</a>
      <?php elseif ($role === 'admin'): ?>
        <a href="index.php?page=admin">Admin</a>
      <?php endif; ?>
    </nav>
    <div class="right">
      <a class="logout" href="index.php?page=logout">Logout</a>
    </div>
  <?php endif; ?>
</header>

<script>
/* === Notifications (admin reports to user/shopkeeper) === */
window.addEventListener('load', function(){
  fetch('controller/notifications.php')
    .then(r => r.ok ? r.json() : [])
    .then(items => {
      if (Array.isArray(items) && items.length) {
        // Combine all messages into one alert box
        alert(items.map(i => i.message).join('\n'));
      }
    })
    .catch(()=>{});
});
window.addEventListener('load', function(){
  fetch('controller/notification.php').then(r=>r.json()).then(items=>{
    if(items && items.length){
      alert(items.map(i=>i.message).join('\n'));
    }
  }).catch(()=>{});
});
</script>

<main class="container">
