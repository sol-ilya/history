<?php
require_once 'users_only.php';

if (!isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Доступ запрещен.';
    exit();
}
?>