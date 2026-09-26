<?php
/**
 * 管理后台侧边栏
 *
 * 可用变量：$current_admin_page  当前页面标识
 */
$current_admin_page = $current_admin_page ?? '';
?>
<aside class="admin-sidebar">
    <div class="admin-logo">
        <span class="logo-icon">🔧</span>
        <span class="logo-text">硬件调试工具</span>
    </div>
    <nav class="admin-nav">
        <a href="/admin/index.php" class="nav-item <?php echo $current_admin_page === 'dashboard' ? 'active' : ''; ?>">
            <span class="icon">📊</span> <span>控制台</span>
        </a>
        <a href="/admin/statistics.php" class="nav-item <?php echo $current_admin_page === 'statistics' ? 'active' : ''; ?>">
            <span class="icon">📈</span> <span>访问统计</span>
        </a>
        <a href="/admin/at-commands.php" class="nav-item <?php echo $current_admin_page === 'at-commands' ? 'active' : ''; ?>">
            <span class="icon">📋</span> <span>AT指令管理</span>
        </a>
        <a href="/admin/help.php" class="nav-item <?php echo $current_admin_page === 'help' ? 'active' : ''; ?>">
            <span class="icon">📖</span> <span>帮助文章</span>
        </a>
        <a href="/admin/settings.php" class="nav-item <?php echo $current_admin_page === 'settings' ? 'active' : ''; ?>">
            <span class="icon">⚙️</span> <span>网站设置</span>
        </a>
        <a href="/" class="nav-item" target="_blank">
            <span class="icon">🌐</span> <span>查看前台</span>
        </a>
    </nav>
    <div class="admin-nav-bottom">
        <form method="post" action="/admin/logout.php" style="display:inline;">
            <?php echo csrfField(); ?>
            <button type="submit" class="nav-item logout-btn" style="border:none;background:none;cursor:pointer;width:100%;text-align:left;">
                <span class="icon">🚪</span> <span>退出登录</span>
            </button>
        </form>
    </div>
</aside>
