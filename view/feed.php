<?php
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='user'){ header("Location: index.php?page=login"); exit; }
?>

<?php if(!empty($_SESSION['flash_bad'])): ?>
  <div class="bad"><?= htmlspecialchars($_SESSION['flash_bad']); unset($_SESSION['flash_bad']); ?></div>
<?php endif; ?>

<div class="card">
  <h3>Create Post</h3>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <textarea name="content" placeholder="What's on your mind?" rows="3" style="width:100%"></textarea>
    <div style="margin-top:8px">
      <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif">
    </div>
    <div style="margin-top:8px">
      <button name="new_post" value="1">Post</button>
    </div>
  </form>
</div>

<?php foreach($posts as $row): ?>
  <div class="card feed-post">
    <div class="meta">
      <strong><?= htmlspecialchars($row['name']) ?></strong>
      <span class="small">• <?= htmlspecialchars($row['created_at']) ?></span>
    </div>
    <div><?= nl2br(htmlspecialchars($row['content'])) ?></div>
    <?php if(!empty($row['image'])): ?>
      <div><img src="<?= htmlspecialchars($row['image']) ?>" alt="" style="max-width:100%"></div>
    <?php endif; ?>

    <div class="feed-actions">
      <a href="index.php?page=feed&like=<?= (int)$row['id'] ?>">Like (<?= (int)$row['like_count'] ?>)</a>
      <span>Comments (<?= (int)$row['comment_count'] ?>)</span>
    </div>

    <div style="margin-top:8px">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="post_id" value="<?= (int)$row['id'] ?>">
        <input name="comment" placeholder="Write a comment">
      </form>
    </div>

    <?php
      $cid = (int)$row['id'];
      $comments = $commentsByPost[$cid] ?? [];
      if ($comments):
    ?>
      <ul class="list" style="margin-top:6px">
        <?php foreach($comments as $c): ?>
          <li><strong><?= htmlspecialchars($c['name']) ?>:</strong> <?= htmlspecialchars($c['comment']) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
