<?php
// model/BaseModel.php
require_once __DIR__ . '/../config.php';

class BaseModel {
  protected $db;
  public function __construct($db = null){
    global $conn;               // from config.php
    $this->db = $db ?: $conn;
  }
}
