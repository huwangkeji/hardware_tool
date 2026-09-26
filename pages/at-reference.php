<?php
/**
 * AT指令百科 - 指令说明页面
 * 展示所有芯片平台的AT指令详细说明
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/schema.php';

// 数据库初始化在 getDB() 中自动完成
getDB();

// 获取参数
$chip_filter = $_GET['chip'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';
$search = trim($_GET['q'] ?? '');

// 获取所有芯片平台
$platforms = getChipPlatforms();
$platformMap = [];
foreach ($platforms as $p) {
    $platformMap[$p['type']] = $p;
}

// 获取AT指令
$commands = getATCommands(
    $chip_filter !== 'all' ? $chip_filter : null,
    $category_filter !== 'all' ? $category_filter : null,
    true,
    $search ?: null
);

// 获取分类列表
$categories = getATCommandCategories($chip_filter !== 'all' ? $chip_filter : null);

// 按芯片分组
$grouped = [];
foreach ($commands as $cmd) {
    $grouped[$cmd['chip_type']][] = $cmd;
}

// 页面标题
$pageTitle = 'AT指令百科 - 指令说明';
$activeNav = 'at-reference';
include __DIR__ . '/../includes/header.php';
?>

<div class="at-ref-container">
    <!-- 页面头部 -->
    <div class="at-ref-header">
        <h1>📖 AT指令百科</h1>
        <p class="at-ref-subtitle">收录中兴微、ASR、展锐、ESP32、STM32、高通、移芯、联发科、海思、芯翼等全平台AT指令说明</p>
    </div>

    <!-- 搜索栏 -->
    <div class="at-ref-search-bar">
        <form method="GET" action="" class="at-ref-search-form">
            <input type="hidden" name="page" value="at-reference">
            <div class="at-ref-search-input-wrap">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="搜索指令名称、说明、语法..." class="at-ref-search-input">
                <button type="submit" class="at-ref-search-btn">🔍 搜索</button>
            </div>
        </form>
    </div>

    <!-- 筛选栏 -->
    <div class="at-ref-filters">
        <div class="at-ref-filter-group">
            <label>芯片平台：</label>
            <div class="at-ref-chip-tabs">
                <a href="?page=at-reference&q=<?= urlencode($search) ?>" 
                   class="at-ref-chip-tab <?= $chip_filter === 'all' ? 'active' : '' ?>">全部</a>
                <?php foreach ($platforms as $p): ?>
                <a href="?page=at-reference&chip=<?= urlencode($p['type']) ?>&q=<?= urlencode($search) ?>" 
                   class="at-ref-chip-tab <?= $chip_filter === $p['type'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($p['short_name'] ?: $p['name']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($categories)): ?>
        <div class="at-ref-filter-group">
            <label>指令分类：</label>
            <div class="at-ref-chip-tabs">
                <a href="?page=at-reference&chip=<?= urlencode($chip_filter) ?>&q=<?= urlencode($search) ?>" 
                   class="at-ref-chip-tab <?= $category_filter === 'all' ? 'active' : '' ?>">全部</a>
                <?php foreach ($categories as $cat): ?>
                <a href="?page=at-reference&chip=<?= urlencode($chip_filter) ?>&category=<?= urlencode($cat) ?>&q=<?= urlencode($search) ?>" 
                   class="at-ref-chip-tab <?= $category_filter === $cat ? 'active' : '' ?>">
                    <?= htmlspecialchars($cat) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- 统计信息 -->
    <div class="at-ref-stats">
        共找到 <strong><?= count($commands) ?></strong> 条AT指令
        <?php if ($chip_filter !== 'all'): ?>
            · 平台：<strong><?= htmlspecialchars($platformMap[$chip_filter]['name'] ?? $chip_filter) ?></strong>
        <?php endif; ?>
        <?php if ($category_filter !== 'all'): ?>
            · 分类：<strong><?= htmlspecialchars($category_filter) ?></strong>
        <?php endif; ?>
    </div>

    <!-- 指令列表 -->
    <?php if (empty($commands)): ?>
    <div class="at-ref-empty">
        <p>📭 未找到匹配的AT指令</p>
        <p class="at-ref-empty-hint">尝试更换筛选条件或搜索关键词</p>
    </div>
    <?php else: ?>
    <div class="at-ref-content">
        <?php foreach ($grouped as $chipType => $cmds): 
            $platform = $platformMap[$chipType] ?? null;
        ?>
        <div class="at-ref-chip-section" id="chip-<?= htmlspecialchars($chipType) ?>">
            <div class="at-ref-chip-header">
                <h2>
                    <?php if ($platform && $platform['icon']): ?>
                        <span class="at-ref-chip-icon"><?= htmlspecialchars($platform['icon']) ?></span>
                    <?php endif; ?>
                    <?= htmlspecialchars($platform['name'] ?? $chipType) ?>
                    <?php if ($platform && $platform['chips']): ?>
                        <span class="at-ref-chip-models"><?= htmlspecialchars($platform['chips']) ?></span>
                    <?php endif; ?>
                </h2>
                <span class="at-ref-chip-count"><?= count($cmds) ?> 条指令</span>
            </div>
            <div class="at-ref-cmd-grid">
                <?php foreach ($cmds as $cmd): ?>
                <div class="at-ref-cmd-card">
                    <div class="at-ref-cmd-card-header">
                        <code class="at-ref-cmd-name"><?= htmlspecialchars($cmd['command']) ?></code>
                        <span class="at-ref-cmd-title"><?= htmlspecialchars($cmd['title']) ?></span>
                        <?php if ($cmd['category']): ?>
                        <span class="at-ref-cmd-cat"><?= htmlspecialchars($cmd['category']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($cmd['description']): ?>
                    <div class="at-ref-cmd-desc"><?= htmlspecialchars($cmd['description']) ?></div>
                    <?php endif; ?>
                    <?php if ($cmd['syntax']): ?>
                    <div class="at-ref-cmd-field">
                        <span class="at-ref-field-label">语法：</span>
                        <code class="at-ref-field-value"><?= htmlspecialchars($cmd['syntax']) ?></code>
                    </div>
                    <?php endif; ?>
                    <?php if ($cmd['parameters']): ?>
                    <div class="at-ref-cmd-field">
                        <span class="at-ref-field-label">参数：</span>
                        <span class="at-ref-field-value"><?= nl2br(htmlspecialchars($cmd['parameters'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($cmd['response']): ?>
                    <div class="at-ref-cmd-field">
                        <span class="at-ref-field-label">响应：</span>
                        <code class="at-ref-field-value"><?= nl2br(htmlspecialchars($cmd['response'])) ?></code>
                    </div>
                    <?php endif; ?>
                    <?php if ($cmd['example']): ?>
                    <div class="at-ref-cmd-field">
                        <span class="at-ref-field-label">示例：</span>
                        <code class="at-ref-field-value at-ref-example"><?= nl2br(htmlspecialchars($cmd['example'])) ?></code>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
