<?php
session_start();
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') { http_response_code(403); exit; }

$uid = (int)$_SESSION['user']['id'];
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

require_once __DIR__ . '/../model/Friend.php';
$friendModel = new Friend($conn);

// If empty query, return empty array (leave UI as-is)
$data = $q !== '' ? $friendModel->searchUsersWithRelation($uid, $q) : [];

// Return FULL list of matches (contains search, case-insensitive handled in model/DB collation)
header('Content-Type: application/json; charset=UTF-8');
echo json_encode($data);
