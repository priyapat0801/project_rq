<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../db_connect.php';

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
$totalReports = q_scalar($conn, "SELECT COUNT(*) FROM report_info");

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

// สัปดาห์นี้ (เริ่มวันจันทร์)
$thisWeek = q_scalar($conn, "
  SELECT COUNT(*) FROM report_info
  WHERE created_at >= CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY
    AND created_at <  CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY + INTERVAL 7 DAY
");

// เดือนที่แล้ว
$lastMonth = q_scalar($conn, "
  SELECT COUNT(*) FROM report_info
  WHERE created_at >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01')
    AND created_at <  DATE_FORMAT(CURDATE(), '%Y-%m-01')
");

// แจกแจงตามหมวด
$catStats = q_rows($conn, "
  SELECT c.id, c.category_name, COALESCE(x.cnt, 0) AS total
  FROM categories c
  LEFT JOIN (
    SELECT category_id, COUNT(*) AS cnt
    FROM report_info
    GROUP BY category_id
  ) x ON x.category_id = c.id
  ORDER BY c.id
");

// แจกแจงตามประเภทผู้ใช้
$userStats = q_rows($conn, "
  SELECT ut.id, ut.user_name, COALESCE(x.cnt, 0) AS total
  FROM user_types ut
  LEFT JOIN (
    SELECT user_type_id, COUNT(*) AS cnt
    FROM report_info
    GROUP BY user_type_id
  ) x ON x.user_type_id = ut.id
  ORDER BY ut.id
");

// รายการล่าสุด
$latest = q_rows($conn, "
  SELECT r.id, r.title, r.created_at, c.category_name
  FROM report_info r
  LEFT JOIN categories c ON c.id = r.category_id
  ORDER BY r.created_at DESC, r.id DESC
  LIMIT 5
");

$sumCat  = array_sum(array_column($catStats, 'total'));
$sumUser = array_sum(array_column($userStats, 'total'));

// ใช้ชื่อนี้ไปแสดงใน <title> ได้
$pageTitle = 'Dashboard';

// ไอคอนที่จะแสดง (Bootstrap Icons)
$catIcons = [
    'ข้อร้องเรียน' => 'bi-clipboard-check',
    'ข้อเสนอแนะ'  => 'bi-lightbulb',
    'แจ้งปัญหา'   => 'bi-exclamation-triangle',
];
$userIcons = [
    'นักศึกษา'      => 'bi-mortarboard',
    'บุคลากร'       => 'bi-people',
    'บุคคลภายนอก'  => 'bi-person-badge',
];
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <?php include __DIR__ . '/../partials/head.php'; ?>
</head>

<body>

    <?php include __DIR__ . '/../partials/header.php'; ?>

    <div class="main-layout">
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>

        <!-- Content -->
        <div class="flex-grow-1 p-4">
            <h2 class="mb-3">ภาพรวมระบบ</h2>
            <div class="row g-3">
                <div class="col-md-2">
                    <div class="card stat-card shadow-sm">
                        <div class="stat-title">วันนี้</div>
                        <div class="stat-value"><?= $todayReports ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stat-card shadow-sm">
                        <div class="stat-title">7 วันล่าสุด</div>
                        <div class="stat-value"><?= $last7 ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stat-card shadow-sm">
                        <div class="stat-title">30 วันล่าสุด</div>
                        <div class="stat-value"><?= $last30 ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stat-card shadow-sm">
                        <div class="stat-title">สัปดาห์นี้</div>
                        <div class="stat-value"><?= $thisWeek ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stat-card shadow-sm">
                        <div class="stat-title">เดือนที่แล้ว</div>
                        <div class="stat-value"><?= $lastMonth ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stat-card shadow-sm">
                        <div class="stat-title">ทั้งหมด</div>
                        <div class="stat-value"><?= $totalReports ?></div>
                    </div>
                </div>
            </div>

            <!-- แจกแจงหมวด + ผู้ใช้งาน -->
            <div class="row g-3 mt-1">
                <!-- ด้านซ้าย: ตามหมวดเรื่อง -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0 fw-semibold">ตามหมวดเรื่อง</h6>
                                <small class="text-muted">รวม: <?= number_format($sumCat) ?></small>
                            </div>

                            <ul class="list-unstyled stat-list mb-0">
                                <?php foreach ($catStats as $c):
                                    $label = htmlspecialchars($c['category_name'] ?? '');
                                    $count = (int)($c['total'] ?? 0);
                                    $ico   = $catIcons[$label] ?? 'bi-dot';
                                ?>
                                    <li class="stat-item">
                                        <div class="left">
                                            <span class="icon"><i class="bi <?= $ico ?>"></i></span>
                                            <span class="label"><?= $label ?></span>
                                        </div>
                                        <span class="count badge text-bg-light"><?= number_format($count) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ด้านขวา: ตามประเภทผู้ใช้งาน -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0 fw-semibold">ตามประเภทผู้ใช้งาน</h6>
                                <small class="text-muted">รวม: <?= number_format($sumUser) ?></small>
                            </div>

                            <ul class="list-unstyled stat-list mb-0">
                                <?php foreach ($userStats as $u):
                                    $label = htmlspecialchars($u['user_name'] ?? '');
                                    $count = (int)($u['total'] ?? 0);
                                    $ico   = $userIcons[$label] ?? 'bi-person';
                                ?>
                                    <li class="stat-item">
                                        <div class="left">
                                            <span class="icon"><i class="bi <?= $ico ?>"></i></span>
                                            <span class="label"><?= $label ?></span>
                                        </div>
                                        <span class="count badge text-bg-light"><?= number_format($count) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
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
                            <li class="list-group-item d-flex align-items-center">
                                <div class="me-3">
                                    <div class="fw-semibold"><?= htmlspecialchars($it['title']) ?></div>
                                    <div class="small text-muted">
                                        หมวด: <?= htmlspecialchars($it['category_name'] ?? 'ไม่ระบุ') ?>
                                        • <?= htmlspecialchars($it['created_at']) ?>
                                    </div>
                                </div>
                                <a class="btn btn-sm btn-outline-success ms-auto"
                                    href="./report_detail.php?id=<?= (int)$it['id'] ?>">
                                    ดูรายละเอียด
                                </a>
                            </li>
                    <?php endforeach;
                    endif; ?>
                </ul>
            </div>
        </div><!-- /.flex-grow-1 -->
    </div><!-- /.main-layout -->

    <?php include __DIR__ . '/../partials/logout_modal.php'; ?>
    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>

</html>