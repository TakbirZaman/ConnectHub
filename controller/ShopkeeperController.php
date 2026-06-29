<?php
// app/controllers/ShopkeeperController.php
session_start();
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Friend.php';

class ShopkeeperController {
    private function authOnly() {
        if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'shopkeeper') {
            header("Location: index.php?controller=auth&action=login");
            exit;
        }
    }
    private function render($view, $data=[]) {
        extract($data);
        require __DIR__ . '/../views/partials/header.php';
        require __DIR__ . '/../views/shopkeeper/' . $view . '.php';
        require __DIR__ . '/../views/partials/footer.php';
    }

    public function marketplace() {
        $this->authOnly();
        $Prod = new Product();
        $errors=[];
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $title = trim($_POST['title'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $img=null;
            if (!empty($_FILES['image']['name'])) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $size = $_FILES['image']['size'] ?? 0;
                $allowed=['jpg','jpeg','png','gif','webp'];
                if ($size > MAX_UPLOAD_BYTES) $errors[] = "Image too large.";
                if (!in_array($ext, $allowed)) $errors[] = "Invalid image type.";
                if (!$errors) {
                    $img = time().'_'.basename($_FILES['image']['name']);
                    move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../../public/uploads/products/' . $img);
                }
            }
            if (!$errors) $Prod->create($_SESSION['uid'],$title,$desc,$price,$img);
        }
        $items = $Prod->allByShop($_SESSION['uid']);
        $this->render('marketplace', ['items'=>$items, 'errors'=>$errors]);
    }

    public function messages() {
        $this->authOnly();
        $M = new Message();
        $with = intval($_GET['with'] ?? 0);
        $thread = $with ? $M->thread($_SESSION['uid'],$with) : null;
        if ($_SERVER['REQUEST_METHOD']==='POST' && $with) {
            $msg = trim($_POST['message'] ?? '');
            if ($msg) $M->send($_SESSION['uid'],$with,$msg);
            header("Location: index.php?controller=shopkeeper&action=messages&with=".$with);
            exit;
        }
        $this->render('messages', ['thread'=>$thread, 'with'=>$with]);
    }
}
?>
