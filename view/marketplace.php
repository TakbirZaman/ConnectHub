<?php
// expects: $me, $role, $products (array from controller)
?>
<div class="card">
  <h3>Marketplace</h3>

  <?php if ($role==='shopkeeper'): ?>
  <form method="post" enctype="multipart/form-data" style="margin:12px 0; display:grid; gap:10px">
    <?= csrf_field() ?>
    <div>
      <label>Name</label>
      <input name="name" required>
    </div>
    <div>
      <label>Description</label>
      <textarea name="description" rows="3"></textarea>
    </div>
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px">
      <div>
        <label>Price</label>
        <input name="price" type="number" step="0.01" min="0" value="0">
      </div>
      <div>
        <label>Product Image</label>
        <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp">
      </div>
    </div>
    <div><button>Add Product</button></div>
  </form>
  <?php endif; ?>

  <?php foreach($products as $p): ?>
    <div class="card" style="margin:10px 0">
      <div style="display:flex; gap:12px; align-items:flex-start">
        <?php if(!empty($p['image'])): ?>
          <img src="<?php echo htmlspecialchars($p['image']); ?>" alt=""
               style="width:140px;height:auto;border-radius:10px;border:1px solid var(--line)">
        <?php endif; ?>
        <div style="flex:1">
          <div style="display:flex; justify-content:space-between; align-items:center">
            <strong><?php echo htmlspecialchars($p['name']); ?></strong>
            <span><strong>$<?php echo number_format((float)$p['price'], 2); ?></strong></span>
          </div>
          <div style="margin-top:6px"><?php echo nl2br(htmlspecialchars($p['description'])); ?></div>
          <div class="muted" style="margin-top:6px">Shop: <?php echo htmlspecialchars($p['shop']); ?></div>

          <?php if($role==='user'): ?>
            <div style="margin-top:8px">
              <a class="button" href="index.php?page=messages&to=<?php echo (int)$p['shopkeeper_id']; ?>">Message shopkeeper</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
