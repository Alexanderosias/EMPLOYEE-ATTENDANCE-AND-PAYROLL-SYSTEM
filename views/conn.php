<?php
/**
 * Database Connections for EAAPS Project: MySQL (local) + Firebase Realtime Database (cloud)
 * Returns an array: ['mysqli' => mysqli instance, 'firebase' => Database instance or null]
 * Usage: 
 *   $db = conn();
 *   $mysqli = $db['mysqli'];  // For MySQL queries (existing code)
 *   if (hasFirebase($db)) { $database = $db['firebase']; $ref = $database->getReference('path'); }  // For Realtime DB
 * Note: Paths adjusted for views/ location (vendor/config at root level, siblings to views/).
 */

// MySQL Configuration (your existing setup)
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'eaaps_db');

// Firebase Realtime Database Configuration (from your JS config)
define('FIREBASE_CREDENTIALS_PATH', __DIR__ . '/../config/firebase-credentials.json');  // ../ to root/config/
define('FIREBASE_DATABASE_URI', 'https://eaaps-45d6a-default-rtdb.asia-southeast1.firebasedatabase.app');  // Your databaseURL
define('FIREBASE_PROJECT_ID', 'eaaps-45d6a');  // Your projectId

// Composer Autoload Path (adjusted for root/vendor/ from views/)
$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload_path)) {
    error_log('EAAPS: Composer autoload not found at ' . $autoload_path . '. Run "composer require kreait/firebase-php" in project root (same level as views/).');
    // Do NOT require – fallback to MySQL only (prevents fatal error/500)
} else {
    require_once $autoload_path;
}

/**
 * Establishes and returns database connections (MySQL + Firebase).
 * @return array ['mysqli' => mysqli, 'firebase' => Database instance or null]
 * @throws Exception If MySQL fails.
 */
function conn() {
    static $mysqli = null;  // Static for MySQL reuse (your existing performance optimization)
    static $firebase = null;  // Static for Firebase reuse (now Database instance)

    // MySQL Connection (unchanged from your code)
    if ($mysqli === null) {
        $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

        if ($mysqli->connect_error) {
            error_log('EAAPS MySQL Connection Error: ' . $mysqli->connect_error);
            throw new Exception('Failed to connect to the database: ' . $mysqli->connect_error);
        }

        $mysqli->set_charset('utf8mb4');
    }

    // Firebase Realtime Database Initialization (lazy/static)
    if ($firebase === null) {
        $firebase = null;  // Default to null (fallback)
        try {
            // Check if SDK is available (Composer installed and loaded)
            if (!class_exists('\Kreait\Firebase\Factory')) {
                throw new Exception("Firebase SDK not installed/loaded. Run 'composer require kreait/firebase-php' in project root.");
            }

            if (!file_exists(FIREBASE_CREDENTIALS_PATH)) {
                throw new Exception("Firebase credentials file not found: " . FIREBASE_CREDENTIALS_PATH . ". Download from Firebase Console > Service Accounts and place in root/config/.");
            }

            // FIXED: Create Firebase factory and get Database instance directly
            $factory = (new \Kreait\Firebase\Factory())
                ->withServiceAccount(FIREBASE_CREDENTIALS_PATH)
                ->withDatabaseUri(FIREBASE_DATABASE_URI)
                ->withProjectId(FIREBASE_PROJECT_ID);

            $firebase = $factory->createDatabase();  // Returns Database instance (standard for Realtime DB)
            
            // Optional: Simple test (get root reference value)
            $rootRef = $firebase->getReference('/');  // Directly on Database instance
            $rootRef->getValue();  // Ping; fails if invalid setup

            error_log("EAAPS Firebase Realtime DB initialized successfully for project: " . FIREBASE_PROJECT_ID);

        } catch (Exception $fb_error) {
            error_log("EAAPS Firebase Realtime DB Initialization Error: " . $fb_error->getMessage());
            $firebase = null;  // Graceful fallback: Use MySQL only
        }
    }

    return [
        'mysqli' => $mysqli,
        'firebase' => $firebase  // Database instance or null; check with hasFirebase()
    ];
}

// Helper: Check if Firebase is available
function hasFirebase($db) {
    return $db['firebase'] !== null;
}

// Optional: Test both connections (extended from your ?test param)
if (isset($_GET['test'])) {
    try {
        $db = conn();
        $mysql_status = $db['mysqli']->ping() ? 'success' : 'error';
        $firebase_status = hasFirebase($db) ? 'success' : 'error';
        
        echo json_encode([
            'status' => 'success',
            'mysql' => ['connected' => $mysql_status === 'success'],
            'firebase' => ['connected' => $firebase_status === 'success', 'project_id' => FIREBASE_PROJECT_ID],
            'message' => 'Connections tested successfully.'
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
