<?php
// app/views/user/friend_profile.php
?>
<div class="card">
  <h3><?php echo htmlspecialchars($friend['name']); ?>'s Profile</h3>
  <?php if($canSee): ?>
    <?php while($p = $posts->fetch_assoc()): ?>
      <div class="post">
        <p><?php echo nl2br(htmlspecialchars($p['content'])); ?></p>
        <?php if($p['image']): ?><img class="post-img" src="public/uploads/posts/<?php echo htmlspecialchars($p['image']); ?>"><?php endif; ?>
      </div>
    <?php endwhile; ?>
    <form method="post" action="index.php?controller=user&action=friendAction">
      <input type="hidden" name="id" value="<?php echo $friend['id']; ?>">
      <button class="btn" name="act" value="unfriend">Unfriend</button>
    </form>
  <?php else: ?>
    <p>Only friends can see posts.</p>
  <?php endif; ?>
</div>
