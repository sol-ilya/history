<?php
// logout.php
require_once 'config/config.php';

// Удаление токена из базы данных
if (isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $db->revokeSessionToken($token);

    // Удаление куки
    setcookie('remember_me', '', time() - 3600, '/', '', false, true);
}

// Уничтожение сессии
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Перенаправление на главную страницу
header('Location: /');
exit();
?>
