<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "eaaps_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    die("No data received.");
}

$employee_id = $conn->real_escape_string($data['employee_id']);
$check_type = $conn->real_escape_string($data['check_type']);
$timestamp = $conn->real_escape_string($data['timestamp']);
$photo = $data['photo']; 

$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$employeeSafe = preg_replace("/[^A-Za-z0-9]/", "", $employee_id); 
$photoFilename = $employeeSafe . "_" . time() . ".png";
$photoPath = $uploadDir . $photoFilename;

if (strpos($photo, "base64,") !== false) {
    $photo = explode("base64,", $photo)[1];
}

$photoData = base64_decode($photo);

if (file_put_contents($photoPath, $photoData)) {
    // Save relative path (for browser access)
    $photoDbPath = "uploads/" . $photoFilename;

    $sql = "INSERT INTO attendance_logs (employee_id, check_type, timestamp, photo_path, synced)
            VALUES ('$employee_id', '$check_type', '$timestamp', '$photoDbPath', 0)";

    if ($conn->query($sql) === TRUE) {
        echo "Attendance saved with photo: " . $photoFilename;
    } else {
        echo "DB Error: " . $conn->error;
    }
} else {
    echo "Failed to save image.";
}

$conn->close();
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
