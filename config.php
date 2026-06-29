<?php
// Basic MySQLi (OOP) connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "connecthub";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

require_once __DIR__ . '/security.php';
?>
