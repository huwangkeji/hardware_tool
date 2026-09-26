<?php
/**
 * 帮助页面
 */
require_once __DIR__ . '/../includes/header.php';

$articles = getHelpArticles(null, true);

// 按分类分组
$categoryLabels = [
    'getting-started' => '快速入门',
    'faq'             => '常见问题',
    'serial'          => '串口通信',
    'troubleshooting' => '故障排查',
    'driver'          => '驱动安装',
    'tool'            => '工具使用',
    'zte'             => '中兴微调试',
    'asr'             => 'ASR 调试',
    'unisoc'          => '展锐调试',
    'esp32'           => 'ESP32 调试',
    'stm32'           => 'STM32 调试',
    'other'           => '其他',
];

$grouped = [];
foreach ($articles as $art) {
    $cat = $art['category'] ?: 'other';
    $grouped[$cat][] = $art;
}
?>
<div class="page-help">
    <div class="help-container">
        <h1 class="help-title">帮助中心</h1>
        <p class="help-subtitle">在这里找到使用指南和常见问题解答</p>

        <?php if (empty($articles)): ?>
        <div class="help-empty">
            <p>暂无帮助文章。</p>
        </div>
        <?php else: ?>
        <div class="help-content">
            <?php foreach ($grouped as $category => $pages): ?>
            <section class="help-section">
                <h2 class="help-section-title"><?php echo h($categoryLabels[$category] ?? $category); ?></h2>
                <?php foreach ($pages as $page): ?>
                <article class="help-article">
                    <h3 class="help-article-title"><?php echo h($page['title']); ?></h3>
                    <div class="help-article-content">
                        <?php echo sanitizeHTML($page['content']); ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </section>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
