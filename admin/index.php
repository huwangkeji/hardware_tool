<?php
/**
 * 管理后台 - 控制台首页
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/app.php';

requireAdmin();

// 获取统计数据
$stats = getStatsOverview(7);

// 读取 flash 消息
$flash = $_SESSION['flash'] ?? null;
if ($flash) {
    unset($_SESSION['flash']);
}

$current_admin_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>控制台 - 管理后台</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { margin: 0; background: #f0f2f5; }
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-content { flex: 1; margin-left: 240px; padding: 24px; }
        .admin-sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 240px; background: #001529; color: #fff; display: flex; flex-direction: column; z-index: 100; }
        .admin-logo { padding: 20px; font-size: 18px; font-weight: bold; border-bottom: 1px solid #003a8c; }
        .admin-nav { flex: 1; padding: 12px 0; }
        .admin-nav .nav-item { display: flex; align-items: center; gap: 10px; padding: 12px 24px; color: rgba(255,255,255,0.65); text-decoration: none; transition: all 0.2s; }
        .admin-nav .nav-item:hover, .admin-nav .nav-item.active { color: #fff; background: #1890ff; }
        .admin-nav-bottom { padding: 12px 0; border-top: 1px solid #003a8c; }
        .logout-btn { display: flex; align-items: center; gap: 10px; padding: 12px 24px; color: rgba(255,255,255,0.65); }
        .logout-btn:hover { color: #ff4d4f; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { margin: 0; font-size: 24px; color: #333; }
        .stat-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .stat-card .label { color: #999; font-size: 14px; margin-bottom: 8px; }
        .stat-card .value { font-size: 28px; font-weight: 600; color: #333; }
        .stat-card .icon { float: right; font-size: 32px; opacity: 0.2; }
        .panel { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 24px; }
        .panel h2 { margin: 0 0 16px; font-size: 18px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        th { color: #999; font-weight: 500; }
        .flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .flash.success { background: #f6ffed; border: 1px solid #b7eb8f; color: #389e0d; }
        .flash.error { background: #fff2f0; border: 1px solid #ffccc7; color: #cf1322; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <div class="page-header">
            <h1>控制台</h1>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?php echo h($flash['type'] ?? 'success'); ?>"><?php echo h($flash['message'] ?? ''); ?></div>
        <?php endif; ?>

        <div class="stat-cards">
            <div class="stat-card">
                <span class="icon">👁️</span>
                <div class="label">7日总访问量</div>
                <div class="value"><?php echo number_format($stats['total_visits']); ?></div>
            </div>
            <div class="stat-card">
                <span class="icon">✅</span>
                <div class="label">7日成功操作</div>
                <div class="value"><?php echo number_format($stats['total_success']); ?></div>
            </div>
            <div class="stat-card">
                <span class="icon">❌</span>
                <div class="label">7日失败操作</div>
                <div class="value"><?php echo number_format($stats['total_fail']); ?></div>
            </div>
            <div class="stat-card">
                <span class="icon">📱</span>
                <div class="label">设备类型数</div>
                <div class="value"><?php echo count($stats['device_stats']); ?></div>
            </div>
        </div>

        <div class="panel">
            <h2>页面访问排名</h2>
            <table>
                <thead>
                    <tr><th>页面</th><th>访问量</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['page_ranking'] as $row): ?>
                    <tr>
                        <td><?php echo h($row['page_name']); ?></td>
                        <td><?php echo (int)$row['visits']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($stats['page_ranking'])): ?>
                    <tr><td colspan="2" style="text-align:center;color:#999;">暂无数据</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h2>最近操作日志</h2>
            <table>
                <thead>
                    <tr><th>时间</th><th>设备</th><th>操作</th><th>状态</th><th>端口</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['recent_logs'] as $log): ?>
                    <tr>
                        <td><?php echo h($log['created_at']); ?></td>
                        <td><?php echo h($log['device_type']); ?></td>
                        <td><?php echo h($log['action']); ?></td>
                        <td><?php echo $log['status'] ? '<span style="color:#52c41a;">成功</span>' : '<span style="color:#ff4d4f;">失败</span>'; ?></td>
                        <td><?php echo h($log['port_name']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($stats['recent_logs'])): ?>
                    <tr><td colspan="5" style="text-align:center;color:#999;">暂无数据</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
