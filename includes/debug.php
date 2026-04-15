<?php
/**
 * 调试工具类 - 输出到浏览器控制台和日志文件
 * 用于开发环境快速定位问题
 */

class Debug {
    private static $enabled = true;
    private static $logFile = null;
    private static $startTime = null;
    private static $timers = [];
    
    /**
     * 初始化调试系统
     */
    public static function init($enabled = true, $logPath = null) {
        self::$enabled = $enabled;
        self::$startTime = microtime(true);
        
        if ($logPath) {
            self::$logFile = $logPath;
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
        }
        
        if ($enabled) {
            self::console('🚀 调试系统已启动', 'info', 'SYSTEM');
            self::console('PHP版本: ' . PHP_VERSION, 'debug', 'SYSTEM');
        }
    }
    
    /**
     * 输出到浏览器控制台
     */
    public static function console($message, $level = 'log', $label = 'DEBUG') {
        if (!self::$enabled) return;
        
        if (headers_sent()) return;
        
        $timestamp = date('H:i:s');
        $prefix = "[{$label} {$timestamp}]";
        
        // 转换JavaScript console方法
        $jsMethod = match($level) {
            'error' => 'error',
            'warn' => 'warn',
            'info' => 'info',
            default => 'log'
        };
        
        // 处理不同类型的数据
        if (is_array($message) || is_object($message)) {
            $output = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            echo "<script>console.{$jsMethod}('{$prefix}', " . json_encode($message, JSON_UNESCAPED_UNICODE) . ");</script>";
        } else {
            $escapedMessage = addslashes($message);
            echo "<script>console.{$jsMethod}('{$prefix} {$escapedMessage}');</script>";
        }
        
        // 同时写入日志文件
        self::writeLog($message, $level, $label);
    }
    
    /**
     * 快速调试 - dump数据
     */
    public static function dump($data, $label = 'DUMP') {
        self::console($data, 'info', $label);
    }
    
    /**
     * 错误输出
     */
    public static function error($message, $label = 'ERROR') {
        self::console($message, 'error', $label);
    }
    
    /**
     * 警告输出
     */
    public static function warn($message, $label = 'WARNING') {
        self::console($message, 'warn', $label);
    }
    
    /**
     * 信息输出
     */
    public static function info($message, $label = 'INFO') {
        self::console($message, 'info', $label);
    }
    
    /**
     * 追踪调用栈
     */
    public static function trace($label = 'TRACE') {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $output = "调用栈:\n";
        foreach ($trace as $i => $frame) {
            $file = isset($frame['file']) ? basename($frame['file']) : '[internal]';
            $line = $frame['line'] ?? '?';
            $func = $frame['function'] ?? 'main';
            $output .= "#{$i} {$file}:{$line} -> {$func}()\n";
        }
        self::console($output, 'info', $label);
    }
    
    /**
     * 开始计时
     */
    public static function timerStart($name = 'default') {
        self::$timers[$name] = microtime(true);
    }
    
    /**
     * 结束计时
     */
    public static function timerEnd($name = 'default') {
        if (!isset(self::$timers[$name])) return;
        
        $elapsed = round((microtime(true) - self::$timers[$name]) * 1000, 2);
        self::console("⏱ 耗时: {$elapsed}ms", 'info', "TIMER:{$name}");
        unset(self::$timers[$name]);
    }
    
    /**
     * 检查数据库连接
     */
    public static function checkDB($db = null) {
        if (!self::$enabled) return;
        
        try {
            self::info("数据库类型: " . DB_TYPE, 'DB');
            
            if (DB_TYPE === 'sqlite') {
                $dbFile = SQLITE_DB_FILE;
                self::info("SQLite文件: {$dbFile}", 'DB');
                if (file_exists($dbFile)) {
                    $size = round(filesize($dbFile) / 1024, 2);
                    self::info("文件大小: {$size} KB", 'DB');
                }
            } else {
                self::info("MySQL: " . DB_NAME . "@" . DB_HOST, 'DB');
            }
            
            if ($db) {
                $stmt = $db->query(DB_TYPE === 'sqlite' 
                    ? "SELECT name FROM sqlite_master WHERE type='table'"
                    : "SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                self::info("数据表数量: " . count($tables), 'DB');
                self::info("数据表: " . implode(', ', $tables), 'DB');
            }
        } catch (Exception $e) {
            self::error("数据库错误: " . $e->getMessage(), 'DB');
        }
    }
    
    /**
     * 检查Session状态
     */
    public static function checkSession() {
        if (!self::$enabled) return;
        
        $status = match(session_status()) {
            PHP_SESSION_ACTIVE => '已启动',
            PHP_SESSION_NONE => '未启动',
            default => '未知'
        };
        
        self::info("Session状态: {$status}", 'SESSION');
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::info("Session ID: " . session_id(), 'SESSION');
            if (!empty($_SESSION)) {
                self::info("Session数据: " . json_encode($_SESSION, JSON_UNESCAPED_UNICODE), 'SESSION');
            }
        }
    }
    
    /**
     * 性能统计
     */
    public static function performance() {
        if (!self::$enabled || !self::$startTime) return;
        
        $time = round((microtime(true) - self::$startTime) * 1000, 2);
        $memory = round(memory_get_peak_usage() / 1024 / 1024, 2);
        
        self::console("⚡ 性能: 耗时{$time}ms | 内存{$memory}MB", 'info', 'PERF');
    }
    
    /**
     * 写入日志文件
     */
    private static function writeLog($message, $level, $label) {
        if (!self::$logFile) return;
        
        $timestamp = date('Y-m-d H:i:s');
        $levelStr = strtoupper($level);
        $logMessage = "[{$timestamp}] [{$levelStr}] [{$label}] ";
        
        if (is_array($message) || is_object($message)) {
            $logMessage .= json_encode($message, JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            $logMessage .= $message . "\n";
        }
        
        error_log($logMessage, 3, self::$logFile);
    }
}

/**
 * 快捷函数: 打印并终止
 */
if (!function_exists('dd')) {
    function dd($data) {
        Debug::dump($data, 'DUMP & DIE');
        echo '<pre style="background:#1e1e1e;color:#fff;padding:10px;margin:10px;">';
        var_dump($data);
        echo '</pre>';
        exit;
    }
}

/**
 * 快捷函数: 仅打印
 */
if (!function_exists('dump')) {
    function dump($data) {
        Debug::dump($data, 'DUMP');
    }
}

/**
 * 快捷函数: 打印变量
 */
if (!function_exists('db')) {
    function db($data, $label = 'DB') {
        Debug::dump($data, $label);
    }
}
