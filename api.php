<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Устанавливаем заголовок для ответа в формате JSON
header('Content-Type: application/json; charset=utf-8');

// Подключение к базе данных и необходимые функции
require_once 'db_connect.php';
require_once 'functions.php'; // Убедитесь, что функция isAdminAPI определена здесь

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Подключение не удалось: ' . $e->getMessage()]);
    exit();
}

// Получаем параметры из запроса
$token = $_GET['token'] ?? null;
$action = $_GET['action'] ?? null;

// Получение метода запроса с проверкой
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Аутентификация по токену, если токен предоставлен
if ($token) {
    $user = authenticateByToken($pdo, $token);
    if (!$user) {
        respondWithError('Недействительный токен', 401);
    }
} else {
    $user = null; // Пользователь не аутентифицирован
}

// Обработка административных действий, если параметр `action` предоставлен
if ($action && $user && isAdminAPI($user)) {
    switch ($action) {
        case 'add-student':
            if ($method !== 'POST') {
                respondWithError('Метод не разрешён для этой операции.', 405);
            }

            // Получение данных из тела запроса (JSON)
            $data = json_decode(file_get_contents('php://input'), true);
            $name = trim($data['name'] ?? '');
            $was_present_before = isset($data['was_present_before']) ? (int)$data['was_present_before'] : 0;
            $is_present_now = isset($data['is_present_now']) ? (int)$data['is_present_now'] : 0;
            $marks = isset($data['marks']) ? (int)$data['marks'] : 0;

            // Валидация данных
            if (empty($name)) {
                respondWithError('Имя ученика обязательно.');
            }

            // Создание ученика
            $stmt = $pdo->prepare('INSERT INTO students (name, was_present_before, is_present_now, marks) VALUES (?, ?, ?, ?)');
            try {
                $stmt->execute([$name, $was_present_before, $is_present_now, $marks]);
                $newStudentId = $pdo->lastInsertId();
                echo json_encode(['success' => 'Ученик успешно добавлен.', 'student_id' => $newStudentId]);
                exit();
            } catch (PDOException $e) {
                respondWithError('Ошибка при добавлении ученика: ' . $e->getMessage(), 500);
            }

        case 'update-student':
            if ($method !== 'PUT') {
                respondWithError('Метод не разрешён для этой операции.', 405);
            }

            // Получение данных из тела запроса (JSON)
            $data = json_decode(file_get_contents('php://input'), true);

            $student_id = $data['id'] ?? null;
            if (!$student_id) {
                respondWithError('ID ученика не указан.');
            }

            $name = isset($data['name']) ? htmlspecialchars(trim($data['name'])) : null;
            $was_present_before = isset($data['was_present_before']) ? (int) $data['was_present_before'] : null;
            $is_present_now = isset($data['is_present_now']) ? (int )$data['is_present_now'] : null;
            $marks = isset($data['marks']) ? (int) $data['marks'] : null;
            
            updateStudent($pdo, $student_id, $name, $was_present_before, $is_present_now, $marks);

        case 'delete-student':
            if ($method !== 'DELETE') {
                respondWithError('Метод не разрешён для этой операции.', 405);
            }

            // Получение ID ученика из параметров
            $student_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            if (!$student_id) {
                respondWithError('ID ученика не указан.');
            }

            // Удаление ученика
            $stmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
            try {
                $stmt->execute([$student_id]);
                echo json_encode(['success' => 'Ученик успешно удалён.']);
                exit();
            } catch (PDOException $e) {
                respondWithError('Ошибка при удалении ученика: ' . $e->getMessage(), 500);
            }

        case 'get-student':
            if ($method !== 'GET') {
                respondWithError('Метод не разрешён для этой операции.', 405);
            }

            // Получение ID ученика из параметров
            $student_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            if (!$student_id) {
                respondWithError('ID ученика не указан.');
            }

            // Получение данных ученика
            $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
            try {
                $stmt->execute([$student_id]);
                $student = $stmt->fetch();
                if ($student) {
                    // Фильтрация нужных столбцов
                    echo json_encode(['student' => $student]);
                    exit();
                } else {
                    respondWithError('Ученик с указанным ID не найден.', 404);
                }
            } catch (PDOException $e) {
                respondWithError('Ошибка при получении данных ученика: ' . $e->getMessage(), 500);
            }

        case 'get-all-students':
            if ($method !== 'GET') {
                respondWithError('Метод не разрешён для этой операции.', 405);
            }

            // Получение всех учеников
            $stmt = $pdo->prepare('SELECT * FROM students');
            try {
                $stmt->execute();
                $students = $stmt->fetchAll();
                echo json_encode(['students' => $students]);
                exit();
            } catch (PDOException $e) {
                respondWithError('Ошибка при получении списка учеников: ' . $e->getMessage(), 500);
            }

        // Добавьте другие действия администратора здесь

        default:
            respondWithError('Неизвестное действие.', 400);
    }
}

// Если токен предоставлен и метод запроса PUT, но без `action`
if ($user && $method === 'PUT' && !$action) {
    // Аутентификация пользователя
    if (!$user) {
        respondWithError('Невозможно найти пользователя по данному токену.', 401);
    }

    // Получаем данные из тела запроса
    $data = json_decode(file_get_contents('php://input'), true);

    $was_present_before = isset($data['was_present_before']) ? (int) $data['was_present_before'] : null;
    $is_present_now = isset($data['is_present_now']) ? (int) $data['is_present_now'] : null;
    $marks = isset($data['marks']) ? (int) $data['marks'] : null;

    //Изменение имени обычному пользователю запрещено
    updateStudent($pdo, $user['id'], null, $was_present_before, $is_present_now, $marks);
}

// Если токен предоставлен и нет `action`, возвращаем информацию о пользователе
if ($user && !$action) {
    // Форматирование данных пользователя для вывода
    $userInfo = [
        'user_id' => $user['user_id'],
        'nickname' => $user['nickname'],
        'is_admin' => (bool)$user['is_admin'],
        'student' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'was_present_before' => (bool)$user['was_present_before'],
            'is_present_now' => (bool)$user['is_present_now'],
            'marks' => $user['marks']
        ]
    ];
    echo json_encode(['user' => $userInfo]);
    exit();
}

// Если токен не предоставлен, продолжаем обработку обычных запросов

$type = $_GET['type'] ?? 'list';
$dateParam = $_GET['date'] ?? null;

// Определяем дни уроков
$lessonDays = [2]; // 2 - вторник (0 - воскресенье, ..., 6 - суббота)

// Определяем выбранную дату
if ($dateParam) {
    $selectedDate = $dateParam;
} else {
    $selectedDate = getNextLessonDate($lessonDays);
}

// Проверка корректности даты
if (!validateDate($selectedDate)) {
    respondWithError('Некорректная дата', 400);
}

// Получаем день месяца из выбранной даты
$day = (int)date('d', strtotime($selectedDate));

// Читаем данные об учениках
$students = readStudents($pdo);
$quantity = count($students);

// Фильтрация нужных столбцов
function filterColumns($columns, $array) {
    $filtered = array();
    foreach ($columns as $column) {
        if (isset($array[$column])) {
            $filtered[$column] = $array[$column];
        } else {
            $filtered[$column] = null;
        }
    }

    return $filtered;
}

// Вычисляем порядок в зависимости от типа запроса
if ($quantity > 0) {
    if ($type == 'examlist') {
        $order = calculateOrderExam($students, $day);
    } else {
        $order = calculateOrderLesson($students, $day);
    }
    $order = array_map(function ($student) { 
                            return filterColumns(['id', 'name', 'nickname'], $student);
                       }, 
                       $order);
} else {
    respondWithError('Список учеников пуст', 400);
}

// Возвращаем данные в формате JSON
echo json_encode([
    'date' => $selectedDate,
    'type' => $type,
    'order' => $order
]);

// Функция для обработки ошибок
function respondWithError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit();
}


function authenticateByToken($pdo, $token) {
    $stmt = $pdo->prepare('
        SELECT u.id AS user_id, u.nickname, u.is_admin, s.* 
        FROM users u 
        JOIN students s ON u.student_id = s.id 
        WHERE u.api_token = ?
    ');
    $stmt->execute([$token]);
    return $stmt->fetch();
}

function isAdminAPI($user) {
    return isset($user['is_admin']) && $user['is_admin'] == 1;
}

function getStudentById($id) {
    $stmt = $pdo->prepare('
        SELECT * FROM students WHERE id = ?
    ');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateStudent($pdo, $id, $name = null, $was_present_before = null, $is_present_now = null, $marks = null) {
    $fields = [];
    $values = [];

    if (isset($name)) {
        $fields[] = 'name = ?';
        $values[] = $name;
    }

    if (isset($was_present_before)) {
        $fields[] = 'was_present_before = ?';
        $values[] = (int) $was_present_before;
    }
    
    if (isset($is_present_now)) {
        $fields[] = 'is_present_now = ?';
        $values[] = (int) $is_present_now;
    }

    if (isset($marks)) {
        $fields[] = 'marks = ?';
        $values[] = (int) $marks;
    }

    // Если нет данных для обновления
    if (empty($fields)) {
        respondWithError('Нет данных для обновления.', 400);
    }

    // Добавляем ID пользователя в конец массива значений
    $values[] = $id;

    // Создаём SQL-запрос с динамически сгенерированными полями
    $sql = 'UPDATE students SET ' . implode(', ', $fields) . ' WHERE id = ?';

    // Выполняем обновление
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute($values);
        echo json_encode(['success' => 'Данные успешно обновлены.']);
        exit();
    } catch (PDOException $e) {
        respondWithError('Ошибка при обновлении данных: ' . $e->getMessage(), 500);
    }

}
?>
