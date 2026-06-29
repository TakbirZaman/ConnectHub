<?php
require_once __DIR__ . '/BaseModel.php';

class Product extends BaseModel {
  public function create($shopkeeper_id, $name, $description, $price, $imagePath = null){
    // Try with image column; fall back if it doesn't exist
    $stmt = $this->db->prepare("INSERT INTO products (shopkeeper_id, name, description, image, price) VALUES (?,?,?,?,?)");
    if ($stmt){
      $stmt->bind_param("isssd", $shopkeeper_id, $name, $description, $imagePath, $price);
      return $stmt->execute();
    } else {
      $stmt2 = $this->db->prepare("INSERT INTO products (shopkeeper_id, name, description, price) VALUES (?,?,?,?)");
      $stmt2->bind_param("issd", $shopkeeper_id, $name, $description, $price);
      return $stmt2->execute();
    }
  }

  public function allWithShop(){
    $res = $this->db->query("
      SELECT p.*, u.name AS shop
      FROM products p
      JOIN users u ON u.id = p.shopkeeper_id
      ORDER BY p.created_at DESC
    ");
    $rows = [];
    while($r = $res->fetch_assoc()){ $rows[] = $r; }
    return $rows;
  }
}
