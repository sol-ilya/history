<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных
require_once 'db_connect.php';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo 'Подключение не удалось: ' . $e->getMessage();
    exit();
}

// Функция для чтения данных об учениках из базы данных
function readStudents($pdo) {
    $stmt = $pdo->query('SELECT * FROM students');
    return $stmt->fetchAll();
}

// Функция для сохранения данных об учениках в базу данных
function saveStudents($pdo, $students) {
    foreach ($students as $student) {
        if (isset($student['id'])) {
            // Обновляем данные ученика
            $stmt = $pdo->prepare('UPDATE students SET surname = ?, was_present_before = ?, is_present_now = ?, marks = ? WHERE id = ?');
            $stmt->execute([$student['surname'], (int)$student['wasPresentBefore'], (int)$student['isPresentNow'], $student['marks'], $student['id']]);
        }
    }
}

// Функция для переноса данных к следующему уроку
function moveToNextLesson($pdo) {
    // Переносим данные: isPresentNow -> wasPresentBefore, isPresentNow устанавливаем в true
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
        foreach ($_POST['students'] as $index => $data) {
            if (isset($data['id'])) {
                $updatedStudents[] = [
                    'id' => $data['id'],
                    'surname' => $data['surname'],
                    'wasPresentBefore' => isset($data['wasPresentBefore']) ? true : false,
                    'isPresentNow' => isset($data['isPresentNow']) ? true : false,
                    'marks' => $data['marks']
                ];
            }
        }
        saveStudents($pdo, $updatedStudents);
        $message = "Данные успешно сохранены.";
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
</head>
<body>
    <div class="container">
        <h1>Консоль администратора</h1>
        <?php if (isset($message)): ?>
            <p class="success"><?php echo $message; ?></p>
        <?php endif; ?>

        <!-- Кнопка для переноса данных к следующему уроку -->
        <form method="post">
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
                            <input type="text" name="students[<?php echo $index; ?>][surname]" value="<?php echo htmlspecialchars($student['surname']); ?>">
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
</body>
</html>
