<?php

session_start();
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private function render($view, $data=[]) {
        extract($data);
        require __DIR__ . '/../views/partials/header.php';
        require __DIR__ . '/../views/' . $view . '.php';
        require __DIR__ . '/../views/partials/footer.php';
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $pass  = $_POST['password'] ?? '';
            $errors = [];

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email.";
            if (strlen($pass) < 1) $errors[] = "Password required.";

            if (!$errors) {
                $U = new User();
                $user = $U->findByEmail($email);
                if ($user && password_verify($pass, $user['password_hash'])) {
                    if ($user['banned_until'] && strtotime($user['banned_until']) > time()) {
                        $errors[] = "You are banned until " . $user['banned_until'];
                    } else {
                        $_SESSION['uid'] = $user['id'];
                        $_SESSION['role'] = $user['role'];
                        if ($user['role'] === 'user') header("Location: index.php?controller=user&action=feed");
                        elseif ($user['role'] === 'shopkeeper') header("Location: index.php?controller=shopkeeper&action=marketplace");
                        else header("Location: index.php?controller=admin&action=dashboard");
                        exit;
                    }
                } else $errors[] = "Wrong credentials.";
            }
            $this->render('auth/login', ['errors'=>$errors]);
        } else {
            $this->render('auth/login');
        }
    }

    public function register() {
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $age = intval($_POST['age'] ?? 0);
            $gender = $_POST['gender'] ?? '';
            $role = $_POST['role'] ?? 'user';
            $pass = $_POST['password'] ?? '';
            $cpass = $_POST['confirm_password'] ?? '';

            if (!preg_match("/^[a-zA-Z ]+$/", $name)) $errors[] = "Name must contain letters and spaces only.";
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email.";
            if ($age < 18 || $age > 100) $errors[] = "Age must be between 18 and 100.";
            if (!in_array($gender, ['male','female'])) $errors[] = "Gender must be male or female.";
            if (!in_array($role, ['user','shopkeeper','admin'])) $errors[] = "Invalid role.";
            if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\W).{8,}$/", $pass)) $errors[] = "Password must be 8+ chars, include upper, lower, special.";
            if ($pass !== $cpass) $errors[] = "Passwords do not match.";

            if (!$errors) {
                $U = new User();
                if ($U->findByEmail($email)) { $errors[] = "Email already registered."; }
                else {
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    if ($U->create($name, $email, $age, $gender, $role, $hash)) {
                        header("Location: index.php?controller=auth&action=login&registered=1");
                        exit;
                    } else $errors[] = "Registration failed.";
                }
            }
        }
        $this->render('auth/register', ['errors'=>$errors]);
    }

    public function logout() {
        session_destroy();
        header("Location: index.php?controller=auth&action=login");
        exit;
    }
}
?>
