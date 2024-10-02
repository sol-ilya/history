<?php
require_once 'config/config.php';
require_once 'config/functions.php';
require_once 'config/admins_only.php';

$errors = [];
$success = '';

// Обработка формы добавления или обновления даты урока
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем, какая форма была отправлена
    if (isset($_POST['add_date'])) {
        // Обработка добавления новой даты
        $newDate = $_POST['new_lesson_date'] ?? '';
        $lessonType = $_POST['lesson_type'] ?? 'lesson';

        if (validateDate($newDate)) {
            // Проверяем, существует ли уже эта дата
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM lesson_dates WHERE lesson_date = ?');
            $stmt->execute([$newDate]);
            $exists = $stmt->fetchColumn();

            if ($exists) {
                $errors[] = 'Эта дата уже существует.';
            } else {
                // Добавляем новую дату в базу данных
                $stmt = $pdo->prepare('INSERT INTO lesson_dates (lesson_date, lesson_type) VALUES (?, ?)');
                try {
                    $stmt->execute([$newDate, $lessonType]);
                    $success = 'Дата успешно добавлена.';
                } catch (PDOException $e) {
                    $errors[] = 'Ошибка при добавлении даты: ' . $e->getMessage();
                }
            }
        } else {
            $errors[] = 'Введите корректную дату.';
        }
    } elseif (isset($_POST['update_type'])) {
        // Обработка обновления типа урока
        $dateToUpdate = $_POST['date_to_update'] ?? '';
        $newLessonType = $_POST['new_lesson_type'] ?? 'lesson';

        if (validateDate($dateToUpdate)) {
            // Обновляем тип урока в базе данных
            $stmt = $pdo->prepare('UPDATE lesson_dates SET lesson_type = ? WHERE lesson_date = ?');
            try {
                $stmt->execute([$newLessonType, $dateToUpdate]);
                $success = 'Тип урока успешно обновлён.';
            } catch (PDOException $e) {
                $errors[] = 'Ошибка при обновлении типа урока: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Некорректная дата для обновления.';
        }
    }
}

// Обработка удаления даты урока
if (isset($_GET['delete_date'])) {
    $dateToDelete = $_GET['delete_date'];

    $stmt = $pdo->prepare('DELETE FROM lesson_dates WHERE lesson_date = ?');
    try {
        $stmt->execute([$dateToDelete]);
        $success = 'Дата успешно удалена.';
    } catch (PDOException $e) {
        $errors[] = 'Ошибка при удалении даты: ' . $e->getMessage();
    }
}

// Получение всех дат уроков с типом урока
$stmt = $pdo->query('SELECT lesson_date, lesson_type FROM lesson_dates ORDER BY lesson_date ASC');
$lessonDates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление датами уроков</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Подключение Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <?php include 'config/header.php'; ?>
    <div class="container">
        <h1>Управление датами уроков</h1>

        <!-- Вывод сообщений об успехе и ошибках -->
        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
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

        <!-- Форма для добавления новой даты урока -->
        <form method="post" class="form-inline">
            <div class="form-group">
                <label for="new_lesson_date">Дата урока:</label>
                <input type="text" id="new_lesson_date" name="new_lesson_date" required>
            </div>
            <div class="form-group">
                <label for="lesson_type">Тип урока:</label>
                <select id="lesson_type" name="lesson_type">
                    <option value="lesson">Обычный урок</option>
                    <option value="exam">Зачет</option>
                </select>
            </div>
            <input type="submit" name="add_date" value="Добавить дату">
        </form>

        <!-- Список всех дат уроков с возможностью редактирования и удаления -->
        <h2>Список дат уроков</h2>
        <table>
            <tr>
                <th>Дата урока</th>
                <th>Тип урока</th>
                <th>Действие</th>
            </tr>
            <?php foreach ($lessonDates as $entry): ?>
                <tr>
                    <td><?php echo date('d.m.Y', strtotime($entry['lesson_date'])); ?></td>
                    <td>
                        <!-- Форма для обновления типа урока -->
                        <form method="post" class="form-inline">
                            <input type="hidden" name="date_to_update" value="<?php echo htmlspecialchars($entry['lesson_date']); ?>">
                            <select name="new_lesson_type">
                                <option value="lesson" <?php if ($entry['lesson_type'] == 'lesson') echo 'selected'; ?>>Обычный урок</option>
                                <option value="exam" <?php if ($entry['lesson_type'] == 'exam') echo 'selected'; ?>>Зачет</option>
                            </select>
                            <input type="submit" name="update_type" value="Сохранить">
                        </form>
                    </td>
                    <td>
                        <a href="?delete_date=<?php echo urlencode($entry['lesson_date']); ?>" onclick="return confirm('Вы уверены, что хотите удалить эту дату?');">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php include 'config/footer.php'; ?>

    <!-- Подключение скриптов Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
    <script>
        flatpickr("#new_lesson_date", {
            "locale": "ru",
            "dateFormat": "d.m.Y",
            "altInput": true,
            "altFormat": "Y-m-d",
        });
    </script>
</body>
</html>
