<?php
/**
 * 数据库配置文件（纯 SQLite 版）
 *
 * 已移除 MySQL 依赖，全面使用 SQLite + WAL 模式。
 * 首次连接时自动建表并写入种子数据，实现零配置启动。
 * 兼容 PHP 7.4 ~ 8.5。
 *
 * 性能优化：
 *   - WAL 模式：读写并发，读不阻塞写
 *   - synchronous=NORMAL：WAL 下安全且快速
 *   - 64MB 内存缓存 + 256MB MMAP I/O
 *   - 临时表/排序在内存中完成
 *
 * @package HardwareDebugTool
 */

// =============================================
// SQLite 数据库文件路径
// =============================================
if (!defined('SQLITE_DB_FILE')) {
    define('SQLITE_DB_FILE', __DIR__ . '/../database/hardware_tool.db');
}

// =============================================
// 数据库连接函数（单例模式）
// =============================================

/**
 * 获取数据库连接（单例）
 *
 * 首次调用时创建 SQLite 数据库、应用 PRAGMA 优化、
 * 自动建表并写入种子数据。后续调用直接返回缓存实例。
 *
 * @return PDO 数据库连接实例
 */
function getDB() {
    static $db = null;

    if ($db !== null) {
        return $db;
    }

    // =============================================
    // 前置检查 1：PDO SQLite 驱动是否可用
    // =============================================
    if (!extension_loaded('pdo_sqlite')) {
        error_log('[DB-ERROR] PDO SQLite 扩展未安装');
        die('数据库连接失败：服务器未安装 PDO SQLite 扩展，请在 PHP 中启用 pdo_sqlite 和 sqlite3 扩展。');
    }

    // =============================================
    // 前置检查 2：数据库目录是否存在且可写
    // =============================================
    $dbDir = dirname(SQLITE_DB_FILE);
    if (!is_dir($dbDir)) {
        if (!@mkdir($dbDir, 0755, true)) {
            error_log('[DB-ERROR] 无法创建数据库目录: ' . $dbDir);
            die('数据库连接失败：无法创建数据库目录，请检查 ' . htmlspecialchars($dbDir) . ' 的父目录权限。');
        }
    }
    if (!is_writable($dbDir)) {
        error_log('[DB-ERROR] 数据库目录不可写: ' . $dbDir);
        die('数据库连接失败：数据库目录不可写，请将 ' . htmlspecialchars($dbDir) . ' 权限设为 755 或更高，并确保 Web 服务器用户有写入权限。');
    }

    // =============================================
    // 建立连接
    // =============================================
    try {
        $dsn = 'sqlite:' . SQLITE_DB_FILE;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT         => false,
        ];

        // PHP 8.4+ 可用 Pdo\Sqlite::ATTR_OPEN_FLAGS 设置打开模式
        if (version_compare(PHP_VERSION, '8.4', '>=')
            && defined('Pdo\Sqlite::ATTR_OPEN_FLAGS')
        ) {
            $options[Pdo\Sqlite::ATTR_OPEN_FLAGS] =
                SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
        }

        $db = new PDO($dsn, null, null, $options);

    } catch (PDOException $e) {
        error_log('[DB-ERROR] PDO 连接失败: ' . $e->getMessage());
        die('数据库连接失败：无法打开 SQLite 数据库文件，请检查文件路径和权限。');
    }

    // =============================================
    // PRAGMA 性能优化
    // =============================================
    try {
        // WAL 模式：读写并发，读不阻塞写（核心优化）
        $db->exec('PRAGMA journal_mode = WAL');
        // 同步级别 NORMAL：WAL 模式下安全且比 FULL 快 2-3 倍
        $db->exec('PRAGMA synchronous = NORMAL');
        // 64MB 内存缓存（负值表示 KB）
        $db->exec('PRAGMA cache_size = -65536');
        // 临时表和排序操作在内存中完成
        $db->exec('PRAGMA temp_store = MEMORY');
        // 256MB 内存映射 I/O，加速大表读取
        $db->exec('PRAGMA mmap_size = 268435456');
        // 启用外键约束
        $db->exec('PRAGMA foreign_keys = ON');
        // 忙碌超时 5 秒，避免并发写时立即报错
        $db->exec('PRAGMA busy_timeout = 5000');
    } catch (PDOException $e) {
        error_log('[DB-ERROR] PRAGMA 设置失败: ' . $e->getMessage());
        // PRAGMA 失败不中断，继续执行
    }

    // =============================================
    // 自动建表 + 种子数据（首次运行时执行）
    // =============================================
    try {
        require_once __DIR__ . '/../includes/schema.php';
        initDatabase($db);
    } catch (Exception $e) {
        error_log('[DB-ERROR] 数据库初始化失败: ' . $e->getMessage());
        die('数据库连接失败：数据库表初始化出错，请查看 error_log 获取详细信息。');
    }

    return $db;
}
