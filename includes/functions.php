<?php
/**
 * 通用函数库
 */

// 引入数据库配置
require_once __DIR__ . '/../config/database.php';

/**
 * 安全输出HTML
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * 重定向
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * JSON响应
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 获取客户端IP
 */
function getClientIP() {
    $ip = '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * 获取网站设置
 */
function getSettings() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM settings WHERE id = 1");
    return $stmt->fetch();
}

/**
 * 更新网站设置
 */
function updateSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE settings SET {$key} = ? WHERE id = 1");
    return $stmt->execute([$value]);
}

/**
 * 记录页面访问
 */
function recordPageVisit($pageName) {
    try {
        $db = getDB();
        $today = date('Y-m-d');
        $stmt = $db->prepare("
            INSERT INTO page_stats (page_name, visit_count, last_visit, date) 
            VALUES (?, 1, NOW(), ?) 
            ON DUPLICATE KEY UPDATE 
            visit_count = visit_count + 1,
            last_visit = NOW()
        ");
        $stmt->execute([$pageName, $today]);
    } catch (Exception $e) {
        // 记录失败不影响页面访问
    }
}

/**
 * 记录串口操作
 */
function recordSerialLog($deviceType, $action, $status = 1, $data = '', $portName = '', $errorMsg = '') {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO serial_logs (device_type, action, status, data, port_name, error_msg, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $deviceType,
            $action,
            $status,
            $data,
            $portName,
            $errorMsg,
            getClientIP()
        ]);
        
        // 更新统计数据
        $today = date('Y-m-d');
        if ($status == 1) {
            $db->prepare("
                INSERT INTO page_stats (page_name, success_count, date) 
                VALUES (?, 1, ?) 
                ON DUPLICATE KEY UPDATE success_count = success_count + 1
            ")->execute([$deviceType, $today]);
        } else {
            $db->prepare("
                INSERT INTO page_stats (page_name, fail_count, date) 
                VALUES (?, 1, ?) 
                ON DUPLICATE KEY UPDATE fail_count = fail_count + 1
            ")->execute([$deviceType, $today]);
        }
    } catch (Exception $e) {
        // 记录失败不影响主流程
    }
}

/**
 * 获取统计概览数据
 */
function getStatsOverview($days = 7) {
    $db = getDB();
    $startDate = date('Y-m-d', strtotime("-{$days} days"));
    
    // 总访问量
    $stmt = $db->prepare("
        SELECT 
            SUM(visit_count) as total_visits,
            SUM(success_count) as total_success,
            SUM(fail_count) as total_fail
        FROM page_stats 
        WHERE date >= ?
    ");
    $stmt->execute([$startDate]);
    $overview = $stmt->fetch();
    
    // 计算成功率
    $totalOperations = ($overview['total_success'] ?? 0) + ($overview['total_fail'] ?? 0);
    $overview['success_rate'] = $totalOperations > 0 
        ? round(($overview['total_success'] / $totalOperations) * 100, 2)
        : 0;
    
    // 按页面统计
    $stmt = $db->prepare("
        SELECT 
            page_name, 
            SUM(visit_count) as visit_count,
            SUM(success_count) as success_count,
            SUM(fail_count) as fail_count,
            MAX(last_visit) as last_visit
        FROM page_stats 
        WHERE date >= ? 
        GROUP BY page_name 
        ORDER BY visit_count DESC
    ");
    $stmt->execute([$startDate]);
    $pageStats = $stmt->fetchAll();
    
    // 每日趋势
    $stmt = $db->prepare("
        SELECT 
            date, 
            SUM(visit_count) as visits,
            SUM(success_count) as success,
            SUM(fail_count) as fail
        FROM page_stats 
        WHERE date >= ? 
        GROUP BY date 
        ORDER BY date
    ");
    $stmt->execute([$startDate]);
    $dailyTrend = $stmt->fetchAll();
    
    return [
        'overview' => $overview,
        'page_stats' => $pageStats,
        'daily_trend' => $dailyTrend
    ];
}
