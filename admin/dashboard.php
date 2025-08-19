<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once('../admin/db_connect.php'); // เชื่อม DB

// ---------------- สถิติ ----------------
// ทั้งหมด
$totalReports = (int)$conn->query("SELECT COUNT(*) c FROM report_info")->fetch_assoc()['c'];

// วันนี้
$todayReports = (int)$conn->query("
    SELECT COUNT(*) c FROM report_info
    WHERE DATE(created_at)=CURDATE()
")->fetch_assoc()['c'];

// 7 วันล่าสุด
$last7 = (int)$conn->query("
    SELECT COUNT(*) c FROM report_info
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
")->fetch_assoc()['c'];

// 30 วันล่าสุด
$last30 = (int)$conn->query("
    SELECT COUNT(*) c FROM report_info
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
")->fetch_assoc()['c'];

// สัปดาห์นี้ (ISO week)
$thisWeek = (int)$conn->query("
    SELECT COUNT(*) c FROM report_info
    WHERE YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)
")->fetch_assoc()['c'];

// เดือนนี้
$thisMonth = (int)$conn->query("
    SELECT COUNT(*) c FROM report_info
    WHERE YEAR(created_at)=YEAR(CURDATE())
      AND MONTH(created_at)=MONTH(CURDATE())
")->fetch_assoc()['c'];

// วันพีคใน 30 วัน
$peakDay = ['d' => null, 'c' => 0];
$rs = $conn->query("
    SELECT DATE(created_at) d, COUNT(*) c
    FROM report_info
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(created_at)
    ORDER BY c DESC, d DESC
    LIMIT 1
");
if ($row = $rs?->fetch_assoc()) $peakDay = $row;

// วันในสัปดาห์ที่พีค
$weekdayMap = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
$peakWeekday = ['w' => null, 'c' => 0];
$rs = $conn->query("
    SELECT DAYOFWEEK(created_at)-1 AS w, COUNT(*) c
    FROM report_info
    GROUP BY w
    ORDER BY c DESC
    LIMIT 1
");
if ($row = $rs?->fetch_assoc()) $peakWeekday = $row;

// ชั่วโมงที่พีค
$peakHour = ['h' => null, 'c' => 0];
$rs = $conn->query("
    SELECT HOUR(created_at) h, COUNT(*) c
    FROM report_info
    GROUP BY h
    ORDER BY c DESC
    LIMIT 1
");
if ($row = $rs?->fetch_assoc()) $peakHour = $row;

// แจกแจงตามหมวด (category)
$byCat = ['1' => 0, '2' => 0, '3' => 0];
$r = $conn->query("SELECT title_type, COUNT(*) c FROM report_info GROUP BY title_type");
while ($row = $r?->fetch_assoc() ?? []) {
    $byCat[(string)$row['title_type']] = (int)$row['c'];
}

// แจกแจงตามประเภทผู้ใช้งาน (usertype) ถ้ามี
$byUsertype = ['1' => 0, '2' => 0, '3' => 0];
if ($conn->query("SHOW COLUMNS FROM report_info LIKE 'usertype'")->num_rows) {
    $r = $conn->query("SELECT usertype, COUNT(*) c FROM report_info GROUP BY usertype");
    while ($row = $r?->fetch_assoc() ?? []) {
        $byUsertype[(string)$row['usertype']] = (int)$row['c'];
    }
}

// รายการล่าสุด
$latest = [];
$r = $conn->query("
    SELECT id,title,title_type,created_at
    FROM report_info
    ORDER BY created_at DESC LIMIT 5
");
while ($row = $r?->fetch_assoc() ?? []) {
    $latest[] = $row;
}

function catLabel($v)
{
    return ['1' => 'ข้อร้องเรียน', '2' => 'ข้อเสนอแนะ', '3' => 'แจ้งปัญหา'][(string)$v] ?? 'ไม่ระบุ';
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>
    <div class="site-header">
        <div class="left-section">
            <button class="burger-btn" onclick="toggleSidebar()">☰</button>
        </div>
        <div class="center-section">
            <h1 class="m-0">ระบบร้องเรียนและข้อเสนอแนะ</h1>
        </div>
        <div class="right-section">
            <!-- เว้นไว้เผื่อใส่โปรไฟล์/โลโก้/แจ้งเตือนในอนาคต -->
        </div>
    </div>

    <div class="main-layout">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <h4 class="text-center mb-4">ADMIN</h4>
            <ul class="nav flex-column px-3">
                <li class="nav-item">
                    <a class="nav-link sidebar-link active" href="#">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link" href="#">รายการร้องเรียน/ข้อเสนอแนะ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link" href="manage_users_add.php">จัดการผู้ใช้งาน</a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" id="btn-logout" class="nav-link">
                        ออกจากระบบ
                    </a>
                </li>
            </ul>
        </div>

        <!-- Content -->
        <div class="flex-grow-1 p-4">
            <h2 class="mb-3">ภาพรวมระบบ</h2>
            <div class="row g-3">
                <div class="col-md-2">
                    <div class="card p-3 text-center shadow-sm">
                        <div>ทั้งหมด</div>
                        <div class="fs-2 fw-bold"><?= $totalReports ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card p-3 text-center shadow-sm">
                        <div>วันนี้</div>
                        <div class="fs-2 fw-bold"><?= $todayReports ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card p-3 text-center shadow-sm">
                        <div>7 วันล่าสุด</div>
                        <div class="fs-2 fw-bold"><?= $last7 ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card p-3 text-center shadow-sm">
                        <div>30 วันล่าสุด</div>
                        <div class="fs-2 fw-bold"><?= $last30 ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card p-3 text-center shadow-sm">
                        <div>สัปดาห์นี้</div>
                        <div class="fs-2 fw-bold"><?= $thisWeek ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card p-3 text-center shadow-sm">
                        <div>เดือนนี้</div>
                        <div class="fs-2 fw-bold"><?= $thisMonth ?></div>
                    </div>
                </div>
            </div>

            <!-- พฤติกรรม -->
            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <div class="card p-3 text-center shadow-sm">
                        <div>วันพีค (30 วัน)</div>
                        <div class="fw-semibold"><?= $peakDay['d'] ? $peakDay['d'] . " (" . $peakDay['c'] . ")" : '-' ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 text-center shadow-sm">
                        <div>วันในสัปดาห์ที่พีค</div>
                        <div class="fw-semibold"><?= $peakWeekday['w'] !== null ? $weekdayMap[$peakWeekday['w']] . " (" . $peakWeekday['c'] . ")" : '-' ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 text-center shadow-sm">
                        <div>ชั่วโมงพีค</div>
                        <div class="fw-semibold"><?= $peakHour['h'] !== null ? sprintf('%02d:00', $peakHour['h']) . " (" . $peakHour['c'] . ")" : '-' ?></div>
                    </div>
                </div>
            </div>

            <!-- แจกแจงหมวด + ผู้ใช้งาน -->
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <div class="card p-3 shadow-sm">
                        <div class="fw-semibold mb-2">ตามหมวดเรื่อง</div>
                        <span class="badge text-bg-light me-2">ข้อร้องเรียน: <?= $byCat['1'] ?></span>
                        <span class="badge text-bg-light me-2">ข้อเสนอแนะ: <?= $byCat['2'] ?></span>
                        <span class="badge text-bg-light">แจ้งปัญหา: <?= $byCat['3'] ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3 shadow-sm">
                        <div class="fw-semibold mb-2">ตามประเภทผู้ใช้งาน</div>
                        <span class="badge text-bg-light me-2">นักศึกษา: <?= $byUsertype['1'] ?></span>
                        <span class="badge text-bg-light me-2">บุคลากร: <?= $byUsertype['2'] ?></span>
                        <span class="badge text-bg-light">บุคคลภายนอก: <?= $byUsertype['3'] ?></span>
                    </div>
                </div>
            </div>

            <!-- รายการล่าสุด -->
            <h4 class="mt-4 mb-2">รายการล่าสุด</h4>
            <div class="card shadow-sm">
                <ul class="list-group list-group-flush">
                    <?php if (empty($latest)): ?>
                        <li class="list-group-item text-center text-muted">ยังไม่มีข้อมูล</li>
                        <?php else: foreach ($latest as $it): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($it['title']) ?></div>
                                    <div class="small text-muted">
                                        หมวด: <?= catLabel($it['title_type']) ?> • <?= htmlspecialchars($it['created_at']) ?>
                                    </div>
                                </div>
                                <a class="btn btn-sm btn-outline-success" href="report_view.php?id=<?= (int)$it['id'] ?>">ดูรายละเอียด</a>
                            </li>
                    <?php endforeach;
                    endif; ?>
                </ul>
            </div>

        </div>
        <!-- Modal ยืนยันออกจากระบบ -->
        <div
            class="modal fade"
            id="logoutModal"
            tabindex="-1"
            aria-labelledby="logoutModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header custom-modal-header">
                        <h5 class="modal-title" id="logoutModalLabel">ยืนยันการออกจากระบบ</h5>
                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body fs-5 text-center py-4">
                        คุณแน่ใจจะออกจากระบบใช่ไหม?
                    </div>
                    <div class="modal-footer justify-content-center border-0 pb-4">
                        <a href="logout.php" class="btn btn-lg px-4 rounded-pill shadow-sm btn-confirm-logout">
                            ใช่, ออกจากระบบ
                        </a>
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-lg px-4 rounded-pill btn-cancel-logout"
                            data-bs-dismiss="modal">
                            ไม่, ยกเลิก
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.getElementById('btn-logout').addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'ออกจากระบบ?',
                    text: 'คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'ใช่, ออกจากระบบ',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'logout.php';
                    }
                });
            });
        </script>
        <script>
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                sidebar.classList.toggle('hide');
            }
        </script>
</body>


</html>