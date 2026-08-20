<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /pdf-word/login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if ($_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        die("403 Forbidden - ليس لديك صلاحية للوصول إلى هذه الصفحة.");
    }
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}
?>