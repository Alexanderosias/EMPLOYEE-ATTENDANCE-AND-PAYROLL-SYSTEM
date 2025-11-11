<?php
session_start();

// Returns JSON about login state
header('Content-Type: application/json; charset=utf-8');

$basePath = str_replace('\\', '/', dirname(__DIR__)); // normalize slashes
$currentPath = $_SERVER['REQUEST_URI'];

// Check if logged in
$loggedIn = isset($_SESSION['user_id']);
$role = $loggedIn ? $_SESSION['role'] : null;

// Return JSON
echo json_encode([
    'loggedIn' => $loggedIn,
    'role' => $role,
    'basePath' => $basePath,
    'currentPath' => $currentPath
]);
