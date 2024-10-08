<?php
require_once 'config/config.php';
require_once 'config/functions.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Чтение списка студентов без аккаунтов
$stmt = $pdo->prepare('SELECT s.id, s.name FROM students s LEFT JOIN users u ON s.id = u.student_id WHERE u.id IS NULL');
$stmt->execute();
$available_students = $stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Неверный CSRF токен');
    }
    $student_id = $_POST['student_id'] ?? '';
    $username = sanitizeString($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $nickname = sanitizeString($_POST['nickname'] ?? '');
    $telegram = sanitizeString($_POST['telegram'] ?? '');

    // Валидация данных
    if (empty($student_id)) {
        $errors[] = 'Выберите ученика.';
    }

    if (empty($username)) {
        $errors[] = 'Введите имя пользователя.';
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $errors[] = 'Имя пользователя должно содержать от 3 до 20 символов и состоять только из букв, цифр и подчёркиваний.';
    }

    if (empty($password)) {
        $errors[] = 'Введите пароль.';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'Пароли не совпадают.';
    }

    // Проверка существования студента
    $stmt = $pdo->prepare('SELECT * FROM students s LEFT JOIN users u ON s.id = u.student_id WHERE s.id = ?');
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if (!$student) {
        $errors[] = 'Выбранный ученик не существует.';
    } elseif ($student['id']) { // если u.id не NULL
        $errors[] = 'Этот ученик уже привязан к аккаунту.';
    }

    // Проверка уникальности имени пользователя
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $errors[] = 'Это имя пользователя уже занято.';
    }

    if (empty($errors)) {
        // Хеширование пароля
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Вставка нового пользователя
        $stmt = $pdo->prepare('INSERT INTO users (id, student_id, username, nickname, telegram, password_hash) VALUES (NULL, ?, ?, ?, ?, ?)');
        try {
            $stmt->execute([$student_id, $username, $nickname ?: null, $telegram ?: null, $password_hash]);

            $user_id = $pdo->lastInsertId();

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            
            if (isset($_POST['remember_me'])) {
                // Генерация уникального токена
                $token = bin2hex(random_bytes(32));

                // Хеширование токена для хранения в базе данных
                $token_hash = hash('sha256', $token);

                // Установка времени истечения токена (например, 30 дней)
                $expires_at = date('Y-m-d H:i:s', time() + (86400 * 30));

                // Сохранение токена в базе данных
                $stmt = $pdo->prepare('INSERT INTO user_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
                $stmt->execute([$user_id, $token_hash, $expires_at]);

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
        } catch (PDOException $e) {
            $errors[] = 'Ошибка при регистрации: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Регистрация</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1>Регистрация</h1>
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label for="student_id">Выберите ученика:</label>
                <select id="student_id" name="student_id" required>
                    <option value="">-- Выберите ученика --</option>
                    <?php foreach ($available_students as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="username">Имя пользователя:</label>
                <input type="text" id="username" name="username" autocomplete="username" 
                        placeholder="username" required value="<?php echo $_POST['username'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" autocomplete="new-password" required>
                <i class="fa-solid fa-eye toggle-password"></i>
            </div>

            <div class="form-group">
                <label for="password_confirm">Подтверждение пароля:</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
                <i class="fa-solid fa-eye toggle-password"></i>
            </div>

            <div class="form-group">
                <label for="nickname">Никнейм (необязательно):</label>
                <input type="text" id="nickname" name="nickname" 
                        placeholder="Мой ник" value="<?php echo $_POST['nickname'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label for="telegram">Telegram (необязательно):</label>
                <input type="text" id="telegram" name="telegram" 
                        placeholder="@telegram_username" value="<?php echo $_POST['telegram'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <input type="checkbox" id="remember_me" name="remember_me" checked>
                <label for="remember_me">Запомнить меня</label>
            </div>

            <input type="submit" value="Зарегистрироваться">
        </form>
        <p>Уже есть аккаунт? <a href="/login">Войдите</a>.</p>
    </div>
    <?php include 'config/footer.php'; ?>

    <script src="js/togglePassword.js"></script>
</body>
</html>
