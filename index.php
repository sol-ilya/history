<?php
require_once 'config/config.php';
require_once 'config/functions.php';

// Получение всех дат уроков с типом урока
$stmt = $pdo->query('SELECT lesson_date, lesson_type FROM lesson_dates');
$lessonDatesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Преобразуем в формат, удобный для JavaScript
$lessonDates = [];
$examDates = [];
foreach ($lessonDatesData as $entry) {
    $lessonDates[] = $entry['lesson_date'];
    if ($entry['lesson_type'] == 'exam') {
        $examDates[] = $entry['lesson_date'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selectedDate = $_POST['date'];
    $algorithm = $_POST['algorithm'];
} else {
    $selectedDate = getNextLessonDate($pdo);
    $algorithm = 'auto';
}

// Проверка корректности даты
if (!validateDate($selectedDate, 'Y-m-d')) {
    $selectedDate = getNextLessonDate($pdo);
}

// Если выбран алгоритм 'auto', определяем алгоритм на основе типа урока
if ($algorithm == 'auto') {
    // Получаем тип урока из базы данных
    $stmt = $pdo->prepare('SELECT lesson_type FROM lesson_dates WHERE lesson_date = ?');
    $stmt->execute([$selectedDate]);
    $lessonType = $stmt->fetchColumn();

    if ($lessonType == 'exam') {
        $algorithm = 'exam';
    } else {
        $algorithm = 'lesson';
    }
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Порядок ответов учеников</title>
    <link rel="stylesheet" href="style.css">
    <!-- Подключаем стили flatpickr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<body>
    <?php include 'config/header.php'; ?>
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
                    <option value="auto" <?php if ($algorithm == 'auto') echo 'selected'; ?>>Авто</option>
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
            <span><span class="lesson-day"></span> — Урок</span>
            <br>
            <span><span class="exam-day"></span> — Зачет</span>
        </div>

    </div>
    <?php include 'config/footer.php'; ?>

    <!-- Подключаем скрипты flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
    <script>
        const lessonDates = <?php echo json_encode($lessonDates); ?>;
        const examDates = <?php echo json_encode($examDates); ?>;

        function formatDate(date) {
            let year = date.getFullYear();
            let month = (date.getMonth() + 1).toString().padStart(2, '0');
            let day = date.getDate().toString().padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        flatpickr("#date", {
            "locale": "ru",
            "dateFormat": "Y-m-d",
            "disable": [
                function(date) {
                    const dateString = formatDate(date);
                    return !lessonDates.includes(dateString);
                }
            ],
            "onDayCreate": function(dObj, dStr, fp, dayElem) {
                const dateString = formatDate(dayElem.dateObj);
                if (lessonDates.includes(dateString)) {
                    dayElem.classList.add("lesson-day");
                    if (examDates.includes(dateString)) {
                        dayElem.classList.add("exam-day");
                    }
                }
            }
        });
    </script>

</body>
</html>
