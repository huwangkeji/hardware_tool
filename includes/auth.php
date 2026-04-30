<?php
/**
 * 后台认证中间件 - 重构版本
 * 完全独立，不依赖外部配置
 */

// =============================================
// Session 管理（必须在最前面，独立处理）
// =============================================

// 设置 Session 保存到项目根目录下的 sessions
$sessionDir = __DIR__ . '/../sessions';
@mkdir($sessionDir, 0777, true);
if (is_dir($sessionDir)) {
    ini_set('session.save_path', $sessionDir);
}
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_strict_mode', 1);

// 启动 Session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 0,
        'cookie_secure' => false,         // 本地开发不需要HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

// =============================================
// 依赖引入
// =============================================

// 引入数据库配置（必须）
require_once __DIR__ . '/../config/database.php';

// 引入通用函数库
require_once __DIR__ . '/functions.php';

// =============================================
// 认证函数
// =============================================

/**
 * 检查是否已登录
 */
function requireLogin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // 未登录，重定向到登录页
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * 检查是否为管理员
 */
function requireAdmin() {
    requireLogin();
    if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
        // 权限不足，重定向到登录页
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * 用户登录
 */
function loginUser($username, $password) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // 登录成功，设置 Session
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = 'admin';
            
            // 更新最后登录时间
            try {
                $updateStmt = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
            } catch (Exception $e) {
                // 更新失败不影响登录
            }
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 用户登出
 */
function logoutUser() {
    // 清除所有 Session 数据
    $_SESSION = [];
    
    // 删除 Session Cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // 销毁 Session
    session_destroy();
    
    // 重定向到登录页
    header('Location: /admin/login.php');
    exit;
}

/**
 * 检查登录状态（不重定向，只返回布尔值）
 */
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * 获取当前登录用户信息
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? null,
        'role' => $_SESSION['admin_role'] ?? null,
    ];
}
