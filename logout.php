<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Tüm oturum değişkenlerini temizle
$_SESSION = array();
 
// Oturumu sonlandır
session_destroy();
 
// Kullanıcıyı giriş sayfasına yönlendir
header("location: login.php");
exit;
?>
