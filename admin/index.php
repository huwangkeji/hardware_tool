<?php
/**
 * 后台仪表盘 - 统计概览
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
requireAdmin();

$settings = getSettings();
$stats = getStatsOverview(7);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - <?php echo h($settings['site_title']); ?></title>
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
                <a href="/admin/index.php" class="nav-item active">
                    <span class="icon">📊</span>
                    <span>数据概览</span>
                </a>
                <a href="/admin/settings.php" class="nav-item">
                    <span class="icon">⚙</span>
                    <span>网站设置</span>
                </a>
                <a href="/admin/statistics.php" class="nav-item">
                    <span class="icon">📈</span>
                    <span>详细统计</span>
                </a>
                <a href="/" class="nav-item">
                    <span class="icon">🏠</span>
                    <span>返回前台</span>
                </a>
                <a href="/admin/logout.php" class="nav-item logout">
                    <span class="icon">🚪</span>
                    <span>退出登录</span>
                </a>
            </nav>
        </aside>

        <!-- 主内容区 -->
        <main class="admin-main">
            <header class="admin-header">
                <div class="admin-header-left">
                    <h1>数据概览</h1>
                    <div class="breadcrumb">
                        <span>🏠</span>
                        <span class="separator">›</span>
                        <span class="current">Dashboard</span>
                        <span class="separator">›</span>
                        <span>数据统计</span>
                    </div>
                </div>
                <div class="admin-header-right">
                    <div class="header-time" id="currentTime"></div>
                    <div class="user-info">
                        <span><?php echo h($_SESSION['admin_username']); ?></span>
                        <span class="user-badge">管理员</span>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- 统计卡片 -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #1890ff 0%, #096dd9 100%);">
                            👁
                        </div>
                        <div class="stat-info">
                            <h3>总访问量</h3>
                            <p class="stat-value"><?php echo number_format($stats['overview']['total_visits'] ?? 0); ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #52c41a 0%, #389e0d 100%);">
                            ✓
                        </div>
                        <div class="stat-info">
                            <h3>成功连接</h3>
                            <p class="stat-value"><?php echo number_format($stats['overview']['total_success'] ?? 0); ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ff4d4f 0%, #cf1322 100%);">
                            ✗
                        </div>
                        <div class="stat-info">
                            <h3>失败次数</h3>
                            <p class="stat-value"><?php echo number_format($stats['overview']['total_fail'] ?? 0); ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #faad14 0%, #d48806 100%);">
                            %
                        </div>
                        <div class="stat-info">
                            <h3>成功率</h3>
                            <p class="stat-value">
                                <?php 
                                $total = ($stats['overview']['total_success'] ?? 0) + ($stats['overview']['total_fail'] ?? 0);
                                $rate = $total > 0 ? round(($stats['overview']['total_success'] / $total) * 100, 2) : 0;
                                echo $rate . '%';
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 页面统计 -->
                <div class="content-section">
                    <h2>各页面访问量</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>页面名称</th>
                                    <th>访问次数</th>
                                    <th>占比</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalVisits = array_sum(array_column($stats['page_stats'], 'visits'));
                                foreach ($stats['page_stats'] as $page): 
                                    $visits = $page['visits'] ?? 0;
                                    $percentage = $totalVisits > 0 ? round(($visits / $totalVisits) * 100, 2) : 0;
                                    $pageNames = [
                                        'home' => '首页',
                                        'zte' => '中兴微调试',
                                        'asr' => 'ASR调试',
                                        'unisoc' => '展锐调试'
                                    ];
                                ?>
                                <tr>
                                    <td><?php echo h($pageNames[$page['page_name']] ?? $page['page_name']); ?></td>
                                    <td><?php echo number_format($visits); ?></td>
                                    <td><?php echo $percentage; ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 访问趋势图表 -->
                <div class="content-section">
                    <h2>近7天访问趋势</h2>
                    <div class="chart-container">
                        <canvas id="visitChart"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // ============================================
        // 实时时钟
        // ============================================
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleString('zh-CN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            const clockElement = document.getElementById('currentTime');
            if (clockElement) {
                clockElement.textContent = timeStr;
            }
        }
        
        // 立即执行一次
        updateClock();
        // 每秒更新
        setInterval(updateClock, 1000);
        
        // ============================================
        // 调试输出 - 方便修复后台功能
        // ============================================
        console.log('%c===== 后台管理系统调试信息 =====', 'color: #1890ff; font-size: 16px; font-weight: bold;');
        
        // 1. 输出统计概览数据
        console.log('%c📊 统计概览数据:', 'color: #52c41a; font-size: 14px; font-weight: bold;');
        const statsOverview = <?php echo json_encode($stats['overview']); ?>;
        console.log('总访问量:', statsOverview.total_visits || 0);
        console.log('成功连接:', statsOverview.total_success || 0);
        console.log('失败次数:', statsOverview.total_fail || 0);
        
        // 2. 输出页面统计数据
        console.log('%c📄 页面统计数据:', 'color: #faad14; font-size: 14px; font-weight: bold;');
        const pageStats = <?php echo json_encode($stats['page_stats']); ?>;
        console.table(pageStats);
        
        // 3. 输出每日趋势数据
        console.log('%c📈 每日趋势数据:', 'color: #ff4d4f; font-size: 14px; font-weight: bold;');
        const dailyTrend = <?php echo json_encode($stats['daily_trend']); ?>;
        console.table(dailyTrend);
        
        // 4. 输出设置数据
        console.log('%c⚙ 网站设置:', 'color: #722ed1; font-size: 14px; font-weight: bold;');
        const siteSettings = <?php echo json_encode($settings); ?>;
        console.log('网站标题:', siteSettings.site_title);
        console.log('网站描述:', siteSettings.site_description);
        
        // 5. 输出 Session 信息
        console.log('%c🔐 Session 信息:', 'color: #13c2c2; font-size: 14px; font-weight: bold;');
        console.log('登录状态:', <?php echo isset($_SESSION['admin_logged_in']) ? 'true' : 'false'; ?>);
        console.log('用户名:', <?php echo isset($_SESSION['admin_username']) ? '"' . $_SESSION['admin_username'] . '"' : 'null'; ?>);
        console.log('角色:', <?php echo isset($_SESSION['admin_role']) ? '"' . $_SESSION['admin_role'] . '"' : 'null'; ?>);
        
        // 6. 数据验证
        console.log('%c✅ 数据验证:', 'color: #52c41a; font-size: 14px; font-weight: bold;');
        console.log('统计数据完整性:', !!statsOverview ? '✅ 正常' : '❌ 缺失');
        console.log('页面统计数量:', pageStats ? pageStats.length : '❌ 无数据');
        console.log('趋势数据数量:', dailyTrend ? dailyTrend.length : '❌ 无数据');
        console.log('%c===== 调试信息输出完成 =====', 'color: #1890ff; font-size: 16px; font-weight: bold;');
        console.log('\n');
        
        // ============================================
        // 渲染访问趋势图表
        // ============================================
        const chartData = <?php echo json_encode($stats['daily_trend']); ?>;
        
        console.log('图表数据:', chartData);
        
        if (chartData && chartData.length > 0) {
            const ctx = document.getElementById('visitChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.map(item => item.date),
                    datasets: [{
                        label: '访问量',
                        data: chartData.map(item => item.visits),
                        borderColor: '#1890ff',
                        backgroundColor: 'rgba(24, 144, 255, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            console.log('✅ 图表渲染成功');
        } else {
            console.warn('⚠️ 图表数据为空，跳过渲染');
        }
    </script>
</body>
</html>
