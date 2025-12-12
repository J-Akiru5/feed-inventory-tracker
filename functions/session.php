<?php
// Session helper functions for consistent access
if (session_status() === PHP_SESSION_NONE) session_start();

function get_user_id() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function get_user_full_name() {
    return isset($_SESSION['full_name']) ? $_SESSION['full_name'] : null;
}

function get_user_role() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

function set_user_session($userId, $role, $fullName = null) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['user_id'] = $userId;
    $_SESSION['role'] = $role;
    if ($fullName !== null) $_SESSION['full_name'] = $fullName;
}

function set_flash($message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = $message;
}

function get_flash() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $m = $_SESSION['flash'] ?? null;
    if (isset($_SESSION['flash'])) unset($_SESSION['flash']);
    return $m;
}
