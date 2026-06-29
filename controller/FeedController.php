<?php
// controller/FeedController.php
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
  header("Location: index.php?page=login"); exit;
}
$user = $_SESSION['user'];
$user_id = (int)$user['id'];

require_once __DIR__ . '/../model/Post.php';
$postModel = new Post($conn);

/* --------- Actions --------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validate()) {
    header("Location: index.php?page=feed"); exit;
}

// Create post
if (isset($_POST['new_post'])) {
  $content = trim($_POST['content'] ?? '');
  $image = '';
  if (!empty($_FILES['image']['name'])) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif'];
    if (in_array($ext, $allowed) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      if (!is_dir('uploads')) { @mkdir('uploads', 0777, true); }
      $image = 'uploads/' . time() . '_' . rand(100,999) . '.' . $ext;
      move_uploaded_file($_FILES['image']['tmp_name'], $image);
    } else {
      $_SESSION['flash_bad'] = 'Invalid image type.';
    }
  }
  if ($content !== '' || $image !== '') {
    $postModel->create($user_id, $content, $image ?: null);
  }
  header("Location: index.php?page=feed"); exit;
}

// Like
if (isset($_GET['like'])) {
  $pid = (int)$_GET['like'];
  $postModel->like($user_id, $pid);
  header("Location: index.php?page=feed"); exit;
}

// Comment
if (isset($_POST['comment'], $_POST['post_id'])) {
  $pid = (int)$_POST['post_id'];
  $comment = trim($_POST['comment']);
  if ($comment !== '') {
    $postModel->addComment($user_id, $pid, $comment);
  }
  header("Location: index.php?page=feed"); exit;
}

/* --------- Data for view --------- */
// friends-only feed (includes own posts)
$posts = $postModel->friendsFeed($user_id);  // array of posts with counts
// comments per post (optional: lazy, but mirrors your original page UX)
$commentsByPost = $postModel->commentsForPosts(array_column($posts, 'id'));

include __DIR__ . '/../view/feed.php';
