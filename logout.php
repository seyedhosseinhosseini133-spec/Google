<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// پاک کردن همه متغیرهای session
$_SESSION = [];

// پاک کردن کوکی session از مرورگر
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// نابود کردن کامل session
session_destroy();

echo json_encode([
    'success' => true,
    'message' => 'خروج با موفقیت انجام شد'
]);