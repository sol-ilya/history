<?php
require_once '../config/config.php';
require_once '../config/functions.php';
$pageTitle = 'Управление данными учеников';

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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Обработка отправленной формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Неверный CSRF токен');
    }
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
<?php include 'header.php'; ?>

<h1 class="mb-4">Управление данными учеников</h1>
<?php if (isset($message)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- Кнопка для переноса данных к следующему уроку -->
<form method="post" class="mb-4">
    <button type="submit" name="move_to_next_lesson" class="btn btn-warning">Перенести данные к следующему уроку</button>
</form>

<!-- Форма для редактирования данных учеников -->
<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="students[<?php echo $index; ?>][id]" value="<?php echo $student['id']; ?>">
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover admin-table">
            <thead class="thead-dark">
                <tr>
                    <th style="width: 40%;">ФИО</th>
                    <th style="width: 20%;">Прошлый урок</th>
                    <th style="width: 20%;">Этот урок</th>
                    <th>Количество оценок</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $index => $student): ?>
                    <tr>
                        <td>
                            <input type="hidden" name="students[<?php echo $index; ?>][id]" value="<?php echo $student['id']; ?>">
                            <input type="text" name="students[<?php echo $index; ?>][name]" class="form-control" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="students[<?php echo $index; ?>][wasPresentBefore]" <?php if ($student['was_present_before']) echo 'checked'; ?>>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="students[<?php echo $index; ?>][isPresentNow]" <?php if ($student['is_present_now']) echo 'checked'; ?>>
                        </td>
                        <td>
                            <input type="number" name="students[<?php echo $index; ?>][marks]" class="form-control" value="<?php echo $student['marks']; ?>" min="0">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <button type="submit" name="update_type" class="btn btn-primary ml-2">
        <i class="fas fa-save"></i> Сохранить
    </button>
</form>


<?php include 'footer.php'; ?>
