<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'database.php';

if (!isset($_SESSION['user_phone'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT name, phone FROM users WHERE phone = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_phone']]);
    $user = $stmt->fetch();

    if ($user) {
        echo json_encode([
            'success' => true,
            'name'    => $user['name']  ?? '',
            'phone'   => $user['phone'] ?? '',
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
}