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
define('DB_NAME', 'demo_czkree_com');
define('DB_USER', 'demo_czkree_com');
define('DB_PASS', 'fPXesGpHZZ7bi5T4');  // 请修改为你的MySQL密码
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
    
    if ($db === null) {
        try {
            if (DB_TYPE === 'sqlite') {
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
                
                // 启用外键约束
                $db->exec('PRAGMA foreign_keys = ON');
                
                // 确保数据库目录存在
                $dbDir = dirname(SQLITE_DB_FILE);
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }
                
            } else {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $db = new PDO($dsn, DB_USER, DB_PASS, $options);
            }
        } catch (PDOException $e) {
            error_log("[DB-ERROR] " . $e->getMessage());
            die("数据库连接失败，请检查配置。");
        }
    }
    
    return $db;
}
