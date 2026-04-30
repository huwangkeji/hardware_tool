<?php
/**
 * 后台管理 - 访问统计
 */
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

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

// 获取统计数据
$days = isset($_GET['days']) ? intval($_GET['days']) : 7;
if ($days < 1 || $days > 90) {
    $days = 7;
}

$stats = getStatsOverview($days);

// 获取串口操作日志
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM serial_logs ORDER BY created_at DESC LIMIT 50");
    $stmt->execute();
    $serialLogs = $stmt->fetchAll();
} catch (PDOException $e) {
    $serialLogs = [];
}

$settings = getSettings();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>访问统计 - <?php echo htmlspecialchars($settings['site_title']); ?></title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <script src="/assets/js/chart.min.js"></script>
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
                <a href="/admin/settings.php" class="nav-item">
                    <span>⚙️</span>
                    <span>网站设置</span>
                </a>
                <a href="/admin/help.php" class="nav-item">
                    <span>📖</span>
                    <span>帮助文章</span>
                </a>
                <a href="/admin/statistics.php" class="nav-item active">
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
                    <h1>访问统计</h1>
                    <div class="breadcrumb">
                        <span>🏠</span>
                        <span class="separator">›</span>
                        <span class="current">Dashboard</span>
                        <span class="separator">›</span>
                        <span>访问统计</span>
                    </div>
                </div>
                <div class="admin-header-right">
                    <div class="header-time" id="currentTime"></div>
                    <div class="user-info">
                        <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></span>
                        <span class="user-badge">管理员</span>
                    </div>
                    <div style="margin-left: 15px;">
                        <label for="daysSelect" style="margin-right: 10px; color: #666;">时间范围:</label>
                        <select id="daysSelect" onchange="changeDays(this.value)" 
                                style="padding: 8px 12px; border: 1px solid #e8e8e8; border-radius: 6px;">
                            <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>近7天</option>
                            <option value="14" <?php echo $days == 14 ? 'selected' : ''; ?>>近14天</option>
                            <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>近30天</option>
                            <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>近90天</option>
                        </select>
                    </div>
                </div>
            </header>
            
            <div class="admin-content">
                <!-- 统计概览卡片 -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>总访问量</h3>
                        <p class="stat-value"><?php echo number_format($stats['overview']['total_visits'] ?? 0); ?></p>
                        <p class="stat-change">所有页面累计访问次数</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>成功连接数</h3>
                        <p class="stat-value" style="color: var(--success-color);">
                            <?php echo number_format($stats['overview']['success_count'] ?? 0); ?>
                        </p>
                        <p class="stat-change positive">串口连接成功次数</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>失败次数</h3>
                        <p class="stat-value" style="color: var(--danger-color);">
                            <?php echo number_format($stats['overview']['fail_count'] ?? 0); ?>
                        </p>
                        <p class="stat-change negative">串口连接失败次数</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>成功率</h3>
                        <p class="stat-value" style="color: var(--primary-dark);">
                            <?php echo number_format($stats['overview']['success_rate'] ?? 0, 2); ?>%
                        </p>
                        <p class="stat-change">串口连接成功率</p>
                    </div>
                </div>

                <!-- 各页面访问统计 -->
                <h2 style="margin: 30px 0 20px; color: var(--text-primary);">各页面访问统计</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>页面名称</th>
                            <th>访问次数</th>
                            <th>成功连接</th>
                            <th>失败次数</th>
                            <th>成功率</th>
                            <th>最后访问</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stats['page_stats'])): ?>
                            <?php foreach ($stats['page_stats'] as $page): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($page['page_name']); ?></td>
                                    <td><?php echo number_format($page['visit_count']); ?></td>
                                    <td style="color: var(--success-color);">
                                        <?php echo number_format($page['success_count']); ?>
                                    </td>
                                    <td style="color: var(--danger-color);">
                                        <?php echo number_format($page['fail_count']); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $rate = $page['success_count'] + $page['fail_count'] > 0 
                                            ? ($page['success_count'] / ($page['success_count'] + $page['fail_count'])) * 100 
                                            : 0;
                                        echo number_format($rate, 2) . '%';
                                        ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($page['last_visit'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-secondary);">
                                    暂无数据
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- 访问趋势图表 -->
                <h2 style="margin: 30px 0 20px; color: var(--text-primary);">访问趋势</h2>
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>

                <!-- 串口操作日志 -->
                <h2 style="margin: 30px 0 20px; color: var(--text-primary);">最近串口操作日志</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>设备类型</th>
                            <th>端口</th>
                            <th>操作</th>
                            <th>状态</th>
                            <th>时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($serialLogs)): ?>
                            <?php foreach ($serialLogs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['device_type']); ?></td>
                                    <td><?php echo htmlspecialchars($log['port_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td>
                                        <?php if ($log['status'] == 1): ?>
                                            <span style="color: var(--success-color);">✓ 成功</span>
                                        <?php else: ?>
                                            <span style="color: var(--danger-color);">✗ 失败</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-secondary);">
                                    暂无日志记录
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // 切换时间范围
        function changeDays(days) {
            window.location.href = '?days=' + days;
        }

        // 渲染趋势图表
        const chartData = <?php echo json_encode($stats['daily_trend']); ?>;
        
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.map(item => item.date),
                datasets: [
                    {
                        label: '访问量',
                        data: chartData.map(item => item.visits),
                        borderColor: '#1890ff',
                        backgroundColor: 'rgba(24, 144, 255, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: '成功连接',
                        data: chartData.map(item => item.success || 0),
                        borderColor: '#52c41a',
                        backgroundColor: 'rgba(82, 196, 26, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: '失败次数',
                        data: chartData.map(item => item.fail || 0),
                        borderColor: '#ff4d4f',
                        backgroundColor: 'rgba(255, 77, 79, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
