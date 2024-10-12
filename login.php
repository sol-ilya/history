<?php
require_once 'config/config.php';
require_once 'config/functions.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Неверный CSRF токен');
    }
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors[] = 'Пожалуйста, заполните все поля.';
    } else {
        // Поиск пользователя по имени пользователя
        $user = $db->getUserByUsername($username, ['password_hash', 'is_admin']);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Успешный вход

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['is_admin'] = $user['is_admin'];
    
            if (isset($_POST['remember_me'])) {
                // Генерация уникального токена
                $token = $db->generateSessionToken($user['user_id']);
    
                // Установка куки с токеном
                setcookie('remember_me', $token, [
                    'expires' => time() + (86400 * 30),
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }

            if (isset($_SESSION['goto_after_login'])) {
                $page = $_SESSION['goto_after_login'];
                $_SESSION['goto_after_login'] = null;
                header("Location: $page");
                exit;
            }
            header('Location: /');
            exit();
        } else {
            $errors[] = 'Неверное имя пользователя или пароль.';
        }
    }
}
?>

<?php 
$special_page = true;
include 'config/header.php';
?>

<div class="container container-custom">
    <h1 class="text-center mb-4">Вход</h1>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="mx-auto" style="max-width: 400px;">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="mb-3">
            <label for="username" class="form-label">Имя пользователя:</label>
            <input type="text" id="username" name="username" class="form-control" autocomplete="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>

        <div class="mb-3 position-relative">
            <label for="password" class="form-label">Пароль:</label>
            <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required>
            <i class="fa-solid fa-eye toggle-password position-absolute" style="top: 38px; right: 10px; cursor: pointer;"></i>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" id="remember_me" name="remember_me" class="form-check-input" checked>
            <label for="remember_me" class="form-check-label">Запомнить меня</label>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Войти</button>
    </form>
    <p class="text-center mt-3">Нет аккаунта? <a href="/signup">Зарегистрируйтесь</a>.</p>
</div>

<?php include 'config/footer.php'; ?>
