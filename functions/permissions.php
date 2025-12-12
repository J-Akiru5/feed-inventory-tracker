<?php
// Centralized role/permission helper functions
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/session.php';

function is_owner() {
    return get_user_role() === 'owner';
}

function is_storekeeper() {
    return get_user_role() === 'storekeeper';
}

function can_edit() {
    // For now, only owners can edit/manage resources
    return is_owner();
}
