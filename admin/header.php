<!-- admin/header.php -->

<?php
// Проверка, авторизован ли администратор
require_once 'admins_only.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Консоль - <?php echo htmlspecialchars($pageTitle ?? ''); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Подключение Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Подключение кастомных стилей -->
    <link rel="stylesheet" href="/style.css">
    <!-- Подключение Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Подключение Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <!-- Навигация для админ-панели -->
    <nav class="navbar navbar-expand-lg bg-dark navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin">Консоль</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" 
                aria-controls="adminNavbar" aria-expanded="false" aria-label="Переключить навигацию">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/students_data">Управление данными учеников</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/lessons">Управление расписанием</a>
                    </li>
                    <!-- Добавьте другие пункты меню по необходимости -->
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Вернуться на сайт</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container container-custom">
