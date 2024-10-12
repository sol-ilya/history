<?php
// Функция для проверки корректности даты
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
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
    protected $minMarkNumber;

    public function __construct($students, $day, $needed = 10)
    {
        parent::__construct($students, $day);
        $this->needed = $needed;
        $this->minMarkNumber = $this->getMinMarkNumber();
    }

    public function getMinMarkNumber() {
        return min(array_column($this->students, 'marks'));
    }

    protected function isSuitable($student) {
        return $student['is_present_now'] && $student['was_present_before'] && $student['marks'] == $this->minMarkNumber;
    }

    public function get() {
        $order = $this->calculateGeneralOrder();
        $order = array_filter($order, [$this, 'isSuitable']);
        return $order;
    }

    public function getSuitabilityTable() {
        $order = $this->calculateGeneralOrder();
        foreach ($order as &$student) {
            if ($this->isSuitable($student)) {
                $student['suitable'] = true;
            } else {
                $student['suitable'] = false;
            }
        }

        return $order;
    }
}

class OrderManager {
    private $students;
    private $lessons;

    public function __construct(private $db)
    {
        $this->students = $this->db->getStudents();
        $this->lessons = $this->db->getLessons();
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
        $date = $date ?? $this->db->getNextLessonDate();
        
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
