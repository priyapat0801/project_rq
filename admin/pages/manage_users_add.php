<?php
// manage_users.php
declare(strict_types=1);
session_start();
require_once '../db_connect.php';


// 1) Guard: superadmin เท่านั้น
if (!isset($_SESSION['user_id']) || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    http_response_code(403);
    exit('Access Denied');
}

// 2) CSRF
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];
function check_csrf()
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        exit('Bad CSRF');
    }
}

// 3) Handle actions: create / update / delete / reset_pw
$err = $ok = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    if ($action === 'create') {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        $r = (int)($_POST['role_id'] ?? 2);
        if ($u === '' || strlen($p) < 6 || !in_array($r, [1, 2], true)) {
            $err = 'ข้อมูลไม่ครบ/รหัสสั้น';
        } else {
            $hash = password_hash($p, PASSWORD_DEFAULT);
            $st = $conn->prepare("INSERT INTO admin (username,password_hash,role_id) VALUES (?,?,?)");
            $st->bind_param("ssi", $u, $hash, $r);
            if ($st->execute()) $ok = 'เพิ่มผู้ใช้เรียบร้อย';
            else $err = ($conn->errno === 1062 ? 'username ซ้ำ' : 'บันทึกไม่สำเร็จ: ' . $conn->error);
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $u  = trim($_POST['username'] ?? '');
        $r  = (int)($_POST['role_id'] ?? 2);
        if ($id <= 0 || $u === ' ' || !in_array($r, [1, 2], true)) {
            $err = 'ข้อมูลไม่ครบ';
        } else {
            $st = $conn->prepare("UPDATE admin SET username=?, role_id=? WHERE id=?");
            $st->bind_param("sii", $u, $r, $id);
            if ($st->execute()) $ok = 'อัปเดตผู้ใช้แล้ว';
            else $err = ($conn->errno === 1062 ? 'username ซ้ำ' : 'อัปเดตไม่สำเร็จ: ' . $conn->error);
        }
    }

    if ($action === 'reset_pw') {
        $id = (int)($_POST['id'] ?? 0);
        $p  = $_POST['new_password'] ?? '';
        if ($id <= 0 || strlen($p) < 6) {
            $err = 'รหัสสั้นเกินไป';
        } else {
            $hash = password_hash($p, PASSWORD_DEFAULT);
            $st = $conn->prepare("UPDATE admin SET password_hash=? WHERE id=?");
            $st->bind_param("si", $hash, $id);
            if ($st->execute()) $ok = 'รีเซ็ตรหัสแล้ว';
            else $err = 'รีเซ็ตไม่สำเร็จ: ' . $conn->error;
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $err = 'ไม่พบผู้ใช้';
        } else {
            if ($id === (int)$_SESSION['user_id']) {
                $err = 'ห้ามลบตัวเอง';
            } else {
                $st = $conn->prepare("DELETE FROM admin WHERE id=?");
                $st->bind_param("i", $id);
                if ($st->execute()) $ok = 'ลบผู้ใช้แล้ว';
                else $err = 'ลบไม่สำเร็จ: ' . $conn->error;
            }
        }
    }
}

// 4) Read list
$users = $conn->query("SELECT id, username, role_id, created_at FROM admin ORDER BY id DESC");
?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>จัดการผู้ใช้งาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <h1 class="mb-3">จัดการผู้ใช้งาน (เฉพาะ Super Admin)</h1>

        <?php if ($ok): ?><div class="alert alert-success"><?= $ok ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

        <!-- Create -->
        <div class="card mb-4">
            <div class="card-header">เพิ่มผู้ใช้ใหม่</div>
            <div class="card-body">
                <form method="post" class="row g-2">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="col-md-3"><input name="username" class="form-control" placeholder="username" required></div>
                    <div class="col-md-3"><input type="password" name="password" class="form-control" placeholder="รหัสผ่าน (≥6)" required></div>
                    <div class="col-md-3">
                        <select name="role_id" class="form-select">
                            <option value="2">Admin</option>
                            <option value="1">Super Admin</option>
                        </select>
                    </div>
                    <div class="col-md-3"><button class="btn btn-primary w-100">เพิ่ม</button></div>
                </form>
            </div>
        </div>

        <!-- List + inline edit / delete / reset pw -->
        <div class="card">
            <div class="card-header">รายชื่อผู้ใช้</div>
            <div class="card-body table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>สร้างเมื่อ</th>
                            <th class="text-end">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$u['id'] ?></td>
                                <td>
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <input name="username" class="form-control form-control-sm" value="<?= htmlspecialchars($u['username']) ?>">
                                        <select name="role_id" class="form-select form-select-sm" style="max-width:140px">
                                            <option value="2" <?= ((int)$u['role_id'] === 2 ? 'selected' : '') ?>>Admin</option>
                                            <option value="1" <?= ((int)$u['role_id'] === 1 ? 'selected' : '') ?>>Super Admin</option>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary">บันทึก</button>
                                    </form>
                                </td>
                                <td><?= ((int)$u['role_id'] === 1 ? 'Super Admin' : 'Admin') ?></td>
                                <td><?= htmlspecialchars($u['created_at']) ?></td>
                                <td class="text-end">
                                    <!-- reset pw -->
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="reset_pw">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <input type="password" name="new_password" class="form-control d-inline-block form-control-sm" style="width:160px" placeholder="รหัสใหม่ (≥6)" required>
                                        <button class="btn btn-sm btn-warning">รีเซ็ตรหัส</button>
                                    </form>
                                    <!-- delete -->
                                    <form method="post" class="d-inline" onsubmit="return confirm('ยืนยันลบผู้ใช้นี้?');">
                                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger">ลบ</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>