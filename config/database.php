<?php
/**
 * 数据库配置文件
 * 支持 MySQL 和 SQLite 两种数据库
 */

// =============================================
// 数据库类型选择: 'mysql' 或 'sqlite'
// =============================================
define('DB_TYPE', 'mysql');  // 当前使用MySQL数据库

// =============================================
// MySQL 数据库配置(如果选择mysql,请配置以下参数)
// =============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'hardware_tool');
define('DB_USER', 'root');
define('DB_PASS', 'heicat');  // 请修改为你的MySQL密码
define('DB_CHARSET', 'utf8mb4');

// =============================================
// SQLite 数据库配置(如果选择sqlite,无需修改)
// =============================================
define('SQLITE_DB_FILE', __DIR__ . '/../database/hardware_tool.db');

// =============================================
// 数据库连接函数
// =============================================
function getDB() {
    static $db = null;
    
    error_log("[DB] 开始获取数据库连接, 类型: " . DB_TYPE);
    
    if ($db === null) {
        try {
            if (DB_TYPE === 'sqlite') {
                error_log("[DB-SQLITE] 使用SQLite数据库");
                // SQLite 连接
                $dsn = 'sqlite:' . SQLITE_DB_FILE;
                
                // 兼容不同PHP版本的SQLite常量
                if (defined('Pdo\Sqlite::ATTR_OPEN_FLAGS')) {
                    // PHP 8.5+
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        Pdo\Sqlite::ATTR_OPEN_FLAGS => SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE
                    ];
                } else {
                    // PHP 8.5 以下
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_PERSISTENT => false
                    ];
                }
                
                $db = new PDO($dsn, null, null, $options);
                error_log("[DB-SQLITE] SQLite连接成功");
                
                // 启用外键约束
                $db->exec('PRAGMA foreign_keys = ON');
                
                // 确保数据库目录存在
                $dbDir = dirname(SQLITE_DB_FILE);
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }
                
            } else {
                error_log("[DB-MySQL] 连接MySQL: " . DB_HOST . "/" . DB_NAME);
                // MySQL 连接
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $db = new PDO($dsn, DB_USER, DB_PASS, $options);
                error_log("[DB-MySQL] MySQL连接成功");
            }
        } catch (PDOException $e) {
            // 生产环境不应该显示详细错误信息
            $errorMsg = "数据库连接失败: " . $e->getMessage();
            error_log("[DB-ERROR] " . $errorMsg);
            die($errorMsg);
        }
    }
    
    error_log("[DB] 返回数据库连接实例");
    return $db;
}
