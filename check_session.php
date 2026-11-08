<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['user_phone'])) {
    echo json_encode([
        'logged_in' => true,
        'user_phone' => $_SESSION['user_phone']
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}