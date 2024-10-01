<?php
// Проверка, авторизован ли пользователь и является ли он админом
require_once 'config.php';
if (!isLoggedIn()) {
    $_SESSION['goto_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: /login');
    exit();
}
$_SESSION['goto_after_login'] = null; // На всякий случай
?>