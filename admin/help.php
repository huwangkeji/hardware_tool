<?php
/**
 * 后台管理 - 帮助文章管理
 */
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/assets/(css|js|images)/#', $request_uri)) {
    $file_path = __DIR__ . '/../' . ltrim($request_uri, '/');
    if (file_exists($file_path)) {
        $ext = pathinfo($file_path, PATHINFO_EXTENSION);
        $mime_types = [
            'css' => 'text/css', 'js' => 'application/javascript',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
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
requireAdmin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$settings = getSettings();
$message = '';
$error = '';

$categoryLabels = [
    'driver' => '驱动安装',
    'tool' => '工具使用',
    'zte' => '中兴微调试',
    'asr' => 'ASR调试',
    'unisoc' => '展锐调试',
    'general' => '通用说明',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'general');
        $content = $_POST['content'] ?? '';
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        if (empty($title) || empty($content)) {
            $error = '标题和内容不能为空';
        } else {
            try {
                createHelpPage($title, $category, $content, $sortOrder, $isPublished);
                $message = '文章创建成功!';
            } catch (Exception $e) {
                $error = '创建失败: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'general');
        $content = $_POST['content'] ?? '';
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        if (empty($title) || empty($content)) {
            $error = '标题和内容不能为空';
        } else {
            try {
                updateHelpPage($id, $title, $category, $content, $sortOrder, $isPublished);
                $message = '文章更新成功!';
            } catch (Exception $e) {
                $error = '更新失败: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            deleteHelpPage($id);
            $message = '文章已删除!';
        } catch (Exception $e) {
            $error = '删除失败: ' . $e->getMessage();
        }
    }
}

$editId = intval($_GET['edit'] ?? 0);
$editPage = $editId > 0 ? getHelpPage($editId) : null;
$helpPages = getHelpPages(null, false);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>帮助文章管理 - <?php echo h($settings['site_title']); ?></title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; width: 100%; }
        .admin-sidebar { width: 260px; background: white; box-shadow: 2px 0 12px rgba(0,0,0,0.08); position: fixed; height: 100vh; overflow-y: auto; left: 0; top: 0; z-index: 1000; display: flex; flex-direction: column; transition: transform 0.3s ease; }
        .admin-main { flex: 1; margin-left: 260px; display: flex; flex-direction: column; min-height: 100vh; width: calc(100% - 260px); transition: margin-left 0.3s ease, width 0.3s ease; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid #e8e8e8; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); flex-shrink: 0; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 8px rgba(102,126,234,0.3); }
        .sidebar-header::before { content: '⚡'; font-size: 24px; }
        .sidebar-header h2 { color: white; font-size: 22px; font-weight: 700; margin: 0; letter-spacing: 1px; }
        .sidebar-nav { padding: 16px 0; display: flex; flex-direction: column; gap: 4px; flex: 1; overflow-y: auto; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 14px 24px; color: #333; text-decoration: none; transition: all 0.3s ease; border-left: 4px solid transparent; font-size: 15px; font-weight: 500; }
        .nav-item:hover { background: rgba(24,144,255,0.06); color: #1890ff; border-left-color: #1890ff; padding-left: 28px; }
        .nav-item.active { background: linear-gradient(90deg, rgba(24,144,255,0.15) 0%, transparent 100%); color: #1890ff; border-left-color: #1890ff; font-weight: 600; }
        .nav-item.logout { margin-top: auto; color: #ff4d4f; border-top: 1px solid #e8e8e8; padding-top: 20px; margin-top: 20px; }
        .nav-item.logout:hover { background: rgba(255,77,79,0.08); border-left-color: #ff4d4f; color: #cf1322; }
        .help-editor { margin-top: 20px; }
        .help-editor .form-group { margin-bottom: 20px; }
        .help-editor label { display: block; font-weight: 600; margin-bottom: 6px; color: #333; }
        .help-editor input[type="text"],
        .help-editor input[type="number"],
        .help-editor select { width: 100%; padding: 10px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px; }
        .help-editor textarea { width: 100%; padding: 10px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px; min-height: 300px; font-family: 'Courier New', monospace; resize: vertical; }
        .help-editor .checkbox-group { display: flex; align-items: center; gap: 8px; }
        .help-editor .checkbox-group input { width: 18px; height: 18px; }
        .help-list-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .help-list-table th, .help-list-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        .help-list-table th { background: #fafafa; font-weight: 600; color: #333; }
        .help-list-table td { font-size: 14px; }
        .help-list-table .actions { white-space: nowrap; }
        .help-list-table .actions a, .help-list-table .actions button { margin-right: 8px; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .status-badge.published { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
        .status-badge.draft { background: #fff7e6; color: #faad14; border: 1px solid #ffe58f; }
        .category-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; background: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>后台管理</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="/admin/index.php" class="nav-item">
                    <span>📊</span><span>仪表盘</span>
                </a>
                <a href="/admin/settings.php" class="nav-item">
                    <span>⚙️</span><span>网站设置</span>
                </a>
                <a href="/admin/help.php" class="nav-item active">
                    <span>📖</span><span>帮助文章</span>
                </a>
                <a href="/admin/statistics.php" class="nav-item">
                    <span>📈</span><span>访问统计</span>
                </a>
                <a href="/" class="nav-item" target="_blank">
                    <span>🏠</span><span>返回前台</span>
                </a>
                <a href="/admin/logout.php" class="nav-item logout">
                    <span>🚪</span><span>退出登录</span>
                </a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <div class="admin-header-left">
                    <h1>帮助文章管理</h1>
                    <div class="breadcrumb">
                        <span>🏠</span><span class="separator">›</span>
                        <span>后台管理</span><span class="separator">›</span>
                        <span class="current">帮助文章</span>
                    </div>
                </div>
                <div class="admin-header-right">
                    <div class="header-time" id="currentTime"></div>
                    <div class="user-info">
                        <span><?php echo h($_SESSION['admin_username'] ?? 'admin'); ?></span>
                        <span class="user-badge">管理员</span>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo h($message); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo h($error); ?></div>
                <?php endif; ?>

                <?php if ($editPage): ?>
                <h2>编辑文章</h2>
                <form method="POST" class="help-editor">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo $editPage['id']; ?>">
                    <div class="form-group">
                        <label>标题</label>
                        <input type="text" name="title" value="<?php echo h($editPage['title']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>分类</label>
                        <select name="category">
                            <?php foreach ($categoryLabels as $key => $label): ?>
                            <option value="<?php echo h($key); ?>" <?php echo $editPage['category'] === $key ? 'selected' : ''; ?>><?php echo h($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>内容 (支持HTML)</label>
                        <textarea name="content" required><?php echo h($editPage['content']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>排序权重</label>
                        <input type="number" name="sort_order" value="<?php echo $editPage['sort_order']; ?>" min="0">
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_published" value="1" <?php echo $editPage['is_published'] ? 'checked' : ''; ?> id="chk_pub">
                            <label for="chk_pub">发布</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">保存修改</button>
                    <a href="/admin/help.php" class="btn btn-secondary">取消</a>
                </form>
                <hr style="margin: 40px 0; border: none; border-top: 1px solid #e8e8e8;">
                <?php else: ?>
                <h2>新建文章</h2>
                <form method="POST" class="help-editor">
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label>标题</label>
                        <input type="text" name="title" placeholder="输入文章标题" required>
                    </div>
                    <div class="form-group">
                        <label>分类</label>
                        <select name="category">
                            <?php foreach ($categoryLabels as $key => $label): ?>
                            <option value="<?php echo h($key); ?>"><?php echo h($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>内容 (支持HTML)</label>
                        <textarea name="content" placeholder="输入文章内容，支持HTML标签" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>排序权重</label>
                        <input type="number" name="sort_order" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_published" value="1" checked id="chk_new_pub">
                            <label for="chk_new_pub">发布</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">创建文章</button>
                </form>
                <hr style="margin: 40px 0; border: none; border-top: 1px solid #e8e8e8;">
                <?php endif; ?>

                <h2>文章列表</h2>
                <div class="table-container">
                    <table class="help-list-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>标题</th>
                                <th>分类</th>
                                <th>排序</th>
                                <th>状态</th>
                                <th>更新时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($helpPages as $hp): ?>
                            <tr>
                                <td><?php echo $hp['id']; ?></td>
                                <td><?php echo h($hp['title']); ?></td>
                                <td><span class="category-badge"><?php echo h($categoryLabels[$hp['category']] ?? $hp['category']); ?></span></td>
                                <td><?php echo $hp['sort_order']; ?></td>
                                <td>
                                    <?php if ($hp['is_published']): ?>
                                    <span class="status-badge published">已发布</span>
                                    <?php else: ?>
                                    <span class="status-badge draft">草稿</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo h($hp['updated_at']); ?></td>
                                <td class="actions">
                                    <a href="/admin/help.php?edit=<?php echo $hp['id']; ?>" class="btn btn-sm btn-primary">编辑</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('确定删除此文章?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $hp['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">删除</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($helpPages)): ?>
                            <tr><td colspan="7" style="text-align:center; color:#999;">暂无帮助文章</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const el = document.getElementById('currentTime');
            if (el) el.textContent = now.toLocaleString('zh-CN', { year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:false });
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>
