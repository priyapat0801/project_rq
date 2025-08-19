<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once('../admin/db_connect.php'); // เชื่อม DB

// 0) Timezone ให้ตรงกัน
date_default_timezone_set('Asia/Bangkok');
$conn->query("SET time_zone = '+07:00'");

function q_scalar(mysqli $conn, string $sql): int
{
    $rs = $conn->query($sql);
    if (!$rs) return 0;
    $row = $rs->fetch_assoc();
    return (int)($row ? array_values($row)[0] : 0);
}
function q_rows(mysqli $conn, string $sql): array
{
    $rows = [];
    if ($rs = $conn->query($sql)) while ($r = $rs->fetch_assoc()) $rows[] = $r;
    return $rows;
}

// ทั้งหมด
$totalReports = q_scalar($conn, "
  SELECT COUNT(*) FROM report_info
");

// วันนี้ [วันนี้ 00:00, พรุ่งนี้ 00:00)
$todayReports = q_scalar($conn, "
  SELECT COUNT(*) FROM report_info
  WHERE created_at >= CURDATE()
    AND created_at <  CURDATE() + INTERVAL 1 DAY
");

// 7 วันล่าสุด (รวมวันนี้ = 7 วัน)
$last7 = q_scalar($conn, "
  SELECT COUNT(*) FROM report_info
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
");

// 30 วันล่าสุด (รวมวันนี้ = 30 วัน)
$last30 = q_scalar($conn, "
  SELECT COUNT(*) FROM report_info
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
");

// สัปดาห์นี้ (เริ่มวันจันทร์) [Mon 00:00, +7 วัน)
$thisWeek = q_scalar($conn, "
  SELECT COUNT(*) FROM report_info
  WHERE created_at >= CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY
    AND created_at <  CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY + INTERVAL 7 DAY
");

// เดือนนี้ [1 ของเดือน 00:00, เดือนถัดไป 00:00)
$thisMonth = q_scalar($conn, "
  SELECT COUNT(*) FROM report_info
  WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    AND created_at <  DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 1 MONTH
");

// วันพีคใน 30 วันล่าสุด
/*$peakDay = ['d' => null, 'c' => 0];
if ($rows = q_rows($conn, "
  SELECT DATE(created_at) AS d, COUNT(*) AS c
  FROM report_info
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
  GROUP BY DATE(created_at)
  ORDER BY c DESC, d DESC
  LIMIT 1
")) $peakDay = $rows[0];

// วันในสัปดาห์พีค (30 วันล่าสุด เพื่อให้สอดคล้องแดชบอร์ด)
$peakWeekday = ['w' => null, 'c' => 0];
if ($rows = q_rows($conn, "
  SELECT (DAYOFWEEK(created_at)-1) AS w, COUNT(*) AS c
  FROM report_info
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
  GROUP BY w
  ORDER BY c DESC
  LIMIT 1
")) $peakWeekday = $rows[0];
$weekdayMap = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];

// ชั่วโมงพีค (30 วันล่าสุด)
$peakHour = ['h' => null, 'c' => 0];
if ($rows = q_rows($conn, "
  SELECT HOUR(created_at) AS h, COUNT(*) AS c
  FROM report_info
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
  GROUP BY h
  ORDER BY c DESC
  LIMIT 1
")) $peakHour = $rows[0];*/

// แจกแจงตามหมวด (กัน key หาย + รวมไม่ระบุ)
$byCat = ['0' => 0, '1' => 0, '2' => 0, '3' => 0]; // 0 = ไม่ระบุ
foreach (
    q_rows($conn, "
  SELECT COALESCE(title_type,'0') AS k, COUNT(*) AS c
  FROM report_info
  GROUP BY k
") as $r
) $byCat[(string)$r['k']] = (int)$r['c'];

// แจกแจงตามประเภทผู้ใช้ (ถ้ามีคอลัมน์)
$byUsertype = ['0' => 0, '1' => 0, '2' => 0, '3' => 0];
if ($conn->query("SHOW COLUMNS FROM report_info LIKE 'usertype'")->num_rows) {
    foreach (
        q_rows($conn, "
    SELECT COALESCE(usertype,'0') AS k, COUNT(*) AS c
    FROM report_info
    GROUP BY k
  ") as $r
    ) $byUsertype[(string)$r['k']] = (int)$r['c'];
}

// รายการล่าสุด (ลำดับเสถียร)
$latest = q_rows($conn, "
  SELECT id, title, title_type, created_at
  FROM report_info
  ORDER BY created_at DESC, id DESC
  LIMIT 5
");

// Label หมวด
function catLabel($v): string
{
    return ['1' => 'ข้อร้องเรียน', '2' => 'ข้อเสนอแนะ', '3' => 'แจ้งปัญหา', '0' => 'ไม่ระบุ'][(string)$v] ?? 'ไม่ระบุ';
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
            <!-- <div class="row g-3 mt-1">
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
            </div> -->

            <!-- แจกแจงหมวด + ผู้ใช้งาน -->
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <div class="card p-3 shadow-sm w-100">
                        <div class="fw-semibold mb-2">ตามหมวดเรื่อง</div>
                        <span class="badge text-bg-light me-2">ข้อร้องเรียน : <?= $byCat['1'] ?></span>
                        <span class="badge text-bg-light me-2">ข้อเสนอแนะ : <?= $byCat['2'] ?></span>
                        <span class="badge text-bg-light">แจ้งปัญหา : <?= $byCat['3'] ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3 shadow-sm">
                        <div class="fw-semibold mb-2">ตามประเภทผู้ใช้งาน</div>
                        <span class="badge text-bg-light me-2">นักศึกษา : <?= $byUsertype['1'] ?></span>
                        <span class="badge text-bg-light me-2">บุคลากร : <?= $byUsertype['2'] ?></span>
                        <span class="badge text-bg-light">บุคคลภายนอก : <?= $byUsertype['3'] ?></span>
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