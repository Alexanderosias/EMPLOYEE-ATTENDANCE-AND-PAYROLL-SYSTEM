<?php
session_start();

// Regenerate session ID for security
// session_regenerate_id(true); // Removed to prevent session loss on refresh

// Check if this is an API request (e.g., fetch from JS with action parameter)
$isApiRequest = isset($_GET['action']) || isset($_POST['action']);

try {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        if ($isApiRequest) {
            throw new Exception('Not logged in.');
        } else {
            header('Location: ../index.html');
            exit;
        }
    }

    $userRoles = $_SESSION['roles'] ?? [];
    if (!is_array($userRoles)) {
        $userRoles = [];
    }

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptName = str_replace('\\', '/', (string)$scriptName);

    $isAdminPage = (strpos($scriptName, '/pages/') !== false);
    $isEmployeePage = (strpos($scriptName, '/employee-pages/') !== false);

    if ($isAdminPage) {
        $isAdminLike = in_array('admin', $userRoles, true) || in_array('head_admin', $userRoles, true);
        if (!$isAdminLike) {
            if ($isApiRequest) {
                throw new Exception('Unauthorized: Admin access required.');
            } else {
                header('Location: ../index.html');
                exit;
            }
        }
    }

    if ($isEmployeePage) {
        $isEmployee = in_array('employee', $userRoles, true);
        if (!$isEmployee) {
            if ($isApiRequest) {
                throw new Exception('Unauthorized: Employee access required.');
            } else {
                header('Location: ../index.html');
                exit;
            }
        }
    }

    $restrictedPages = [
        'user_page.php' => ['head_admin'],
        'settings_page.php' => ['head_admin'],
        'holidays_events_page.php' => ['head_admin'],
    ];

    $currentPage = basename($_SERVER['PHP_SELF'] ?? '');

    if (isset($restrictedPages[$currentPage])) {
        $allowedRoles = $restrictedPages[$currentPage];

        $hasAccess = false;
        foreach ($allowedRoles as $allowedRole) {
            if (in_array($allowedRole, $userRoles, true)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            if ($isApiRequest) {
                throw new Exception('Unauthorized: Insufficient role.');
            } else {
                header('Location: ../index.html');
                exit;
            }
        }
    }
} catch (Exception $e) {
    if ($isApiRequest) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    } else {
        header('Location: ../index.html');
        exit;
    }
}
