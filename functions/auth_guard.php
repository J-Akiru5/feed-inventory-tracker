<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/permissions.php';

function require_login()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (get_user_id() === null) {
        // Redirect to the app's login page. Adjust path to project folder.
        header('Location: /feed-inventory-tracker/public/login.php');
        exit;
    }
}

function require_role(string $role)
{
    require_login();
    if (get_user_role() !== $role) {
        http_response_code(403);
        die('Access Denied');
    }
}

function require_any_role(array $roles)
{
    require_login();
    $r = get_user_role();
    if (!in_array($r, $roles, true)) {
        http_response_code(403);
        die('Access Denied');
    }
}

function require_owner()
{
    require_role('owner');
}
