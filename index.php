<?php
/**
 * 硬件调试工具站 - 前端入口
 * 路由分发器
 */
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// 静态资源直接返回，不经过PHP处理
$request_uri = $_SERVER['REQUEST_URI'];
if (preg_match('#^/assets/(css|js|images)/#', $request_uri)) {
    // 静态资源直接返回
    $file_path = __DIR__ . $request_uri;
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
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
        ];
        if (isset($mime_types[$ext])) {
            header('Content-Type: ' . $mime_types[$ext]);
            header('Cache-Control: public, max-age=2592000');
            readfile($file_path);
            exit;
        }
    }
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/functions.php';

// 获取请求的页面
$page = $_GET['page'] ?? 'home';

// 验证页面是否存在
$allowed_pages = ['home', 'zte', 'asr', 'unisoc', 'about', 'help'];
if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

// 记录页面访问统计
recordPageVisit($page);

// 设置当前页面
$current_page = $page;

// 加载对应页面
$page_file = __DIR__ . '/pages/' . $page . '.php';

if (file_exists($page_file)) {
    require_once $page_file;
} else {
    require_once __DIR__ . '/pages/home.php';
}
