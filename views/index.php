<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>

<body>
  <?php
  // Load Composer autoload and modern QR library (chillerlan/php-qrcode)
  $vendorAutoload = __DIR__ . '/../vendor/autoload.php';
  if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
  } else {
    die('Composer autoload not found for QR library.');
  }

  use chillerlan\QRCode\QRCode;
  use chillerlan\QRCode\QROptions;

  // Include the database connection file
  include 'conn.php';

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

    // Generate QR code as PNG using chillerlan/php-qrcode
    $options = new QROptions([
      'outputType' => QRCode::OUTPUT_IMAGE_PNG,
      'eccLevel'   => QRCode::ECC_L,
    ]);

    $imageData = (new QRCode($options))->render($qrContent);
    if ($imageData === null || $imageData === '') {
      continue; // skip if generation failed
    }

    if (file_put_contents($qrFile, $imageData) === false) {
      continue; // skip if write failed
    }

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