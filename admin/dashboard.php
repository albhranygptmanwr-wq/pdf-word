<?php
require_once '../config/database.php';
require_once '../auth/check.php';
require_admin();

$usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$conversionsCount = $pdo->query("SELECT COUNT(*) FROM conversions")->fetchColumn();
$successCount = $pdo->query("SELECT COUNT(*) FROM conversions WHERE status = 'completed'")->fetchColumn();

$recentStmt = $pdo->query("
    SELECT c.original_name, c.file_size, c.status, c.created_at, u.username 
    FROM conversions c 
    JOIN users u ON c.user_id = u.id 
    ORDER BY c.created_at DESC LIMIT 10
");
$recent = $recentStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - PDF TO WORD</title>
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
                <li><a href="dashboard.php" class="active">الرئيسية</a></li>
                <li><a href="users.php">المستخدمون</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>عدد المستخدمين</h4>
                    <div class="stat-value"><?= $usersCount ?></div>
                </div>
                <div class="stat-card">
                    <h4>عدد عمليات التحويل</h4>
                    <div class="stat-value"><?= $conversionsCount ?></div>
                </div>
                <div class="stat-card">
                    <h4>العمليات الناجحة</h4>
                    <div class="stat-value text-success"><?= $successCount ?></div>
                </div>
            </div>

            <div class="card mt-4">
                <h3>آخر عمليات التحويل</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>المستخدم</th>
                                <th>اسم الملف</th>
                                <th>الحجم</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['original_name']) ?></td>
                                <td><?= round($row['file_size'] / 1024 / 1024, 2) ?> MB</td>
                                <td><span class="badge badge-success"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td dir="ltr"><?= $row['created_at'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($recent)): ?>
                                <tr><td colspan="5" class="text-center">لا توجد عمليات تحويل حتى الآن.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>