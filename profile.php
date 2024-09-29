<?php
session_start();
// Включение отображения ошибок для отладки (удалите или закомментируйте в продакшене)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connect.php';
require_once 'functions.php';

// Проверка, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: /');
    exit();
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Подключение не удалось: ' . $e->getMessage());
}

$errors = [];
$success = '';
$success_password = '';

// Получение ID пользователя из сессии
$user_id = $_SESSION['user_id'];

// Обработка обновления профиля (никнейм и Telegram)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nickname = trim($_POST['nickname'] ?? '');
    $telegram = trim($_POST['telegram'] ?? '');

    // Валидация данных
    if (strlen($nickname) > 50) {
        $errors[] = 'Никнейм не должен превышать 50 символов.';
    }

    if (strlen($telegram) > 50) {
        $errors[] = 'Telegram username не должен превышать 50 символов.';
    }

    if (empty($errors)) {
        // Обновление данных
        $stmt = $pdo->prepare('UPDATE users SET nickname = ?, telegram = ? WHERE id = ?');
        try {
            $stmt->execute([$nickname ?: null, $telegram ?: null, $user_id]);
            $_SESSION['success'] = 'Данные успешно обновлены.';
        } catch (PDOException $e) {
            $errors[] = 'Ошибка при обновлении данных: ' . $e->getMessage();
        }
    }
}

// Обработка изменения пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Валидация данных
    if (empty($current_password)) {
        $errors[] = 'Введите текущий пароль.';
    }

    if (empty($new_password)) {
        $errors[] = 'Введите новый пароль.';
    } elseif (strlen($new_password) < 6) {
        $errors[] = 'Новый пароль должен содержать минимум 6 символов.';
    }

    if ($new_password !== $confirm_password) {
        $errors[] = 'Новый пароль и подтверждение не совпадают.';
    }

    if (empty($errors)) {
        // Получение текущего хеша пароля из базы данных
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user_password = $stmt->fetchColumn();

        if ($user_password && password_verify($current_password, $user_password)) {
            // Хеширование нового пароля
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            // Обновление пароля в базе данных
            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            try {
                $stmt->execute([$new_password_hash, $user_id]);
                $_SESSION['success_password'] = 'Пароль успешно изменен.';
            } catch (PDOException $e) {
                $errors[] = 'Ошибка при изменении пароля: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Неверный текущий пароль.';
        }
    }
}

// Получение текущих данных пользователя вместе с фамилией из таблицы students
$stmt = $pdo->prepare('
    SELECT u.*, s.name 
    FROM users u 
    JOIN students s ON u.student_id = s.id 
    WHERE u.id = ?
');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die('Пользователь не найден.');
}

// Проверка сообщений об успехе из сессии
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['success_password'])) {
    $success_password = $_SESSION['success_password'];
    unset($_SESSION['success_password']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль</title>
    <link rel="stylesheet" href="style.css">
    <!-- Подключение Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <h1>Профиль</h1>

        <!-- Сообщения об успехе -->
        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if ($success_password): ?>
            <p class="success"><?php echo htmlspecialchars($success_password); ?></p>
        <?php endif; ?>

        <!-- Сообщения об ошибках -->
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Информация о пользователе -->
        <div class="profile-section">
            <h2>Основная информация</h2>
            <div class="profile-info">
                <p><strong>ФИО:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                <p><strong>Имя пользователя:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <!-- <p><strong>Никнейм:</strong> <?php echo htmlspecialchars($user['nickname'] ?? 'Не указано'); ?></p>
                <p><strong>Telegram:</strong> <?php echo htmlspecialchars($user['telegram'] ?? 'Не указано'); ?></p> -->
            </div>
        </div>

        <!-- Форма для обновления профиля -->
        <div class="profile-section">
            <h2>Редактировать профиль</h2>
            <form method="post" class="profile-form">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group">
                    <label for="nickname">Никнейм:</label>
                    <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($user['nickname'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="telegram">Telegram:</label>
                    <input type="text" id="telegram" name="telegram" value="<?php echo htmlspecialchars($user['telegram'] ?? ''); ?>">
                </div>

                <input type="submit" value="Сохранить изменения">
            </form>
        </div>

        <!-- Форма для изменения пароля -->
        <div class="profile-section">
            <h2>Изменить пароль</h2>
            <form method="post" class="profile-form">
                <input type="hidden" name="change_password" value="1">
                <div class="form-group">
                    <label for="current_password">Текущий пароль:</label>
                    <input type="password" id="current_password" name="current_password" required>
                    <i class="fa-solid fa-eye toggle-password"></i>
                </div>

                <div class="form-group">
                    <label for="new_password">Новый пароль:</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <i class="fa-solid fa-eye toggle-password"></i>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Подтвердите новый пароль:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <i class="fa-solid fa-eye toggle-password"></i>
                </div>

                <input type="submit" value="Изменить пароль">
            </form>
        </div>
    </div>
    <footer>
        &copy; <?php echo date('Y'); ?> Школьный портал
    </footer>

    <!-- Подключение внешнего JavaScript-файла -->
    <script src="js/togglePassword.js"></script>
</body>
</html>
