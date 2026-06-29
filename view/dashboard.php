<?php
// app/views/admin/dashboard.php
?>
<div class="card">
  <h3>Admin - Manage Users & Shopkeepers</h3>
  <form method="get">
    <input type="hidden" name="controller" value="admin">
    <input type="hidden" name="action" value="dashboard">
    <label>Filter by role:</label>
    <select name="role" onchange="this.form.submit()">
      <option value="">All</option>
      <option value="user" <?php if(($_GET['role'] ?? '')==='user') echo 'selected'; ?>>User</option>
      <option value="shopkeeper" <?php if(($_GET['role'] ?? '')==='shopkeeper') echo 'selected'; ?>>Shopkeeper</option>
    </select>
  </form>
  <form method="post">
    <table class="table">
      <thead><tr><th></th><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Age</th><th>Gender</th><th>Banned Until</th></tr></thead>
      <tbody>
        <?php while($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?php echo $r['id']; ?>"></td>
            <td><?php echo $r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['name']); ?></td>
            <td><?php echo htmlspecialchars($r['email']); ?></td>
            <td><?php echo htmlspecialchars($r['role']); ?></td>
            <td><?php echo htmlspecialchars($r['age']); ?></td>
            <td><?php echo htmlspecialchars($r['gender']); ?></td>
            <td><?php echo htmlspecialchars($r['banned_until']); ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <div class="row">
      <label>Ban for (days): <input type="number" name="ban_days" value="7" min="1"></label>
      <button class="btn" type="submit">Ban Selected</button>
      <button class="btn danger" name="delete" value="1">Delete Selected</button>
    </div>
  </form>
</div>

<script>
try{if(window.location.search.indexOf('report_success=1')!==-1){alert('Report submitted successfully');}}catch(e){}
</script>
