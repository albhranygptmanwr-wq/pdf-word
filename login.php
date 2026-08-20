<?php
require_once 'config/database.php';
require_once 'auth/csrf.php';
require_once 'auth/check.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "رمز التحقق غير صالح.";
    } else {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login) || empty($password)) {
            $error = "الرجاء إدخال بيانات الدخول.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'");
            $stmt->execute([$login, $login]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                
                header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php'));
                exit;
            } else {
                $error = "بيانات الدخول غير صحيحة أو الحساب معطل.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - PDF TO WORD</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-bg">
    <div class="auth-card">
        <h2 class="text-center">PDF &rarr; WORD</h2>
        <h3 class="text-center">تسجيل الدخول</h3>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrf_input() ?>
            <div class="form-group">
                <label>اسم المستخدم أو البريد الإلكتروني</label>
                <input type="text" name="login" required autofocus>
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">دخول</button>
        </form>
    </div>
</body>
</html>