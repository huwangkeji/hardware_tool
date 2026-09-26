<?php
/**
 * 管理后台 - 访问统计
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/app.php';

requireAdmin();

$days = isset($_GET['days']) ? max(1, min(90, (int)$_GET['days'])) : 7;
$stats = getStatsOverview($days);

$current_admin_page = 'statistics';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>访问统计 - 管理后台</title>
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
        .page-header { margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        .page-header h1 { margin: 0; font-size: 24px; color: #333; }
        .day-selector a { padding: 6px 14px; border-radius: 6px; text-decoration: none; color: #555; background: #fff; margin-left: 8px; font-size: 14px; }
        .day-selector a.active { background: #1890ff; color: #fff; }
        .stat-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .stat-card .label { color: #999; font-size: 14px; margin-bottom: 8px; }
        .stat-card .value { font-size: 28px; font-weight: 600; color: #333; }
        .panel { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 24px; }
        .panel h2 { margin: 0 0 16px; font-size: 18px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        th { color: #999; font-weight: 500; }
        .chart-bar { display: inline-block; background: #1890ff; height: 20px; border-radius: 4px; min-width: 2px; vertical-align: middle; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <div class="page-header">
            <h1>访问统计</h1>
            <div class="day-selector">
                <?php foreach ([7, 14, 30, 90] as $d): ?>
                    <a href="?days=<?php echo $d; ?>" class="<?php echo $days === $d ? 'active' : ''; ?>"><?php echo $d; ?>天</a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="stat-cards">
            <div class="stat-card">
                <div class="label">总访问量</div>
                <div class="value"><?php echo number_format($stats['total_visits']); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">成功操作</div>
                <div class="value"><?php echo number_format($stats['total_success']); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">失败操作</div>
                <div class="value"><?php echo number_format($stats['total_fail']); ?></div>
            </div>
        </div>

        <div class="panel">
            <h2>每日访问趋势</h2>
            <table>
                <thead>
                    <tr><th>日期</th><th>访问量</th><th>趋势</th></tr>
                </thead>
                <tbody>
                    <?php
                    $maxVisits = 1;
                    foreach ($stats['daily_trend'] as $row) {
                        $maxVisits = max($maxVisits, (int)$row['visits']);
                    }
                    ?>
                    <?php foreach ($stats['daily_trend'] as $row): ?>
                    <tr>
                        <td><?php echo h($row['date']); ?></td>
                        <td><?php echo (int)$row['visits']; ?></td>
                        <td><span class="chart-bar" style="width: <?php echo (int)($row['visits'] / $maxVisits * 200); ?>px;"></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($stats['daily_trend'])): ?>
                    <tr><td colspan="3" style="text-align:center;color:#999;">暂无数据</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h2>设备操作统计</h2>
            <table>
                <thead>
                    <tr><th>设备类型</th><th>操作次数</th><th>成功次数</th><th>成功率</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['device_stats'] as $row): ?>
                    <tr>
                        <td><?php echo h($row['device_type']); ?></td>
                        <td><?php echo (int)$row['count']; ?></td>
                        <td><?php echo (int)$row['success']; ?></td>
                        <td><?php echo $row['count'] > 0 ? round($row['success'] / $row['count'] * 100, 1) . '%' : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($stats['device_stats'])): ?>
                    <tr><td colspan="4" style="text-align:center;color:#999;">暂无数据</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
