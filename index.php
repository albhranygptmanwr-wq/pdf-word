<?php
require_once 'auth/check.php';
require_login();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المحول - PDF TO WORD</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-content">
            <div class="brand">PDF &rarr; WORD</div>
            <div class="nav-links">
                <?php if($_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin/dashboard.php">لوحة التحكم</a>
                <?php endif; ?>
                <span>مرحباً، <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php" class="btn btn-sm">خروج</a>
            </div>
        </div>
    </nav>

    <div class="container main-converter">
        <div class="converter-card">
            <h1 class="text-center">حوّل PDF إلى Word</h1>
            <p class="text-center text-muted">حوّل ملفات PDF إلى مستندات Word قابلة للتحرير بسهولة وسرعة.</p>

            <div id="drop-zone" class="drop-zone">
                <div class="drop-zone-text">
                    <p>اسحب ملف PDF إلى هنا<br>أو اختر ملفًا من جهازك</p>
                    <button id="browse-btn" class="btn btn-outline">اختيار ملف PDF</button>
                    <input type="file" id="file-input" accept="application/pdf" hidden>
                </div>
                <p class="text-muted mt-2">الحد الأقصى لحجم الملف: 50MB</p>
            </div>

            <div id="file-info" class="file-info hidden">
                <div class="file-details">
                    <span class="file-icon">📄</span>
                    <div class="file-meta">
                        <span id="filename"></span>
                        <span id="filesize" class="text-muted"></span>
                    </div>
                    <button id="remove-file" class="btn-close">&times;</button>
                </div>
                <button id="convert-btn" class="btn btn-primary btn-block mt-3">تحويل إلى Word</button>
            </div>

            <div id="progress-area" class="progress-area hidden">
                <p id="progress-text">جاري تحويل الملف...</p>
                <div class="progress-bar-container">
                    <div id="progress-bar" class="progress-bar"></div>
                </div>
                <p id="progress-percentage" class="text-center mt-1">0%</p>
            </div>

            <div id="success-area" class="success-area hidden text-center">
                <div class="success-icon">✓</div>
                <h3>تم التحويل بنجاح</h3>
                <p id="result-filename" class="font-weight-bold"></p>
                <a id="download-btn" href="#" class="btn btn-success mt-2">تحميل ملف Word</a>
                <button id="convert-another-btn" class="btn btn-outline mt-2">تحويل ملف آخر</button>
            </div>

            <div id="error-alert" class="alert alert-error hidden mt-3"></div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>