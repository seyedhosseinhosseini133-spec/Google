    <?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>تست دیتابیس Supabase</title>";
echo "<style>
    body { font-family: Tahoma, sans-serif; direction: rtl; padding: 20px; background: #0f172a; color: #e2e8f0; }
    h1 { color: #38bdf8; }
    .box { background: #1e293b; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); max-width: 600px; margin: 0 auto; }
    .success { color: #10b981; font-weight: bold; }
    .error { color: #ef4444; font-weight: bold; }
</style></head><body><div class='box'>";

echo "<h1>🔧 تست اتصال به Supabase (PostgreSQL)</h1>";
echo "<hr>";

if (!file_exists('database.php')) {
    die("<p class='error'>❌ خطا: فایل database.php پیدا نشد!</p>");
}

require 'database.php'; // <--- اینجا هم از فایل خودت استفاده میکنه

try {
    $stmt = $pdo->query("SELECT current_database(), current_user");
    $result = $stmt->fetch();
    
    echo "<p class='success'>✅ اتصال به دیتابیس با موفقیت انجام شد!</p>";
    echo "<p>دیتابیس فعال: <b>" . $result['current_database'] . "</b></p>";
    echo "<p>کاربر متصل: <b>" . $result['current_user'] . "</b></p>";

    // تست وجود جدول users
    $stmt = $pdo->query("SELECT to_regclass('public.users')");
    $table_exists = $stmt->fetchColumn();

    if ($table_exists) {
        echo "<p class='success'>✅ جدول <b>users</b> در دیتابیس وجود دارد.</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $count = $stmt->fetch();
        echo "<p>تعداد کاربران ثبت‌شده: <b>" . $count['total'] . "</b></p>";
    } else {
        echo "<p class='error'>❌ خطا: جدول <b>users</b> در دیتابیس وجود ندارد!</p>";
        echo "<p>لطفا این دستور SQL را در بخش SQL Editor پنل Supabase اجرا کنید:</p>";
        echo "<pre style='background:#0f172a; padding:10px; border-radius:5px; color:#38bdf8; direction:ltr; text-align:left;'>
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);</pre>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ خطا در اتصال به دیتابیس: " . $e->getMessage() . "</p>";
}

echo "</div></body></html>";
?>