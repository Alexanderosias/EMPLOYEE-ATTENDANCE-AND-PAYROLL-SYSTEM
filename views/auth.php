<?php
session_start();

// Regenerate session ID for security
// session_regenerate_id(true); // Removed to prevent session loss on refresh

// Check if this is an API request (e.g., fetch from JS with action parameter)
$isApiRequest = isset($_GET['action']) || isset($_POST['action']);

try {
    // Redirect if not logged in (only for non-API requests)
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        if ($isApiRequest) {
            throw new Exception('Not logged in.');
        } else {
            header('Location: ../index.html'); // Back to login
            exit;
        }
    }

    // Role-based page access
    $restrictedPages = [
        'user_page.php' => ['head_admin'], // Only head_admin can access
        'settings_page.php' => ['head_admin'],
        'holidays_events_page.php' => ['head_admin'],
    ];

    // Get current page name
    $currentPage = basename($_SERVER['PHP_SELF']);

    // Check if current page is restricted
    if (isset($restrictedPages[$currentPage])) {
        $allowedRoles = $restrictedPages[$currentPage];
        $userRoles = $_SESSION['roles'] ?? [];

        // Check if user has any of the allowed roles
        $hasAccess = false;
        foreach ($allowedRoles as $allowedRole) {
            if (in_array($allowedRole, $userRoles)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            if ($isApiRequest) {
                throw new Exception('Unauthorized: Insufficient role.');
            } else {
                header('Location: dashboard.php'); // Fallback for unauthorized
                exit;
            }
        }
    }
} catch (Exception $e) {
    if ($isApiRequest) {
        // For API requests, output JSON error and exit
        header('Content-Type: application/json');
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    } else {
        // For page requests, redirect as before
        header('Location: ../index.html');
        exit;
    }
}
