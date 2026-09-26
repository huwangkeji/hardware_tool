<?php
/**
 * 管理后台 - 网站设置
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/app.php';

requireAdmin();

$settings = getSettings();
$message  = '';
$error    = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF()) {
        $error = 'CSRF 验证失败，请刷新页面重试';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            $db = getDB();

            switch ($action) {
                case 'site':
                    $db->prepare("UPDATE settings SET
                        site_title = ?, site_description = ?, site_text_logo = ?, theme_color = ?,
                        footer_copyright = ?, footer_version = ? WHERE id = 1")
                       ->execute([
                           trim($_POST['site_title'] ?? ''),
                           trim($_POST['site_description'] ?? ''),
                           trim($_POST['site_text_logo'] ?? ''),
                           trim($_POST['theme_color'] ?? '#1890ff'),
                           trim($_POST['footer_copyright'] ?? ''),
                           trim($_POST['footer_version'] ?? '1.0.0'),
                       ]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => '网站基础信息已保存'];
                    redirect('/admin/settings.php');
                    break;

                case 'contact':
                    $db->prepare("UPDATE settings SET
                        contact_email = ?, contact_qq = ?, contact_qq_group = ?, contact_doc_url = ?,
                        douyin_text = ?, douyin_url = ?, bilibili_text = ?, bilibili_url = ? WHERE id = 1")
                       ->execute([
                           trim($_POST['contact_email'] ?? ''),
                           trim($_POST['contact_qq'] ?? ''),
                           trim($_POST['contact_qq_group'] ?? ''),
                           trim($_POST['contact_doc_url'] ?? ''),
                           trim($_POST['douyin_text'] ?? ''),
                           trim($_POST['douyin_url'] ?? ''),
                           trim($_POST['bilibili_text'] ?? ''),
                           trim($_POST['bilibili_url'] ?? ''),
                       ]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => '联系方式已保存'];
                    redirect('/admin/settings.php');
                    break;

                case 'about':
                    $aboutContent = $_POST['about_content'] ?? '';
                    $db->prepare("UPDATE settings SET about_content = ? WHERE id = 1")
                       ->execute([$aboutContent]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => '关于页面内容已保存'];
                    redirect('/admin/settings.php');
                    break;

                case 'password':
                    $oldPass = $_POST['old_password'] ?? '';
                    $newPass = $_POST['new_password'] ?? '';
                    $confirmPass = $_POST['confirm_password'] ?? '';

                    $stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
                    $stmt->execute([$_SESSION['admin_user_id']]);
                    $user = $stmt->fetch();

                    if (!$user || !password_verify($oldPass, $user['password_hash'])) {
                        $error = '原密码不正确';
                    } elseif (strlen($newPass) < 8) {
                        $error = '新密码至少 8 个字符';
                    } elseif (!preg_match('/[A-Za-z]/', $newPass) || !preg_match('/[0-9]/', $newPass)) {
                        $error = '新密码必须包含字母和数字';
                    } elseif ($newPass !== $confirmPass) {
                        $error = '两次输入的密码不一致';
                    } else {
                        $hash = password_hash($newPass, PASSWORD_DEFAULT);
                        $db->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?")
                           ->execute([$hash, $_SESSION['admin_user_id']]);
                        $_SESSION['flash'] = ['type' => 'success', 'message' => '密码已修改'];
                        redirect('/admin/settings.php');
                    }
                    break;
            }
        } catch (Exception $e) {
            $error = '保存失败：' . $e->getMessage();
        }
    }
}

// 读取 flash 消息
$flash = $_SESSION['flash'] ?? null;
if ($flash) {
    $message = $flash['message'] ?? '';
    unset($_SESSION['flash']);
}

$current_admin_page = 'settings';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站设置 - 管理后台</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { margin: 0; background: #f0f2f5; }
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-content { flex: 1; margin-left: 240px; padding: 24px; max-width: 800px; }
        .admin-sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 240px; background: #001529; color: #fff; display: flex; flex-direction: column; z-index: 100; }
        .admin-logo { padding: 20px; font-size: 18px; font-weight: bold; border-bottom: 1px solid #003a8c; }
        .admin-nav { flex: 1; padding: 12px 0; }
        .admin-nav .nav-item { display: flex; align-items: center; gap: 10px; padding: 12px 24px; color: rgba(255,255,255,0.65); text-decoration: none; transition: all 0.2s; }
        .admin-nav .nav-item:hover, .admin-nav .nav-item.active { color: #fff; background: #1890ff; }
        .admin-nav-bottom { padding: 12px 0; border-top: 1px solid #003a8c; }
        .logout-btn { display: flex; align-items: center; gap: 10px; padding: 12px 24px; color: rgba(255,255,255,0.65); }
        .logout-btn:hover { color: #ff4d4f; }
        .page-header h1 { margin: 0 0 24px; font-size: 24px; color: #333; }
        .panel { background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 24px; }
        .panel h2 { margin: 0 0 20px; font-size: 18px; color: #333; border-bottom: 1px solid #f0f0f0; padding-bottom: 12px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 14px; color: #555; font-weight: 500; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        .form-group input:focus, .form-group textarea:focus { border-color: #1890ff; outline: none; }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .btn-save { padding: 8px 24px; background: #1890ff; color: #fff; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; }
        .btn-save:hover { background: #40a9ff; }
        .flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .flash.success { background: #f6ffed; border: 1px solid #b7eb8f; color: #389e0d; }
        .flash.error { background: #fff2f0; border: 1px solid #ffccc7; color: #cf1322; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <div class="page-header">
            <h1>网站设置</h1>
        </div>

        <?php if ($message): ?>
            <div class="flash success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="flash error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <!-- 网站基础信息 -->
        <div class="panel">
            <h2>网站基础信息</h2>
            <form method="post" action="">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="site">
                <div class="form-group">
                    <label>网站标题</label>
                    <input type="text" name="site_title" value="<?php echo h($settings['site_title']); ?>">
                </div>
                <div class="form-group">
                    <label>网站描述</label>
                    <input type="text" name="site_description" value="<?php echo h($settings['site_description']); ?>">
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>文字 Logo</label>
                        <input type="text" name="site_text_logo" value="<?php echo h($settings['site_text_logo']); ?>">
                    </div>
                    <div class="form-group">
                        <label>主题颜色</label>
                        <input type="text" name="theme_color" value="<?php echo h($settings['theme_color']); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>页脚版权</label>
                        <input type="text" name="footer_copyright" value="<?php echo h($settings['footer_copyright']); ?>">
                    </div>
                    <div class="form-group">
                        <label>版本号</label>
                        <input type="text" name="footer_version" value="<?php echo h($settings['footer_version']); ?>">
                    </div>
                </div>
                <button type="submit" class="btn-save">保存</button>
            </form>
        </div>

        <!-- 联系方式 -->
        <div class="panel">
            <h2>联系方式</h2>
            <form method="post" action="">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="contact">
                <div class="row">
                    <div class="form-group">
                        <label>联系邮箱</label>
                        <input type="email" name="contact_email" value="<?php echo h($settings['contact_email']); ?>">
                    </div>
                    <div class="form-group">
                        <label>QQ 号</label>
                        <input type="text" name="contact_qq" value="<?php echo h($settings['contact_qq']); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>QQ 群</label>
                        <input type="text" name="contact_qq_group" value="<?php echo h($settings['contact_qq_group']); ?>">
                    </div>
                    <div class="form-group">
                        <label>文档地址</label>
                        <input type="text" name="contact_doc_url" value="<?php echo h($settings['contact_doc_url']); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>抖音文字</label>
                        <input type="text" name="douyin_text" value="<?php echo h($settings['douyin_text']); ?>">
                    </div>
                    <div class="form-group">
                        <label>抖音链接</label>
                        <input type="text" name="douyin_url" value="<?php echo h($settings['douyin_url']); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>B站文字</label>
                        <input type="text" name="bilibili_text" value="<?php echo h($settings['bilibili_text']); ?>">
                    </div>
                    <div class="form-group">
                        <label>B站链接</label>
                        <input type="text" name="bilibili_url" value="<?php echo h($settings['bilibili_url']); ?>">
                    </div>
                </div>
                <button type="submit" class="btn-save">保存</button>
            </form>
        </div>

        <!-- 关于页面 -->
        <div class="panel">
            <h2>关于页面内容</h2>
            <form method="post" action="">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="about">
                <div class="form-group">
                    <label>关于页面 HTML 内容</label>
                    <textarea name="about_content"><?php echo h($settings['about_content']); ?></textarea>
                </div>
                <button type="submit" class="btn-save">保存</button>
            </form>
        </div>

        <!-- 修改密码 -->
        <div class="panel">
            <h2>修改密码</h2>
            <form method="post" action="">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="password">
                <div class="form-group">
                    <label>原密码</label>
                    <input type="password" name="old_password" required>
                </div>
                <div class="form-group">
                    <label>新密码（至少 8 位，含字母和数字）</label>
                    <input type="password" name="new_password" minlength="8" required>
                </div>
                <div class="form-group">
                    <label>确认新密码</label>
                    <input type="password" name="confirm_password" minlength="8" required>
                </div>
                <button type="submit" class="btn-save">修改密码</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
