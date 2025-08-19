<?php
// ตรวจสอบ query string ว่ามีสถานะ success หรือไม่
$status = $_GET['status'] ?? '';

if ($status === 'success') {
    $message = "ส่งข้อมูลเรียบร้อยแล้วค่ะ 🎉";
} else {
    $message = "เกิดข้อผิดพลาด กรุณาลองใหม่";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ขอบคุณ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt&display=swap" rel="stylesheet">
</head>

<body>
    <header class="site-header">
        <div class="header-content">
            <h1 class="header-title">ระบบร้องเรียนและข้อเสนอแนะ</h1>
            <p class="header-subtitle">Report & Suggestions</p>
        </div>
    </header>

    <main>
        <div class="feedback-box">
            <div class="form-title">ขอบคุณที่ส่งข้อมูล!</div>
            <p class="subtitle-en">Thank you for your submission</p>

            <!-- แสดงข้อความผลลัพธ์ -->
            <?php if ($status === 'success'): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php else: ?>
                <div class="alert alert-danger"><?= $message ?></div>
            <?php endif; ?>

            <!-- สามารถใส่ปุ่มหรือข้อความเพิ่มเติมตามที่ต้องการ -->
            <a href="index.php" class="btn btn-primary">กลับไปที่หน้าแรก</a>
        </div>
    </main>

    <footer class="footer">
        <p>© 2025 ระบบร้องเรียนและข้อเสนอแนะ </p>
    </footer>
</body>

</html>