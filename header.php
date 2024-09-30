<?php
// header.php
?>
<header>
    <div class="header-container">
        <div class="logo">
            <a href="/">Школьный портал</a>
        </div>
        <div class="user-menu">
            <?php if (isLoggedIn()): ?>
                <div class="dropdown">
                    <span class="user-icon">&#128100;</span> <!-- Иконка пользователя -->
                    <div class="dropdown-content">
                        <a href="/">Главная</a>
                        <a href="/profile">Профиль</a>
                        <?php if (isAdmin()): ?>
                            <a href="/admin">Консоль</a>
                        <?php endif; ?>
                        <a href="/logout">Выйти</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/login">&#128100; Войти</a>
            <?php endif; ?>
        </div>
    </div>
</header>
