<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>

<body>
  <?php
  require "phpqrcode/qrlib.php";
  $host = "localhost";
  $user = "root";
  $pass = "";
  $dbname = "eaaps_db";

  $conn = new mysqli($host, $user, $pass, $dbname);

  if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
  }

  // Generate QR for each employee
  $sql = "SELECT employee_id, first_name, last_name FROM employees";
  $result = $conn->query($sql);

  $qrDir = "qrcodes/";
  if (!is_dir($qrDir)) mkdir($qrDir);

  while ($row = $result->fetch_assoc()) {
    $employee_id = $row['employee_id'];
    $full_name = $row['first_name'] . " " . $row['last_name'];

    // sanitize filename (avoid spaces/special chars)
    $employee_file = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['first_name'] . "_" . $row['last_name']);
    $qrFile = $qrDir . $employee_file . ".png";

    // Content of QR (store unique ID + optional full name)
    $qrContent = json_encode([
      "employee_id" => $employee_id,
      "first_name" => $row['first_name'],
      "last_name"  => $row['last_name']
    ]);

    // Generate QR code
    QRcode::png($qrContent, $qrFile, QR_ECLEVEL_L, 5);

    // Update DB with QR code path
    $stmt = $conn->prepare("UPDATE employees SET qr_code_path = ? WHERE employee_id = ?");
    $stmt->bind_param("ss", $qrFile, $employee_id);
    $stmt->execute();
  }


  echo "QR codes generated successfully!";
  $conn->close();
  ?>

</body>

</html>