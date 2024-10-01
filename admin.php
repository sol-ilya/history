<?php
require_once 'config/config.php';
require_once 'config/functions.php';

// Проверка, авторизован ли пользователь и является ли он админом
if (!isLoggedIn()) {
    $_SESSION['goto_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: /login');
    exit();
}
$_SESSION['goto_after_login'] = null; // На всякий случай

if (!isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Доступ запрещен.';
    exit();
}

// Функция для сохранения данных об учениках в базу данных
function saveStudents($pdo, $students) {
    foreach ($students as $student) {
        if (isset($student['id'])) {
            // Обновляем данные ученика
            $stmt = $pdo->prepare('UPDATE students SET name = ?, was_present_before = ?, is_present_now = ?, marks = ? WHERE id = ?');
            $stmt->execute([
                $student['name'],
                $student['wasPresentBefore'] ? 1 : 0,
                $student['isPresentNow'] ? 1 : 0,
                (int)$student['marks'],
                $student['id']
            ]);
        }
    }
}

// Функция для переноса данных к следующему уроку
function moveToNextLesson($pdo) {
    // Переносим данные: isPresentNow -> was_present_before, is_present_now устанавливаем в true
    $stmt = $pdo->prepare('UPDATE students SET was_present_before = is_present_now, is_present_now = 1');
    $stmt->execute();
}

// Обработка отправленной формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['move_to_next_lesson'])) {
        // Выполняем перенос данных к следующему уроку
        moveToNextLesson($pdo);
        $message = "Данные успешно перенесены к следующему уроку.";
    } else {
        // Обновление данных учеников через форму
        $updatedStudents = [];
        if (isset($_POST['students']) && is_array($_POST['students'])) {
            foreach ($_POST['students'] as $index => $data) {
                if (isset($data['id'])) {
                    $updatedStudents[] = [
                        'id' => $data['id'],
                        'name' => trim($data['name']),
                        'wasPresentBefore' => isset($data['wasPresentBefore']),
                        'isPresentNow' => isset($data['isPresentNow']),
                        'marks' => (int)$data['marks']
                    ];
                }
            }
        }
        if (!empty($updatedStudents)) {
            saveStudents($pdo, $updatedStudents);
            $message = "Данные успешно сохранены.";
        } else {
            $message = "Нет данных для сохранения.";
        }
    }
}

// Читаем текущие данные об учениках
$students = readStudents($pdo);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Консоль администратора</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <h1>Консоль администратора</h1>
        <?php if (isset($message)): ?>
            <p class="success"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <!-- Кнопка для переноса данных к следующему уроку -->
        <form method="post" style="margin-bottom: 20px;">
            <input type="submit" name="move_to_next_lesson" value="Перенести данные к следующему уроку">
        </form>

        <!-- Форма для редактирования данных учеников -->
        <form method="post">
            <table>
                <tr>
                    <th>ФИО</th>
                    <th>Прошлый урок</th>
                    <th>Этот урок</th>
                    <th>Количество оценок</th>
                </tr>
                <?php foreach ($students as $index => $student): ?>
                    <tr>
                        <td>
                            <!-- Добавляем скрытое поле ID -->
                            <input type="hidden" name="students[<?php echo $index; ?>][id]" value="<?php echo $student['id']; ?>">
                            <input type="text" name="students[<?php echo $index; ?>][name]" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                        </td>
                        <td>
                            <input type="checkbox" name="students[<?php echo $index; ?>][wasPresentBefore]" <?php if ($student['was_present_before']) echo 'checked'; ?>>
                        </td>
                        <td>
                            <input type="checkbox" name="students[<?php echo $index; ?>][isPresentNow]" <?php if ($student['is_present_now']) echo 'checked'; ?>>
                        </td>
                        <td>
                            <input type="number" name="students[<?php echo $index; ?>][marks]" value="<?php echo $student['marks']; ?>" min="0">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <input type="submit" value="Сохранить">
        </form>
    </div>
    <footer>
        &copy; <?php echo date('Y'); ?> Школьный портал
    </footer>
    <script src="js/dropdownToggle.js"></script>
</body>
</html>
