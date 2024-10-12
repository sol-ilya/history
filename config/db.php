<?php
class Database {
    private $host = "localhost";
    private $db = "school";
    private $user = "user";
    private $password = "pass123";
    private $charset = 'utf8mb4';
    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Режим обработки ошибок
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Режим выборки по умолчанию
        PDO::ATTR_EMULATE_PREPARES   => false, // Отключение эмуляции подготовленных запросов
    ];
    private $conn;

    // Метод для установления соединения с базой данных
    public function connect() {
        if($this->conn) {
            return $this->conn;
        }

        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";

        try {
            $this->conn = new PDO($dsn, $this->user, $this->password, $this->options);
        } catch(PDOException $e) {
            echo "Подключение не удалось: " . $e->getMessage();
        }

        return $this->conn;
    }

    public function fetch($sql, $params = []) {
        if (!$this->conn) {
            return null;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function fetchAll($sql, $params = []) {
        if (!$this->conn) {
            return null;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function execute($sql, $params = []) {
        if (!$this->conn) {
            return null;
        }
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function getStudents($fields = ['s.*', 'u.id AS user_id', 'u.nickname']) {
        $str = implode(", ", $fields);
        return $this->fetchAll("SELECT $str FROM students s LEFT JOIN users u ON s.id=u.student_id");
    }

    public function getStudentById($id, $fields = ['*']) {
        $str = implode(", ", array_merge(['id AS student_id'], $fields));
        return $this->fetch("SELECT $str FROM students WHERE id = ?", [$id]);
    }

    public function updateStudent($student) {
        return $this->execute(
            'UPDATE students SET name = ?, was_present_before = ?, is_present_now = ?, marks = ? WHERE id = ?',
            [$student['name'], $student['wasPresentBefore'], $student['isPresentNow'], $student['marks'], $student['id']]
        );
    }

    public function getUsers($fields = ['*']) {
        $str = implode(", ", array_merge(['id AS user_id'], $fields));
        return $this->fetchAll("SELECT $str FROM users");
    }

    public function getUserById($id, $fields = ['*']) {
        $str = implode(", ", array_merge(['id AS user_id'], $fields));
        return $this->fetch("SELECT $str FROM users WHERE id = ?", [$id]);
    }

    public function getUserByUsername($username, $fields = ['*']) {
        $str = implode(", ", array_merge(['id AS user_id'], $fields));
        return $this->fetch("SELECT $str FROM users WHERE username = ?", [$username]);
    }

    public function getUserIdBySessionToken($token) {
        $token_hash = hash('sha256', $token);
        $token_data = $this->fetch("SELECT user_id FROM user_tokens WHERE token_hash = ? AND expires_at > NOW()", [$token_hash]);
        return $token_data['user_id'] ?? null;
    }

    public function getUserBySessionToken($token, $fields = ['*']) {
        $id = $this->getUserIdBySessionToken($token);
        if(!$id) return null;
        return $this->getUserById($id, $fields);
    }

    public function getUserByAPIKey($key, $fields = ['*']) {
        $str = implode(", ", array_merge(['id AS user_id'], $fields));
        return $this->fetch("SELECT $str FROM users WHERE api_key = ?", [$key]);
    }

    public function generateSessionToken($user_id) {
        // Генерация уникального токена
        $token = bin2hex(random_bytes(32));

        // Хеширование токена для хранения в базе данных
        $token_hash = hash('sha256', $token);

        // Установка времени истечения токена (например, 30 дней)
        $expires_at = date('Y-m-d H:i:s', time() + (86400 * 30));

        // Сохранение токена в базе данных
        $sql = 'INSERT INTO user_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)';
        $this->execute($sql, [$user_id, $token_hash, $expires_at]);

        return $token;
    }

    public function revokeSessionToken($token) {
        $token_hash = hash('sha256', $token);
        $this->execute('DELETE FROM user_tokens WHERE token_hash = ?', [$token_hash]);
    }

    public function getAvailableStudents() {
        return $this->fetchAll('SELECT s.id, s.name FROM students s LEFT JOIN users u ON s.id = u.student_id WHERE u.id IS NULL');
    }

    public function getLessonDates() {
        return $this->fetchAll('SELECT lesson_date, lesson_type FROM lesson_dates ORDER BY lesson_date ASC');
    }

    public function addLessonDate($date, $type) {
        return $this->execute('INSERT INTO lesson_dates (lesson_date, lesson_type) VALUES (?, ?)', [$date, $type]);
    }

    public function updateLessonType($date, $type) {
        return $this->execute('UPDATE lesson_dates SET lesson_type = ? WHERE lesson_date = ?', [$type, $date]);
    }

    public function deleteLessonDate($date) {
        return $this->execute('DELETE FROM lesson_dates WHERE lesson_date = ?', [$date]);
    }

    public function getLessons() {
        $lessonsData = $this->fetchAll('SELECT lesson_date, lesson_type FROM lesson_dates');
        $lessons = [];
        foreach ($lessonsData as $lesson) {
            $lessons[$lesson['lesson_date']] = $lesson['lesson_type'];
        }
        return $lessons;
    }

    public function getNextLessonDate() {
        $today = date('Y-m-d');
        $answer = $this->fetch(
            'SELECT lesson_date FROM lesson_dates WHERE lesson_date >= ? ORDER BY lesson_date ASC LIMIT 1', [$today]);

        return $answer['lesson_date'] ?? null;   
    }

    public function moveDataToNextLesson() {
        return $this->execute('UPDATE students SET was_present_before = is_present_now, is_present_now = 1');
    }
}
