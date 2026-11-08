<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'database.php';

if (!isset($_SESSION['user_phone'])) {
    echo json_encode(['success' => false, 'message' => 'لطفاً دوباره وارد شوید']);
    exit;
}

$name  = trim($_POST['name']  ?? '');
$phone = trim($_POST['phone'] ?? '');
$pass  = $_POST['password'] ?? '';

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'نام خالی است']);
    exit;
}
if ($phone !== '' && !preg_match('/^09\d{9}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'شماره موبایل نامعتبر است']);
    exit;
}

try {
    if ($pass !== '') {
        if (strlen($pass) < 6) {
            echo json_encode(['success' => false, 'message' => 'رمز کوتاه است']);
            exit;
        }
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET name=?, phone=?, password=? WHERE phone=?');
        $stmt->execute([$name, $phone, $hash, $_SESSION['user_phone']]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET name=?, phone=? WHERE phone=?');
        $stmt->execute([$name, $phone, $_SESSION['user_phone']]);
    }

    // ⭐ اگه شماره عوض شد، session رو هم آپدیت کن
    if ($phone !== '' && $phone !== $_SESSION['user_phone']) {
        $_SESSION['user_phone'] = $phone;
    }

    echo json_encode(['success' => true, 'message' => 'ذخیره شد']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
}