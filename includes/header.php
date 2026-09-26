<?php
/**
 * 公共头部模板
 *
 * 由 index.php 在加载页面后引入。依赖 database.php / app.php / functions.php
 * 已在 index.php 中 require_once，此处不再重复引入。
 *
 * 可用变量：
 *   $current_page  string  当前页面标识（由 index.php 设置）
 *   $skip_stats    bool    是否跳过统计记录（默认 false）
 *   $extra_css     array   额外 CSS 路径列表
 */

// 获取网站设置
$settings = getSettings();

// 记录页面访问（index.php 已对前台页面记录，此处仅对未经过 index.php 统计的页面补充记录）
// 注意：index.php 已调用 recordPageVisit()，此处不再重复调用以避免双重计数
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
                <button class="nav-toggle" aria-label="切换菜单" aria-expanded="false">
                    <span class="nav-toggle-bar"></span>
                    <span class="nav-toggle-bar"></span>
                    <span class="nav-toggle-bar"></span>
                </button>
                <nav class="main-nav">
                    <ul>
                        <li><a href="/" class="<?php echo ($current_page ?? '') === 'home' ? 'active' : ''; ?>">首页</a></li>
                        <li><a href="/?page=zte" class="<?php echo ($current_page ?? '') === 'zte' ? 'active' : ''; ?>">中兴微</a></li>
                        <li><a href="/?page=asr" class="<?php echo ($current_page ?? '') === 'asr' ? 'active' : ''; ?>">ASR</a></li>
                        <li><a href="/?page=unisoc" class="<?php echo ($current_page ?? '') === 'unisoc' ? 'active' : ''; ?>">展锐</a></li>
                        <li><a href="/?page=esp32" class="<?php echo ($current_page ?? '') === 'esp32' ? 'active' : ''; ?>">ESP32</a></li>
                        <li><a href="/?page=stm32" class="<?php echo ($current_page ?? '') === 'stm32' ? 'active' : ''; ?>">STM32</a></li>
                        <li class="nav-dropdown">
                            <a href="javascript:void(0)" class="dropdown-toggle">更多芯片</a>
                            <ul class="dropdown-menu">
                                <li><a href="/?page=qualcomm">高通 (Qualcomm)</a></li>
                                <li><a href="/?page=eigencomm">移芯通信 (Eigencomm)</a></li>
                                <li><a href="/?page=mediatek">联发科 (MediaTek)</a></li>
                                <li><a href="/?page=hisilicon">海思 (HiSilicon)</a></li>
                                <li><a href="/?page=xinyi">芯翼信息 (Xinyi)</a></li>
                            </ul>
                        </li>
                        <li><a href="/?page=at-reference" class="<?php echo ($current_page ?? '') === 'at-reference' ? 'active' : ''; ?>">📖 指令说明</a></li>
                        <li><a href="/?page=help" class="<?php echo ($current_page ?? '') === 'help' ? 'active' : ''; ?>">帮助</a></li>
                        <li><a href="/?page=about" class="<?php echo ($current_page ?? '') === 'about' ? 'active' : ''; ?>">关于</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    <main class="site-main">
        <div class="container">
