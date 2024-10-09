<?php

// Функция для получения следующей даты урока
function getNextLessonDate($pdo) {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare('SELECT lesson_date FROM lesson_dates WHERE lesson_date >= ? ORDER BY lesson_date ASC LIMIT 1');
    $stmt->execute([$today]);
    $nextLesson = $stmt->fetchColumn();

    if ($nextLesson) {
        return $nextLesson;
    } else {
        // Если нет будущих уроков, вернем текущую дату
        return $today;
    }
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

function sanitizeString($string) {
    $string = htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    $string = preg_replace('/\s+/', ' ', $string);
    return $string;
}

function selectStudentByDefault($index, $students) {
    return $students[$index]['is_present_now'] && $students[$index]['was_present_before'] && $students[$index]['marks'] == 0;
}

function calculateExamOrder($students, $day) {
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

function calculateLessonOrder($students, $day, $n = 10, $selectionFunction = 'selectStudentByDefault') {
    $quantity = count($students);
    $index = ($day - 1) % $quantity;
    $order = [];
    $selectedIndices = [];
    while (count($order) < $n && count($selectedIndices) < $quantity) {
        if (!in_array($index, $selectedIndices)) {
            if ($selectionFunction($index, $students))
                $order[] =  $students[$index];
            $selectedIndices[] = $index;
            $index = ($index + $day) % $quantity;
        } else {
            $index = ($index + 1) % $quantity;
        }
    }
    return $order;
}

function getLessons($pdo) {
    $stmt = $pdo->query('SELECT lesson_date, lesson_type FROM lesson_dates');
    $lessonDatesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lessons = [];
    foreach ($lessonDatesData as $lesson) {
        $lessons[$lesson['lesson_date']] = $lesson['lesson_type'];
    }

    return $lessons;
}

function getOrder($pdo, $date = null, $type = null) {
    $date = $date ?? getNextLessonDate($pdo);

    if(!validateDate($date)) {
        throw new Exception('Некорректная дата');
    }

    $lessons = getLessons($pdo);

    
    $type = $type ?? $lessons[$date] ?? null;

    if(is_null($type)) {
        throw new Exception('На выбранную дату уроки не запланированы');
    }

    $students = readStudents($pdo);
    $day = (int)date('d', strtotime($date));

    switch($type) {
        case 'lesson':
            return [
                'date' => $date,
                'type' => 'lesson', 
                'order' => calculateLessonOrder($students, $day) 
            ];
        case 'exam':
            return [
                'date' => $date,
                'type' => 'exam',
                'order' => calculateExamOrder($students, $day) 
            ];
        default:
            throw new Exception('Некорректный тип урока');
    }
}
?>
