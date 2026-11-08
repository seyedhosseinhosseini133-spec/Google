<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'database.php';

try {
    // اسلایدهای فعال
    $stmt = $pdo->query('SELECT * FROM slides WHERE is_active = true ORDER BY sort_order ASC, id DESC');
    $slides = $stmt->fetchAll();
    
    // تنظیمات
    $stmt = $pdo->query('SELECT key, value FROM site_settings');
    $settings = [];
    foreach ($stmt->fetchAll() as $row) $settings[$row['key']] = $row['value'];
    
    echo json_encode([
        'success'=>true,
        'slides'=>$slides,
        'settings'=>$settings
    ]);
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'slides'=>[],'settings'=>[]]);
}
?>