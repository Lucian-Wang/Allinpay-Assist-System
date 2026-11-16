<?php
if (session_status() == PHP_SESSION_NONE) session_start();

function require_admin_login() {
    if (!isset($_SESSION['admin'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_current_user() {
    return isset($_SESSION['admin']) ? $_SESSION['admin'] : null;
}

function admin_logged_in() {
    return isset($_SESSION['admin']);
}

function admin_esc($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}