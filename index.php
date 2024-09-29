<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных и функции
require_once 'db_connect.php';
require_once 'functions.php';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo 'Подключение не удалось: ' . $e->getMessage();
    exit();
}

// Определяем выбранную дату и алгоритм
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selectedDate = $_POST['date'];
    $algorithm = $_POST['algorithm'];
} else {
    $selectedDate = getNextLessonDate($lessonDays);
    $algorithm = 'lesson';
}

// Проверка корректности даты
if (!validateDate($selectedDate)) {
    $selectedDate = getNextLessonDate($lessonDays);
}

// Получаем день месяца из выбранной даты
$day = (int)date('d', strtotime($selectedDate));

// Читаем данные об учениках
$students = readStudents($pdo);
$quantity = count($students);

// Инициализация переменной для сообщения
$user_message = '';

if ($quantity > 0) {
    if ($algorithm == 'exam') {
        $order = calculateOrderExam($students, $day);
    } else {
        $order = calculateOrderLesson($students, $day);
    }
} else {
    echo "<p>Не удалось вычислить порядок, так как список учеников пуст.</p>";
    $order = [];
}

// Проверка, находится ли пользователь в списке
if (isLoggedIn() && $algorithm == 'lesson') {
    if (in_array($_SESSION['user_id'], array_column($order, 'user_id'))){
        $user_message = '<p class="user-alert" style="color: red; font-weight: bold; text-align: center; margin-top: 20px;">Возможно Вас спросят.</p>';
    } else {
        $user_message = '<p class="user-alert" style="color: green; font-weight: bold; text-align: center; margin-top: 20px;">Скорее всего Вас не спросят.</p>';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Порядок ответов учеников</title>
    <link rel="stylesheet" href="style.css">
    <!-- Подключаем стили flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <h1>Порядок ответов учеников</h1>
        <form method="post">
            <div class="form-group">
                <label for="date">Выберите дату урока:</label>
                <input type="text" id="date" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>" required>
            </div>
            <div class="form-group">
                <label for="algorithm">Выберите алгоритм:</label>
                <select id="algorithm" name="algorithm">
                    <option value="lesson" <?php if ($algorithm == 'lesson') echo 'selected'; ?>>Работа на уроке</option>
                    <option value="exam" <?php if ($algorithm == 'exam') echo 'selected'; ?>>Зачет</option>     
                </select>
            </div>
            <input type="submit" value="Вычислить порядок">
        </form>

        <?php if (isset($user_message)): ?>
            <?php echo $user_message; ?>
        <?php endif; ?>

        <?php if (!empty($order)): ?>
            <h2>Порядок ответов на дату <?php echo date('d.m.Y', strtotime($selectedDate)); ?>:</h2>
            <ol>
                <?php foreach ($order as $student): ?>
                    <li><?php 
                        echo htmlspecialchars($student['name']);
                        if (isLoggedIn() && $_SESSION['user_id'] == $student['user_id'])
                            echo ' (Вы)';
                        elseif ($student['nickname'])
                            echo ' ('. htmlspecialchars($student['nickname']) . ')';
                    
                    ?></li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p>Нет доступных учеников для выбранного алгоритма и даты.</p>
        <?php endif; ?>

        <div class="legend">
            <span><span class="lesson-day"></span> — Дни уроков</span>
        </div>
    </div>
    <footer>
        &copy; <?php echo date('Y'); ?> Школьный портал
    </footer>

    <!-- Подключаем скрипты flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
    <script>
        // Определяем дни уроков (0 - воскресенье, 1 - понедельник, ..., 6 - суббота)
        const lessonDays = [2]; // Здесь 2 - вторник

        flatpickr("#date", {
            "locale": "ru",
            "dateFormat": "Y-m-d",
            "defaultDate": "<?php echo htmlspecialchars($selectedDate); ?>",
            "disable": [
                function(date) {
                    // Отключаем даты, которые не являются днями уроков
                    return !lessonDays.includes(date.getDay());
                }
            ],
            "onDayCreate": function(dObj, dStr, fp, dayElem) {
                // Выделяем дни уроков
                if (lessonDays.includes(dayElem.dateObj.getDay())) {
                    dayElem.classList.add("lesson-day");
                }
            }
        });
    </script>
</body>
</html>
