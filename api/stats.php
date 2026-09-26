<?php
/**
 * 统计数据 API 接口
 *
 * 提供页面访问记录、串口操作日志记录、统计数据查询三个接口。
 * 前端通过 fetch + JSON 调用，本接口同时兼容 JSON body 和传统 form-urlencoded。
 *
 * @package HardwareDebugTool
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

/**
 * 解析请求参数：兼容 JSON body 和传统 $_POST
 *
 * @return array 合并后的参数数组
 */
function parseRequestBody() {
    // 尝试解析 JSON body（前端 fetch 发送 application/json 时 $_POST 为空）
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $json = json_decode($input, true);
        if (is_array($json)) {
            return array_merge($_GET, $_POST, $json);
        }
    }
    return array_merge($_GET, $_POST);
}

// 允许的设备类型白名单
$allowedDeviceTypes = ['zte', 'asr', 'unisoc'];
// 允许的页面名称白名单
$allowedPageNames = ['home', 'zte', 'asr', 'unisoc', 'about', 'help'];

try {
    $params = parseRequestBody();
    $action = $params['action'] ?? '';

    switch ($action) {
        case 'record_visit':
            // 记录页面访问
            $pageName = trim($params['page_name'] ?? '');
            if (empty($pageName)) {
                throw new Exception('页面名称不能为空');
            }
            // 白名单校验，防止注入非法页面名
            if (!in_array($pageName, $allowedPageNames, true)) {
                throw new Exception('无效的页面名称');
            }

            recordPageVisit($pageName);

            echo json_encode([
                'code'    => 0,
                'message' => '记录成功',
                'data'    => null,
            ]);
            break;

        case 'record_serial_log':
            // 记录串口操作日志
            $deviceType = trim($params['device_type'] ?? '');
            $actionType = trim($params['action'] ?? '');
            $status     = isset($params['status']) ? intval($params['status']) : 1;
            $portName   = trim($params['port_name'] ?? '');
            $data       = $params['data'] ?? '';
            $errorMsg   = trim($params['error'] ?? '');

            if (empty($deviceType) || empty($actionType)) {
                throw new Exception('设备类型和操作类型不能为空');
            }
            // 设备类型白名单校验
            if (!in_array($deviceType, $allowedDeviceTypes, true)) {
                throw new Exception('无效的设备类型');
            }
            // 限制 data 字段长度，防止超大 payload
            if (strlen($data) > 10000) {
                $data = substr($data, 0, 10000);
            }

            // 参数顺序: ($deviceType, $action, $status, $data, $portName, $errorMsg)
            recordSerialLog($deviceType, $actionType, $status, $data, $portName, $errorMsg);

            echo json_encode([
                'code'    => 0,
                'message' => '记录成功',
                'data'    => null,
            ]);
            break;

        case 'get_stats':
            // 获取统计数据
            $days  = isset($params['days']) ? intval($params['days']) : 7;
            if ($days < 1 || $days > 90) {
                $days = 7;
            }
            $stats = getStatsOverview($days);

            echo json_encode([
                'code'    => 0,
                'message' => '获取成功',
                'data'    => $stats,
            ]);
            break;

        default:
            throw new Exception('无效的操作');
    }
} catch (PDOException $e) {
    error_log('[API/stats] DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'code'    => -1,
        'message' => '服务器内部错误，请稍后重试。',
        'data'    => null,
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'code'    => -1,
        'message' => $e->getMessage(),
        'data'    => null,
    ]);
}
