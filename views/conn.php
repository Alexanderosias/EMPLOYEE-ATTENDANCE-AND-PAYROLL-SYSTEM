<?php
/**
 * Database Connection for EAAPS Project: MySQL Only
 * Returns an array: ['mysqli' => mysqli instance]
 * Usage: 
 *   $db = conn();
 *   $mysqli = $db['mysqli'];  // For MySQL queries
 */

// MySQL Configuration (update for Hostinger after upload)
define('DB_SERVER', 'localhost');  // Hostinger: Use provided MySQL host (e.g., 'mysql.hostinger.com')
define('DB_USERNAME', 'root');     // Hostinger: Use provided username
define('DB_PASSWORD', '');         // Hostinger: Use provided password
define('DB_NAME', 'eaaps_db');     // Hostinger: Use provided database name

/**
 * Establishes and returns database connection (MySQL only).
 * @return array ['mysqli' => mysqli instance]
 * @throws Exception If MySQL fails.
 */
function conn() {
    static $mysqli = null;  // Static for reuse

    if ($mysqli === null) {
        $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

        if ($mysqli->connect_error) {
            error_log('EAAPS MySQL Connection Error: ' . $mysqli->connect_error);
            throw new Exception('Failed to connect to the database: ' . $mysqli->connect_error);
        }

        $mysqli->set_charset('utf8mb4');
        error_log('EAAPS MySQL connected successfully to ' . DB_NAME);
    }

    return [
        'mysqli' => $mysqli
    ];
}

// Helper: Firebase not used (always false)
function hasFirebase($db) {
    return false;
}

// Test endpoint
if (isset($_GET['test'])) {
    try {
        $db = conn();
        $mysql_status = $db['mysqli']->ping() ? 'success' : 'error';
        
        echo json_encode([
            'status' => 'success',
            'mysql' => ['connected' => $mysql_status === 'success'],
            'message' => 'MySQL connection tested successfully.'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
?>