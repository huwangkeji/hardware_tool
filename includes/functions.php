<?php
/**
 * 全局工具函数库
 *
 * 提供 HTML 转义、设置读取、统计记录、AT 指令查询、
 * 芯片平台查询、帮助文章查询、CSRF 防护、HTML 消毒等功能。
 *
 * @package HardwareDebugTool
 */

// =============================================
// 基础工具
// =============================================

/**
 * 安全输出 HTML（htmlspecialchars 包装）
 *
 * @param mixed $string 待转义的字符串
 * @return string 转义后的字符串
 */
function h($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * 重定向辅助函数
 *
 * @param string $url 目标 URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * 获取客户端 IP 地址
 *
 * @return string
 */
function getClientIp() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($forwarded[0]);
    }
    return $ip;
}

// =============================================
// CSRF 防护
// =============================================

/**
 * 生成 CSRF Token 并存入 Session
 *
 * @return string
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 输出 CSRF 隐藏字段
 *
 * @return string
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . h($token) . '">';
}

/**
 * 验证 CSRF Token
 *
 * @return bool
 */
function verifyCSRF() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return !empty($sessionToken) && hash_equals($sessionToken, $token);
}

// =============================================
// HTML 消毒（白名单方式）
// =============================================

/**
 * 基于白名单的 HTML 消毒，防止存储型 XSS
 *
 * @param string $html 原始 HTML
 * @return string 消毒后的 HTML
 */
function sanitizeHTML($html) {
    if (empty($html)) {
        return '';
    }
    // 允许的标签及属性
    $allowedTags = '<p><br><strong><b><em><i><u><s><h1><h2><h3><h4><h5><h6>'
                 . '<ul><ol><li><a><img><table><thead><tbody><tr><td><th>'
                 . '<blockquote><code><pre><hr><div><span><sub><sup>';
    $result = strip_tags($html, $allowedTags);
    // 移除事件属性
    $result = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $result);
    // 移除 javascript: 协议
    $result = preg_replace('/(href|src)\s*=\s*["\']javascript:[^"\']*["\']/i', '', $result);
    return $result;
}

// =============================================
// 设置读写
// =============================================

/**
 * 获取网站设置（单例缓存）
 *
 * @return array
 */
function getSettings() {
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }
    try {
        $db = getDB();
        $stmt = $db->query('SELECT * FROM settings LIMIT 1');
        $row = $stmt->fetch();
        if ($row) {
            $settings = $row;
        } else {
            $settings = [
                'site_title'       => '硬件调试工具',
                'site_description' => '基于 Web Serial API 的在线硬件调试工具',
                'site_text_logo'   => '硬件调试工具',
                'about_content'    => '',
                'theme_color'      => '#1890ff',
                'contact_email'    => '',
                'contact_qq'       => '',
                'contact_qq_group' => '',
                'contact_doc_url'  => '',
                'douyin_text'      => '',
                'douyin_url'       => '',
                'bilibili_text'    => '',
                'bilibili_url'     => '',
                'footer_copyright' => '',
                'footer_version'   => '1.0.0',
                'core_features'    => '[]',
                'tech_features'    => '[]',
            ];
        }
    } catch (Exception $e) {
        $settings = [
            'site_title'       => '硬件调试工具',
            'site_description' => '基于 Web Serial API 的在线硬件调试工具',
            'site_text_logo'   => '硬件调试工具',
            'about_content'    => '',
            'theme_color'      => '#1890ff',
            'contact_email'    => '',
            'contact_qq'       => '',
            'contact_qq_group' => '',
            'contact_doc_url'  => '',
            'douyin_text'      => '',
            'douyin_url'       => '',
            'bilibili_text'    => '',
            'bilibili_url'     => '',
            'footer_copyright' => '',
            'footer_version'   => '1.0.0',
            'core_features'    => '[]',
            'tech_features'    => '[]',
        ];
    }
    return $settings;
}

// =============================================
// 统计相关
// =============================================

/**
 * 记录页面访问
 *
 * @param string $pageName 页面标识
 */
function recordPageVisit($pageName) {
    try {
        $db = getDB();
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $stmt = $db->prepare(
            'INSERT INTO page_stats (page_name, visit_count, date, last_visit)
             VALUES (?, 1, ?, ?)
             ON CONFLICT(page_name, date) DO UPDATE SET
                 visit_count = visit_count + 1,
                 last_visit = ?'
        );
        $stmt->execute([$pageName, $today, $now, $now]);
    } catch (Exception $e) {
        error_log('[recordPageVisit] ' . $e->getMessage());
    }
}

/**
 * 记录串口操作日志
 *
 * @param string $deviceType 设备类型
 * @param string $action     操作类型
 * @param int    $status     状态（1=成功, 0=失败）
 * @param string $data       数据内容
 * @param string $portName   端口名
 * @param string $errorMsg   错误信息
 */
function recordSerialLog($deviceType, $action, $status, $data, $portName, $errorMsg) {
    try {
        $db = getDB();
        $now = date('Y-m-d H:i:s');
        $ip = getClientIp();

        $stmt = $db->prepare(
            'INSERT INTO serial_logs
                (device_type, action, status, data, port_name, error_msg, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$deviceType, $action, $status, $data, $portName, $errorMsg, $ip, $now]);

        // 更新页面统计的成功/失败计数
        $today = date('Y-m-d');
        $field = $status ? 'success_count' : 'fail_count';
        $db->exec("UPDATE page_stats SET $field = $field + 1 WHERE page_name = " . $db->quote($deviceType) . " AND date = " . $db->quote($today));
    } catch (Exception $e) {
        error_log('[recordSerialLog] ' . $e->getMessage());
    }
}

/**
 * 获取统计数据概览
 *
 * @param int $days 统计天数
 * @return array
 */
function getStatsOverview($days = 7) {
    $db = getDB();
    $result = [
        'total_visits'   => 0,
        'total_success'  => 0,
        'total_fail'     => 0,
        'daily_trend'    => [],
        'page_ranking'   => [],
        'device_stats'   => [],
        'recent_logs'    => [],
    ];

    try {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        // 总计
        $stmt = $db->prepare(
            "SELECT
                SUM(visit_count) AS visits,
                SUM(success_count) AS success,
                SUM(fail_count) AS fail
             FROM page_stats
             WHERE date >= ?"
        );
        $stmt->execute([$startDate]);
        $totals = $stmt->fetch();
        $result['total_visits']  = (int)($totals['visits'] ?? 0);
        $result['total_success'] = (int)($totals['success'] ?? 0);
        $result['total_fail']    = (int)($totals['fail'] ?? 0);

        // 每日趋势
        $stmt = $db->prepare(
            "SELECT date, SUM(visit_count) AS visits
             FROM page_stats
             WHERE date >= ?
             GROUP BY date
             ORDER BY date ASC"
        );
        $stmt->execute([$startDate]);
        $result['daily_trend'] = $stmt->fetchAll();

        // 页面排名
        $stmt = $db->prepare(
            "SELECT page_name, SUM(visit_count) AS visits
             FROM page_stats
             WHERE date >= ?
             GROUP BY page_name
             ORDER BY visits DESC
             LIMIT 10"
        );
        $stmt->execute([$startDate]);
        $result['page_ranking'] = $stmt->fetchAll();

        // 设备统计
        $stmt = $db->prepare(
            "SELECT device_type, COUNT(*) AS count,
                    SUM(status) AS success
             FROM serial_logs
             WHERE created_at >= ?
             GROUP BY device_type
             ORDER BY count DESC"
        );
        $stmt->execute([$startDate . ' 00:00:00']);
        $result['device_stats'] = $stmt->fetchAll();

        // 最近日志
        $stmt = $db->prepare(
            "SELECT * FROM serial_logs
             WHERE created_at >= ?
             ORDER BY id DESC
             LIMIT 20"
        );
        $stmt->execute([$startDate . ' 00:00:00']);
        $result['recent_logs'] = $stmt->fetchAll();

    } catch (Exception $e) {
        error_log('[getStatsOverview] ' . $e->getMessage());
    }

    return $result;
}

// =============================================
// 芯片平台
// =============================================

/**
 * 获取芯片平台列表
 *
 * @param bool $activeOnly 是否只返回启用的平台
 * @return array
 */
function getChipPlatforms($activeOnly = false) {
    try {
        $db = getDB();
        $sql = 'SELECT * FROM chip_platforms';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('[getChipPlatforms] ' . $e->getMessage());
        return [];
    }
}

// =============================================
// AT 指令
// =============================================

/**
 * 获取 AT 指令列表
 *
 * @param string|null $chipType      芯片类型过滤
 * @param string|null $category      分类过滤
 * @param bool        $publishedOnly 仅返回已发布
 * @param string|null $search        搜索关键词
 * @return array
 */
function getATCommands($chipType = null, $category = null, $publishedOnly = false, $search = null) {
    try {
        $db = getDB();
        $sql = 'SELECT * FROM at_commands WHERE 1=1';
        $params = [];

        if ($chipType !== null) {
            $sql .= ' AND chip_type = ?';
            $params[] = $chipType;
        }
        if ($category !== null) {
            $sql .= ' AND category = ?';
            $params[] = $category;
        }
        if ($publishedOnly) {
            $sql .= ' AND is_published = 1';
        }
        if ($search !== null) {
            $sql .= ' AND (command LIKE ? OR title LIKE ? OR description LIKE ?)';
            $kw = "%{$search}%";
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('[getATCommands] ' . $e->getMessage());
        return [];
    }
}

/**
 * 获取 AT 指令分类列表
 *
 * @param string|null $chipType 芯片类型过滤
 * @return array
 */
function getATCommandCategories($chipType = null) {
    try {
        $db = getDB();
        $sql = 'SELECT DISTINCT category FROM at_commands WHERE category != ""';
        $params = [];
        if ($chipType !== null) {
            $sql .= ' AND chip_type = ?';
            $params[] = $chipType;
        }
        $sql .= ' ORDER BY category ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log('[getATCommandCategories] ' . $e->getMessage());
        return [];
    }
}

/**
 * 统计 AT 指令总数
 *
 * @return int
 */
function countATCommands() {
    try {
        $db = getDB();
        $stmt = $db->query('SELECT COUNT(*) FROM at_commands WHERE is_published = 1');
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// =============================================
// 帮助文章
// =============================================

/**
 * 获取帮助文章列表
 *
 * @param string|null $category      分类过滤
 * @param bool        $publishedOnly 仅返回已发布
 * @return array
 */
function getHelpArticles($category = null, $publishedOnly = false) {
    try {
        $db = getDB();
        $sql = 'SELECT * FROM help_pages WHERE 1=1';
        $params = [];

        if ($category !== null) {
            $sql .= ' AND category = ?';
            $params[] = $category;
        }
        if ($publishedOnly) {
            $sql .= ' AND is_published = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('[getHelpArticles] ' . $e->getMessage());
        return [];
    }
}

/**
 * 获取单篇帮助文章
 *
 * @param int $id 文章 ID
 * @return array|null
 */
function getHelpArticle($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM help_pages WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

// =============================================
// 静态资源服务（带路径遍历防护）
// =============================================

/**
 * 安全地输出静态资源文件
 *
 * @param string $relativePath 相对于项目根的路径
 */
function serveStaticAsset($relativePath) {
    $root = dirname(__DIR__);
    $realPath = realpath($root . '/' . ltrim($relativePath, '/'));

    // 路径遍历防护：确保解析后的路径仍在项目根下
    if ($realPath === false || strpos($realPath, $root) !== 0) {
        http_response_code(404);
        exit('Not Found');
    }

    if (!is_file($realPath)) {
        http_response_code(404);
        exit('Not Found');
    }

    $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
    ];

    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
        header('Cache-Control: public, max-age=2592000');
        readfile($realPath);
        exit;
    }

    http_response_code(404);
    exit('Not Found');
}
