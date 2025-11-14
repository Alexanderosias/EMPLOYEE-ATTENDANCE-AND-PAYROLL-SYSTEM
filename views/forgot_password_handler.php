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

      // Check if email exists
      $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result->num_rows === 0) {
        throw new Exception('Email not found.');
      }
      $stmt->close();

      // Generate 6-digit code
      $code = rand(100000, 999999);
      $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes

      // Delete existing
      $stmt = $mysqli->prepare("DELETE FROM password_resets WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $stmt->close();

      // Insert
      $stmt = $mysqli->prepare("INSERT INTO password_resets (email, code, expires_at) VALUES (?, ?, ?)");
      $stmt->bind_param("sss", $email, $code, $expiresAt);
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

      $stmt = $mysqli->prepare("SELECT code, expires_at FROM password_resets WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      $stmt->close();

      if (!$row) {
        throw new Exception('No reset code found for this email.');
      }

      if (strtotime($row['expires_at']) < time()) {
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

      // Update the user's password
      $stmt = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
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
