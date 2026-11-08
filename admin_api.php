<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'database.php';

// چک ادمین
if (!isset($_SESSION['admin_phone'])) {
    echo json_encode(['success'=>false,'message'=>'not_admin']); exit;
}
try {
    $stmt = $pdo->prepare('SELECT id, is_admin FROM users WHERE phone = ? LIMIT 1');
    $stmt->execute([$_SESSION['admin_phone']]);
    $admin = $stmt->fetch();
    if (!$admin || $admin['is_admin'] !== true) {
        echo json_encode(['success'=>false,'message'=>'not_admin']); exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'message'=>'DB error']); exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {

        // ========== USERS ==========
        case 'list_users':
            $stmt = $pdo->query('SELECT id, name, phone, is_admin, is_active, created_at FROM users ORDER BY id DESC');
            $users = $stmt->fetchAll();
            foreach ($users as &$u) {
                $u['is_admin'] = ($u['is_admin'] === true || $u['is_admin'] === 't' || $u['is_admin'] === 1);
                $u['is_active'] = !($u['is_active'] === false || $u['is_active'] === 'f' || $u['is_active'] === 0);
            }
            echo json_encode(['success'=>true,'users'=>$users]);
            break;

        case 'update_user':
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            if (!$id || $name === '' || !preg_match('/^09\d{9}$/', $phone)) {
                echo json_encode(['success'=>false,'message'=>'اطلاعات نامعتبر']); exit;
            }
            $chk = $pdo->prepare('SELECT id FROM users WHERE phone = ? AND id != ? LIMIT 1');
            $chk->execute([$phone, $id]);
            if ($chk->fetch()) {
                echo json_encode(['success'=>false,'message'=>'این شماره قبلاً ثبت شده']); exit;
            }
            if ($password !== '') {
                if (strlen($password) < 6) {
                    echo json_encode(['success'=>false,'message'=>'رمز کوتاه است']); exit;
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET name=?, phone=?, password=? WHERE id=?');
                $stmt->execute([$name, $phone, $hash, $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET name=?, phone=? WHERE id=?');
                $stmt->execute([$name, $phone, $id]);
            }
            echo json_encode(['success'=>true]);
            break;

        case 'delete_user':
            $id = (int)($_POST['id'] ?? 0);
            if ($id == $admin['id']) {
                echo json_encode(['success'=>false,'message'=>'نمی‌توانید خودتان را حذف کنید']); exit;
            }
            $stmt = $pdo->prepare('DELETE FROM users WHERE id=?');
            $stmt->execute([$id]);
            echo json_encode(['success'=>true]);
            break;

        case 'toggle_admin':
            $id = (int)($_POST['id'] ?? 0);
            $val = $_POST['is_admin'] === '1';
            if ($id == $admin['id'] && !$val) {
                echo json_encode(['success'=>false,'message'=>'نمی‌توانید ادمینی خودتان را بردارید']); exit;
            }
            $stmt = $pdo->prepare('UPDATE users SET is_admin=? WHERE id=?');
            $stmt->execute([$val ? 'true' : 'false', $id]);
            echo json_encode(['success'=>true]);
            break;

        case 'toggle_active':
            $id = (int)($_POST['id'] ?? 0);
            $val = $_POST['is_active'] === '1';
            if ($id == $admin['id'] && !$val) {
                echo json_encode(['success'=>false,'message'=>'نمی‌توانید خودتان را غیرفعال کنید']); exit;
            }
            $stmt = $pdo->prepare('UPDATE users SET is_active=? WHERE id=?');
            $stmt->execute([$val ? 'true' : 'false', $id]);
            echo json_encode(['success'=>true]);
            break;

        // ========== STATS ==========
        case 'stats':
            $days = max(7, min(365, (int)($_GET['days'] ?? 7)));
            $stmt = $pdo->query('SELECT id, is_admin, is_active, created_at FROM users');
            $users = $stmt->fetchAll();
            
            $total = count($users);
            $active = 0; $admins = 0; $today = 0; $week = 0; $month = 0;
            $now = new DateTime(); $todayStr = $now->format('Y-m-d');
            $weekAgo = (new DateTime('-7 days'))->format('Y-m-d H:i:s');
            $monthAgo = (new DateTime('-30 days'))->format('Y-m-d H:i:s');
            
            $daily = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $d = (new DateTime("-$i days"))->format('Y-m-d');
                $daily[$d] = 0;
            }
            
            foreach ($users as $u) {
                $isActive = !($u['is_active'] === false || $u['is_active'] === 'f' || $u['is_active'] === 0);
                $isAdmin = ($u['is_admin'] === true || $u['is_admin'] === 't' || $u['is_admin'] === 1);
                if ($isActive) $active++;
                if ($isAdmin) $admins++;
                $created = $u['created_at'] ?? '';
                $createdDay = substr($created, 0, 10);
                if ($createdDay === $todayStr) $today++;
                if ($created >= $weekAgo) $week++;
                if ($created >= $monthAgo) $month++;
                if (isset($daily[$createdDay])) $daily[$createdDay]++;
            }
            
            echo json_encode([
                'success'=>true,
                'total'=>$total, 'active'=>$active, 'inactive'=>$total-$active,
                'admins'=>$admins, 'today'=>$today, 'week'=>$week, 'month'=>$month,
                'daily'=>$daily, 'days'=>$days
            ]);
            break;

        // ========== SLIDES ==========
        case 'list_slides':
            $stmt = $pdo->query('SELECT * FROM slides ORDER BY sort_order ASC, id DESC');
            echo json_encode(['success'=>true,'slides'=>$stmt->fetchAll()]);
            break;

        case 'save_slide':
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $subtitle = trim($_POST['subtitle'] ?? '');
            $link = trim($_POST['link_url'] ?? '');
            $order = (int)($_POST['sort_order'] ?? 0);
            $isActive = ($_POST['is_active'] ?? '1') === '1';
            $imageUrl = trim($_POST['image_url'] ?? '');
            
            if ($title === '') {
                echo json_encode(['success'=>false,'message'=>'عنوان الزامی است']); exit;
            }
            
            // آپلود فایل
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/slides/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    echo json_encode(['success'=>false,'message'=>'فرمت مجاز: jpg, png, gif, webp']); exit;
                }
                if ($_FILES['image']['size'] > 5*1024*1024) {
                    echo json_encode(['success'=>false,'message'=>'حجم بیشتر از ۵ مگابایت']); exit;
                }
                $filename = 'slide_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $imageUrl = 'uploads/slides/' . $filename;
                }
            }
            
            if ($imageUrl === '') {
                echo json_encode(['success'=>false,'message'=>'تصویر الزامی است']); exit;
            }
            
            if ($id > 0) {
                // حذف فایل قدیمی اگه فایل جدید آپلود شده
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $oldStmt = $pdo->prepare('SELECT image_url FROM slides WHERE id=?');
                    $oldStmt->execute([$id]);
                    $old = $oldStmt->fetch();
                    if ($old && $old['image_url'] && strpos($old['image_url'], 'uploads/') === 0) {
                        $oldFile = __DIR__ . '/' . $old['image_url'];
                        if (file_exists($oldFile)) @unlink($oldFile);
                    }
                }
                $stmt = $pdo->prepare('UPDATE slides SET title=?, subtitle=?, image_url=?, link_url=?, sort_order=?, is_active=? WHERE id=?');
                $stmt->execute([$title, $subtitle, $imageUrl, $link, $order, $isActive ? 'true' : 'false', $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO slides (title, subtitle, image_url, link_url, sort_order, is_active) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$title, $subtitle, $imageUrl, $link, $order, $isActive ? 'true' : 'false']);
            }
            echo json_encode(['success'=>true]);
            break;

        case 'delete_slide':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT image_url FROM slides WHERE id=?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $row['image_url'] && strpos($row['image_url'], 'uploads/') === 0) {
                $file = __DIR__ . '/' . $row['image_url'];
                if (file_exists($file)) @unlink($file);
            }
            $stmt = $pdo->prepare('DELETE FROM slides WHERE id=?');
            $stmt->execute([$id]);
            echo json_encode(['success'=>true]);
            break;

        // ========== SETTINGS ==========
        case 'get_settings':
            $stmt = $pdo->query('SELECT key, value FROM site_settings');
            $settings = [];
            foreach ($stmt->fetchAll() as $row) $settings[$row['key']] = $row['value'];
            echo json_encode(['success'=>true,'settings'=>$settings]);
            break;

        case 'save_settings':
            $settings = $_POST['settings'] ?? [];
            if (!is_array($settings)) {
                echo json_encode(['success'=>false,'message'=>'داده نامعتبر']); exit;
            }
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare('INSERT INTO site_settings (key, value, updated_at) VALUES (?, ?, NOW())
                    ON CONFLICT (key) DO UPDATE SET value=EXCLUDED.value, updated_at=NOW()');
                $stmt->execute([$key, $value]);
            }
            echo json_encode(['success'=>true]);
            break;

        default:
            echo json_encode(['success'=>false,'message'=>'action نامعتبر']);
    }
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'message'=>'DB error: ' . $e->getMessage()]);
}
?>