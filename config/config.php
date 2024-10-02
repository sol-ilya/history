<?php
// config.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo 'Подключение не удалось: ' . $e->getMessage();
    exit();
}

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

    $token_hash = hash('sha256', $token);

    // Поиск токена в базе данных
    $stmt = $pdo->prepare('SELECT user_id FROM user_tokens WHERE token_hash = ? AND expires_at > NOW()');
    $stmt->execute([$token_hash]);
    $token_data = $stmt->fetch();

    if ($token_data) {
        // Авторизация пользователя

        $stmt  = $pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
        $stmt->execute([$token_data['user_id']]);
        $user = $stmt->fetch();

        $_SESSION['user_id'] = $token_data['user_id'];
        $_SESSION['is_admin'] = $user['is_admin'];

        // Обновление времени активности сессии (необязательно)
        // Можно обновить expires_at или создать новый токен
    } else {
        // Токен недействителен, удаляем куки
        setcookie('remember_me', '', time() - 3600, '/', '', false, true);
    }
}

date_default_timezone_set('Europe/Moscow');

?>
