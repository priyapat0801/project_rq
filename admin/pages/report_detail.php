<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit;
}

require_once __DIR__ . '/../db_connect.php';
$conn->set_charset('utf8mb4');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
  header('Location: ./dashboard.php');
  exit;
}

$sql = "
  SELECT r.*,
         c.category_name,
         ut.user_name AS user_type_name
  FROM report_info r
  LEFT JOIN categories  c  ON c.id  = r.category_id
  LEFT JOIN user_types  ut ON ut.id = r.user_type_id
  WHERE r.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$report) {
  header('Location: ./dashboard.php');
  exit;
}

// ไฟล์แนบ
$files = [];
if ($stf = $conn->prepare("SELECT id, file_name, file_path FROM `file` WHERE report_info_id = ?")) {
  $stf->bind_param('i', $id);
  $stf->execute();
  $files = $stf->get_result()->fetch_all(MYSQLI_ASSOC);
  $stf->close();
}

function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
$pageTitle = 'รายละเอียด #' . $report['id'];
?>
<!doctype html>
<html lang="th">

<head><?php include __DIR__ . '/../partials/head.php'; ?></head>

<body>
  <?php include __DIR__ . '/../partials/header.php'; ?>

  <div class="main-layout">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-grow-1 p-4">
      <!-- Top bar -->
      <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3 d-print-none">
        <div class="d-flex flex-column">
          <nav aria-label="breadcrumb" class="small breadcrumb">
            <ol class="breadcrumb mb-1">
              <li class="breadcrumb-item"><a href="./dashboard.php">Dashboard</a></li>
              <li class="breadcrumb-item active">รายละเอียด</li>
            </ol>
          </nav>
          <h2 class="m-0 text-success fw-semibold">รายละเอียดการร้องเรียน</h2>
        </div>
        <div class="d-flex gap-2">
          <a href="./dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> กลับ</a>
          <button class="btn btn-outline-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> พิมพ์
          </button>
        </div>
      </div>

      <!-- หัวกระดาษสำหรับ Print เท่านั้น -->
      <div class="d-none d-print-block mb-3">
        <h2 class="m-0">รายละเอียดการร้องเรียน</h2>
        <div class="small text-muted">
          เลขที่: <?= (int)$report['id'] ?> |
          หมวด: <?= h($report['category_name'] ?: 'ไม่ระบุ') ?> |
          วันที่สร้าง: <?= h($report['created_at']) ?> |
          วันที่พิมพ์: <?= date('Y-m-d H:i') ?>
        </div>
        <hr>
      </div>

      <!-- กรอบเดียว: รวมหัวเรื่อง/เมตา/รายละเอียด/ผู้ส่ง/ไฟล์แนบ -->
      <div class="card shadow-sm section-card mb-4">
        <div class="card-body">
          <!-- หัวเรื่อง + เมตาดาต้า -->
          <div class="mb-3">
            <h3 class="mb-1"><?= h($report['title']) ?></h3>
            <div class="d-flex flex-wrap gap-3 small text-muted mt-1">
              <div><span class="meta-label">หมวด</span> <?= h($report['category_name'] ?: 'ไม่ระบุ') ?></div>
              <div><span class="meta-label">ประเภทผู้ใช้งาน</span> <?= h($report['user_type_name'] ?: 'ไม่ระบุ') ?></div>
              <div><span class="meta-label">วันที่สร้าง</span> <?= h($report['created_at']) ?></div>
              <?php if ((int)$report['is_anonymous'] === 2): ?>
                <span class="badge rounded-pill text-bg-warning align-self-center">ไม่ระบุตัวตน</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- เนื้อหาภายในกรอบเดียว: ซ้ายรายละเอียด / ขวาผู้ส่ง+ไฟล์แนบ -->
          <div class="row g-4">
            <!-- ซ้าย: รายละเอียด -->
            <div class="col-lg-8">
              <div class="card border-0">
                <div class="card-header bg-body-tertiary">
                  <strong>รายละเอียด</strong>
                </div>
                <div class="card-body">
                  <div class="rich-text">
                    <?= nl2br(h($report['details'])) ?>
                  </div>
                </div>
              </div>
            </div>

            <!-- ขวา: ผู้ส่ง + ไฟล์แนบ -->
            <div class="col-lg-4">
              <?php if ((int)$report['is_anonymous'] !== 2): ?>
                <div class="card border-0 mb-4">
                  <div class="card-header bg-body-terติary"><strong>ข้อมูลผู้ส่ง</strong></div>
                  <div class="card-body">
                    <dl class="row mb-0">
                      <dt class="col-5">ชื่อ-นามสกุล</dt>
                      <dd class="col-7"><?= h(trim(($report['sender_fname'] ?? '') . ' ' . ($report['sender_lname'] ?? ''))) ?></dd>

                      <dt class="col-5">อีเมล</dt>
                      <dd class="col-7">
                        <a href="mailto:<?= h($report['sender_email']) ?>"><?= h($report['sender_email']) ?></a>
                      </dd>

                      <dt class="col-5">เบอร์โทร</dt>
                      <dd class="col-7"><?= h($report['sender_mobile'] ?? '') ?></dd>
                    </dl>
                  </div>
                </div>
              <?php endif; ?>

              <div class="card border-0">
                <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
                  <strong>ไฟล์แนบ</strong>
                  <?php if (!empty($files)): ?>
                    <a class="btn btn-sm btn-outline-primary"
                      href="<?= '../../user/' . ltrim($files[0]['file_path'] ?? '', '/') ?>"
                      target="_blank" rel="noopener">เปิดไฟล์ล่าสุด</a>
                  <?php endif; ?>
                </div>
                <div class="card-body">
                  <?php if (empty($files)): ?>
                    <div class="text-muted small">ไม่มีไฟล์แนบ</div>
                  <?php else: ?>
                    <ul class="list-group list-group-flush">
                      <?php foreach ($files as $f): ?>
                        <?php $fileUrl = "../../user/" . ltrim($f['file_path'], '/'); ?>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                          <a class="text-decoration-none" href="<?= h($fileUrl) ?>" target="_blank" rel="noopener">
                            <i class="bi bi-paperclip"></i>
                            <?= h(mb_strimwidth($f['file_name'], 0, 48, '…', 'UTF-8')) ?>
                          </a>
                          <a class="btn btn-sm btn-outline-secondary" href="<?= h($fileUrl) ?>" download>ดาวน์โหลด</a>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-4 d-print-none">
        <a href="./dashboard.php" class="btn btn-outline-secondary">← กลับ</a>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/logout_modal.php'; ?>
  <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>

</html>