<?php
require_once 'config/config.php';
require_once 'config/functions.php';

// Получение всех дат уроков с типом урока
$lessons = getLessons($pdo);
// Преобразуем в формат, удобный для JavaScript
$lessonDates = [];
$examDates = [];
foreach ($lessons as $date => $type) {
    $lessonDates[] = $date;
    if ($type == 'exam')
        $examDates[] = $date;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selectedDate = $_POST['date'];
    $algorithm = $_POST['algorithm'];
} else {
    $selectedDate = getNextLessonDate($pdo);
    $algorithm = 'auto';
}

$lesson_type = null;
switch($algorithm) {
    case 'auto':
        break;
    case 'lesson':
    case 'exam':
        $lesson_type = $algorithm;
        break;
    default:
        die('Некорректный алгоритм');
}

try {
    $result = getOrder($pdo, $selectedDate, $lesson_type);
    $order = $result['order'];
    $lesson_type = $result['type'];
} catch(Exception $e) {
    die('Ошибка при вычислении порядка');
}

// Проверка, находится ли пользователь в списке
if (isLoggedIn() && $lesson_type == 'lesson') {
    if (in_array($_SESSION['user_id'], array_column($order, 'user_id'))){
        $user_message = '<p class="user-alert text-danger">Возможно Вас спросят.</p>';
    } else {
        $user_message = '<p class="user-alert text-success">Скорее всего Вас не спросят.</p>';
    }
}
?>

<?php include 'config/header.php'; ?>

<div class="container container-custom">
    <h1 class="text-center mb-4">Порядок ответов учеников</h1>
    <form method="post" class="mb-4">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="date" class="form-label">Выберите дату урока:</label>
                <input type="text" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selectedDate); ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="algorithm" class="form-label">Выберите тип урока:</label>
                <select id="algorithm" name="algorithm" class="form-select">
                    <option value="auto" <?php if ($algorithm == 'auto') echo 'selected'; ?>>Определить автоматически</option>
                    <option value="lesson" <?php if ($algorithm == 'lesson') echo 'selected'; ?>>Обычный урок</option>
                    <option value="exam" <?php if ($algorithm == 'exam') echo 'selected'; ?>>Зачет</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Вычислить порядок</button>
    </form>

    <?php if (isset($user_message)): ?>
        <?php echo $user_message; ?>
    <?php endif; ?>

    <?php if (!empty($order)): ?>
        <h2>Порядок ответов на 
            <?php echo ($lesson_type == 'exam' ? 'зачет ' : 'урок ') . date('d.m.Y', strtotime($selectedDate)); ?>:</h2>
        <ul class="list-group mt-3 numbered-list">
            <?php foreach ($order as $student): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?php 
                        echo htmlspecialchars($student['name']);
                        if (isLoggedIn() && $_SESSION['user_id'] == $student['user_id'])
                            echo ' (Вы)';
                        elseif ($student['nickname'])
                            echo ' ('. htmlspecialchars($student['nickname']) . ')';
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Нет доступных учеников для выбранного алгоритма и даты.</p>
    <?php endif; ?>


    <div class="legend">
        <span><span class="lesson-day"></span> — Урок</span>
        <br>
        <span><span class="exam-day"></span> — Зачет</span>
    </div>
</div>

<!-- Подключаем скрипты Flatpickr -->
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

<?php include 'config/footer.php'; ?>
