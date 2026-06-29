<?php if(isset($_SESSION['user'])){ header("Location: index.php?page=feed"); exit; } ?>
<div class="auth-wrap">
  <div class="auth-logo">
    <img src="uploads/logo.png" alt="ConnectHub" style="max-width:260px">
    <h1>ConnectHub</h1>
    <p>Where conversations spark friendships and ideas find a marketplace</p>
  </div>
  <div class="auth-form card">
    <?php if(!empty($_SESSION['flash_bad'])): ?>
      <div class="bad"><?= htmlspecialchars($_SESSION['flash_bad']); unset($_SESSION['flash_bad']); ?></div>
    <?php endif; ?>
    <?php
    if($_SERVER['REQUEST_METHOD']=='POST'){
        if(!csrf_validate()){ echo '<div class="bad">Session expired. Please try again.</div>'; }
        else {
            $email=trim($_POST['email'] ?? '');
            $password=$_POST['password'] ?? '';

            if(!brute_force_allowed($email)){ /* message set by brute_force_allowed */ }
            else {
                $stmt=$conn->prepare("SELECT * FROM users WHERE email=?");
                $stmt->bind_param("s",$email);
                $stmt->execute();
                $res=$stmt->get_result();
                if($user=$res->fetch_assoc()){
                    if($user['banned_until'] && $user['banned_until'] > date('Y-m-d')){
                        echo '<p class="bad">You are banned until '.$user['banned_until'].'</p>';
                    } elseif(password_verify($password,$user['password'])){
                        brute_force_reset($email);
                        sec_session_regenerate();
                        $_SESSION['user']=$user;
                        header("Location: index.php?page=feed");
                        exit;
                    } else {
                        brute_force_increment($email);
                        echo '<p class="bad">Invalid credentials</p>';
                    }
                } else {
                    brute_force_increment($email);
                    echo '<p class="bad">Invalid credentials</p>';
                }
            }
        }
    }
    ?>
    <form method="post">
      <?= csrf_field() ?>
      <label>Email</label>
      <input name="email" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <button style="width:100%;margin-top:10px">Login</button>
    </form>
    <p style="margin-top:10px">No account? <a href="index.php?page=register">Register</a></p>
  </div>
</div>
