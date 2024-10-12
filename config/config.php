<?php
// config.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
$db = new Database();
$db->connect();

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false, // Установите true при использовании HTTPS
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];

    $user_id = $db->getUserIdBySessionToken($token);

    if ($user_id) {
        // Авторизация пользователя
        $user = $db->getUserById($user_id, ['is_admin']);

        $_SESSION['user_id'] = $user_id;
        $_SESSION['is_admin'] = $user['is_admin'];

        // Обновление времени активности сессии (необязательно)
    } else {
        // Токен недействителен, удаляем куки
        setcookie('remember_me', '', time() - 3600, '/', '', false, true);
    }
}

date_default_timezone_set('Europe/Moscow');
?>
