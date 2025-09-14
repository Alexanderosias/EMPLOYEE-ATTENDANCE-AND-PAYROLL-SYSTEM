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
$photo = $data['photo']; // Base64 string (data:image/png;base64,...)

// ✅ Make sure uploads folder exists
$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// ✅ Generate safe filename (EMP001_1756396134.png)
$employeeSafe = preg_replace("/[^A-Za-z0-9]/", "", $employee_id); // remove bad chars
$photoFilename = $employeeSafe . "_" . time() . ".png";
$photoPath = $uploadDir . $photoFilename;

// ✅ Remove "data:image/png;base64," part if present
if (strpos($photo, "base64,") !== false) {
    $photo = explode("base64,", $photo)[1];
}

// ✅ Decode base64
$photoData = base64_decode($photo);

// ✅ Save to file
if (file_put_contents($photoPath, $photoData)) {
    // Save relative path (for browser access)
    $photoDbPath = "uploads/" . $photoFilename;

    // ✅ Insert into attendance_logs table
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
