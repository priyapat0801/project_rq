<?php
session_start();
require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  $sql = "SELECT id, username, password_hash, role_id FROM admin WHERE username = ?";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();


    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
      if (password_verify($password, $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']  = (int)$row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role_id']  = (int)$row['role_id'];

        if ((int)$row['role_id'] === 1) {
          header("Location:  dashboard.php");
          exit;
        }
        if ((int)$row['role_id'] === 2) {
          header("Location: dashboard.php");
          exit;
        }
        $error = "Access Denied";
      } else {
        $error = "รหัสผ่านไม่ถูกต้อง";
      }
    } else {
      $error = "ไม่พบชื่อผู้ใช้";
    }
  } else {
    $error = "DB Error: " . $conn->error;
  }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>เข้าสู่ระบบผู้ดูแล</title>
  <link href="https://fonts.googleapis.com/css2?family=Prompt&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/login.css">
</head>

<body>
  <div class="login-container">
    <div class="login-box">
      <h2 class="title">เข้าสู่ระบบผู้ดูแล</h2>
      <form method="post" action="">
        <div class="input-group">
          <label for="username">รหัสผู้ใช้</label>
          <input type="text" id="username" name="username" placeholder="กรอกชื่อผู้ใช้" required />
        </div>
        <div class="input-group">
          <label for="password">รหัสผ่าน</label>
          <input type="password" id="password" name="password" placeholder="กรอกรหัสผ่าน" required />
        </div>
        <button type="submit" class="login-btn">เข้าสู่ระบบ</button>
      </form>

    </div>
  </div>
</body>

</html>