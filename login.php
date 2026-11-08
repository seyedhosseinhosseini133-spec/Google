<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $pass  = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT id, name, password, is_admin FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($pass, $user['password'])) {
                $_SESSION['user_phone'] = $phone;
                
                // ⭐ اگه ادمین بود، session ادمین هم ست کن
                $isAdmin = ($user['is_admin'] === true || $user['is_admin'] === 't' || $user['is_admin'] === 1);
                if ($isAdmin) {
                    $_SESSION['admin_phone'] = $phone;
                }
                
                echo json_encode([
                    'success'  => true,
                    'message'  => 'ورود موفق',
                    'is_admin' => $isAdmin
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'رمز عبور اشتباه است']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'کاربری با این شماره یافت نشد']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'خطای دیتابیس']);
    }
}
?>