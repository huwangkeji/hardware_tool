<?php
/**
 * 关于页面
 */
require_once __DIR__ . '/../includes/header.php';

$aboutContent = sanitizeHTML($settings['about_content'] ?? '');
?>
<div class="page-about">
    <div class="about-container">
        <h1 class="about-title">关于我们</h1>
        <div class="about-content">
            <?php if ($aboutContent): ?>
                <?php echo $aboutContent; ?>
            <?php else: ?>
                <p><?php echo h($settings['site_description']); ?></p>
                <p>本工具基于 Web Serial API，支持在浏览器中直接进行串口通信，无需安装任何驱动或软件。</p>
                <p>目前支持中兴微、ASR、展锐、ESP32、STM32 等多种芯片平台的在线调试。</p>
            <?php endif; ?>
        </div>

        <?php
        $hasContact = !empty($settings['contact_email'])
                   || !empty($settings['contact_qq'])
                   || !empty($settings['contact_qq_group'])
                   || !empty($settings['douyin_url'])
                   || !empty($settings['bilibili_url']);
        ?>
        <?php if ($hasContact): ?>
        <div class="about-contact">
            <h2>联系方式</h2>
            <ul>
                <?php if (!empty($settings['contact_email'])): ?>
                <li>邮箱：<?php echo h($settings['contact_email']); ?></li>
                <?php endif; ?>
                <?php if (!empty($settings['contact_qq'])): ?>
                <li>QQ：<?php echo h($settings['contact_qq']); ?></li>
                <?php endif; ?>
                <?php if (!empty($settings['contact_qq_group'])): ?>
                <li>QQ群：<?php echo h($settings['contact_qq_group']); ?></li>
                <?php endif; ?>
                <?php if (!empty($settings['douyin_url'])): ?>
                <li><a href="<?php echo h($settings['douyin_url']); ?>" target="_blank"><?php echo h($settings['douyin_text'] ?: '抖音'); ?></a></li>
                <?php endif; ?>
                <?php if (!empty($settings['bilibili_url'])): ?>
                <li><a href="<?php echo h($settings['bilibili_url']); ?>" target="_blank"><?php echo h($settings['bilibili_text'] ?: 'B站'); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
