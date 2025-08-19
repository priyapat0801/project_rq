<?php
require 'db_connect.php';

$username = 'testsuperadmin';
$password_to_test = '123456';

$stmt = $conn->prepare("SELECT password_hash FROM admin WHERE username=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($hash);
$stmt->fetch();
$stmt->close();

if (password_verify($password_to_test, $hash)) {
    echo "รหัสถูกต้อง ✅";
} else {
    echo "รหัสไม่ถูก ❌";
}
