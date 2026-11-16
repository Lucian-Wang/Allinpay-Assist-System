<?php
function get_db() {
    static $db;
    if ($db) return $db;
    $db = new mysqli('localhost', 'user', 'password', 'db');
    if ($db->connect_error) die('数据库连接失败');
    $db->set_charset('utf8mb4');
    return $db;
}

session_start();

function current_user() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}
?>