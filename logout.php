<?php
require_once 'session.php';

// Уничтожение всех данных сессии
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
