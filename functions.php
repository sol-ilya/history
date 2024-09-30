<?php
// Определяем дни уроков (например, вторник)
$lessonDays = [2]; // 2 - вторник (0 - воскресенье, ..., 6 - суббота)

// Функция для получения следующей даты урока
function getNextLessonDate($lessonDays) {
    $today = strtotime('today');
    $currentDay = date('w', $today); // День недели сегодня (0 - воскресенье)
    foreach (range(0, 6) as $i) {
        $nextDay = ($currentDay + $i) % 7;
        if (in_array($nextDay, $lessonDays)) {
            return date('Y-m-d', strtotime("+$i days", $today));
        }
    }
    return date('Y-m-d', $today); // Если что-то пойдет не так, вернем сегодняшнюю дату
}

// Функция для проверки корректности даты
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Функция для чтения данных об учениках из базы данных
function readStudents($pdo) {
    $stmt = $pdo->query('SELECT s.*, u.id AS user_id, u.nickname FROM students s LEFT JOIN users u ON s.id=u.student_id');
    return $stmt->fetchAll();
}

// Функция для проверки, авторизован ли пользователь
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Функция для проверки, является ли пользователь админом
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

// Функция для безопасного вывода данных
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Остальные функции (calculateOrderExam, calculateOrderLesson) остаются без изменений
function calculateOrderExam($students, $day) {
    $quantity = count($students);
    $index = ($day - 1) % $quantity;
    $order = [];
    $visited = [];
    while (count($visited) < $quantity) {
        if (!in_array($index, $visited)) {
            if ($students[$index]['is_present_now']) {
                $order[] = $students[$index];
            }
            $visited[] = $index;
            $index = ($index + $day) % $quantity;
        }
        else {
            $index = ($index + 1) % $quantity;
        }
    }
    return $order;
}

function calculateOrderLesson($students, $day, $n = 10) {
    $quantity = count($students);
    $index = ($day - 1) % $quantity;
    $order = [];
    $selectedIndices = [];
    while (count($order) < $n && count($selectedIndices) < $quantity) {
        $student = $students[$index];
        if (!in_array($index, $selectedIndices)) {
            if ($student['is_present_now'] && $student['was_present_before'] && $student['marks'] == 0)
                $order[] = $student;
            $selectedIndices[] = $index;
            $index = ($index + $day) % $quantity;
        } else {
            $index = ($index + 1) % $quantity;
        }
    }
    return $order;
}

?>
