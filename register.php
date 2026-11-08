<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'database.php';

$name     = trim($_POST['name'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$code     = trim($_POST['code'] ?? '');

// چک کد OTP
if (!isset($_SESSION['otp_code']) || $_SESSION['otp_code'] !== $code) {
    echo json_encode(['success' => false, 'message' => 'کد تأیید اشتباه است']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'رمز کوتاه است']);
    exit;
}

try {
    // چک تکراری نبودن شماره
    $check = $pdo->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
    $check->execute([$phone]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'این شماره قبلاً ثبت شده']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, phone, password) VALUES (?, ?, ?)');
    $stmt->execute([$name, $phone, $hash]);

    // ⭐ ست کردن session — تا کاربر بلافاصله لاگین بشه
    $_SESSION['user_phone'] = $phone;
    unset($_SESSION['otp_code']);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطای دیتابیس: ' . $e->getMessage()]);
}