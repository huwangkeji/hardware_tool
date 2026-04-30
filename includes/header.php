<?php
/**
 * 公共头部模板
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

// 获取网站设置
$settings = getSettings();

// 记录页面访问
if (!isset($skip_stats)) {
    recordPageVisit($current_page ?? 'home');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($settings['site_title']); ?></title>
    <meta name="description" content="<?php echo h($settings['site_description']); ?>">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo h($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <style>
        :root {
            --theme-color: <?php echo h($settings['theme_color']); ?>;
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="/" class="text-logo">
                        <?php 
                        // 优先使用自定义文字标识，否则使用默认
                        $logoText = !empty($settings['site_text_logo']) ? $settings['site_text_logo'] : '硬件调试工具';
                        echo h($logoText);
                        ?>
                    </a>
                </div>
                <nav class="main-nav">
                    <ul>
                        <li><a href="/" class="<?php echo ($current_page ?? '') === 'home' ? 'active' : ''; ?>">首页</a></li>
                        <li><a href="/?page=zte" class="<?php echo ($current_page ?? '') === 'zte' ? 'active' : ''; ?>">中兴微</a></li>
                        <li><a href="/?page=asr" class="<?php echo ($current_page ?? '') === 'asr' ? 'active' : ''; ?>">ASR</a></li>
                        <li><a href="/?page=unisoc" class="<?php echo ($current_page ?? '') === 'unisoc' ? 'active' : ''; ?>">展锐</a></li>
                        <li><a href="/?page=help" class="<?php echo ($current_page ?? '') === 'help' ? 'active' : ''; ?>">帮助</a></li>
                        <li><a href="/?page=about" class="<?php echo ($current_page ?? '') === 'about' ? 'active' : ''; ?>">关于</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    <main class="site-main">
        <div class="container">
