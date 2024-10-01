<?php
// header.php
?>
<header>
    <div class="header-container">
        <div class="logo">
            <a href="/">Школьный портал</a>
        </div>
        <div class="user-menu">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                    <span class="user-icon"><i class="fas fa-user"></i></span>
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
                <a href="/login"><i class="fas fa-sign-in-alt"></i> Войти</a>
            <?php endif; ?>
        </div>
    </div>
</header>
