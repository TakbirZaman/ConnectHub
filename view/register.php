<?php if(isset($_SESSION['user'])){ header("Location: index.php?page=feed"); exit; } ?>
<div class="card">
<h2>Create account</h2>
<?php
if($_SERVER['REQUEST_METHOD']=='POST'){
    if(!csrf_validate()){ echo '<div class="bad">Session expired. Please try again.</div>'; }
    else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $age = (int)($_POST['age'] ?? 0);
        $gender = $_POST['gender'] ?? '';
        $role = in_array($_POST['role'] ?? '', ['user','shopkeeper'], true) ? $_POST['role'] : 'user';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        $errors=[];

        if(!preg_match('/^[a-zA-Z ]+$/',$name)) $errors[]='Name must contain letters only.';
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Invalid email.';
        if($age<18 || $age>100) $errors[]='Age must be between 18 and 100.';
        if(!in_array($gender,['male','female'])) $errors[]='Gender must be male or female.';
        if(strlen($password)<8 || !preg_match('/[A-Z]/',$password) || !preg_match('/[a-z]/',$password) || !preg_match('/[^A-Za-z0-9]/',$password))
            $errors[]='Password must be 8+ chars and include upper, lower and special chars.';
        if($password!==$confirm) $errors[]='Passwords do not match.';

        if(empty($errors)){
            $hash=password_hash($password,PASSWORD_DEFAULT);
            $stmt=$conn->prepare("INSERT INTO users(name,email,password,age,gender,role) VALUES(?,?,?,?,?,?)");
            $stmt->bind_param("sssiss",$name,$email,$hash,$age,$gender,$role);
            if($stmt->execute()){
                echo '<p class="good">Registered! You can now <a href="index.php?page=login">login</a>.</p>';
            } else {
                echo '<p class="bad">Error: '.$conn->error.'</p>';
            }
        } else {
            foreach($errors as $e) echo '<div class="bad">'.$e.'</div>';
        }
    }
}
?>
<form method="post">
  <?= csrf_field() ?>
  <div class="grid grid-2">
    <div>
      <label>Name</label><input name="name" required>
    </div>
    <div>
      <label>Email</label><input name="email" required>
    </div>
    <div>
      <label>Age</label><input type="number" name="age" min="18" max="100" required>
    </div>
    <div>
      <label>Gender</label>
      <select name="gender">
        <option value="male">male</option>
        <option value="female">female</option>
      </select>
    </div>
    <div>
      <label>Role</label>
      <select name="role">
        <option value="user" selected>user</option>
        <option value="shopkeeper">shopkeeper</option>
      </select>
    </div>
    <div></div>
    <div>
      <label>Password</label><input type="password" name="password" required>
    </div>
    <div>
      <label>Confirm Password</label><input type="password" name="confirm" required>
    </div>
  </div>
  <button style="margin-top:10px">Register</button>
</form>
</div>
