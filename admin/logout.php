<?php
/**
 * 管理后台 - 登出
 *
 * 仅接受 POST 请求，防止 CSRF（GET 链接登出不安全）
 */
require_once __DIR__ . '/../includes/auth.php';

// 仅允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/index.php');
}

// 验证 CSRF Token
if (!verifyCSRF()) {
    redirect('/admin/index.php');
}

logoutUser();
redirect('/admin/login.php');
