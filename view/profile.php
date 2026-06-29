<?php
// expects: $u (user array), $posts (array of posts), $counts (map post_id=>counts), $me (session user)
?>
<div class="card">
  <div style="display:flex;gap:16px;align-items:center">
    <img src="<?php echo htmlspecialchars($u['profile_pic'] ?: 'uploads/logo.png'); ?>"
         style="width:80px;height:80px;border-radius:50%;object-fit:cover">
    <div>
      <h2 style="margin:0"><?php echo htmlspecialchars($u['name']); ?></h2>
      <div class="muted"><?php echo htmlspecialchars($u['email']); ?></div>
    </div>
  </div>
  <p style="margin-top:10px"><?php echo nl2br(htmlspecialchars($u['bio'] ?? '')); ?></p>

  <?php if($u['id']===$me['id']): ?>
    <form method="post" enctype="multipart/form-data" class="grid grid-2">
      <?= csrf_field() ?>
      <div><label>Bio</label><textarea name="bio"><?php echo htmlspecialchars($u['bio'] ?? ''); ?></textarea></div>
      <div><label>Profile Pic</label><input type="file" name="pic" accept=".jpg,.jpeg,.png,.gif,.webp"></div>
      <div><button>Save</button></div>
    </form>
  <?php endif; ?>
</div>

<div class="card">
  <h3>Posts</h3>
  <?php foreach($posts as $p): $pid=(int)$p['id']; ?>
    <div class="profile-post">
      <?php if(!empty($p['image'])): ?>
        <div class="media">
          <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
        </div>
      <?php endif; ?>
      <div class="body">
        <?php echo nl2br(htmlspecialchars($p['content'])); ?>
        <div class="meta-row">
          <span class="badge">❤️ <?php echo (int)$counts[$pid]['like']; ?></span>
          <span class="badge">💬 <?php echo (int)$counts[$pid]['comment']; ?></span>
        </div>
      </div>
    </div>
    <hr>
  <?php endforeach; ?>
</div>
