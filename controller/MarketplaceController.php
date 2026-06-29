<?php
if(!isset($_SESSION['user'])){ header("Location: index.php?page=login"); exit; }
$me   = $_SESSION['user'];
$role = $me['role'];

require_once __DIR__ . '/../model/Product.php';
$productModel = new Product($conn);

/* Create product (shopkeeper) */
if($role==='shopkeeper' && $_SERVER['REQUEST_METHOD']==='POST'){
  if(!csrf_validate()){ header("Location: index.php?page=marketplace"); exit; }
  $name = trim($_POST['name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $price = (float)($_POST['price'] ?? 0);

  $imagePath = null;
  if(!empty($_FILES['image']['name'])){
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if(in_array($ext,$allowed,true)){
      if(!is_dir('uploads/products')){ @mkdir('uploads/products',0777,true); }
      $imagePath = 'uploads/products/'.time().'_'.rand(1000,9999).'.'.$ext;
      move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
    }
  }

  if($name !== '' && $price >= 0){
    $productModel->create((int)$me['id'], $name, $desc, $price, $imagePath);
  }
  header("Location: index.php?page=marketplace"); exit;
}

/* Data for view */
$products = $productModel->allWithShop();

include __DIR__ . '/../view/marketplace.php';
