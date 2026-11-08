<?php
session_start();
require_once 'database.php';

$isLoggedIn = isset($_SESSION['admin_phone']);
$isAdmin = false;

if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare('SELECT is_admin FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$_SESSION['admin_phone']]);
        $u = $stmt->fetch();
        $isAdmin = $u && $u['is_admin'] === true;
    } catch (PDOException $e) { $isAdmin = false; }
}

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $phone = trim($_POST['phone'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!preg_match('/^09\d{9}$/', $phone)) $loginError = 'شماره نامعتبر';
    elseif ($pass === '') $loginError = 'رمز را وارد کنید';
    else {
        try {
            $stmt = $pdo->prepare('SELECT phone, password, is_admin FROM users WHERE phone = ? LIMIT 1');
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            if (!$user) $loginError = 'کاربری یافت نشد';
            elseif (!password_verify($pass, $user['password'])) $loginError = 'رمز اشتباه است';
            elseif ($user['is_admin'] !== true) $loginError = 'دسترسی ادمین ندارید';
            else { $_SESSION['admin_phone'] = $phone; header('Location: admin.php'); exit; }
        } catch (PDOException $e) { $loginError = 'خطای دیتابیس'; }
    }
}

// ⭐ خروج → login.html
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_phone']);
    unset($_SESSION['user_phone']);
    session_destroy();
    header('Location: login.html');
    exit;
}

if (!$isLoggedIn || !$isAdmin) {
    ?>
    <!doctype html><html lang="fa" dir="rtl"><head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>ورود ادمین</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
      *{box-sizing:border-box;margin:0;padding:0}
      body{min-height:100vh;display:grid;place-items:center;font-family:Vazirmatn,sans-serif;padding:20px;
        background:radial-gradient(70% 55% at 92% -6%,rgba(74,111,165,.55),transparent 62%),
        radial-gradient(60% 45% at 2% 30%,rgba(143,163,184,.32),transparent 60%),
        radial-gradient(70% 50% at 0% 105%,rgba(201,123,74,.30),transparent 60%),
        linear-gradient(180deg,#0d1117,#070a0e 55%,#050810);color:#eef2ff}
      .login-card{width:100%;max-width:400px;background:linear-gradient(145deg,rgba(143,163,184,.22),rgba(43,49,56,.42));
        backdrop-filter:blur(30px);border:1px solid rgba(212,221,230,.28);border-radius:28px;padding:36px 28px;text-align:center;
        box-shadow:inset 0 2px 1px rgba(212,221,230,.45),inset 0 -3px 2px rgba(0,0,0,.4),0 6px 0 rgba(0,0,0,.4),0 10px 22px rgba(0,0,0,.45)}
      .logo{font-family:Arial;font-weight:500;font-size:42px;letter-spacing:-2px;direction:ltr;margin-bottom:8px}
      .logo .b{color:#4285f4}.logo .r{color:#ea4335}.logo .y{color:#fbbc05}.logo .g{color:#34a853}
      .title{font-size:20px;font-weight:800;margin:0 0 6px}
      .sub{font-size:13px;color:#93a0bd;margin:0 0 28px}
      .form-group{text-align:right;margin-bottom:16px}
      .form-group label{display:block;font-size:13px;font-weight:600;color:#c9d4ea;margin-bottom:8px;margin-right:6px}
      .form-group input{width:100%;height:54px;padding:0 16px;background:rgba(255,255,255,.05);
        border:1.5px solid rgba(255,255,255,.15);border-radius:16px;color:#eef2ff;font-size:15px;outline:none;font-family:inherit}
      .form-group input:focus{border-color:#8ab4f8}
      .btn{width:100%;height:54px;border-radius:16px;margin-top:10px;background:linear-gradient(145deg,#4285f4,#2563eb);
        color:#fff;font-size:16px;font-weight:700;border:none;cursor:pointer;font-family:inherit;
        box-shadow:0 8px 20px rgba(37,99,235,.4);transition:all .2s}
      .btn:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(37,99,235,.5)}
      .btn:active{transform:translateY(2px)}
      .error{padding:12px;border-radius:12px;background:rgba(234,67,53,.15);color:#fca5a5;font-size:13px;font-weight:600;margin-bottom:14px;border:1px solid rgba(234,67,53,.3)}
    </style></head><body>
      <div class="login-card">
        <div class="logo"><span class="b">G</span><span class="r">o</span><span class="y">o</span><span class="b">g</span><span class="g">l</span><span class="r">e</span></div>
        <h1 class="title">🔐 پنل مدیریت</h1><p class="sub">فقط ادمین‌ها وارد شوند</p>
        <?php if ($loginError): ?><div class="error"><?= htmlspecialchars($loginError) ?></div><?php endif; ?>
        <form method="POST"><input type="hidden" name="action" value="login">
          <div class="form-group"><label>شماره ادمین</label>
            <input type="tel" name="phone" placeholder="09XXXXXXXXX" maxlength="11" required dir="ltr" style="text-align:right" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"></div>
          <div class="form-group"><label>رمز عبور</label>
            <input type="password" name="password" placeholder="رمز" required></div>
          <button class="btn" type="submit">ورود</button>
        </form>
      </div>
    </body></html>
    <?php exit;
}

$adminName = 'مدیر';
try {
    $stmt = $pdo->prepare('SELECT name FROM users WHERE phone = ? LIMIT 1');
    $stmt->execute([$_SESSION['admin_phone']]);
    $u = $stmt->fetch();
    if ($u && $u['name']) $adminName = $u['name'];
} catch (PDOException $e) {}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Google Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
:root{--ink:#eef2ff;--ink-soft:#c9d4ea;--muted:#93a0bd;--blue:#8ab4f8;
  --ease:cubic-bezier(.2,.8,.2,1);
  --card-bg:linear-gradient(145deg,rgba(30,40,65,.85),rgba(15,20,35,.92));
  --card-border:rgba(100,130,200,.18);
  --card-shadow:inset 0 1px 0 rgba(255,255,255,.06),0 8px 24px rgba(0,0,0,.4);}
body{min-height:100vh;font-family:Vazirmatn,sans-serif;color:var(--ink);
  background:radial-gradient(70% 55% at 92% -6%,rgba(45,80,160,.4),transparent 62%),
  radial-gradient(60% 45% at 2% 30%,rgba(80,100,140,.2),transparent 60%),
  radial-gradient(70% 50% at 0% 105%,rgba(180,100,60,.2),transparent 60%),
  linear-gradient(180deg,#0a0f1a,#070a12 55%,#050810);
  -webkit-font-smoothing:antialiased;padding-bottom:100px}

.container{max-width:900px;margin:0 auto;padding:16px 16px 0}

/* ══════ General buttons animation ══════ */
button, a{-webkit-tap-highlight-color:transparent}
button{cursor:pointer;font-family:inherit;border:none;background:none;color:inherit;transition:transform .15s var(--ease),box-shadow .2s,background .2s,opacity .2s}
button:hover{opacity:.95}
button:active{transform:scale(.96)}
a{text-decoration:none;color:inherit;transition:transform .15s var(--ease),opacity .15s}
a:hover{opacity:.9}
a:active{transform:scale(.97)}

/* ══════ Header ══════ */
.top-header{display:flex;align-items:center;justify-content:space-between;padding:14px 8px;margin-bottom:14px}
.header-left{display:flex;align-items:center;gap:10px}
.brand-title{font-size:20px;font-weight:800;letter-spacing:-.3px;display:flex;align-items:center;gap:6px}
.brand-sub{font-size:11px;color:var(--muted);font-weight:600}
.header-right{display:flex;align-items:center;gap:10px}

.bell-btn{width:44px;height:44px;border-radius:50%;position:relative;
  background:var(--card-bg);border:1px solid var(--card-border);display:grid;place-items:center;
  box-shadow:var(--card-shadow);transition:transform .2s var(--ease),box-shadow .25s}
.bell-btn:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(66,133,244,.3)}
.bell-btn:active{transform:scale(.9)}
.bell-btn svg{width:20px;height:20px;stroke:#c9d4ea;fill:none;stroke-width:1.8}
.bell-dot{position:absolute;top:8px;right:8px;width:8px;height:8px;border-radius:50%;background:#ff4d4f;box-shadow:0 0 8px rgba(255,77,79,.8);animation:pulseDot 2s ease-in-out infinite}
@keyframes pulseDot{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.3);opacity:.7}}

.user-avatar-btn{width:44px;height:44px;border-radius:50%;
  background:linear-gradient(145deg,#4285f4,#2563eb);display:grid;place-items:center;
  font-weight:800;color:#fff;font-size:16px;border:2px solid rgba(138,180,248,.5);
  box-shadow:0 4px 12px rgba(66,133,244,.4);transition:transform .2s var(--ease),box-shadow .25s}
.user-avatar-btn:hover{transform:scale(1.08);box-shadow:0 6px 20px rgba(66,133,244,.6)}
.user-avatar-btn:active{transform:scale(.95)}

/* ══════ Welcome ══════ */
.welcome-banner{position:relative;overflow:hidden;padding:22px;border-radius:22px;
  background:linear-gradient(135deg,#1a4ec9 0%,#2a5ddb 40%,#1e3a8a 100%);margin-bottom:18px;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.15),0 12px 32px rgba(30,64,175,.35);
  transition:transform .3s var(--ease)}
.welcome-banner:hover{transform:translateY(-2px)}
.welcome-banner::after{content:'G';position:absolute;top:50%;left:-10px;transform:translateY(-50%);
  font-family:Arial;font-size:140px;font-weight:800;
  background:conic-gradient(from 0deg,#4285f4,#ea4335,#fbbc05,#34a853,#4285f4);
  -webkit-background-clip:text;background-clip:text;color:transparent;opacity:.35;pointer-events:none}
.welcome-content{position:relative;z-index:2}
.welcome-title{font-size:18px;font-weight:800;margin-bottom:6px}
.welcome-sub{font-size:14px;color:rgba(255,255,255,.85);margin-bottom:14px}
.welcome-date{display:inline-flex;align-items:center;gap:6px;font-size:11px;color:rgba(255,255,255,.85);
  padding:6px 12px;background:rgba(0,0,0,.25);border-radius:10px;border:1px solid rgba(255,255,255,.15);font-weight:600}
.welcome-date svg{width:13px;height:13px;stroke:#fff;fill:none;stroke-width:2}

/* ══════ Stats Cards ══════ */
.stats-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:16px}
.stat-card{padding:16px;border-radius:20px;background:var(--card-bg);border:1px solid var(--card-border);
  box-shadow:var(--card-shadow);display:flex;align-items:center;justify-content:space-between;gap:10px;
  transition:transform .25s var(--ease),box-shadow .25s;contain:layout style}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(66,133,244,.2)}
.stat-info{flex:1;text-align:right}
.stat-label{font-size:11.5px;color:var(--muted);font-weight:600;margin-bottom:6px}
.stat-value{font-size:22px;font-weight:800;color:#fff;letter-spacing:-.5px}
.stat-icon-wrap{width:50px;height:50px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;transition:transform .3s var(--ease)}
.stat-card:hover .stat-icon-wrap{transform:scale(1.1) rotate(-5deg)}
.stat-icon-wrap.blue{background:radial-gradient(circle at 30% 30%,#4d9eff,#1a4ec9);box-shadow:0 0 24px rgba(66,133,244,.5)}
.stat-icon-wrap.green{background:radial-gradient(circle at 30% 30%,#4ade80,#16a34a);box-shadow:0 0 24px rgba(52,199,89,.5)}
.stat-icon-wrap.purple{background:radial-gradient(circle at 30% 30%,#c084fc,#7c3aed);box-shadow:0 0 24px rgba(155,114,203,.5)}
.stat-icon-wrap.orange{background:radial-gradient(circle at 30% 30%,#fbbf24,#d97706);box-shadow:0 0 24px rgba(251,191,36,.5)}
.stat-icon-wrap svg{width:24px;height:24px;fill:#fff}

/* ══════ Chart ══════ */
.chart-card{padding:18px;border-radius:22px;margin-bottom:16px;background:var(--card-bg);
  border:1px solid var(--card-border);box-shadow:var(--card-shadow);overflow:hidden;contain:layout style}
.chart-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px}
.chart-title{font-size:14px;font-weight:700}
.chart-canvas-wrap{position:relative;height:200px;width:100%;margin-top:8px;overflow:hidden}
.chart-canvas-wrap canvas{display:block !important;position:absolute;top:0;left:0;width:100% !important;height:100% !important}

/* ══════ Quick Access ══════ */
.quick-access{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-bottom:16px}
.quick-card{padding:16px 12px;border-radius:18px;background:var(--card-bg);border:1px solid var(--card-border);
  box-shadow:var(--card-shadow);display:flex;align-items:center;gap:12px;color:var(--ink);
  transition:transform .2s var(--ease),box-shadow .25s;cursor:pointer}
.quick-card:hover{transform:translateY(-3px) scale(1.01);box-shadow:0 12px 32px rgba(66,133,244,.25)}
.quick-card:active{transform:scale(.97)}
.quick-icon{width:44px;height:44px;border-radius:12px;display:grid;place-items:center;flex-shrink:0;transition:transform .3s var(--ease)}
.quick-card:hover .quick-icon{transform:scale(1.15) rotate(5deg)}
.quick-icon.purple{background:radial-gradient(circle at 30% 30%,#c084fc,#7c3aed)}
.quick-icon.blue{background:radial-gradient(circle at 30% 30%,#4d9eff,#1a4ec9)}
.quick-icon.green{background:radial-gradient(circle at 30% 30%,#4ade80,#16a34a)}
.quick-icon.orange{background:radial-gradient(circle at 30% 30%,#fbbf24,#d97706)}
.quick-icon svg{width:20px;height:20px;fill:#fff}
.quick-text{flex:1}
.quick-title{font-size:13px;font-weight:700;margin-bottom:2px}
.quick-sub{font-size:10.5px;color:var(--muted)}

/* ══════ System Status ══════ */
.system-status{padding:16px 18px;border-radius:20px;background:var(--card-bg);
  border:1px solid var(--card-border);box-shadow:var(--card-shadow);margin-bottom:16px;
  display:flex;align-items:center;gap:14px;transition:transform .2s}
.system-status:hover{transform:translateY(-2px)}
.status-icon{width:50px;height:50px;border-radius:50%;flex-shrink:0;
  background:radial-gradient(circle at 30% 30%,#4d9eff,#1a4ec9);display:grid;place-items:center;
  position:relative;box-shadow:0 0 24px rgba(66,133,244,.5)}
.status-icon::before{content:'';position:absolute;inset:-6px;border-radius:50%;
  border:2px solid rgba(66,133,244,.3);animation:statusPulse 2s ease-out infinite}
@keyframes statusPulse{0%{transform:scale(.9);opacity:1}100%{transform:scale(1.4);opacity:0}}
.status-icon svg{width:24px;height:24px;fill:#fff}
.status-info{flex:1;text-align:right}
.status-title{font-size:13px;font-weight:700;margin-bottom:4px;display:flex;align-items:center;gap:6px;justify-content:flex-end}
.status-check{width:18px;height:18px;border-radius:50%;background:#34c759;display:grid;place-items:center}
.status-check svg{width:10px;height:10px;fill:none;stroke:#fff;stroke-width:3;stroke-linecap:round}
.status-sub{font-size:11px;color:#4ade80;font-weight:600;display:flex;align-items:center;gap:4px;justify-content:flex-end}
.status-sub::before{content:'';width:6px;height:6px;border-radius:50%;background:#4ade80;box-shadow:0 0 6px #4ade80}

/* ══════ Bottom Nav ══════ */
.bottom-nav{position:fixed;bottom:0;left:0;right:0;z-index:50;max-width:900px;margin:0 auto;
  padding:8px 6px calc(8px + env(safe-area-inset-bottom));
  background:linear-gradient(180deg,rgba(10,15,26,.95),rgba(5,8,16,.99));
  border-top:1px solid rgba(100,130,200,.15);
  box-shadow:0 -8px 24px rgba(0,0,0,.6)}
.nav-items{display:flex;align-items:center;justify-content:space-around;gap:2px}
.nav-item{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;
  padding:6px 2px;border-radius:12px;font-size:9.5px;font-weight:700;
  color:var(--muted);position:relative;overflow:hidden}
.nav-item svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;
  transition:transform .3s var(--ease)}
.nav-item:hover{color:#8ab4f8}
.nav-item:hover svg{transform:scale(1.15)}
.nav-item.active{color:#8ab4f8;background:linear-gradient(145deg,rgba(66,133,244,.2),rgba(30,60,120,.3))}
.nav-item.active svg{transform:scale(1.1)}
.nav-item:active{transform:scale(.95)}

/* ══════ Pages ══════ */
.page{display:none}
.page.active{display:block;animation:pageFade .25s var(--ease)}
@keyframes pageFade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}

/* ══════ Search ══════ */
.search-wrap{margin-bottom:14px;position:relative}
.search-input{width:100%;height:50px;padding:0 46px 0 16px;
  background:var(--card-bg);border:1px solid var(--card-border);border-radius:14px;
  color:var(--ink);font-size:14px;font-family:inherit;outline:none;
  box-shadow:var(--card-shadow);transition:border-color .2s,box-shadow .2s}
.search-input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(138,180,248,.15)}
.search-icon{position:absolute;right:14px;top:50%;transform:translateY(-50%);width:20px;height:20px;color:var(--muted);pointer-events:none}
.search-icon svg{width:100%;height:100%;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round}

/* ══════ Users ══════ */
.users-card{background:var(--card-bg);border:1px solid var(--card-border);border-radius:20px;
  overflow:hidden;box-shadow:var(--card-shadow);margin-bottom:16px}
.users-header{padding:14px 18px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;justify-content:space-between}
.users-title{font-size:14px;font-weight:700}
.users-count{font-size:11px;color:var(--muted);font-weight:600}
.users-list{max-height:600px;overflow-y:auto}
.user-row{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.04);
  display:flex;align-items:center;gap:10px;transition:background .15s}
.user-row:hover{background:rgba(255,255,255,.03)}
.user-avatar{width:40px;height:40px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(145deg,#8fa3b8,#4a6fa5 50%,#2b3138);display:grid;place-items:center;
  color:#fff;font-weight:800;font-size:15px;transition:transform .25s}
.user-row:hover .user-avatar{transform:scale(1.1)}
.user-info{flex:1;min-width:0}
.user-name{font-size:13px;font-weight:700;margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.user-phone{font-size:11px;color:var(--muted);direction:ltr;text-align:right}
.user-date{font-size:10px;color:var(--muted);margin-top:2px}
.user-badges{display:flex;gap:3px;margin-top:3px;flex-wrap:wrap}
.badge-tag{padding:1px 6px;border-radius:6px;font-size:9px;font-weight:700}
.badge-admin{background:rgba(251,191,36,.2);color:#fbbf24;border:1px solid rgba(251,191,36,.4)}
.badge-active{background:rgba(52,199,89,.2);color:#34c759;border:1px solid rgba(52,199,89,.4)}
.badge-inactive{background:rgba(239,68,68,.2);color:#f87171;border:1px solid rgba(239,68,68,.4)}

.user-actions{display:flex;gap:3px;flex-shrink:0}
.btn-action{width:30px;height:30px;border-radius:8px;display:grid;place-items:center;
  background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:var(--ink-soft);
  transition:transform .15s var(--ease),background .2s,color .2s}
.btn-action:hover{transform:scale(1.15);background:rgba(255,255,255,.15)}
.btn-action:active{transform:scale(.9)}
.btn-action svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.btn-action.danger{color:#f87171;background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.25)}
.btn-action.danger:hover{background:rgba(239,68,68,.25);box-shadow:0 0 12px rgba(239,68,68,.4)}
.btn-action.success{color:#4ade80;background:rgba(52,199,89,.12);border-color:rgba(52,199,89,.25)}
.btn-action.success:hover{background:rgba(52,199,89,.25);box-shadow:0 0 12px rgba(52,199,89,.4)}
.btn-action.warning{color:#fbbf24;background:rgba(251,191,36,.12);border-color:rgba(251,191,36,.25)}
.btn-action.warning:hover{background:rgba(251,191,36,.25);box-shadow:0 0 12px rgba(251,191,36,.4)}
.empty{padding:36px 20px;text-align:center;color:var(--muted);font-size:12px}

/* ══════ Slides ══════ */
.slides-grid{display:grid;gap:12px;margin-bottom:16px}
.slide-item{background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;
  overflow:hidden;box-shadow:var(--card-shadow);transition:transform .25s var(--ease),box-shadow .25s}
.slide-item:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(66,133,244,.2)}
.slide-img{width:100%;height:140px;object-fit:cover;display:block;background:#1a2030;transition:transform .5s var(--ease)}
.slide-item:hover .slide-img{transform:scale(1.05)}
.slide-body{padding:12px 14px;position:relative}
.slide-title{font-size:14px;font-weight:700;margin-bottom:3px}
.slide-sub{font-size:11px;color:var(--muted);margin-bottom:4px}
.slide-link{font-size:10px;color:var(--blue);direction:ltr;text-align:left;word-break:break-all}
.slide-actions{display:flex;gap:6px;margin-top:8px;justify-content:flex-end}

.btn-sm{padding:5px 12px;border-radius:8px;font-size:11px;font-weight:700;
  display:inline-flex;align-items:center;gap:4px;transition:transform .15s var(--ease),box-shadow .2s}
.btn-sm:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.3)}
.btn-sm:active{transform:scale(.95)}
.btn-sm.edit{background:linear-gradient(145deg,#4285f4,#2563eb);color:#fff}
.btn-sm.delete{background:linear-gradient(145deg,#ef4444,#b91c1c);color:#fff}
.btn-sm svg{width:12px;height:12px;fill:none;stroke:currentColor;stroke-width:2}

.btn-main{width:100%;padding:14px;border-radius:14px;font-weight:700;
  background:linear-gradient(145deg,#4285f4,#2563eb);color:#fff;
  font-size:14px;display:flex;align-items:center;justify-content:center;gap:6px;
  box-shadow:0 4px 12px rgba(37,99,235,.4);margin-bottom:14px;
  transition:transform .2s var(--ease),box-shadow .25s;position:relative;overflow:hidden}
.btn-main:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(37,99,235,.5)}
.btn-main:active{transform:translateY(1px)}
.btn-main svg{width:16px;height:16px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round}

/* ══════ Period Buttons ══════ */
.period-btns{display:flex;gap:8px;margin-bottom:14px}
.period-btn{flex:1;padding:10px 16px;border-radius:12px;font-weight:700;font-size:12px;
  background:var(--card-bg);border:1px solid var(--card-border);color:var(--ink);
  transition:transform .15s var(--ease),box-shadow .2s,background .2s}
.period-btn:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(66,133,244,.25)}
.period-btn:active{transform:scale(.96)}
.period-btn.active{background:linear-gradient(145deg,#4285f4,#2563eb);color:#fff;border-color:#4285f4;
  box-shadow:0 4px 12px rgba(37,99,235,.4)}

/* ══════ Settings ══════ */
.settings-section{background:var(--card-bg);border:1px solid var(--card-border);border-radius:20px;
  padding:18px;margin-bottom:14px;box-shadow:var(--card-shadow);contain:layout style}
.settings-title{font-size:15px;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.color-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;
  border-bottom:1px solid rgba(255,255,255,.05)}
.color-row:last-child{border-bottom:none}
.color-row label{font-size:12.5px;font-weight:600}
.color-input-wrap{display:flex;align-items:center;gap:8px}
.color-input{width:44px;height:32px;border-radius:8px;border:2px solid rgba(255,255,255,.2);
  cursor:pointer;background:transparent;padding:0;transition:transform .2s}
.color-input:hover{transform:scale(1.1)}
.color-input::-webkit-color-swatch-wrapper{padding:0}
.color-input::-webkit-color-swatch{border-radius:6px;border:none}
.color-value{font-size:11px;color:var(--muted);font-family:monospace;direction:ltr}

/* ══════ Modal ══════ */
.modal{position:fixed;inset:0;z-index:100;display:none;align-items:center;justify-content:center;padding:20px;
  background:rgba(3,6,14,.85);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px)}
.modal.show{display:flex;animation:fadeIn .2s}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.modal-card{width:100%;max-width:440px;max-height:90vh;overflow-y:auto;
  background:linear-gradient(180deg,rgba(30,38,52,.98),rgba(13,17,23,.98));
  border:1px solid rgba(212,221,230,.2);border-radius:24px;padding:24px 20px;
  box-shadow:0 25px 60px rgba(0,0,0,.9);animation:slideUp .3s var(--ease)}
@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
.modal-title{font-size:17px;font-weight:800;margin-bottom:14px}
.modal-field{margin-bottom:12px}
.modal-field label{display:block;font-size:12px;font-weight:600;color:#c9d4ea;margin-bottom:6px}
.modal-field input,.modal-field textarea{width:100%;min-height:44px;padding:10px 14px;
  border-radius:12px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.15);
  color:#eef2ff;font-size:14px;font-family:inherit;outline:none;resize:vertical;transition:border-color .2s}
.modal-field input:focus,.modal-field textarea:focus{border-color:var(--blue)}
.modal-field textarea{min-height:70px}
.modal-field input[type=file]{padding:8px;font-size:12px}
.img-preview{width:100%;height:140px;object-fit:cover;border-radius:12px;margin-top:8px;background:#1a2030}
.modal-actions{display:flex;gap:10px;margin-top:16px}
.modal-btn{flex:1;height:44px;border-radius:12px;font-weight:700;font-size:13px;
  transition:transform .15s var(--ease),box-shadow .2s}
.modal-btn:hover{transform:translateY(-2px)}
.modal-btn:active{transform:translateY(1px)}
.modal-btn-cancel{background:rgba(255,255,255,.08);color:#eef2ff;border:1px solid rgba(255,255,255,.15)}
.modal-btn-save{background:linear-gradient(145deg,#4285f4,#2563eb);color:#fff}
.modal-btn-delete{background:linear-gradient(145deg,#ef4444,#b91c1c);color:#fff}

/* ══════ Toast ══════ */
.toast{position:fixed;top:20px;left:50%;transform:translate(-50%,-20px);opacity:0;pointer-events:none;
  padding:12px 20px;border-radius:14px;font-size:13px;font-weight:600;white-space:nowrap;
  background:linear-gradient(145deg,#eef2ff,#c9d4ea);color:#0b1020;
  box-shadow:0 4px 0 rgba(0,0,0,.35),0 12px 30px rgba(0,0,0,.55);
  transition:opacity .25s,transform .3s var(--ease);z-index:1000}
.toast.show{opacity:1;transform:translate(-50%,0)}
.toast.error{background:linear-gradient(145deg,#fee2e2,#fca5a5);color:#7f1d1d}

/* ══════ Loading overlay for tabs ══════ */
.tab-loading{display:none;text-align:center;padding:40px 20px;color:var(--muted);font-size:13px}
.tab-loading.show{display:block}
.tab-loading::before{content:'';display:inline-block;width:20px;height:20px;margin-left:8px;
  border:2px solid rgba(138,180,248,.2);border-top-color:#8ab4f8;border-radius:50%;
  animation:spin .8s linear infinite;vertical-align:middle}
@keyframes spin{to{transform:rotate(360deg)}}

@media(max-width:400px){
  .container{padding:12px 12px 0}
  .brand-title{font-size:17px}
  .stat-value{font-size:19px}
  .stat-icon-wrap{width:44px;height:44px}
  .stat-icon-wrap svg{width:20px;height:20px}
}
@media(prefers-reduced-motion:reduce){
  *,*::before,*::after{animation-duration:.01ms!important;transition-duration:.01ms!important}
}
</style>
</head>
<body>

<div class="container">

  <div class="top-header">
    <div class="header-left">
      <div>
        <div class="brand-title">
          <svg viewBox="0 0 48 48" style="width:22px;height:22px"><path fill="#4285f4" d="M44 24c0-1.4-.1-2.7-.4-4H24v8h11.3c-.5 2.6-2 4.8-4.3 6.3v5.2h6.9C41.7 36 44 30.6 44 24z"/><path fill="#34a853" d="M24 44c5.4 0 9.9-1.8 13.2-4.8l-6.9-5.2c-1.9 1.3-4.4 2-6.3 2-4.9 0-9-3.3-10.5-7.7H6.4v5.4C9.7 40 16.4 44 24 44z"/><path fill="#fbbc05" d="M13.5 28.3c-.4-1.1-.6-2.3-.6-3.5s.2-2.4.6-3.5v-5.4H6.4C5.1 18.3 4.4 21 4.4 24s.7 5.7 2 8.1l7.1-5.4z"/><path fill="#ea4335" d="M24 12.2c3 0 5.6 1 7.7 3l5.8-5.8C33.9 6.1 29.4 4 24 4 16.4 4 9.7 8 6.4 14.4l7.1 5.4c1.5-4.4 5.6-7.6 10.5-7.6z"/></svg>
          Google Admin
        </div>
        <div class="brand-sub">Control Center</div>
      </div>
    </div>
    <div class="header-right">
      <button class="bell-btn" onclick="showToast('اعلان جدیدی ندارید')" title="اعلان‌ها">
        <svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 1 0-12 0c0 6-3 7-3 7h18s-3-1-3-7"/><path d="M10.3 20a2 2 0 0 0 3.4 0"/></svg>
        <span class="bell-dot"></span>
      </button>
      <button class="user-avatar-btn" title="پروفایل"><?= strtoupper(mb_substr($adminName, 0, 1)) ?></button>
    </div>
  </div>

  <!-- ══════ خانه ══════ -->
  <div class="page active" id="pageHome">
    <div class="welcome-banner">
      <div class="welcome-content">
        <div class="welcome-title">👋 سلام، <?= htmlspecialchars($adminName) ?> عزیز</div>
        <div class="welcome-sub">به پنل مدیریت گوگل خوش آمدید</div>
        <div class="welcome-date">
          <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <span id="todayDate">—</span>
        </div>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-info">
          <div class="stat-label">کل کاربران</div>
          <div class="stat-value" id="statTotal">—</div>
        </div>
        <div class="stat-icon-wrap blue">
          <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-info">
          <div class="stat-label">کاربران فعال</div>
          <div class="stat-value" id="statActive">—</div>
        </div>
        <div class="stat-icon-wrap green">
          <svg viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-info">
          <div class="stat-label">ادمین‌ها</div>
          <div class="stat-value" id="statAdmins">—</div>
        </div>
        <div class="stat-icon-wrap purple">
          <svg viewBox="0 0 24 24"><path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/></svg>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-info">
          <div class="stat-label">ثبت‌نام امروز</div>
          <div class="stat-value" id="statToday">—</div>
        </div>
        <div class="stat-icon-wrap orange">
          <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        </div>
      </div>
    </div>

    <div class="chart-card">
      <div class="chart-header">
        <div class="chart-title">📈 رشد کاربران (۷ روز اخیر)</div>
      </div>
      <div class="chart-canvas-wrap"><canvas id="growthChart"></canvas></div>
    </div>

    <div class="quick-access">
      <a class="quick-card" onclick="switchPage('users')">
        <div class="quick-icon purple"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <div class="quick-text"><div class="quick-title">مدیریت کاربران</div><div class="quick-sub">ویرایش، حذف، ادمین</div></div>
      </a>
      <a class="quick-card" onclick="switchPage('slides')">
        <div class="quick-icon blue"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>
        <div class="quick-text"><div class="quick-title">مدیریت اسلاید</div><div class="quick-sub">عکس، تیتر، لینک</div></div>
      </a>
      <a class="quick-card" onclick="switchPage('settings')">
        <div class="quick-icon green"><svg viewBox="0 0 24 24"><path d="M12 2v20M2 12h20"/><circle cx="12" cy="12" r="9"/></svg></div>
        <div class="quick-text"><div class="quick-title">رنگ‌بندی سایت</div><div class="quick-sub">حالت شب و روز</div></div>
      </a>
      <a class="quick-card" onclick="switchPage('stats')">
        <div class="quick-icon orange"><svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
        <div class="quick-text"><div class="quick-title">گزارش و آمار</div><div class="quick-sub">تحلیل داده‌ها</div></div>
      </a>
    </div>

    <div class="system-status">
      <div class="status-icon"><svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
      <div class="status-info">
        <div class="status-title">وضعیت سیستم
          <div class="status-check"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
        </div>
        <div class="status-sub">سایت در حال اجرا است</div>
      </div>
    </div>
  </div>

  <!-- ══════ کاربران ══════ -->
  <div class="page" id="pageUsers">
    <div class="search-wrap">
      <input type="search" class="search-input" id="searchInput" placeholder="جستجو...">
      <span class="search-icon"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg></span>
    </div>
    <div class="users-card">
      <div class="users-header">
        <div class="users-title">👥 لیست کاربران</div>
        <div class="users-count" id="usersCount">...</div>
      </div>
      <div class="users-list" id="usersList"><div class="empty">در حال بارگذاری...</div></div>
    </div>
  </div>

  <!-- ══════ اسلایدها ══════ -->
  <div class="page" id="pageSlides">
    <button class="btn-main" onclick="openSlideModal()">
      <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      افزودن اسلاید جدید
    </button>
    <div class="slides-grid" id="slidesGrid"><div class="empty">در حال بارگذاری...</div></div>
  </div>

  <!-- ══════ آمار ══════ -->
  <div class="page" id="pageStats">
    <div class="period-btns">
      <button class="period-btn active" data-days="7" onclick="loadStats(7,this)">۷ روز</button>
      <button class="period-btn" data-days="30" onclick="loadStats(30,this)">۳۰ روز</button>
      <button class="period-btn" data-days="90" onclick="loadStats(90,this)">۹۰ روز</button>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-info">
          <div class="stat-label">در این بازه</div>
          <div class="stat-value" id="stRange">—</div>
        </div>
        <div class="stat-icon-wrap blue">
          <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-info">
          <div class="stat-label">این ماه</div>
          <div class="stat-value" id="stMonth">—</div>
        </div>
        <div class="stat-icon-wrap purple">
          <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" fill="none" stroke="#fff" stroke-width="2"/><line x1="16" y1="2" x2="16" y2="6" stroke="#fff" stroke-width="2"/><line x1="8" y1="2" x2="8" y2="6" stroke="#fff" stroke-width="2"/></svg>
        </div>
      </div>
    </div>

    <div class="chart-card">
      <div class="chart-header">
        <div class="chart-title">📊 ثبت‌نام‌های روزانه</div>
      </div>
      <div class="chart-canvas-wrap"><canvas id="statsChart"></canvas></div>
    </div>

    <div class="chart-card">
      <div class="chart-header">
        <div class="chart-title">🥧 توزیع کاربران</div>
      </div>
      <div class="chart-canvas-wrap" style="height:240px"><canvas id="pieChart"></canvas></div>
    </div>
  </div>

  <!-- ══════ تنظیمات ══════ -->
  <div class="page" id="pageSettings">
    <div class="settings-section">
      <div class="settings-title">🌙 رنگ‌بندی حالت شب</div>
      <div class="color-row">
        <label>رنگ اصلی</label>
        <div class="color-input-wrap">
          <input type="color" class="color-input" id="darkPrimary" value="#8ab4f8">
          <span class="color-value" id="darkPrimaryVal">#8ab4f8</span>
        </div>
      </div>
      <div class="color-row">
        <label>رنگ تاکید</label>
        <div class="color-input-wrap">
          <input type="color" class="color-input" id="darkAccent" value="#4285f4">
          <span class="color-value" id="darkAccentVal">#4285f4</span>
        </div>
      </div>
      <div class="color-row">
        <label>پس‌زمینه</label>
        <div class="color-input-wrap">
          <input type="color" class="color-input" id="darkBg" value="#04060c">
          <span class="color-value" id="darkBgVal">#04060c</span>
        </div>
      </div>
    </div>

    <div class="settings-section">
      <div class="settings-title">☀️ رنگ‌بندی حالت روز</div>
      <div class="color-row">
        <label>رنگ اصلی</label>
        <div class="color-input-wrap">
          <input type="color" class="color-input" id="lightPrimary" value="#2563eb">
          <span class="color-value" id="lightPrimaryVal">#2563eb</span>
        </div>
      </div>
      <div class="color-row">
        <label>رنگ تاکید</label>
        <div class="color-input-wrap">
          <input type="color" class="color-input" id="lightAccent" value="#4285f4">
          <span class="color-value" id="lightAccentVal">#4285f4</span>
        </div>
      </div>
      <div class="color-row">
        <label>پس‌زمینه</label>
        <div class="color-input-wrap">
          <input type="color" class="color-input" id="lightBg" value="#eef2fb">
          <span class="color-value" id="lightBgVal">#eef2fb</span>
        </div>
      </div>
    </div>

    <button class="btn-main" onclick="saveSettings()" style="margin-bottom:14px">
      <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      ذخیره تنظیمات
    </button>

    <div class="settings-section">
      <div class="settings-title">⚙️ اطلاعات</div>
      <div class="color-row"><label>نسخه</label><span style="color:var(--muted);font-size:12px">Google Admin v2.0</span></div>
      <div class="color-row"><label>دیتابیس</label><span style="color:#4ade80;font-size:12px">Supabase ✓</span></div>
    </div>

    <a href="?logout=1" onclick="return confirm('خروج از پنل؟')" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:14px;border-radius:14px;background:linear-gradient(145deg,#ef4444,#b91c1c);color:#fff;font-weight:700;font-size:14px;box-shadow:0 4px 12px rgba(239,68,68,.4);transition:transform .2s,box-shadow .2s">
      <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      خروج از حساب
    </a>
  </div>

</div>

<nav class="bottom-nav">
  <div class="nav-items">
    <button class="nav-item active" data-page="home" onclick="switchPage('home')">
      <svg viewBox="0 0 24 24"><path d="M4 11.2 12 4.5l8 6.7V19a1.5 1.5 0 0 1-1.5 1.5h-4V15h-5v5.5h-4A1.5 1.5 0 0 1 4 19z"/></svg>
      <span>خانه</span>
    </button>
    <button class="nav-item" data-page="users" onclick="switchPage('users')">
      <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      <span>کاربران</span>
    </button>
    <button class="nav-item" data-page="slides" onclick="switchPage('slides')">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      <span>اسلایدها</span>
    </button>
    <button class="nav-item" data-page="stats" onclick="switchPage('stats')">
      <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      <span>آمار</span>
    </button>
    <button class="nav-item" data-page="settings" onclick="switchPage('settings')">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-1.8-.3 1.6 1.6 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.6 1.6 0 0 0-1-1.5 1.6 1.6 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.6 1.6 0 0 0 .3-1.8 1.6 1.6 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.6 1.6 0 0 0 1.5-1 1.6 1.6 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.6 1.6 0 0 0 1.8.3H9a1.6 1.6 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.6 1.6 0 0 0 1 1.5 1.6 1.6 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0-.3 1.8V9a1.6 1.6 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.6 1.6 0 0 0-1.5 1z"/></svg>
      <span>تنظیمات</span>
    </button>
  </div>
</nav>

<!-- Modals -->
<div class="modal" id="slideModal">
  <div class="modal-card">
    <h3 class="modal-title" id="slideModalTitle">افزودن اسلاید</h3>
    <input type="hidden" id="slideId">
    <div class="modal-field"><label>عنوان *</label><input type="text" id="slideTitle" placeholder="عنوان اسلاید"></div>
    <div class="modal-field"><label>زیرنویس</label><input type="text" id="slideSub" placeholder="توضیح کوتاه"></div>
    <div class="modal-field"><label>لینک</label><input type="url" id="slideLink" placeholder="https://..." dir="ltr"></div>
    <div class="modal-field"><label>ترتیب</label><input type="number" id="slideOrder" value="0"></div>
    <div class="modal-field"><label>تصویر از گالری</label><input type="file" id="slideImage" accept="image/*"><img id="slidePreview" class="img-preview" style="display:none"></div>
    <div class="modal-field"><label>یا URL تصویر</label><input type="url" id="slideUrl" placeholder="https://.../image.jpg" dir="ltr"></div>
    <div class="modal-field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" id="slideActive" checked style="width:auto;min-height:auto">فعال باشد</label></div>
    <div class="modal-actions">
      <button class="modal-btn modal-btn-cancel" onclick="closeSlideModal()">انصراف</button>
      <button class="modal-btn modal-btn-save" onclick="saveSlide()">ذخیره</button>
    </div>
  </div>
</div>

<div class="modal" id="editUserModal">
  <div class="modal-card">
    <h3 class="modal-title">✏️ ویرایش کاربر</h3>
    <input type="hidden" id="editUserId">
    <div class="modal-field"><label>نام</label><input type="text" id="editName"></div>
    <div class="modal-field"><label>شماره</label><input type="tel" id="editPhone" maxlength="11" dir="ltr"></div>
    <div class="modal-field"><label>رمز جدید</label><input type="password" id="editPassword" placeholder="اختیاری"></div>
    <div class="modal-actions">
      <button class="modal-btn modal-btn-cancel" onclick="document.getElementById('editUserModal').classList.remove('show')">انصراف</button>
      <button class="modal-btn modal-btn-save" onclick="saveUser()">ذخیره</button>
    </div>
  </div>
</div>

<div class="modal" id="deleteUserModal">
  <div class="modal-card">
    <h3 class="modal-title">🗑️ حذف کاربر</h3>
    <p style="font-size:13px;color:var(--muted);margin-bottom:14px">آیا از حذف <strong id="deleteName"></strong> مطمئنید؟</p>
    <input type="hidden" id="deleteUserId">
    <div class="modal-actions">
      <button class="modal-btn modal-btn-cancel" onclick="document.getElementById('deleteUserModal').classList.remove('show')">انصراف</button>
      <button class="modal-btn modal-btn-delete" onclick="confirmDeleteUser()">حذف</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
const $ = id => document.getElementById(id);
let allUsers = [];
let allSlides = [];
let currentStatsDays = 7;
let usersLoaded = false;
let slidesLoaded = false;
let settingsLoaded = false;

$('todayDate').textContent = new Intl.DateTimeFormat('fa-IR',{weekday:'long',day:'numeric',month:'long',year:'numeric'}).format(new Date());

function showToast(msg, isErr=false){
  const t = $('toast'); t.textContent = msg;
  t.className = 'toast show' + (isErr?' error':'');
  clearTimeout(showToast.t);
  showToast.t = setTimeout(()=>t.classList.remove('show'),3000);
}
function esc(s){ const d=document.createElement('div'); d.textContent=s??''; return d.innerHTML; }
function faDate(iso){
  if (!iso) return '—';
  try { return new Intl.DateTimeFormat('fa-IR',{year:'numeric',month:'short',day:'numeric'}).format(new Date(iso)); }
  catch(e) { return String(iso).slice(0,10); }
}

// ══════ Navigation ══════
function switchPage(p){
  document.querySelectorAll('.page').forEach(x=>x.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(x=>x.classList.remove('active'));
  const page = $('page'+p.charAt(0).toUpperCase()+p.slice(1));
  if (page) page.classList.add('active');
  const nav = document.querySelector(`.nav-item[data-page="${p}"]`);
  if (nav) nav.classList.add('active');
  window.scrollTo({top:0,behavior:'smooth'});
  
  // ⭐ لود تنبل (lazy loading)
  if (p === 'users' && !usersLoaded) { loadUsers(); usersLoaded = true; }
  if (p === 'slides' && !slidesLoaded) { loadSlides(); slidesLoaded = true; }
  if (p === 'stats') setTimeout(() => loadStats(currentStatsDays), 200);
  if (p === 'settings' && !settingsLoaded) { loadSettings(); settingsLoaded = true; }
}

// ══════ Charts ══════
function drawLineChart(canvasId, data, labels){
  const c = $(canvasId);
  if (!c) return;
  const keys = Object.keys(data || {});
  if (!keys.length) return;
  
  const parent = c.parentElement;
  const rect = parent.getBoundingClientRect();
  if (rect.width < 50 || rect.height < 50) {
    setTimeout(() => drawLineChart(canvasId, data, labels), 100);
    return;
  }
  
  const dpr = window.devicePixelRatio || 1;
  const W = rect.width, H = rect.height;
  c.width = W * dpr; c.height = H * dpr;
  c.style.width = W + 'px'; c.style.height = H + 'px';
  
  const ctx = c.getContext('2d');
  ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  ctx.clearRect(0, 0, W, H);
  
  const padTop = 20, padBot = 30, padL = 12, padR = 12;
  const vals = keys.map(k => Number(data[k]) || 0);
  const maxV = Math.max(...vals, 5);
  const rangeH = H - padTop - padBot;
  
  ctx.strokeStyle = 'rgba(255,255,255,.06)';
  ctx.lineWidth = 1;
  for (let i = 0; i <= 4; i++) {
    const y = padTop + rangeH * (i / 4);
    ctx.beginPath(); ctx.moveTo(padL, y); ctx.lineTo(W - padR, y); ctx.stroke();
  }
  
  const pts = vals.map((v, i) => ({
    x: padL + (i / Math.max(vals.length - 1, 1)) * (W - padL - padR),
    y: H - padBot - (v / maxV) * rangeH
  }));
  
  const grad = ctx.createLinearGradient(0, padTop, 0, H - padBot);
  grad.addColorStop(0, 'rgba(77,158,255,.45)');
  grad.addColorStop(1, 'rgba(77,158,255,0)');
  ctx.beginPath();
  ctx.moveTo(pts[0].x, H - padBot);
  pts.forEach(p => ctx.lineTo(p.x, p.y));
  ctx.lineTo(pts[pts.length - 1].x, H - padBot);
  ctx.closePath();
  ctx.fillStyle = grad; ctx.fill();
  
  ctx.beginPath();
  pts.forEach((p, i) => i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y));
  ctx.strokeStyle = '#4d9eff';
  ctx.lineWidth = 2.5;
  ctx.lineJoin = 'round'; ctx.lineCap = 'round';
  ctx.shadowColor = 'rgba(77,158,255,.7)';
  ctx.shadowBlur = 10;
  ctx.stroke(); ctx.shadowBlur = 0;
  
  pts.forEach((p, i) => {
    const isLast = i === pts.length - 1;
    ctx.beginPath();
    ctx.arc(p.x, p.y, isLast ? 5 : 3.5, 0, Math.PI * 2);
    ctx.fillStyle = isLast ? '#fff' : '#4d9eff'; ctx.fill();
    if (isLast) { ctx.strokeStyle = '#4d9eff'; ctx.lineWidth = 3; ctx.stroke(); }
  });
  
  ctx.fillStyle = 'rgba(200,210,230,.65)';
  ctx.font = '10px Vazirmatn, sans-serif';
  ctx.textAlign = 'center'; ctx.textBaseline = 'top';
  const step = Math.max(1, Math.ceil(labels.length / 7));
  labels.forEach((l, i) => {
    if (i % step === 0 || i === labels.length - 1) ctx.fillText(l, pts[i].x, H - padBot + 8);
  });
}

function drawPieChart(canvasId, data){
  const c = $(canvasId);
  if (!c) return;
  const parent = c.parentElement;
  const rect = parent.getBoundingClientRect();
  if (rect.width < 50 || rect.height < 50) {
    setTimeout(() => drawPieChart(canvasId, data), 100);
    return;
  }
  
  const dpr = window.devicePixelRatio || 1;
  const W = rect.width, H = rect.height;
  c.width = W * dpr; c.height = H * dpr;
  c.style.width = W + 'px'; c.style.height = H + 'px';
  
  const ctx = c.getContext('2d');
  ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  ctx.clearRect(0, 0, W, H);
  
  const cx = W / 2, cy = H / 2 - 10;
  const R = Math.min(W, H) / 2 - 30;
  
  const entries = Object.entries(data);
  const total = entries.reduce((a, [,v]) => a + Number(v || 0), 0);
  
  if (total === 0) {
    ctx.beginPath(); ctx.arc(cx, cy, R, 0, Math.PI * 2);
    ctx.fillStyle = 'rgba(255,255,255,.06)'; ctx.fill();
    ctx.strokeStyle = 'rgba(255,255,255,.1)'; ctx.lineWidth = 2; ctx.stroke();
    ctx.fillStyle = 'rgba(200,210,230,.7)';
    ctx.font = 'bold 24px Vazirmatn'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    ctx.fillText('۰', cx, cy - 4);
    ctx.font = '11px Vazirmatn'; ctx.fillText('کاربر', cx, cy + 16);
    return;
  }
  
  const colors = ['#34c759', '#ef4444', '#fbbf24'];
  let start = -Math.PI / 2;
  
  entries.forEach(([k, v], i) => {
    const val = Number(v) || 0;
    if (val === 0) return;
    const angle = (val / total) * Math.PI * 2;
    ctx.beginPath(); ctx.moveTo(cx, cy);
    ctx.arc(cx, cy, R, start, start + angle);
    ctx.closePath();
    ctx.fillStyle = colors[i] || '#4285f4'; ctx.fill();
    ctx.strokeStyle = '#0a0f1a'; ctx.lineWidth = 3; ctx.stroke();
    start += angle;
  });
  
  ctx.beginPath(); ctx.arc(cx, cy, R * 0.55, 0, Math.PI * 2);
  ctx.fillStyle = '#0a0f1a'; ctx.fill();
  
  ctx.fillStyle = '#fff';
  ctx.font = 'bold 24px Vazirmatn'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
  ctx.fillText(total.toLocaleString('fa-IR'), cx, cy - 4);
  ctx.font = '11px Vazirmatn';
  ctx.fillStyle = 'rgba(200,210,230,.6)';
  ctx.fillText('کل کاربران', cx, cy + 18);
  
  const legendY = H - 20;
  const itemW = Math.min(140, W / entries.length);
  let lx = (W - itemW * entries.length) / 2;
  
  entries.forEach(([k, v], i) => {
    ctx.fillStyle = colors[i] || '#4285f4';
    ctx.beginPath(); ctx.arc(lx + 5, legendY - 4, 5, 0, Math.PI * 2); ctx.fill();
    ctx.fillStyle = 'rgba(200,210,230,.85)';
    ctx.font = '11px Vazirmatn'; ctx.textAlign = 'left'; ctx.textBaseline = 'middle';
    ctx.fillText(`${k}: ${Number(v).toLocaleString('fa-IR')}`, lx + 15, legendY - 4);
    lx += itemW;
  });
}

// ══════ Stats ══════
async function loadStatsData(days=7){
  try {
    const res = await fetch(`admin_api.php?action=stats&days=${days}`, { credentials: 'include' });
    const text = await res.text();
    return JSON.parse(text);
  } catch(e) {
    return { success: false, message: 'خطای شبکه' };
  }
}

async function loadDashboard(){
  try {
    const d = await loadStatsData(7);
    if (!d.success) return;
    
    $('statTotal').textContent = (d.total || 0).toLocaleString('fa-IR');
    $('statActive').textContent = (d.active || 0).toLocaleString('fa-IR');
    $('statAdmins').textContent = (d.admins || 0).toLocaleString('fa-IR');
    $('statToday').textContent = (d.today || 0).toLocaleString('fa-IR');
    
    const daily = d.daily || {};
    const labels = Object.keys(daily).map(k => {
      try { return new Intl.DateTimeFormat('fa-IR', { weekday: 'short' }).format(new Date(k)); }
      catch(e) { return ''; }
    });
    
    setTimeout(() => drawLineChart('growthChart', daily, labels), 150);
  } catch(e) {}
}

async function loadStats(days=7, btn){
  currentStatsDays = days;
  document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  else document.querySelector(`.period-btn[data-days="${days}"]`)?.classList.add('active');
  
  try {
    const d = await loadStatsData(days);
    if (!d.success) {
      showToast(d.message || 'خطا', true);
      return;
    }
    
    const daily = d.daily || {};
    const rangeSum = Object.values(daily).reduce((a, b) => a + Number(b || 0), 0);
    $('stRange').textContent = rangeSum.toLocaleString('fa-IR');
    $('stMonth').textContent = (d.month || 0).toLocaleString('fa-IR');
    
    const labels = Object.keys(daily).map(k => {
      try { return new Intl.DateTimeFormat('fa-IR', { month: 'numeric', day: 'numeric' }).format(new Date(k)); }
      catch(e) { return k.slice(5); }
    });
    
    setTimeout(() => {
      drawLineChart('statsChart', daily, labels);
      drawPieChart('pieChart', {
        'فعال': d.active || 0,
        'غیرفعال': d.inactive || 0,
        'ادمین': d.admins || 0
      });
    }, 150);
  } catch(e) {
    showToast('خطای شبکه', true);
  }
}

// ══════ Users ══════
async function loadUsers(){
  try {
    const res = await fetch('admin_api.php?action=list_users', { credentials: 'include' });
    const d = await res.json();
    if (d.success) { allUsers = d.users; renderUsers(allUsers); }
    else if (d.message === 'not_admin') location.href = 'admin.php?logout=1';
  } catch(e) { showToast('خطای شبکه', true); }
}

function renderUsers(users){
  const list = $('usersList');
  $('usersCount').textContent = users.length + ' نفر';
  if (!users.length){ list.innerHTML='<div class="empty">کاربری یافت نشد</div>'; return; }
  list.innerHTML = users.map(u=>{
    const L = (u.name||'?').trim().charAt(0).toUpperCase();
    const isAdmin = u.is_admin===true;
    const isActive = u.is_active!==false;
    const badges = [];
    if (isAdmin) badges.push('<span class="badge-tag badge-admin">👑 ادمین</span>');
    badges.push(isActive?'<span class="badge-tag badge-active">فعال</span>':'<span class="badge-tag badge-inactive">غیرفعال</span>');
    return `
    <div class="user-row">
      <div class="user-avatar">${esc(L)}</div>
      <div class="user-info">
        <div class="user-name">${esc(u.name||'بدون نام')}</div>
        <div class="user-phone">${esc(u.phone||'')}</div>
        <div class="user-date">📅 ${faDate(u.created_at)}</div>
        <div class="user-badges">${badges.join('')}</div>
      </div>
      <div class="user-actions">
        <button class="btn-action warning" onclick="toggleAdmin(${u.id},${isAdmin})">
          <svg viewBox="0 0 24 24"><path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/></svg>
        </button>
        <button class="btn-action ${isActive?'danger':'success'}" onclick="toggleActive(${u.id},${isActive})">
          <svg viewBox="0 0 24 24">${isActive?'<path d="M18 6 6 18M6 6l12 12"/>':'<path d="M20 6 9 17l-5-5"/>'}</svg>
        </button>
        <button class="btn-action" onclick="openEditUser(${u.id})">
          <svg viewBox="0 0 24 24"><path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
        </button>
        <button class="btn-action danger" onclick="openDeleteUser(${u.id})">
          <svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m-9 0v14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V6"/></svg>
        </button>
      </div>
    </div>`;
  }).join('');
}

$('searchInput').addEventListener('input', e=>{
  const t = e.target.value.trim().toLowerCase();
  if (!t) renderUsers(allUsers);
  else renderUsers(allUsers.filter(u=>(u.name||'').toLowerCase().includes(t)||(u.phone||'').includes(t)));
});

async function toggleAdmin(id, cur){
  if (!confirm(cur?'حذف ادمینی؟':'ادمین شود؟')) return;
  const fd = new FormData(); fd.append('id',id); fd.append('is_admin',cur?'0':'1');
  const r = await fetch('admin_api.php?action=toggle_admin',{method:'POST',body:fd,credentials:'include'});
  const d = await r.json();
  if (d.success){ showToast('✓'); loadUsers(); }
  else showToast(d.message||'خطا',true);
}
async function toggleActive(id, cur){
  if (!confirm(cur?'غیرفعال شود؟':'فعال شود؟')) return;
  const fd = new FormData(); fd.append('id',id); fd.append('is_active',cur?'0':'1');
  const r = await fetch('admin_api.php?action=toggle_active',{method:'POST',body:fd,credentials:'include'});
  const d = await r.json();
  if (d.success){ showToast('✓'); loadUsers(); }
  else showToast(d.message||'خطا',true);
}
function openEditUser(id){
  const u = allUsers.find(x=>x.id==id); if (!u) return;
  $('editUserId').value=u.id; $('editName').value=u.name||''; $('editPhone').value=u.phone||''; $('editPassword').value='';
  $('editUserModal').classList.add('show');
}
async function saveUser(){
  const id=$('editUserId').value, name=$('editName').value.trim(),
    phone=$('editPhone').value.trim(), password=$('editPassword').value;
  if (!name){ showToast('نام خالی',true); return; }
  if (!/^09\d{9}$/.test(phone)){ showToast('شماره نامعتبر',true); return; }
  const fd=new FormData(); fd.append('id',id); fd.append('name',name); fd.append('phone',phone);
  if (password) fd.append('password',password);
  const r = await fetch('admin_api.php?action=update_user',{method:'POST',body:fd,credentials:'include'});
  const d = await r.json();
  if (d.success){ showToast('✓'); $('editUserModal').classList.remove('show'); loadUsers(); }
  else showToast(d.message||'خطا',true);
}
function openDeleteUser(id){
  const u=allUsers.find(x=>x.id==id); if(!u)return;
  $('deleteUserId').value=u.id; $('deleteName').textContent=u.name||u.phone;
  $('deleteUserModal').classList.add('show');
}
async function confirmDeleteUser(){
  const id=$('deleteUserId').value;
  const fd=new FormData(); fd.append('id',id);
  const r=await fetch('admin_api.php?action=delete_user',{method:'POST',body:fd,credentials:'include'});
  const d=await r.json();
  if(d.success){showToast('✓ حذف شد');$('deleteUserModal').classList.remove('show');loadUsers();}
  else showToast(d.message||'خطا',true);
}

// ══════ Slides ══════
async function loadSlides(){
  try {
    const res = await fetch('admin_api.php?action=list_slides',{credentials:'include'});
    const d = await res.json();
    if (d.success){ allSlides=d.slides; renderSlides(allSlides); }
  } catch(e) { showToast('خطای شبکه',true); }
}

function renderSlides(slides){
  const grid = $('slidesGrid');
  if (!slides.length){ grid.innerHTML='<div class="empty">اسلایدی نیست</div>'; return; }
  grid.innerHTML = slides.map(s=>`
    <div class="slide-item">
      ${s.image_url ? `<img src="${esc(s.image_url)}" class="slide-img" onerror="this.style.display='none'" loading="lazy">` : ''}
      <div class="slide-body">
        <div class="slide-title">${esc(s.title)}</div>
        ${s.subtitle ? `<div class="slide-sub">${esc(s.subtitle)}</div>` : ''}
        ${s.link_url ? `<div class="slide-link">🔗 ${esc(s.link_url)}</div>` : ''}
        <div style="font-size:10px;color:var(--muted);margin-top:4px">ترتیب: ${s.sort_order||0} · ${s.is_active?'فعال':'غیرفعال'}</div>
        <div class="slide-actions">
          <button class="btn-sm edit" onclick="editSlide(${s.id})">
            <svg viewBox="0 0 24 24"><path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg> ویرایش
          </button>
          <button class="btn-sm delete" onclick="deleteSlide(${s.id})">
            <svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2m-9 0v14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V6"/></svg> حذف
          </button>
        </div>
      </div>
    </div>
  `).join('');
}

function openSlideModal(){
  $('slideId').value=''; $('slideTitle').value=''; $('slideSub').value='';
  $('slideLink').value=''; $('slideOrder').value=0; $('slideUrl').value='';
  $('slideImage').value=''; $('slidePreview').style.display='none';
  $('slideActive').checked=true;
  $('slideModalTitle').textContent='افزودن اسلاید';
  $('slideModal').classList.add('show');
}
function closeSlideModal(){ $('slideModal').classList.remove('show'); }

function editSlide(id){
  const s = allSlides.find(x=>x.id==id); if(!s) return;
  $('slideId').value=s.id; $('slideTitle').value=s.title||'';
  $('slideSub').value=s.subtitle||''; $('slideLink').value=s.link_url||'';
  $('slideOrder').value=s.sort_order||0;
  $('slideUrl').value=s.image_url?.startsWith('uploads/')?'':s.image_url;
  $('slideImage').value='';
  if (s.image_url){ $('slidePreview').src=s.image_url; $('slidePreview').style.display='block'; }
  else $('slidePreview').style.display='none';
  $('slideActive').checked = s.is_active===true || s.is_active==='t';
  $('slideModalTitle').textContent='ویرایش اسلاید';
  $('slideModal').classList.add('show');
}

$('slideImage').addEventListener('change', e=>{
  const f = e.target.files[0];
  if (f){
    const r = new FileReader();
    r.onload = ev=>{ $('slidePreview').src=ev.target.result; $('slidePreview').style.display='block'; };
    r.readAsDataURL(f);
  }
});

async function saveSlide(){
  const id = $('slideId').value;
  const title = $('slideTitle').value.trim();
  if (!title){ showToast('عنوان الزامی',true); return; }
  const fd = new FormData();
  fd.append('id', id); fd.append('title', title);
  fd.append('subtitle', $('slideSub').value.trim());
  fd.append('link_url', $('slideLink').value.trim());
  fd.append('sort_order', $('slideOrder').value||0);
  fd.append('image_url', $('slideUrl').value.trim());
  fd.append('is_active', $('slideActive').checked?'1':'0');
  if ($('slideImage').files[0]) fd.append('image', $('slideImage').files[0]);
  try {
    const r = await fetch('admin_api.php?action=save_slide',{method:'POST',body:fd,credentials:'include'});
    const d = await r.json();
    if (d.success){ showToast('✓ ذخیره شد'); closeSlideModal(); loadSlides(); }
    else showToast(d.message||'خطا',true);
  } catch(e){ showToast('خطای شبکه',true); }
}

async function deleteSlide(id){
  if (!confirm('حذف اسلاید؟')) return;
  const fd=new FormData(); fd.append('id',id);
  const r=await fetch('admin_api.php?action=delete_slide',{method:'POST',body:fd,credentials:'include'});
  const d=await r.json();
  if (d.success){showToast('✓ حذف شد');loadSlides();}
  else showToast(d.message||'خطا',true);
}

// ══════ Settings ══════
async function loadSettings(){
  try {
    const r = await fetch('admin_api.php?action=get_settings',{credentials:'include'});
    const d = await r.json();
    if (d.success){
      const s = d.settings;
      const set = (id,key)=>{ if(s[key]) { const el=$(id); if(el) el.value=s[key]; const v=$(id+'Val'); if(v) v.textContent=s[key]; } };
      set('darkPrimary','theme_dark_primary'); set('darkAccent','theme_dark_accent'); set('darkBg','theme_dark_bg');
      set('lightPrimary','theme_light_primary'); set('lightAccent','theme_light_accent'); set('lightBg','theme_light_bg');
    }
  } catch(e){}
}

['darkPrimary','darkAccent','darkBg','lightPrimary','lightAccent','lightBg'].forEach(id=>{
  const el = $(id); if (!el) return;
  el.addEventListener('input', e=>{
    const v = $(id+'Val'); if (v) v.textContent = e.target.value;
  });
});

async function saveSettings(){
  const settings = {
    theme_dark_primary: $('darkPrimary').value,
    theme_dark_accent: $('darkAccent').value,
    theme_dark_bg: $('darkBg').value,
    theme_light_primary: $('lightPrimary').value,
    theme_light_accent: $('lightAccent').value,
    theme_light_bg: $('lightBg').value
  };
  const fd = new FormData();
  for (const k in settings) fd.append(`settings[${k}]`, settings[k]);
  try {
    const r = await fetch('admin_api.php?action=save_settings',{method:'POST',body:fd,credentials:'include'});
    const d = await r.json();
    if (d.success) showToast('✓ ذخیره شد');
    else showToast(d.message||'خطا',true);
  } catch(e){ showToast('خطای شبکه',true); }
}

document.querySelectorAll('.modal').forEach(m=>{
  m.addEventListener('click', e=>{ if (e.target===m) m.classList.remove('show'); });
});

// ══════ INIT ══════
// فقط داشبورد لود می‌شه (بقیه lazy)
loadDashboard();
</script>
</body>
</html>