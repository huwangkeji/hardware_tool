<?php
/**
 * 帮助页面 - 驱动和工具使用说明
 */
$skip_stats = false;
require_once __DIR__ . '/../includes/header.php';

$categoryLabels = [
    'driver' => '驱动安装',
    'tool' => '工具使用',
    'zte' => '中兴微调试',
    'asr' => 'ASR调试',
    'unisoc' => '展锐调试',
    'general' => '通用说明',
];

$selectedCategory = $_GET['cat'] ?? null;
$helpPages = getHelpPages($selectedCategory, true);

$allCategories = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT DISTINCT category FROM help_pages WHERE is_published = 1 ORDER BY category");
    $allCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $allCategories = [];
}
?>

<div class="page-help">
    <div class="help-header">
        <h1>帮助中心</h1>
        <p class="help-subtitle">驱动安装与工具使用说明</p>
    </div>

    <div class="help-layout">
        <aside class="help-sidebar">
            <h3>分类导航</h3>
            <ul class="help-nav">
                <li>
                    <a href="/?page=help" class="<?php echo !$selectedCategory ? 'active' : ''; ?>">全部</a>
                </li>
                <?php foreach ($allCategories as $cat): ?>
                <li>
                    <a href="/?page=help&cat=<?php echo h($cat); ?>" class="<?php echo $selectedCategory === $cat ? 'active' : ''; ?>">
                        <?php echo h($categoryLabels[$cat] ?? $cat); ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <div class="help-content">
            <?php if (empty($helpPages)): ?>
            <div class="help-empty">
                <p>暂无帮助内容，请通过后台管理添加帮助文章。</p>
            </div>
            <?php else: ?>
            <?php foreach ($helpPages as $page): ?>
            <article class="help-article">
                <div class="help-article-header">
                    <h2><?php echo h($page['title']); ?></h2>
                    <span class="help-category-tag"><?php echo h($categoryLabels[$page['category']] ?? $page['category']); ?></span>
                </div>
                <div class="help-article-body">
                    <?php echo $page['content']; ?>
                </div>
                <div class="help-article-footer">
                    <span class="help-meta">更新于 <?php echo h($page['updated_at']); ?></span>
                </div>
            </article>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
