<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Устанавливаем заголовок для ответа в формате JSON
header('Content-Type: application/json; charset=utf-8');

// Подключение к базе данных и необходимые функции
require_once 'db_connect.php';
require_once 'functions.php'; // Создадим этот файл для общих функций

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Подключение не удалось: ' . $e->getMessage()]);
    exit();
}

// Получаем параметры из запроса
$type = $_GET['type'] ?? 'list';
$dateParam = $_GET['date'] ?? null;

// Определяем дни уроков
//$lessonDays = [2]; // 2 - вторник

// Определяем выбранную дату
if ($dateParam) {
    $selectedDate = $dateParam;
} else {
    $selectedDate = getNextLessonDate($lessonDays);
}

// Проверяем корректность даты
// if (!validateDate($selectedDate)) {
//     echo json_encode(['error' => 'Некорректная дата']);
//     exit();
// }

// Получаем день месяца из выбранной даты
$day = (int)date('d', strtotime($selectedDate));

// Читаем данные об учениках
$students = readStudents($pdo);
$quantity = count($students);

// Вычисляем порядок в зависимости от типа запроса
if ($quantity > 0) {
    if ($type == 'exam-list') {
        $order = calculateOrderExam($students, $day);
    } else {
        $order = calculateOrderLesson($students, $day);
    }
} else {
    echo json_encode(['error' => 'Список учеников пуст']);
    exit();
}

// Возвращаем данные в формате JSON
echo json_encode([
    'date' => $selectedDate,
    'type' => $type,
    'order' => $order
]);
