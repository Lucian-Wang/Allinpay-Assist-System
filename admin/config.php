<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'db');
define('DB_USER', 'user');
define('DB_PWD',  'password');

function get_admin_db() {
    static $db = null;
    if ($db === null) {
        $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PWD);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $db;
}