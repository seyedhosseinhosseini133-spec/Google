<?php
$host = "aws-0-ap-southeast-1.pooler.supabase.com";
$port = "5432";
$dbname = "postgres";
$username = "postgres.uhkoayrrvessrvkihkbr";
$password = "Seyed333Hossein";

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    // echo "Supabase Connected Successfully!";  <-- این خط رو حذف کردم چون خروجی JSON رو خراب میکنه
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'خطا در اتصال به دیتابیس: ' . $e->getMessage()]);
    exit;
}
?>