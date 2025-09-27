<?php
/**
 * Database Connection File for EAAPS Project (mysqli Version)
 * Returns a mysqli instance or throws an exception on failure.
 */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'eaaps_db');

/**
 * Establishes and returns a mysqli database connection.
 * @return mysqli The mysqli connection instance.
 * @throws Exception If connection fails.
 */
function conn() {
    static $conn = null;  // Static to reuse connection (performance)

    if ($conn === null) {
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

        if ($conn->connect_error) {
            error_log('EAAPS Database Connection Error: ' . $conn->connect_error);
            throw new Exception('Failed to connect to the database: ' . $conn->connect_error);
        }

        $conn->set_charset('utf8mb4');
    }

    return $conn;
}

// Optional: Test connection (remove in production)
if (isset($_GET['test'])) {
    try {
        $mysqli = conn();
        echo json_encode(['status' => 'success', 'message' => 'Database connected successfully.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>
