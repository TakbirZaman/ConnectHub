<?php

session_start();
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Friend.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Product.php';

class UserController {
    private function authOnly() {
        if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'user') {
            header("Location: index.php?controller=auth&action=login");
            exit;
        }
    }
    private function render($view, $data=[]) {
        extract($data);
        require __DIR__ . '/../views/partials/header.php';
        require __DIR__ . '/../views/user/' . $view . '.php';
        require __DIR__ . '/../views/partials/footer.php';
    }

    public function feed() {
        $this->authOnly();
        $P = new Post();
        $posts = $P->allForFeed();
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
            $content = trim($_POST['content'] ?? '');
            $imgName = null;
            if (!empty($_FILES['image']['name'])) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $size = $_FILES['image']['size'] ?? 0;
                if ($size > MAX_UPLOAD_BYTES) $errors[] = "Image too large.";
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (!in_array($ext, $allowed)) $errors[] = "Invalid image type.";
                if (!$errors) {
                    $imgName = time() . '_' . basename($_FILES['image']['name']);
                    move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../../public/uploads/posts/' . $imgName);
                }
            }
            if (!$errors) {
                $P->create($_SESSION['uid'], $content, $imgName);
                header("Location: index.php?controller=user&action=feed");
                exit;
            }
        }
        $this->render('feed', ['posts'=>$posts, 'errors'=>$errors, 'P'=>new Post()]);
    }

    public function like() {
        $this->authOnly();
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $post_id = intval($_POST['post_id']);
            $P = new Post();
            $P->like($post_id, $_SESSION['uid']);
        }
        header("Location: index.php?controller=user&action=feed");
    }

    public function comment() {
        $this->authOnly();
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $post_id = intval($_POST['post_id']);
            $comment = trim($_POST['comment']);
            $P = new Post();
            $P->addComment($post_id, $_SESSION['uid'], $comment);
        }
        header("Location: index.php?controller=user&action=feed");
    }

    public function friends() {
        $this->authOnly();
        $F = new Friend();
        $incoming = $F->incomingRequests($_SESSION['uid']);
        $outgoing = $F->outgoingRequests($_SESSION['uid']);
        $friends = $F->listFriends($_SESSION['uid']);
        $this->render('friends', ['incoming'=>$incoming,'outgoing'=>$outgoing,'friends'=>$friends]);
    }

    public function friendAction() {
        $this->authOnly();
        $F = new Friend();
        $act = $_POST['act'] ?? '';
        $id = intval($_POST['id'] ?? 0);
        if ($act === 'send') $F->sendRequest($_SESSION['uid'], $id);
        if ($act === 'cancel') $F->cancelRequest($_SESSION['uid'], $id);
        if ($act === 'accept') $F->respond($id, $_SESSION['uid'], 'accepted');
        if ($act === 'reject') $F->respond($id, $_SESSION['uid'], 'rejected');
        if ($act === 'unfriend') $F->unfriend($_SESSION['uid'], $id);
        header("Location: index.php?controller=user&action=friends");
    }

    public function searchFriendsAjax() {
        $this->authOnly();
        header('Content-Type: application/json');
        $term = $_GET['q'] ?? '';
        $U = new User();
        $res = $U->searchUsersAjax($term, $_SESSION['uid']);
        $data = [];
        while ($row = $res->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
        exit;
    }

    public function messages() {
        $this->authOnly();
        $F = new Friend();
        $friends = $F->listFriends($_SESSION['uid']);
        $chatWith = intval($_GET['with'] ?? 0);
        $M = new Message();
        $thread = null;
        if ($chatWith) {
            if (!$F->areFriends($_SESSION['uid'],$chatWith)) $chatWith = 0;
            else $thread = $M->thread($_SESSION['uid'], $chatWith);
        }
        if ($_SERVER['REQUEST_METHOD']==='POST' && $chatWith) {
            $msg = trim($_POST['message'] ?? '');
            if ($msg) $M->send($_SESSION['uid'],$chatWith,$msg);
            header("Location: index.php?controller=user&action=messages&with=".$chatWith);
            exit;
        }
        $this->render('messages', ['friends'=>$friends, 'thread'=>$thread, 'chatWith'=>$chatWith]);
    }

    public function marketplace() {
        $this->authOnly();
        $P = new Product();
        $products = $P->all();
        $this->render('marketplace', ['products'=>$products]);
    }

    public function profile() {
        $this->authOnly();
        $U = new User();
        $P = new Post();
        $user = $U->findById($_SESSION['uid']);
        $posts = $P->allByUser($_SESSION['uid']);
        $errors = [];
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $bio = trim($_POST['bio'] ?? '');
            $pic = null;
            if (!empty($_FILES['profile']['name'])) {
                $ext = strtolower(pathinfo($_FILES['profile']['name'], PATHINFO_EXTENSION));
                $size = $_FILES['profile']['size'] ?? 0;
                if ($size > MAX_UPLOAD_BYTES) $errors[] = "Image too large.";
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (!in_array($ext, $allowed)) $errors[] = "Invalid image type.";
                if (!$errors) {
                    $pic = time().'_'.basename($_FILES['profile']['name']);
                    move_uploaded_file($_FILES['profile']['tmp_name'], __DIR__ . '/../../public/uploads/profiles/' . $pic);
                }
            }
            if (!$errors) {
                $U->updateProfile($_SESSION['uid'], $bio, $pic);
                header("Location: index.php?controller=user&action=profile");
                exit;
            }
        }
        $this->render('profile', ['user'=>$user, 'posts'=>$posts, 'errors'=>$errors]);
    }

    public function friendProfile() {
        $this->authOnly();
        $id = intval($_GET['id'] ?? 0);
        $U = new User();
        $P = new Post();
        $F = new Friend();
        $user = $U->findById($id);
        if (!$user || $user['role']!=='user') { header("Location: index.php?controller=user&action=friends"); exit; }
        $canSee = $F->areFriends($_SESSION['uid'],$id) || $id===$_SESSION['uid'];
        $posts = $canSee ? $P->allByUser($id) : null;
        $this->render('friend_profile', ['friend'=>$user, 'canSee'=>$canSee, 'posts'=>$posts]);
    }
}
?>
