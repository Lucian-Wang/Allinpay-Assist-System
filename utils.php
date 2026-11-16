<?php
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf_token'];
}
function csrf_check($token) {
    return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
}
function esc($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function redirect($url) {
    header('Location: '.$url); exit;
}
function require_login() {
    if (!current_user()) redirect('/users/login.php');
}
function password_hash_safe($pwd) {
    return password_hash($pwd, PASSWORD_DEFAULT);
}
function password_verify_safe($pwd, $hash) {
    return password_verify($pwd, $hash);
}
?>