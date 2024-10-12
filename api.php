<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Устанавливаем заголовок для ответа в формате JSON
header('Content-Type: application/json; charset=utf-8');

// Подключение к базе данных и необходимые функции
require_once 'config/db.php';
require_once 'config/functions.php';
date_default_timezone_set('Europe/Moscow');

// Создаём объект базы данных
$db = new Database();
$db->connect();

// Получаем метод запроса
$method = $_SERVER['REQUEST_METHOD'];

// Получаем параметр 'path' из URL
$path = isset($_GET['path']) ? $_GET['path'] : '';

// Убираем ведущие и замыкающие слэши
$path = trim($path, '/');

// Разбиваем путь на сегменты
$segments = explode('/', $path);

// Получаем ресурс и дополнительные параметры из сегментов
$resource = array_shift($segments);

// Получаем API-ключ из заголовков
$headers = getallheaders();
$apiKey = $headers['X-API-Key'] ?? null;

// Аутентификация пользователя по API-ключу
$user = null;
if ($apiKey) {
    $user = $db->getUserByAPIKey($apiKey, ['is_admin', 'student_id', 'nickname']);
}

// Обработка запроса в зависимости от ресурса
switch ($resource) {
    case '':
        // Главная страница или корневой ресурс
        respondWithMessage('API работает. Пожалуйста, используйте правильный ресурс.', 200);
        break;

    case 'students':
        // Доступ только для администраторов
        if (!$user || !isAdminAPI($user)) {
            respondWithError('Доступ запрещён. Требуется авторизация администратора.', 401);
        }
        $id = array_shift($segments);
        handleStudents($method, $id, $user, $db);
        break;

    case 'order':
    case 'lesson-order':
    case 'exam-order':
        handleOrder($method, $resource, $segments, $db);
        break;

    case 'me':
        // Доступ только для аутентифицированных пользователей
        if (!$user) {
            respondWithError('Требуется авторизация.', 401);
        }
        handleMe($method, $user, $db);
        break;

    default:
        respondWithError('Ресурс не найден', 404);
        break;
}

// Функции обработки запросов

function handleStudents($method, $id, $user, $db) {
    // Получение данных из тела запроса
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            if ($id) {
                // Получение информации о конкретном студенте
                getStudent($db, $id);
            } else {
                // Получение списка всех студентов
                getStudents($db);
            }
            break;

        case 'POST':
            // Создание нового студента
            createStudent($db, $input);
            break;

        case 'PUT':
            if (!$id) {
                respondWithError('ID студента не указан', 400);
            }
            // Обновление информации о студенте
            updateStudent($db, $id, $input);
            break;

        case 'DELETE':
            if (!$id) {
                respondWithError('ID студента не указан', 400);
            }
            // Удаление студента
            deleteStudent($db, $id);
            break;

        default:
            respondWithError('Метод не поддерживается', 405);
            break;
    }
}

function handleOrder($method, $resource, $segments, $db) {
    if ($method !== 'GET') {
        respondWithError('Метод не поддерживается', 405);
    }
    

    // Фильтрация нужных столбцов
    function filterColumns($array) {
        $columns = ['id', 'name', 'nickname'];
        $filtered = [];
        foreach ($columns as $column) {
            $filtered[$column] = $array[$column] ?? null;
        }
        return $filtered;
    }

    $dateParam = isset($segments[0]) ? $segments[0] : null;

    switch ($resource) {
        case 'lesson-order':
            $type = 'lesson';
            break;
        case 'exam-order':
            $type = 'exam';
            break;
        default:
            $type = null;
            break;
    }

    
    try {
        $manager = new OrderManager($db);
        $result = $manager->getOrder($dateParam, $type);
    } catch (Exception $e) {
        respondWithError($e->getMessage(), 400);
    }

    $result['order'] = array_map('filterColumns', $result['order']);

    echo json_encode($result);
    exit();
}

function handleMe($method, $user, $db) {
    switch ($method) {
        case 'GET':
            // Возвращаем данные текущего пользователя
            getCurrentUser($user, $db);
            break;

        case 'PUT':
            // Обновляем данные текущего пользователя, кроме поля 'name'
            $input = json_decode(file_get_contents('php://input'), true);
            updateCurrentUser($db, $user, $input);
            break;

        default:
            respondWithError('Метод не поддерживается', 405);
            break;
    }
}

// Функции аутентификации и проверки прав

function isAdminAPI($user) {
    return isset($user['is_admin']) && $user['is_admin'] == 1;
}

// Функции обработки студентов

function getStudent($db, $id) {
    $student = $db->fetch('SELECT s.*, u.nickname FROM students s LEFT JOIN users u ON s.id = u.student_id WHERE s.id = ?', [$id]);
    if (!$student) {
        respondWithError('Студент не найден', 404);
    }
    echo json_encode(['student' => $student]);
    exit();
}

function getStudents($db) {
    $students = $db->getStudents();
    echo json_encode(['students' => $students]);
    exit();
}

function createStudent($db, $input) {
    $name = trim($input['name'] ?? '');
    $was_present_before = isset($input['was_present_before']) ? (int)$input['was_present_before'] : 0;
    $is_present_now = isset($input['is_present_now']) ? (int)$input['is_present_now'] : 0;
    $marks = isset($input['marks']) ? (int)$input['marks'] : 0;

    if (empty($name)) {
        respondWithError('Имя студента обязательно');
    }

    try {
        $db->execute('INSERT INTO students (name, was_present_before, is_present_now, marks) VALUES (?, ?, ?, ?)', [$name, $was_present_before, $is_present_now, $marks]);
        $newStudentId = $db->connect()->lastInsertId();
        http_response_code(201);
        echo json_encode(['message' => 'Студент создан', 'student_id' => $newStudentId]);
        exit();
    } catch (PDOException $e) {
        respondWithError('Ошибка при создании студента: ' . $e->getMessage(), 500);
    }
}

function updateStudent($db, $id, $input) {
    $fields = [];
    $values = [];

    if (isset($input['name'])) {
        $fields[] = 'name = ?';
        $values[] = htmlspecialchars(trim($input['name']));
    }
    if (isset($input['was_present_before'])) {
        $fields[] = 'was_present_before = ?';
        $values[] = (int)$input['was_present_before'];
    }
    if (isset($input['is_present_now'])) {
        $fields[] = 'is_present_now = ?';
        $values[] = (int)$input['is_present_now'];
    }
    if (isset($input['marks'])) {
        $fields[] = 'marks = ?';
        $values[] = (int)$input['marks'];
    }

    if (empty($fields)) {
        respondWithError('Нет данных для обновления', 400);
    }

    $values[] = $id;
    $sql = 'UPDATE students SET ' . implode(', ', $fields) . ' WHERE id = ?';

    try {
        $db->execute($sql, $values);
        echo json_encode(['message' => 'Данные студента обновлены']);
        exit();
    } catch (PDOException $e) {
        respondWithError('Ошибка при обновлении данных: ' . $e->getMessage(), 500);
    }
}

function deleteStudent($db, $id) {
    try {
        $db->execute('DELETE FROM students WHERE id = ?', [$id]);
        echo json_encode(['message' => 'Студент удалён']);
        exit();
    } catch (PDOException $e) {
        respondWithError('Ошибка при удалении студента: ' . $e->getMessage(), 500);
    }
}

// Функции для /api/me

function getCurrentUser($user, $db) {
    // Получаем полную информацию о пользователе
    $userData = $db->fetch('
        SELECT u.id AS user_id, u.nickname, u.is_admin, s.*
        FROM users u
        LEFT JOIN students s ON u.student_id = s.id
        WHERE u.id = ?', [$user['user_id']]);

    echo json_encode(['user' => $userData]);
    exit();
}

function updateCurrentUser($db, $user, $input) {
    $fields = [];
    $values = [];

    // Разрешаем обновлять только определённые поля
    if (isset($input['was_present_before'])) {
        $fields[] = 'was_present_before = ?';
        $values[] = (int)$input['was_present_before'];
    }
    if (isset($input['is_present_now'])) {
        $fields[] = 'is_present_now = ?';
        $values[] = (int)$input['is_present_now'];
    }
    if (isset($input['marks'])) {
        $fields[] = 'marks = ?';
        $values[] = (int)$input['marks'];
    }

    if (empty($fields)) {
        respondWithError('Нет данных для обновления', 400);
    }

    $values[] = $user['student_id'];
    $sql = 'UPDATE students SET ' . implode(', ', $fields) . ' WHERE id = ?';

    try {
        $db->execute($sql, $values);
        echo json_encode(['message' => 'Ваши данные обновлены']);
        exit();
    } catch (PDOException $e) {
        respondWithError('Ошибка при обновлении данных: ' . $e->getMessage(), 500);
    }
}

// Функции обработки ошибок и сообщений

function respondWithError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit();
}

function respondWithMessage($message, $code = 200) {
    http_response_code($code);
    echo json_encode(['message' => $message]);
    exit();
}
?>
