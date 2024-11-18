<?php

function isAdminAuthenticated() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
}

function requireAdminAuth() {
    if (!isAdminAuthenticated()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function validateAdminCredentials($username, $password) {
    // Get admin username from environment, default to 'admin'
    $validUsername = env('ADMIN_USERNAME', 'admin');
    
    // For development, use a simple password
    // In production, this should be changed to a secure password
    $validPassword = env('ADMIN_PASSWORD', 'admin123');
    
    return $username === $validUsername && $password === $validPassword;
}
