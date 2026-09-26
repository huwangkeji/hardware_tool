<?php
/**
 * 认证管理
 *
 * 提供 Session 初始化、用户登录/登出、权限检查等功能。
 *
 * @package HardwareDebugTool
 */

// =============================================
// Session 初始化
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    // 自动检测 HTTPS：代理环境下读取 X-Forwarded-Proto
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_start([
        'cookie_lifetime' => 0,
        'cookie_secure'   => $isHttps,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

// 引入依赖
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// =============================================
// 认证函数
// =============================================

/**
 * 用户登录
 *
 * @param string $username 用户名
 * @param string $password 密码
 * @return bool 登录是否成功
 */
function loginUser($username, $password) {
    // 登录限速：5 次失败后锁定 15 分钟
    $maxAttempts = 5;
    $lockoutTime = 900; // 15 分钟

    if (isset($_SESSION['login_fail_count']) && $_SESSION['login_fail_count'] >= $maxAttempts) {
        $elapsed = time() - ($_SESSION['login_lock_time'] ?? 0);
        if ($elapsed < $lockoutTime) {
            return false; // 锁定中
        }
        // 锁定过期，重置计数
        unset($_SESSION['login_fail_count'], $_SESSION['login_lock_time']);
    }

    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // 登录成功：防止 Session 固定攻击
            session_regenerate_id(true);

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id']   = $user['id'];
            $_SESSION['admin_username']  = $user['username'];

            // 更新最后登录时间
            $now = date('Y-m-d H:i:s');
            $db->prepare('UPDATE admin_users SET last_login = ? WHERE id = ?')
               ->execute([$now, $user['id']]);

            // 清除失败计数
            unset($_SESSION['login_fail_count'], $_SESSION['login_lock_time']);

            return true;
        }
    } catch (Exception $e) {
        error_log('[loginUser] ' . $e->getMessage());
    }

    // 登录失败：增加失败计数
    $_SESSION['login_fail_count'] = ($_SESSION['login_fail_count'] ?? 0) + 1;
    if ($_SESSION['login_fail_count'] >= $maxAttempts) {
        $_SESSION['login_lock_time'] = time();
    }

    // 时序攻击防护：无论用户是否存在都执行一次密码验证
    if (!isset($user)) {
        password_verify($password, '$2y$10$dummyHashForTimingAttackPreventionxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
    }

    return false;
}

/**
 * 用户登出
 */
function logoutUser() {
    session_regenerate_id(true);
    $_SESSION = [];
    session_destroy();
}

/**
 * 检查是否已登录
 *
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * 要求管理员登录，未登录则跳转登录页
 */
function requireAdmin() {
    if (!isAdmin()) {
        redirect('/admin/login.php');
    }
}
