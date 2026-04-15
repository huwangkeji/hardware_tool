<?php
/**
 * 后台管理 - 网站设置
 */
// 静态资源直接返回，不经过PHP处理
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/assets/(css|js|images)/#', $request_uri)) {
    $file_path = __DIR__ . '/../' . ltrim($request_uri, '/');
    if (file_exists($file_path)) {
        $ext = pathinfo($file_path, PATHINFO_EXTENSION);
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
        ];
        if (isset($mime_types[$ext])) {
            header('Content-Type: ' . $mime_types[$ext]);
            header('Cache-Control: public, max-age=2592000');
            readfile($file_path);
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/auth.php';
requireAdmin(); // 验证管理员权限

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$error = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_settings') {
        try {
            $db = getDB();
            
            // 获取当前设置作为默认值
            $currentSettings = getSettings();
            
            // 所有字段都使用 POST 数据，缺失时使用数据库中的值
            $siteTitle = htmlspecialchars(trim($_POST['site_title'] ?? ($currentSettings['site_title'] ?? '')));
            $siteDescription = htmlspecialchars(trim($_POST['site_description'] ?? ($currentSettings['site_description'] ?? '')));
            $siteTextLogo = htmlspecialchars(trim($_POST['site_text_logo'] ?? ($currentSettings['site_text_logo'] ?? '')));
            $aboutContent = $_POST['about_content'] ?? ($currentSettings['about_content'] ?? '');
            $themeColor = htmlspecialchars(trim($_POST['theme_color'] ?? ($currentSettings['theme_color'] ?? '#1890ff')));
            
            // 联系信息字段也使用 POST 数据，缺失时使用数据库中的值
            $contactEmail = htmlspecialchars(trim($_POST['contact_email'] ?? ($currentSettings['contact_email'] ?? '')));
            $contactQQ = htmlspecialchars(trim($_POST['contact_qq'] ?? ($currentSettings['contact_qq'] ?? '')));
            $contactQQGroup = htmlspecialchars(trim($_POST['contact_qq_group'] ?? ($currentSettings['contact_qq_group'] ?? '')));
            $contactDocUrl = htmlspecialchars(trim($_POST['contact_doc_url'] ?? ($currentSettings['contact_doc_url'] ?? '')));
            
            // 社交媒体链接字段
            $douyinText = htmlspecialchars(trim($_POST['douyin_text'] ?? ($currentSettings['douyin_text'] ?? '我的抖音')));
            $douyinUrl = htmlspecialchars(trim($_POST['douyin_url'] ?? ($currentSettings['douyin_url'] ?? '')));
            $bilibiliText = htmlspecialchars(trim($_POST['bilibili_text'] ?? ($currentSettings['bilibili_text'] ?? '我的B站')));
            $bilibiliUrl = htmlspecialchars(trim($_POST['bilibili_url'] ?? ($currentSettings['bilibili_url'] ?? '')));
            
            // 底部信息字段
            $footerCopyright = htmlspecialchars(trim($_POST['footer_copyright'] ?? ($currentSettings['footer_copyright'] ?? '')));
            $footerVersion = htmlspecialchars(trim($_POST['footer_version'] ?? ($currentSettings['footer_version'] ?? '1.0.0')));
            
            // 更新设置
            $stmt = $db->prepare("UPDATE settings SET 
                site_title = :title, 
                site_description = :description,
                site_text_logo = :text_logo,
                about_content = :about_content,
                theme_color = :color,
                contact_email = :email,
                contact_qq = :qq,
                contact_qq_group = :qq_group,
                contact_doc_url = :doc_url,
                douyin_text = :douyin_text,
                douyin_url = :douyin_url,
                bilibili_text = :bilibili_text,
                bilibili_url = :bilibili_url,
                footer_copyright = :footer_copyright,
                footer_version = :footer_version
                WHERE id = 1");
            
            $stmt->execute([
                ':title' => $siteTitle,
                ':description' => $siteDescription,
                ':text_logo' => $siteTextLogo,
                ':about_content' => $aboutContent,
                ':color' => $themeColor,
                ':email' => $contactEmail,
                ':qq' => $contactQQ,
                ':qq_group' => $contactQQGroup,
                ':doc_url' => $contactDocUrl,
                ':douyin_text' => $douyinText,
                ':douyin_url' => $douyinUrl,
                ':bilibili_text' => $bilibiliText,
                ':bilibili_url' => $bilibiliUrl,
                ':footer_copyright' => $footerCopyright,
                ':footer_version' => $footerVersion
            ]);
            
            $message = '设置保存成功!';
        } catch (PDOException $e) {
            $error = '保存失败: ' . $e->getMessage();
        }
    } elseif ($action === 'save_contact_info') {
        try {
            $db = getDB();
            
            // 获取当前设置作为默认值
            $currentSettings = getSettings();
            
            // 联系信息字段
            $contactEmail = htmlspecialchars(trim($_POST['contact_email'] ?? ($currentSettings['contact_email'] ?? '')));
            $contactQQ = htmlspecialchars(trim($_POST['contact_qq'] ?? ($currentSettings['contact_qq'] ?? '')));
            $contactQQGroup = htmlspecialchars(trim($_POST['contact_qq_group'] ?? ($currentSettings['contact_qq_group'] ?? '')));
            $contactDocUrl = htmlspecialchars(trim($_POST['contact_doc_url'] ?? ($currentSettings['contact_doc_url'] ?? '')));
            
            // 社交媒体链接字段
            $douyinText = htmlspecialchars(trim($_POST['douyin_text'] ?? ($currentSettings['douyin_text'] ?? '我的抖音')));
            $douyinUrl = htmlspecialchars(trim($_POST['douyin_url'] ?? ($currentSettings['douyin_url'] ?? '')));
            $bilibiliText = htmlspecialchars(trim($_POST['bilibili_text'] ?? ($currentSettings['bilibili_text'] ?? '我的B站')));
            $bilibiliUrl = htmlspecialchars(trim($_POST['bilibili_url'] ?? ($currentSettings['bilibili_url'] ?? '')));
            
            // 底部信息字段
            $footerCopyright = htmlspecialchars(trim($_POST['footer_copyright'] ?? ($currentSettings['footer_copyright'] ?? '')));
            $footerVersion = htmlspecialchars(trim($_POST['footer_version'] ?? ($currentSettings['footer_version'] ?? '1.0.0')));
            
            // 更新联系信息
            $stmt = $db->prepare("UPDATE settings SET 
                contact_email = :email,
                contact_qq = :qq,
                contact_qq_group = :qq_group,
                contact_doc_url = :doc_url,
                douyin_text = :douyin_text,
                douyin_url = :douyin_url,
                bilibili_text = :bilibili_text,
                bilibili_url = :bilibili_url,
                footer_copyright = :footer_copyright,
                footer_version = :footer_version
                WHERE id = 1");
            
            $stmt->execute([
                ':email' => $contactEmail,
                ':qq' => $contactQQ,
                ':qq_group' => $contactQQGroup,
                ':doc_url' => $contactDocUrl,
                ':douyin_text' => $douyinText,
                ':douyin_url' => $douyinUrl,
                ':bilibili_text' => $bilibiliText,
                ':bilibili_url' => $bilibiliUrl,
                ':footer_copyright' => $footerCopyright,
                ':footer_version' => $footerVersion
            ]);
            
            $message = '联系信息保存成功!';
        } catch (PDOException $e) {
            $error = '保存失败: ' . $e->getMessage();
        }
    } elseif ($action === 'update_password') {
        try {
            $db = getDB();
            
            $oldPassword = $_POST['old_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // 验证旧密码
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['admin_id']]);
            $user = $stmt->fetch();
            
            if (!password_verify($oldPassword, $user['password_hash'])) {
                $error = '原密码错误';
            } elseif ($newPassword !== $confirmPassword) {
                $error = '两次输入的新密码不一致';
            } elseif (strlen($newPassword) < 6) {
                $error = '新密码长度不能少于6位';
            } else {
                // 更新密码
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE admin_users SET password_hash = :hash WHERE id = :id");
                $stmt->execute([
                    ':hash' => $passwordHash,
                    ':id' => $_SESSION['admin_id']
                ]);
                
                $message = '密码修改成功!';
            }
        } catch (PDOException $e) {
            $error = '密码修改失败: ' . $e->getMessage();
        }
    }
}

// 获取当前设置
$settings = getSettings();

// 获取管理员信息
$db = getDB();
$stmt = $db->prepare("SELECT username, email FROM admin_users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['admin_id']]);
$adminUser = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站设置 - <?php echo htmlspecialchars($settings['site_title']); ?></title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        /* 内联样式确保基础布局 */
        .admin-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }
        .admin-sidebar {
            width: 260px;
            background: white;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.08);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            left: 0;
            top: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }
        .admin-main {
            flex: 1;
            margin-left: 260px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: calc(100% - 260px);
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        
        /* 菜单样式 */
        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid #e8e8e8;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        .sidebar-header::before {
            content: '⚡';
            font-size: 24px;
        }
        .sidebar-header h2 {
            color: white;
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 1px;
        }
        .sidebar-nav {
            padding: 16px 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
            overflow-y: auto;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 24px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            font-size: 15px;
            font-weight: 500;
        }
        .nav-item:hover {
            background: rgba(24, 144, 255, 0.06);
            color: #1890ff;
            border-left-color: #1890ff;
            padding-left: 28px;
        }
        .nav-item.active {
            background: linear-gradient(90deg, rgba(24, 144, 255, 0.15) 0%, transparent 100%);
            color: #1890ff;
            border-left-color: #1890ff;
            font-weight: 600;
        }
        .nav-item.logout {
            margin-top: auto;
            color: #ff4d4f;
            border-top: 1px solid #e8e8e8;
            padding-top: 20px;
            margin-top: 20px;
        }
        .nav-item.logout:hover {
            background: rgba(255, 77, 79, 0.08);
            border-left-color: #ff4d4f;
            color: #cf1322;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- 侧边栏 -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>后台管理</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="/admin/index.php" class="nav-item">
                    <span>📊</span>
                    <span>仪表盘</span>
                </a>
                <a href="/admin/settings.php" class="nav-item active">
                    <span>⚙️</span>
                    <span>网站设置</span>
                </a>
                <a href="/admin/statistics.php" class="nav-item">
                    <span>📈</span>
                    <span>访问统计</span>
                </a>
                <a href="/" class="nav-item" target="_blank">
                    <span>🏠</span>
                    <span>返回前台</span>
                </a>
                <a href="/admin/logout.php" class="nav-item logout">
                    <span>🚪</span>
                    <span>退出登录</span>
                </a>
            </nav>
        </aside>

        <!-- 主内容区 -->
        <main class="admin-main">
            <header class="admin-header">
                <div class="admin-header-left">
                    <h1>网站设置</h1>
                    <div class="breadcrumb">
                        <span>🏠</span>
                        <span class="separator">›</span>
                        <span class="current">Dashboard</span>
                        <span class="separator">›</span>
                        <span>网站设置</span>
                    </div>
                </div>
                <div class="admin-header-right">
                    <div class="header-time" id="currentTime"></div>
                    <div class="user-info">
                        <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></span>
                        <span class="user-badge">管理员</span>
                    </div>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- 基本设置 -->
                <h2 style="margin-bottom: 20px; color: var(--text-primary);">基本设置</h2>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_settings">
                    
                    <div class="form-group">
                        <label for="site_title">网站标题</label>
                        <input type="text" id="site_title" name="site_title" 
                               value="<?php echo htmlspecialchars($settings['site_title']); ?>" 
                               required>
                        <p class="help-text">显示在浏览器标签页和页面顶部的标题</p>
                    </div>

                    <div class="form-group">
                        <label for="site_description">网站描述</label>
                        <textarea id="site_description" name="site_description" rows="4" 
                                  required><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                        <p class="help-text">网站的简要描述,用于SEO和用户了解网站功能</p>
                    </div>

                    <div class="form-group">
                        <label for="site_text_logo">首页标识文字</label>
                        <input type="text" id="site_text_logo" name="site_text_logo" 
                               value="<?php echo htmlspecialchars($settings['site_text_logo'] ?? '硬件调试工具'); ?>" 
                               maxlength="50">
                        <p class="help-text">显示在页面左上角的Logo文字（蓝色渐变背景）</p>
                    </div>

                    <div class="form-group">
                        <label for="theme_color">主题颜色</label>
                        <input type="color" id="theme_color" name="theme_color" 
                               value="<?php echo htmlspecialchars($settings['theme_color']); ?>">
                        <p class="help-text">网站的主要颜色主题(默认: #1890ff)</p>
                    </div>

                    <button type="submit" class="btn btn-primary">保存设置</button>
                </form>

                <hr style="margin: 40px 0; border: none; border-top: 1px solid var(--border-color);">

                <!-- 关于页面设置 -->
                <h2 style="margin-bottom: 20px; color: var(--text-primary);">关于页面内容</h2>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_settings">
                    
                    <div class="form-group">
                        <label for="about_content">关于页面 - 项目介绍内容</label>
                        <textarea id="about_content" name="about_content" rows="15" 
                                  style="font-family: 'Courier New', monospace;"><?php echo htmlspecialchars($settings['about_content'] ?? ''); ?></textarea>
                        <p class="help-text">💡 此内容只显示在“关于”页面的<strong>项目介绍</strong>区块，其他区块（核心功能、技术特性、支持芯片、联系我们）保持固定不变。支持HTML标签，留空将显示默认内容。</p>
                    </div>

                    <button type="submit" class="btn btn-primary">保存关于页面内容</button>
                    <a href="/?page=about" class="btn btn-secondary" target="_blank">预览关于页面</a>
                </form>

                <hr style="margin: 40px 0; border: none; border-top: 1px solid var(--border-color);">

                <!-- 联系信息设置 -->
                <h2 style="margin-bottom: 20px; color: var(--text-primary);">联系信息设置</h2>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_contact_info">
                    
                    <div class="form-group">
                        <label for="contact_email">📧 联系邮箱</label>
                        <input type="email" id="contact_email" name="contact_email" 
                               value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" 
                               placeholder="请输入联系邮箱">
                        <p class="help-text">显示在关于页面的联系邮箱</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_qq">💬 联系QQ</label>
                        <input type="text" id="contact_qq" name="contact_qq" 
                               value="<?php echo htmlspecialchars($settings['contact_qq'] ?? ''); ?>" 
                               placeholder="请输入QQ号">
                        <p class="help-text">显示在关于页面的QQ号码</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_qq_group">👥 联系QQ群</label>
                        <input type="text" id="contact_qq_group" name="contact_qq_group" 
                               value="<?php echo htmlspecialchars($settings['contact_qq_group'] ?? ''); ?>" 
                               placeholder="请输入QQ群号">
                        <p class="help-text">显示在关于页面的QQ群号</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_doc_url">📖 文档链接</label>
                        <input type="url" id="contact_doc_url" name="contact_doc_url" 
                               value="<?php echo htmlspecialchars($settings['contact_doc_url'] ?? ''); ?>" 
                               placeholder="请输入文档链接URL">
                        <p class="help-text">显示在关于页面的文档链接（完整URL）</p>
                    </div>

                    <hr style="margin: 30px 0; border: none; border-top: 1px dashed var(--border-color);">
                    
                    <h3 style="margin-bottom: 20px; color: var(--text-primary); font-size: 16px;">📱 社交媒体链接</h3>

                    <div class="form-group">
                        <label for="douyin_text">🎵 抖音链接文字</label>
                        <input type="text" id="douyin_text" name="douyin_text" 
                               value="<?php echo htmlspecialchars($settings['douyin_text'] ?? '我的抖音'); ?>" 
                               placeholder="请输入显示文字">
                        <p class="help-text">在关于页面显示的抖音链接文字</p>
                    </div>

                    <div class="form-group">
                        <label for="douyin_url">🔗 抖音链接URL</label>
                        <input type="url" id="douyin_url" name="douyin_url" 
                               value="<?php echo htmlspecialchars($settings['douyin_url'] ?? ''); ?>" 
                               placeholder="请输入抖音主页URL">
                        <p class="help-text">点击后跳转到的抖音主页链接（完整URL）</p>
                    </div>

                    <div class="form-group">
                        <label for="bilibili_text">📺 B站链接文字</label>
                        <input type="text" id="bilibili_text" name="bilibili_text" 
                               value="<?php echo htmlspecialchars($settings['bilibili_text'] ?? '我的B站'); ?>" 
                               placeholder="请输入显示文字">
                        <p class="help-text">在关于页面显示的B站链接文字</p>
                    </div>

                    <div class="form-group">
                        <label for="bilibili_url">🔗 B站链接URL</label>
                        <input type="url" id="bilibili_url" name="bilibili_url" 
                               value="<?php echo htmlspecialchars($settings['bilibili_url'] ?? ''); ?>" 
                               placeholder="请输入B站主页URL">
                        <p class="help-text">点击后跳转到的B站主页链接（完整URL）</p>
                    </div>

                    <hr style="margin: 30px 0; border: none; border-top: 1px dashed var(--border-color);">
                    
                    <h3 style="margin-bottom: 20px; color: var(--text-primary); font-size: 16px;">📄 底部信息</h3>

                    <div class="form-group">
                        <label for="footer_copyright">© 底部版权信息</label>
                        <input type="text" id="footer_copyright" name="footer_copyright" 
                               value="<?php echo htmlspecialchars($settings['footer_copyright'] ?? ''); ?>" 
                               placeholder="例如：© 2026 硬件调试工具. All rights reserved.">
                        <p class="help-text">显示在页面底部的版权信息（留空则使用默认格式）</p>
                    </div>

                    <div class="form-group">
                        <label for="footer_version">🔢 版本号</label>
                        <input type="text" id="footer_version" name="footer_version" 
                               value="<?php echo htmlspecialchars($settings['footer_version'] ?? '1.0.0'); ?>" 
                               placeholder="例如：1.0.0">
                        <p class="help-text">显示在页面底部的版本号（留空则使用默认：1.0.0）</p>
                    </div>

                    <button type="submit" class="btn btn-primary">保存联系信息</button>
                    <a href="/" class="btn btn-secondary" target="_blank">预览前台效果</a>
                </form>

                <hr style="margin: 40px 0; border: none; border-top: 1px solid var(--border-color);">

                <!-- 修改密码 -->
                <h2 style="margin-bottom: 20px; color: var(--text-primary);">修改密码</h2>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_password">
                    
                    <div class="form-group">
                        <label for="username">用户名</label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($adminUser['username']); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label for="old_password">原密码</label>
                        <input type="password" id="old_password" name="old_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">新密码</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                        <p class="help-text">密码长度至少6位</p>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">确认新密码</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>

                    <button type="submit" class="btn btn-primary">修改密码</button>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        // 实时时钟
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleString('zh-CN', {
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
            });
            const clockElement = document.getElementById('currentTime');
            if (clockElement) clockElement.textContent = timeStr;
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>
