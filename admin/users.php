<?php
require_once '../config/database.php';
require_once '../auth/csrf.php';
require_once '../auth/check.php';
require_admin();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        if($username && $email && $password && in_array($role, ['admin','user'])) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hash, $role]);
                $msg = "<div class='alert alert-success'>تم إضافة المستخدم بنجاح.</div>";
            } catch (PDOException $e) {
                $msg = "<div class='alert alert-error'>خطأ: اسم المستخدم أو البريد موجود مسبقاً.</div>";
            }
        }
    } elseif (isset($_POST['action']) && in_array($_POST['action'], ['enable', 'disable'])) {
        $userId = (int)$_POST['user_id'];
        if ($userId !== $_SESSION['user_id']) { // Prevent self-disable
            $newStatus = $_POST['action'] === 'enable' ? 'active' : 'disabled';
            $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $userId]);
            $msg = "<div class='alert alert-success'>تم تحديث حالة المستخدم.</div>";
        } else {
            $msg = "<div class='alert alert-error'>لا يمكنك تعطيل حسابك الشخصي.</div>";
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين - PDF TO WORD</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
    <nav class="navbar">
        <div class="container nav-content">
            <div class="brand">PDF &rarr; WORD</div>
            <div class="nav-links">
                <a href="../index.php">المحول</a>
                <span>مرحباً، <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="../logout.php" class="btn btn-sm">خروج</a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <aside class="sidebar">
            <h3>لوحة التحكم</h3>
            <ul>
                <li><a href="dashboard.php">الرئيسية</a></li>
                <li><a href="users.php" class="active">المستخدمون</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <?= $msg ?>
            <div class="card">
                <h3>إضافة مستخدم جديد</h3>
                <form method="POST" class="grid-form mt-3">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="add_user">
                    <div class="form-group">
                        <label>اسم المستخدم</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>البريد الإلكتروني</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>الصلاحية</label>
                        <select name="role">
                            <option value="user">مستخدم</option>
                            <option value="admin">مدير</option>
                        </select>
                    </div>
                    <div class="form-group" style="align-self: end;">
                        <button type="submit" class="btn btn-primary">إضافة</button>
                    </div>
                </form>
            </div>

            <div class="card mt-4">
                <h3>قائمة المستخدمين</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>اسم المستخدم</th>
                                <th>البريد</th>
                                <th>الصلاحية</th>
                                <th>الحالة</th>
                                <th>آخر دخول</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td><?= $u['id'] ?></td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= $u['role'] === 'admin' ? 'مدير' : 'مستخدم' ?></td>
                                <td>
                                    <span class="badge <?= $u['status'] === 'active' ? 'badge-success' : 'badge-error' ?>">
                                        <?= $u['status'] === 'active' ? 'نشط' : 'معطل' ?>
                                    </span>
                                </td>
                                <td dir="ltr"><?= $u['last_login'] ?? '-' ?></td>
                                <td>
                                    <?php if($u['id'] !== $_SESSION['user_id']): ?>
                                        <form method="POST" style="display:inline;">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <?php if($u['status'] === 'active'): ?>
                                                <input type="hidden" name="action" value="disable">
                                                <button type="submit" class="btn btn-sm btn-error">تعطيل</button>
                                            <?php else: ?>
                                                <input type="hidden" name="action" value="enable">
                                                <button type="submit" class="btn btn-sm btn-success">تفعيل</button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>