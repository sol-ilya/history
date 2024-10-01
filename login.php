<?php
require_once 'config/config.php';
require_once 'config/functions.php';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Подключение не удалось: ' . $e->getMessage());
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors[] = 'Пожалуйста, заполните все поля.';
    } else {
        // Поиск пользователя по имени пользователя
        $stmt = $pdo->prepare('SELECT id, password_hash, is_admin FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Успешный вход

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = $user['is_admin'];
    
            if (isset($_POST['remember_me'])) {
                // Генерация уникального токена
                $token = bin2hex(random_bytes(32));
    
                // Хеширование токена для хранения в базе данных
                $token_hash = hash('sha256', $token);
    
                // Установка времени истечения токена (например, 30 дней)
                $expires_at = date('Y-m-d H:i:s', time() + (86400 * 30));
    
                // Сохранение токена в базе данных
                $stmt = $pdo->prepare('INSERT INTO user_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
                $stmt->execute([$user['id'], $token_hash, $expires_at]);
    
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
            exit;
        } else {
            $errors[] = 'Неверное имя пользователя или пароль.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1>Вход</h1>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Имя пользователя:</label>
                <input type="text" id="username" name="username" autocomplete="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
                <i class="fa-solid fa-eye toggle-password"></i>
            </div>

            <div class="form-group">
                <input type="checkbox" id="remember_me" name="remember_me" checked>
                <label for="remember_me">Запомнить меня</label>
            </div>

            <input type="submit" value="Войти">
        </form>
        <p>Нет аккаунта? <a href="/signup">Зарегистрируйтесь</a>.</p>
    </div>
    <footer>
        &copy; <?php echo date('Y'); ?> Школьный портал
    </footer>

    <script src="js/togglePassword.js"></script>
</body>
</html>
