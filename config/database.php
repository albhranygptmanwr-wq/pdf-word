<?php
$host = '127.0.0.1';
$db   = 'pdf_word';
$user = 'root';
$pass = '';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Auto-seed default admin if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('Admin@12345', PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES ('admin', 'admin@example.com', ?, 'admin')");
        $insert->execute([$hash]);
    }
}  catch (PDOException $e) {
    die("خطأ قاعدة البيانات: " . $e->getMessage());
}
?>