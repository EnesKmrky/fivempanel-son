<?php
// db.php - Veritabanı bağlantı bilgilerini buraya gir.
$servername = "188.191.107.176";
$username = "paneluser"; // Hosting bilgilerine göre değiştir
$password = "enesenes6636!#!"; // Hosting bilgilerine göre değiştir
$dbname = "effronte13_pck"; // SQL dosyasındaki veritabanı adın

// Bağlantı oluştur
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
?>
