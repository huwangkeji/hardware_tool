<?php
/**
 * 应用配置文件
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

// Session配置 - 简化版本，不再在app.php中启动Session
// Session 将由 auth.php 独立管理

// 引入调试工具
require_once ROOT_PATH . '/includes/debug.php';
Debug::init(true, ROOT_PATH . '/logs/debug.log');  // 开启调试，日志保存到/logs/debug.log

Debug::info('App配置加载完成', 'APP');
Debug::checkSession();
Debug::info('Session路径: ' . ini_get('session.save_path'), 'SESSION');
Debug::info('PHP版本: ' . PHP_VERSION, 'SYSTEM');

// 错误报告(生产环境请关闭)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 时区设置
date_default_timezone_set('Asia/Shanghai');
