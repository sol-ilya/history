<?php
require_once '../config/config.php';
require_once '../config/functions.php';

$pageTitle = 'Управление расписанием';

$errors = [];
$success = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Обработка формы добавления или обновления даты урока
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    // Проверяем, какая форма была отправлена
    if (isset($_POST['add_date'])) {
        // Обработка добавления новой даты
        $newDate = $_POST['new_lesson_date'] ?? '';
        $lessonType = $_POST['lesson_type'] ?? 'lesson';

        if (validateDate($newDate)) {
            // Проверяем, существует ли уже эта дата
            $exists = $db->fetch('SELECT COUNT(*) AS count FROM lesson_dates WHERE lesson_date = ?', [$newDate])['count'];

            if ($exists) {
                $errors[] = 'Эта дата уже существует.';
            } else {
                // Добавляем новую дату в базу данных
                try {
                    $db->addLessonDate($newDate, $lessonType);
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
            try {
                $db->updateLessonType($dateToUpdate, $newLessonType);
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

    try {
        $db->deleteLessonDate($dateToDelete);
        $success = 'Дата успешно удалена.';
    } catch (PDOException $e) {
        $errors[] = 'Ошибка при удалении даты: ' . $e->getMessage();
    }
}

// Получение всех дат уроков с типом урока
$lessonDates = $db->getLessonDates();
?>

<?php include 'header.php'; ?>
<h1 class="mb-4">Управление расписанием</h1>

<!-- Вывод сообщений об успехе и ошибках -->
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
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

<!-- Форма для добавления новой даты урока -->
<form method="post" class="mb-4">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <div class="row align-items-end">
        <div class="col-md-4 mb-3">
            <label for="new_lesson_date" class="form-label">Дата урока:</label>
            <input type="text" id="new_lesson_date" name="new_lesson_date" class="form-control" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="lesson_type" class="form-label">Тип урока:</label>
            <select id="lesson_type" name="lesson_type" class="form-select">
                <option value="lesson">Обычный урок</option>
                <option value="exam">Зачет</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <button type="submit" name="add_date" class="btn btn-primary w-100">Добавить дату</button>
        </div>
    </div>
</form>

<!-- Список всех дат уроков с возможностью редактирования и удаления -->
<h2>Список дат уроков</h2>
<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover mt-3">
        <thead class="table-dark">
            <tr>
                <th>Дата урока</th>
                <th>Тип урока</th>
                <th>Действие</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lessonDates as $entry): ?>
                <tr>
                    <td><?php echo date('d.m.Y', strtotime($entry['lesson_date'])); ?></td>
                    <td>
                        <form method="post" class="d-flex align-items-center">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="date_to_update" value="<?php echo htmlspecialchars($entry['lesson_date']); ?>">
                            <div class="me-2">
                                <select name="new_lesson_type" class="form-select">
                                    <option value="lesson" <?php if ($entry['lesson_type'] == 'lesson') echo 'selected'; ?>>Обычный урок</option>
                                    <option value="exam" <?php if ($entry['lesson_type'] == 'exam') echo 'selected'; ?>>Зачет</option>
                                </select>
                            </div>
                            <button type="submit" name="update_type" class="btn btn-primary">
                                <i class="fas fa-save"></i> Сохранить
                            </button>
                        </form>
                    </td>
                    <td>
                        <a href="?delete_date=<?php echo urlencode($entry['lesson_date']); ?>" class="btn btn-danger" 
                                            onclick="return confirm('Вы уверены, что хотите удалить эту дату?');">
                            <i class="fas fa-trash-alt"></i> Удалить
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

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

<?php include 'footer.php'; ?>
