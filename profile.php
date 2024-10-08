<?php
require_once 'config/config.php';
require_once 'config/functions.php';

require_once 'config/users_only.php';

$errors = [];
$success = '';
$success_password = '';
$success_key = '';

// Получение ID пользователя из сессии
$user_id = $_SESSION['user_id'];

// Обработка обновления профиля (никнейм и Telegram)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nickname = sanitizeString($_POST['nickname'] ?? '');
    $telegram = sanitizeString($_POST['telegram'] ?? '');

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

// Обработка генерации API-токена
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_key'])) {
    // Генерация безопасного случайного токена
    $key = bin2hex(random_bytes(16)); // 32-символьный токен

    // Обновление токена в базе данных
    $stmt = $pdo->prepare('UPDATE users SET api_key = ? WHERE id = ?');
    try {
        $stmt->execute([$key, $user_id]);
        // Установка сообщения успеха
        $_SESSION['success_key'] = 'API-токен успешно сгенерирован.';
        // Редирект с фрагментом
        header('Location: /profile#api-key-section');
        exit();
    } catch (PDOException $e) {
        $errors[] = 'Ошибка при генерации токена: ' . $e->getMessage();
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

if (isset($_SESSION['success_key'])) {
    $success_key = $_SESSION['success_key'];
    unset($_SESSION['success_key']);
}
?>

<?php include 'config/header.php'; ?>

<div class="container container-custom">
    <h1 class="mb-4">Профиль</h1>

    <!-- Сообщения об успехе -->
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($success_password): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_password); ?></div>
    <?php endif; ?>
    <?php if ($success_key): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_key); ?></div>
    <?php endif; ?>

    <!-- Сообщения об ошибках -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
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
        </div>
    </div>

    <!-- Форма для обновления профиля -->
    <div class="profile-section">
        <h2>Редактировать профиль</h2>
        <form method="post" class="mx-auto" style="max-width: 500px;">
            <input type="hidden" name="update_profile" value="1">
            <div class="form-group">
                <label for="nickname">Никнейм:</label>
                <input type="text" id="nickname" name="nickname" class="form-control"
                        placeholder="Мой ник" value="<?php echo htmlspecialchars($user['nickname'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="telegram">Telegram:</label>
                <input type="text" id="telegram" name="telegram" class="form-control"
                        placeholder="@telegram_username" value="<?php echo htmlspecialchars($user['telegram'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </form>
    </div>

    <!-- Форма для изменения пароля -->
    <div class="profile-section">
        <h2>Изменить пароль</h2>
        <form method="post" class="mx-auto" style="max-width: 500px;">
            <input type="hidden" name="change_password" value="1">
            <div class="form-group position-relative">
                <label for="current_password">Текущий пароль:</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required>
                <i class="fa-solid fa-eye toggle-password position-absolute" style="top: 38px; right: 10px; cursor: pointer;"></i>
            </div>

            <div class="form-group position-relative">
                <label for="new_password">Новый пароль:</label>
                <input type="password" id="new_password" name="new_password" class="form-control" autocomplete="new-password" required>
                <i class="fa-solid fa-eye toggle-password position-absolute" style="top: 38px; right: 10px; cursor: pointer;"></i>
            </div>

            <div class="form-group position-relative">
                <label for="confirm_password">Подтвердите новый пароль:</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" autocomplete="new-password" required>
                <i class="fa-solid fa-eye toggle-password position-absolute" style="top: 38px; right: 10px; cursor: pointer;"></i>
            </div>

            <button type="submit" class="btn btn-primary">Изменить пароль</button>
        </form>
    </div>

    <!-- Форма для генерации API-ключа -->
    <div class="profile-section" id="api-key-section">
        <h2>Управление API-ключом</h2>
        <form method="post" class="mx-auto" style="max-width: 500px;">
            <input type="hidden" name="generate_key" value="1">
            <div class="form-group">
                <label for="api_key">Ваш API-ключ:</label>
                <input type="text" id="api_key" class="form-control" value="<?php echo htmlspecialchars($user['api_key'] ?? 'Не сгенерирован'); ?>" readonly>
                <?php if ($user['api_key']): ?>
                    <button type="button" id="copy_key" class="btn btn-secondary copy-button">Скопировать</button>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Сгенерировать новый ключ</button>
        </form>
    </div>
</div>

<?php include 'config/footer.php'; ?>