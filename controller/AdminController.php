<?php
// app/controllers/AdminController.php
session_start();
require_once __DIR__ . '/../models/User.php';

class AdminController {
    private function authOnly() {
        if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
            header("Location: index.php?controller=auth&action=login");
            exit;
        }
    }
    private function render($view, $data=[]) {
        extract($data);
        require __DIR__ . '/../views/partials/header.php';
        require __DIR__ . '/../views/admin/' . $view . '.php';
        require __DIR__ . '/../views/partials/footer.php';
    }

    public function dashboard() {
        $this->authOnly();
        $U = new User();
        $filter = $_GET['role'] ?? null;
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $ids = array_map('intval', $_POST['ids'] ?? []);
            if (isset($_POST['ban_days'])) {
                $days = intval($_POST['ban_days']);
                $until = date('Y-m-d H:i:s', time() + ($days*86400));
                $U->banUsers($ids, $until);
            }
            if (isset($_POST['delete'])) {
                $U->deleteUsers($ids);
            }
        }
        $rows = $U->listForAdmin($filter);
        $this->render('dashboard', ['rows'=>$rows]);
    }
}
?>

<script>
try{if(window.location.search.indexOf('report_success=1')!==-1){alert('Report submitted successfully');}}catch(e){}
</script>
