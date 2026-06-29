<?php
require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
  public function getById($id){
    $stmt = $this->db->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  public function updateProfile($id, $bio, $profile_pic){
    $stmt = $this->db->prepare("UPDATE users SET bio=?, profile_pic=? WHERE id=?");
    $stmt->bind_param("ssi", $bio, $profile_pic, $id);
    return $stmt->execute();
  }
}
