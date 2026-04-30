<?php
/**
 * 管理员密码重置工具
 * 使用后请立即删除此文件！
 */
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($newPassword)) {
        $message = '用户名和新密码不能为空';
    } elseif ($newPassword !== $confirmPassword) {
        $message = '两次输入的密码不一致';
    } elseif (strlen($newPassword) < 6) {
        $message = '密码长度不能少于6位';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user) {
                $message = '用户名不存在';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE admin_users SET password_hash = ? WHERE username = ?");
                $stmt->execute([$hash, $username]);
                $message = "密码已重置成功！用户: {$username}，新密码: {$newPassword}";
                $success = true;
            }
        } catch (Exception $e) {
            $message = '操作失败: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重置管理员密码</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, 'PingFang SC', 'Microsoft YaHei', sans-serif; background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.1); padding: 40px; width: 380px; }
        h1 { font-size: 22px; color: #333; text-align: center; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 14px; color: #555; margin-bottom: 6px; }
        input { width: 100%; padding: 10px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px; }
        input:focus { outline: none; border-color: #1890ff; box-shadow: 0 0 0 3px rgba(24,144,255,0.1); }
        button { width: 100%; padding: 12px; background: #1890ff; color: #fff; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; }
        button:hover { background: #096dd9; }
        .msg { padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; text-align: center; }
        .msg.ok { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
        .msg.err { background: #fff1f0; color: #ff4d4f; border: 1px solid #ffa39e; }
        .warn { margin-top: 20px; padding: 10px; background: #fff7e6; border: 1px solid #ffe58f; border-radius: 6px; font-size: 12px; color: #d48806; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h1>重置管理员密码</h1>
        <?php if ($message): ?>
            <div class="msg <?php echo $success ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" value="admin" required>
            </div>
            <div class="form-group">
                <label>新密码</label>
                <input type="text" name="new_password" placeholder="输入新密码" required>
            </div>
            <div class="form-group">
                <label>确认密码</label>
                <input type="text" name="confirm_password" placeholder="再次输入新密码" required>
            </div>
            <button type="submit">重置密码</button>
        </form>
        <div class="warn">使用后请立即删除此文件！</div>
    </div>
</body>
</html>
