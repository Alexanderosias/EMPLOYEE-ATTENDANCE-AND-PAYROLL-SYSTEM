<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? '';
    $scan_type = $_POST['scan_type'] ?? 'IN';
    $photo = $_POST['photo'] ?? '';

    if ($employee_id && $photo) {
        // Decode Base64 image
        $photo = str_replace('data:image/png;base64,', '', $photo);
        $photo = str_replace(' ', '+', $photo);
        $photoData = base64_decode($photo);

        // Create filename with employee ID + timestamp
        $filename = "photos/" . $employee_id . "_" . time() . ".png";

        // Ensure photos directory exists
        if (!file_exists("photos")) {
            mkdir("photos", 0777, true);
        }

        // Save file
        file_put_contents($filename, $photoData);

        // TODO: Insert attendance record into DB here
        // Example: INSERT INTO attendance (employee_id, scan_type, photo_path, date_time) VALUES (...)

        echo json_encode(["status" => "success", "message" => "Attendance logged with photo"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid data"]);
    }
}
?>
