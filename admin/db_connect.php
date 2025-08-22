<?php
$servername = "localhost";
$db_username = "root";
$db_password = ""; // รหัสผ่านของ MySQL
$dbname = "db_rq";  // เปลี่ยนชื่อฐานข้อมูลให้ถูกต้อง

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
