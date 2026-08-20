<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(get_csrf_token()) . '">';
}
?>