<?php
$host = 'localhost';
$db = 'school';
$user = 'user';  // Замените на вашего пользователя
$pass = 'pass123';      // Замените на ваш пароль
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Режим обработки ошибок
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Режим выборки по умолчанию
    PDO::ATTR_EMULATE_PREPARES   => false, // Отключение эмуляции подготовленных запросов
];
?>
