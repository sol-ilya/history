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

function getLessons($pdo) {
    $stmt = $pdo->query('SELECT lesson_date, lesson_type FROM lesson_dates');
    $lessonDatesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lessons = [];
    foreach ($lessonDatesData as $lesson) {
        $lessons[$lesson['lesson_date']] = $lesson['lesson_type'];
    }

    return $lessons;
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


abstract class BaseOrder {
    protected $students;
    protected $day;

    public function __construct($students, $day) {
        $this->students = $students;
        $this->day = $day;
    }

    protected function calculateGeneralOrder() {
        $quantity = count($this->students);
        $index = ($this->day - 1) % $quantity;
        $order = [];
        $visited = [];
        while (count($visited) < $quantity) {
            if (!in_array($index, $visited)) {
                $order[] = $this->students[$index];
                $visited[] = $index;
                $index = ($index + $this->day) % $quantity;
            }
            else {
                $index = ($index + 1) % $quantity;
            }
        }
        return $order;
    }

    abstract public function get();
}

class ExamOrder extends BaseOrder {
    public function get() {
        $order = $this->calculateGeneralOrder();

        function isPresent($student) {
            return $student['is_present_now'];
        }
    
        return array_filter($order, 'isPresent');
    }
}

class LessonOrder extends BaseOrder {
    protected $needed;

    public function __construct($students, $day, $needed = 10)
    {
        parent::__construct($students, $day);
        $this->needed = $needed;
    }

    public function getLayers() {
        $layers = [];
        foreach ($this->students as $student) {
            $marksNumber = $student['marks'];
            $layers[$marksNumber] = ($layers[$marksNumber] ?? 0) + 1;
        }
        ksort($layers);
        return $layers;
    }

    private function isLayerComplete(&$array, &$layerIter) {
        return $layerIter->valid() && count(array_filter($array, function ($student) use ($layerIter) {
            return $student['marks'] ==  $layerIter->key();
        })) == $layerIter->current();
    }

    public function get() {
        $order = $this->calculateGeneralOrder();

        $filteredOrder = [];
    
        if (!$order) {
            return [];
        }

        $layers = $this->getLayers();

        $layerIter = new ArrayIterator($layers);

        function loop(&$order) {
            while (True) {
                foreach($order as $student) {
                    yield $student;
                }
            }
        }

        $count = 0;
        foreach(loop($order) as $student) {
            if ($this->isLayerComplete($filteredOrder, $layerIter)) {
                $layerIter->next();
            }

            if ($student['is_present_now'] && $student['was_present_before'] && $student['marks'] == $layerIter->key()) {
                $filteredOrder[] = $student;
                $count++;
            }

            if ($count >= $this->needed) 
                break;
        }

        return $filteredOrder;
    }

    public function getSuitabilityTable() {

    }
}

class OrderManager {
    private $students;
    private $lessons;

    public function __construct(private $pdo)
    {
        $this->students = readStudents($pdo);
        $this->lessons = getLessons($pdo);
    }

    public function getStudents() {
        return $this->students;
    }

    public function getLessons() {
        return $this->lessons;
    }

    public function getOrder($date = null, $type = null) {
        if (isset($date) && !validateDate($date)) {
            throw new Exception('Некорректная дата');
        }
        $date = $date ?? getNextLessonDate($this->pdo);
        
        if(!$type) {
            $type = $this->lessons[$date] ?? null;
            if (!$type) {
                throw new Exception('На выбранную дату уроки не запланированы');
            }
        }

        $day = (int) date('d', strtotime($date));

        switch ($type) {
            case 'lesson':
                return [
                    'date' => $date,
                    'type' => 'lesson',
                    'order' => (new LessonOrder($this->students, $day))->get(),
                ];
            case 'exam':
                return [
                    'date' => $date,
                    'type' => 'exam',
                    'order' => (new ExamOrder($this->students, $day))->get(),
                ];
            default:
                throw new Exception('Некорректный тип урока');
        }

    }


}
