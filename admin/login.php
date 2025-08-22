<?php
session_start();

require_once 'db_connect.php';
$conn->set_charset('utf8mb4');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username === '' || $password === '') {
    $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
  } else {
    $sql = "SELECT id, username, password, role_id FROM admin WHERE username = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
      $error = 'DB Error (prepare): ' . $conn->error;
    } else {
      $stmt->bind_param("s", $username);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($row = $result->fetch_assoc()) {

        // ⛏️ DEBUG ชั่วคราว: ดูความยาว hash (ทดสอบเสร็จลบ 2 บรรทัดนี้ทิ้ง)
        // echo 'DEBUG len='.strlen($row['password']).' pref='.substr($row['password'],0,4); exit;

        if (password_verify($password, $row['password'])) {

          if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($upd = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?")) {
              $upd->bind_param("si", $newHash, $row['id']);
              $upd->execute();
              $upd->close();
            }
          }

          session_regenerate_id(true);
          $_SESSION['user_id']  = (int)$row['id'];
          $_SESSION['username'] = $row['username'];
          $_SESSION['role_id']  = (int)$row['role_id'];

          header('Location: pages/dashboard.php');
          exit;
        } else {
          $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
      } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
      }

      $stmt->close();
    }
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

      <?php if ($error): ?>
        <div style="color:red; margin-bottom:10px;"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

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