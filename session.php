<?php
// session.php

// Настройка параметров куки для сессии
session_set_cookie_params([
    'lifetime' => 86400 * 30, // 30 дней
    'path' => '/',
    'domain' => '', // Укажите домен, если необходимо
    'secure' => false, // Установите true, если используете HTTPS
    'httponly' => true,
    'samesite' => 'Lax', // Также может быть 'Strict' или 'None' при необходимости
]);

// Запуск сессии
session_start();
?>
