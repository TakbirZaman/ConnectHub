<?php
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin'){ header("Location: index.php?page=login"); exit; }

// actions
// ensure reports table exists for moderation alerts
$conn->query("CREATE TABLE IF NOT EXISTS reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  target_id INT NOT NULL,
  message TEXT NOT NULL,
  seen TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if($_SERVER['REQUEST_METHOD']=='POST'){
  $ids = $_POST['ids'] ?? [];
  $action = $_POST['action'] ?? 'report';
  $msg = trim($_POST['message'] ?? '');
  $until = $_POST['until'] ?? null;
  foreach($ids as $id){
    $id=(int)$id;
    if($action==='delete'){
      $conn->query("DELETE FROM users WHERE id=$id");
    } elseif($action==='ban'){
      $conn->query("UPDATE users SET banned_until='".($until ?: date('Y-m-d'))."' WHERE id=$id");
      $stmt=$conn->prepare("INSERT INTO reports(admin_id,target_id,message) VALUES(?,?,?)");
      $a='ban'; $aid=$_SESSION['user']['id'];
      $stmt->bind_param("iis",$aid,$id,$msg);
      $stmt->execute();
    } else {
      $stmt=$conn->prepare("INSERT INTO reports(admin_id,target_id,message) VALUES(?,?,?)");
      $aid=$_SESSION['user']['id'];
      $stmt->bind_param("iis",$aid,$id,$msg);
      $stmt->execute();
    }
  }
  echo '<div class="good">Action completed.</div>';
}

$all=$conn->query("SELECT id,name,email,role FROM users WHERE role IN ('user','shopkeeper') ORDER BY role,name");
?>
<div class="card">
  <h3>Moderation</h3>
  <form method="post">
    <table class="table">
      <thead><tr><th><input type="checkbox" onclick="for(let c of document.querySelectorAll('.chk')) c.checked=this.checked"></th><th>Name</th><th>Email</th><th>Role</th></tr></thead>
      <tbody>
        <?php while($u=$all->fetch_assoc()): ?>
          <tr>
            <td><input class="chk" type="checkbox" name="ids[]" value="<?php echo $u['id']; ?>"></td>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo $u['role']; ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <div class="grid grid-2" style="margin-top:10px">
      <div>
        <label>Action</label>
        <select name="action">
          <option value="report">Report</option>
          <option value="ban">Ban (limited time)</option>
          <option value="delete">Delete</option>
        </select>
      </div>
      <div>
        <label>Ban until</label>
        <input type="date" name="until">
      </div>
    </div>
    <label>Message</label>
    <textarea name="message" placeholder="Reason..."></textarea>
    <button>Apply</button>
  </form>
</div>

<script>
try{if(window.location.search.indexOf('report_success=1')!==-1){alert('Report submitted successfully');}}catch(e){}
</script>
