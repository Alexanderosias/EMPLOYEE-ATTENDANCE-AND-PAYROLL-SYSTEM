<?php
session_start();
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require_once 'conn.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = null;
$mysqli = null;
try {
  $db = conn();
  $mysqli = $db['mysqli'];
  if (!$mysqli || $mysqli->connect_error) {
    throw new Exception('MySQL connection failed.');
  }
} catch (Exception $e) {
  ob_end_clean();
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
  exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
  case 'send_code':
    try {
      $email = $_POST['email'] ?? '';
      if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address.');
      }

      // Check if email exists in users_employee (canonical auth table)
      $stmt = $mysqli->prepare("SELECT id FROM users_employee WHERE email = ?");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();
      $user = $result->fetch_assoc();
      $stmt->close();
      if (!$user) {
        throw new Exception('Email not found.');
      }
      $userId = (int)$user['id'];

      // Generate 6-digit code (valid for ~10 minutes)
      $code = rand(100000, 999999);
      $requestedAt = date('Y-m-d H:i:s');
      $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes from now

      // Delete existing reset requests for this email
      $stmt = $mysqli->prepare("DELETE FROM password_resets WHERE email = ?");
      if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();
      }

      // Insert new reset request aligned with systemintegration schema
      // password_resets: reset_id, user_id, token, requested_at, email, code, expired_at, created_at
      $stmt = $mysqli->prepare("INSERT INTO password_resets (user_id, email, code, requested_at, expired_at) VALUES (?, ?, ?, ?, ?)");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param("issss", $userId, $email, $code, $requestedAt, $expiresAt);
      $stmt->execute();
      $stmt->close();

      // Send email
      $mail = new PHPMailer(true);
      try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'adfceaaps@gmail.com';
        $mail->Password = 'dajfllzleewickfp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('adfceaaps@gmail.com', 'ADFC EAAPS Admin');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Code';
        $mail->Body = "Your password reset code is: <strong>$code</strong>. It expires in 10 minutes.";

        $mail->send();
      } catch (Exception $e) {
        throw new Exception('Failed to send email: ' . $mail->ErrorInfo);
      }

      ob_end_clean();
      echo json_encode(['success' => true, 'message' => 'Code sent successfully.']);
    } catch (Exception $e) {
      ob_end_clean();
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  case 'verify_code':
    try {
      $email = $_POST['email'] ?? '';
      $code = $_POST['code'] ?? '';

      // Fetch most recent reset request for this email
      $stmt = $mysqli->prepare("SELECT code, requested_at FROM password_resets WHERE email = ? ORDER BY reset_id DESC LIMIT 1");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      $stmt->close();

      if (!$row) {
        throw new Exception('No reset code found for this email.');
      }

      // Validate expiry based on requested_at + 10 minutes
      $requestedAtTs = isset($row['requested_at']) ? strtotime($row['requested_at']) : 0;
      if ($requestedAtTs === 0 || ($requestedAtTs + 600) < time()) {
        throw new Exception('Code has expired.');
      }

      if ($row['code'] !== $code) {
        throw new Exception('Invalid code.');
      }

      // Delete
      $stmt = $mysqli->prepare("DELETE FROM password_resets WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $stmt->close();

      ob_end_clean();
      echo json_encode(['success' => true, 'message' => 'Code verified.']);
    } catch (Exception $e) {
      ob_end_clean();
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  case 'reset_password':
    try {
      $email = $_POST['email'] ?? '';
      $newPassword = $_POST['password'] ?? '';

      if (!$email || !$newPassword) {
        throw new Exception('Email and password required.');
      }

      // Hash the new password
      $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

      // Update the user's password in users_employee (canonical auth table)
      $stmt = $mysqli->prepare("UPDATE users_employee SET password_hash = ? WHERE email = ?");
      $stmt->bind_param("ss", $hashedPassword, $email);
      $stmt->execute();
      $affected = $stmt->affected_rows;
      $stmt->close();

      if ($affected === 0) {
        throw new Exception('User not found or password not updated.');
      }

      ob_end_clean();
      echo json_encode(['success' => true, 'message' => 'Password reset successfully.']);
    } catch (Exception $e) {
      ob_end_clean();
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  default:
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    break;
}

if ($mysqli) {
  $mysqli->close();
}
ob_end_flush();
