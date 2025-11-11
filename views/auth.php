<?php
session_start();

// Regenerate session ID for security
session_regenerate_id(true);

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ../index.html'); // Back to login
    exit;
}

// Role-based page access
$restrictedPages = [
    'user_page.php' => ['head_admin'], // Only head_admin can access
    // Add more: 'settings_page.php' => ['head_admin'],
];

// Get current page name
$currentPage = basename($_SERVER['PHP_SELF']);

// Check if current page is restricted
if (isset($restrictedPages[$currentPage])) {
    $allowedRoles = $restrictedPages[$currentPage];
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header('Location: dashboard.php'); // Fallback for unauthorized
        exit;
    }
}
