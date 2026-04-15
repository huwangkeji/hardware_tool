<?php
/**
 * 统计数据API接口
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'record_visit':
            // 记录页面访问
            $pageName = $_POST['page_name'] ?? '';
            if (empty($pageName)) {
                throw new Exception('页面名称不能为空');
            }
            
            recordPageVisit($pageName);
            
            echo json_encode([
                'code' => 0,
                'message' => '记录成功',
                'data' => null
            ]);
            break;
            
        case 'record_serial_log':
            // 记录串口操作日志
            $deviceType = $_POST['device_type'] ?? '';
            $actionType = $_POST['action'] ?? '';
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            $portName = $_POST['port_name'] ?? '';
            $data = $_POST['data'] ?? '';
            
            if (empty($deviceType) || empty($actionType)) {
                throw new Exception('设备类型和操作类型不能为空');
            }
            
            recordSerialLog($deviceType, $actionType, $status, $portName, $data);
            
            echo json_encode([
                'code' => 0,
                'message' => '记录成功',
                'data' => null
            ]);
            break;
            
        case 'get_stats':
            // 获取统计数据
            $days = isset($_GET['days']) ? intval($_GET['days']) : 7;
            $stats = getStatsOverview($days);
            
            echo json_encode([
                'code' => 0,
                'message' => '获取成功',
                'data' => $stats
            ]);
            break;
            
        default:
            throw new Exception('无效的操作');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'code' => -1,
        'message' => $e->getMessage(),
        'data' => null
    ]);
}
