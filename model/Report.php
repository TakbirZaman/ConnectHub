<?php
// model/Report.php
require_once __DIR__ . '/BaseModel.php';

class Report extends BaseModel {

  // Create reports table if it doesn't exist
  public function ensureTable(){
    $sql = "CREATE TABLE IF NOT EXISTS reports (
              id INT AUTO_INCREMENT PRIMARY KEY,
              admin_id INT NOT NULL,
              target_id INT NOT NULL,
              message TEXT NOT NULL,
              seen TINYINT(1) DEFAULT 0,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $this->db->query($sql);
  }

  // Insert a new report (helper in case we want to use it elsewhere)
  public function add($adminId, $targetId, $message){
    $this->ensureTable();
    $stmt = $this->db->prepare("INSERT INTO reports (admin_id, target_id, message) VALUES (?,?,?)");
    $stmt->bind_param("iis", $adminId, $targetId, $message);
    $stmt->execute();
    return $this->db->insert_id;
  }

  // Get unseen alerts for a user
  public function getAlertsForTarget($uid){
    $this->ensureTable();
    $stmt = $this->db->prepare("SELECT id, message, created_at FROM reports WHERE target_id=? AND seen=0 ORDER BY id DESC");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()){
      $rows[] = $row;
    }
    return $rows;
  }

  public function markSeenForUser($uid){
    $stmt = $this->db->prepare("UPDATE reports SET seen=1 WHERE target_id=? AND seen=0");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
  }
}
