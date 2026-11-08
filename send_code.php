<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$phone = trim($_POST['phone'] ?? '');

if (!preg_match('/^09\d{9}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'شماره نامعتبر']);
    exit;
}

// تولید کد 5 رقمی
$code = str_pad((string)random_int(0, 99999), 5, '0', STR_PAD_LEFT);

// ذخیره در session
$_SESSION['otp_code'] = $code;
$_SESSION['otp_phone'] = $phone;

// TODO: اینجا کد رو با SMS بفرست (مثلاً Kavenegar یا ملی پیامک)
// فعلاً برای تست، کد رو در پاسخ برمی‌گردونیم

echo json_encode([
    'success' => true,
    'code' => $code,  // ⚠️ در محیط واقعی این رو حذف کن!
    'message' => 'کد ارسال شد'
]);