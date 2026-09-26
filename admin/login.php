<?php
/**
 * 管理后台 - 登录页
 */
require_once __DIR__ . '/../includes/auth.php';

// 如果已登录，跳转到后台首页
if (isAdmin()) {
    redirect('/admin/index.php');
}

$error = '';

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        if (loginUser($username, $password)) {
            redirect('/admin/index.php');
        } else {
            $failCount = $_SESSION['login_fail_count'] ?? 0;
            if ($failCount >= 5) {
                $error = '登录失败次数过多，请 15 分钟后再试';
            } else {
                $error = '用户名或密码错误';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 硬件调试工具</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .login-card { background: #fff; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); padding: 40px; width: 400px; max-width: 90vw; }
        .login-card h1 { text-align: center; margin: 0 0 8px; font-size: 24px; color: #333; }
        .login-card .subtitle { text-align: center; color: #999; margin: 0 0 30px; font-size: 14px; }
        .login-card .form-group { margin-bottom: 20px; }
        .login-card label { display: block; margin-bottom: 6px; font-size: 14px; color: #555; font-weight: 500; }
        .login-card input[type="text"], .login-card input[type="password"] { width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px; transition: border-color 0.2s; box-sizing: border-box; }
        .login-card input:focus { border-color: #1890ff; outline: none; }
        .login-card .btn-login { width: 100%; padding: 12px; background: #1890ff; color: #fff; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; transition: background 0.2s; }
        .login-card .btn-login:hover { background: #40a9ff; }
        .login-card .error-msg { background: #fff2f0; border: 1px solid #ffccc7; color: #cf1322; padding: 10px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .login-card .back-link { text-align: center; margin-top: 20px; }
        .login-card .back-link a { color: #1890ff; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>🔧 硬件调试工具</h1>
        <p class="subtitle">管理后台登录</p>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" autocomplete="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn-login">登 录</button>
        </form>

        <div class="back-link">
            <a href="/">← 返回首页</a>
        </div>
    </div>
</body>
</html>
