<?php
require_once('../admin/db_connect.php');

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anonymous = $_POST['anonymousCheck'] ?? '1';

    $fname = $_POST['firstname'] ?? '';
    $lname = $_POST['lastname'] ?? '';
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['phonenumber'] ?? '';
    $title_type = $_POST['category'] ?? '';
    $department_type = $_POST['usertype'] ?? '';
    $title = $_POST['subject'] ?? '';
    $details = $_POST['description'] ?? '';

    if ($title === '' || $details === '') {
        $error = "กรุณากรอกชื่อเรื่องและรายละเอียด";
    } elseif ($anonymous != '2') {
        if ($fname === '' || $lname === '' || $email === '') {
            $error = "กรุณากรอกชื่อ นามสกุล และอีเมล หากไม่ได้เลือกส่งแบบไม่ระบุตัวตน";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "รูปแบบอีเมลไม่ถูกต้อง";
        }
    }
    if (!$error) {
        // ➕ Insert into report_info (ปล่อย created_at ให้ DB ใส่เอง)
        $sql = "INSERT INTO report_info 
            (sender_fname, sender_lname, sender_email, sender_mobile, title_type, department_type, title, details, is_anonymous)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            $error = "ขออภัย ระบบขัดข้อง กรุณาลองใหม่อีกครั้ง";
        } else {
            $is_anonymous = (int)$anonymous;
            $stmt->bind_param(
                "ssssssssi",
                $fname,
                $lname,
                $email,
                $mobile,
                $title_type,
                $department_type,
                $title,
                $details,
                $is_anonymous
            );

            if ($stmt->execute()) {
                $report_id = $stmt->insert_id;

                if (isset($_FILES['formFile']) && $_FILES['formFile']['error'] !== UPLOAD_ERR_NO_FILE) {

                    if ($_FILES['formFile']['error'] !== UPLOAD_ERR_OK) {
                        $error = "อัปโหลดไฟล์ไม่สำเร็จ (รหัส: {$_FILES['formFile']['error']})";
                    } else {

                        $target_dir = __DIR__ . "/uploads/";
                        if (!is_dir($target_dir)) {
                            mkdir($target_dir, 0755, true);
                        }

                        $originalName = $_FILES["formFile"]["name"];
                        $tmpPath      = $_FILES["formFile"]["tmp_name"];
                        $sizeBytes    = (int)$_FILES["formFile"]["size"];
                        $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                        $max_size    = 5 * 1024 * 1024; // 5MB

                        if (!in_array($ext, $allowed_ext, true)) {
                            $error = "อนุญาตเฉพาะไฟล์: " . implode(', ', $allowed_ext);
                        } elseif ($sizeBytes > $max_size) {
                            $error = "ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 5MB)";
                        } else {
                            $newName    = uniqid('rep' . $report_id . '_') . '.' . $ext;
                            $target_file = $target_dir . $newName;
                            $dbPath     = 'uploads/' . $newName;

                            if (move_uploaded_file($tmpPath, $target_file)) {
                                $sql_file  = "INSERT INTO `file` (`report_id`, `file_name`, `file_path`) VALUES (?, ?, ?)";
                                if ($stmt_file = $conn->prepare($sql_file)) {
                                    $stmt_file->bind_param("iss", $report_id, $newName, $dbPath);
                                    $stmt_file->execute();
                                    $stmt_file->close();
                                } else {
                                    error_log("File stmt prepare failed: " . $conn->error);
                                }
                            } else {
                                $error = "เกิดข้อผิดพลาดในการย้ายไฟล์";
                            }
                        }
                    }
                }
                if (!$error) {
                    header('Location: thank_you.php?status=success');
                    exit;
                }
            } else {
                error_log("Execute failed: " . $stmt->error);
                $error = "ขออภัย เกิดข้อผิดพลาดในการบันทึกข้อมูล";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index Page</title>
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
            <div class="form-title">ระบบร้องเรียนและข้อเสนอแนะ</div>
            <p class="subtitle-en">Report / Suggestions</p>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-12 mb-3">
                        <div class="checkbox-group">
                            <input type="checkbox" id="anonymousCheck" name="anonymousCheck" value="2">
                            <label for="anonymousCheck">ส่งแบบไม่ระบุตัวตน</label>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3 personal-info">
                            <label for="firstname" class="form-label ">ชื่อจริง</label>
                            <input type="text" id="firstname" name="firstname" class="form-control" aria-label="First name">
                        </div>
                        <div class="col-md-6 mb-3 personal-info">
                            <label for="lastname" class="form-label ">นามสกุล</label>
                            <input type="text" id="lastname" name="lastname" class="form-control" aria-label="Last name">
                        </div>
                        <div class="col-md-6 mb-3 personal-info">
                            <label for="email" class="form-label ">อีเมลล์</label>
                            <input type="email" id="email" name="email" class="form-control" aria-label="Email">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="usertype" class="form-label ">ประเภทผู้ใช้งาน</label>
                            <select id="usertype" name="usertype" class="form-select">
                                <option value="1">นักศึกษา</option>
                                <option value="2">บุคคลากร</option>
                                <option value="3">บุคคลภายนอก</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 personal-info">
                            <label for="phonenumber" class="form-label ">เบอร์โทรศัพท์</label>
                            <input type="text" id="phonenumber" name="phonenumber" class="form-control"
                                aria-label="PhoneNumber">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label ">หมวดเรื่อง</label>
                            <select id="category" name="category" class="form-select">
                                <option value="1">ข้อร้องเรียน</option>
                                <option value="2">ข้อเสนอแนะ</option>
                                <option value="3">แจ้งปัญหา</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="subject" class="form-label ">ชื่อเรื่อง</label>
                            <input type="text" id="subject" name="subject" class="form-control" aria-label="subject">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label ">รายละเอียด</label>
                            <textarea class="form-control " id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="formFile" class="form-label">แนบไฟล์</label>
                            <input class="form-control" type="file" id="formFile" name="formFile" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        </div>

                        <button type="submit" class="custom-btn">
                            ส่งข้อมูล
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
        <p>© 2025 ระบบร้องเรียนและข้อเสนอแนะ </p>
    </footer>
    <script>
        function toggleFields() {
            const isAnon = document.getElementById('anonymousCheck').checked; // true = ติ๊ก
            document.querySelectorAll('.personal-info').forEach(block => {
                block.style.display = isAnon ? 'none' : 'block';
                block.querySelectorAll('input').forEach(input => {
                    input.disabled = isAnon;
                    input.required = !isAnon;
                });
            });
        }
        document.addEventListener("DOMContentLoaded", () => {
            toggleFields();
            document.getElementById('anonymousCheck').addEventListener('change', toggleFields);
        });
    </script>

</body>



</html>