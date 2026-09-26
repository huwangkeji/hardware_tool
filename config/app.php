<?php
/**
 * 应用配置文件
 *
 * 全局常量、路径定义、调试开关与错误报告配置。
 * 生产环境请将 APP_DEBUG 设为 false。
 *
 * @package HardwareDebugTool
 */

// 站点基础配置
define('SITE_NAME', '硬件调试工具');
define('SITE_URL', 'http://localhost');
define('VERSION', '1.0.0');

// 路径配置
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('API_PATH', ROOT_PATH . '/api');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// 调试开关：生产环境设为 false
// 也可通过环境变量覆盖：putenv('APP_DEBUG=false')
if (!defined('APP_DEBUG')) {
    $envDebug = getenv('APP_DEBUG');
    // 生产安全：默认关闭调试，仅通过环境变量 APP_DEBUG=true 显式开启
    define('APP_DEBUG', $envDebug === false ? false : filter_var($envDebug, FILTER_VALIDATE_BOOLEAN));
}

// Session配置 - 简化版本，不再在app.php中启动Session
// Session 将由 auth.php 独立管理

// 引入调试工具
require_once ROOT_PATH . '/includes/debug.php';
Debug::init(APP_DEBUG, ROOT_PATH . '/logs/debug.log');

if (APP_DEBUG) {
    Debug::info('App配置加载完成', 'APP');
    Debug::checkSession();
    Debug::info('Session路径: ' . ini_get('session.save_path'), 'SESSION');
    Debug::info('PHP版本: ' . PHP_VERSION, 'SYSTEM');
}

// 错误报告：根据调试开关区分开发/生产环境
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// 时区设置
date_default_timezone_set('Asia/Shanghai');
