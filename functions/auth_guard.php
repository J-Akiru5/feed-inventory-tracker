<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        // Redirect to the app's login page. Adjust path to project folder.
        header('Location: /feed-inventory-tracker/public/login.php');
        exit;
    }
}

function require_owner()
{
    require_login();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
        die('Access Denied');
    }
}
