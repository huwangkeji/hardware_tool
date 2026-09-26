<?php
/**
 * 首页 - 硬件调试工具站（企业级 UI）
 */
$skip_stats = false;
require_once __DIR__ . '/../includes/header.php';

// 获取芯片平台数据
$platforms = getChipPlatforms(true);
$atCmdCount = countATCommands();

// 解析核心功能和技术特性
$coreFeatures = json_decode($settings['core_features'] ?? '[]', true) ?: [];
$techFeatures = json_decode($settings['tech_features'] ?? '[]', true) ?: [];
?>

<div class="page-home">

    <!-- ============ Hero ============ -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-content">
            <span class="hero-badge">Web Serial API · 浏览器原生串口通信</span>
            <h1 class="hero-title"><?php echo h($settings['site_title']); ?></h1>
            <p class="hero-desc"><?php echo h($settings['site_description']); ?></p>
            <div class="hero-actions">
                <a href="/?page=zte" class="btn-hero btn-hero-primary">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    开始调试
                </a>
                <a href="/?page=at-reference" class="btn-hero btn-hero-ghost">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                    指令百科
                </a>
            </div>
        </div>
        <!-- 统计栏 -->
        <div class="hero-stats">
            <div class="stat-item">
                <span class="stat-num"><?php echo count($platforms); ?></span>
                <span class="stat-label">芯片平台</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-num"><?php echo $atCmdCount; ?>+</span>
                <span class="stat-label">AT 指令</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-num">100%</span>
                <span class="stat-label">浏览器运行</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-num">0</span>
                <span class="stat-label">安装依赖</span>
            </div>
        </div>
    </section>

    <!-- ============ 芯片平台 ============ -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">芯片调试平台</h2>
            <p class="section-subtitle">支持主流物联网通信芯片，在线串口调试 · AT 指令测试 · 固件管理</p>
        </div>
        <div class="platform-grid">
            <?php foreach ($platforms as $p):
                $chips = json_decode($p['chips'] ?? '[]', true) ?: [];
                $debugUrl = "/?page=" . h($p['type']);
            ?>
            <a href="<?php echo $debugUrl; ?>" class="platform-card">
                <div class="platform-card-top">
                    <span class="platform-icon"><?php echo h($p['icon']); ?></span>
                    <span class="platform-name"><?php echo h($p['name']); ?></span>
                </div>
                <p class="platform-desc"><?php echo h($p['description']); ?></p>
                <div class="platform-chips">
                    <?php foreach (array_slice($chips, 0, 4) as $chip): ?>
                        <span class="chip-tag"><?php echo h($chip); ?></span>
                    <?php endforeach; ?>
                    <?php if (count($chips) > 4): ?>
                        <span class="chip-tag chip-tag-more">+<?php echo count($chips) - 4; ?></span>
                    <?php endif; ?>
                </div>
                <div class="platform-card-footer">
                    <span class="platform-enter">进入调试</span>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" class="platform-arrow"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6z"/></svg>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ============ AT 指令百科 ============ -->
    <section class="section">
        <div class="at-banner">
            <div class="at-banner-bg"></div>
            <div class="at-banner-body">
                <div class="at-banner-left">
                    <span class="at-banner-icon">📖</span>
                    <div>
                        <h3 class="at-banner-title">AT 指令百科</h3>
                        <p class="at-banner-desc">收录全部 <?php echo $atCmdCount; ?> 条 AT 指令，涵盖语法、参数、响应及使用示例</p>
                    </div>
                </div>
                <a href="/?page=at-reference" class="btn-at-banner">
                    查看全部
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6z"/></svg>
                </a>
            </div>
        </div>
    </section>

    <!-- ============ 核心功能 & 技术特性 ============ -->
    <section class="section">
        <div class="features-row">
            <div class="features-panel">
                <div class="features-panel-header">
                    <span class="features-panel-icon">⚡</span>
                    <h3>核心功能</h3>
                </div>
                <div class="features-list">
                    <?php foreach ($coreFeatures as $f): ?>
                    <div class="feature-item">
                        <span class="feature-item-icon"><?php echo h($f['icon'] ?? ''); ?></span>
                        <div>
                            <h4><?php echo h($f['title'] ?? ''); ?></h4>
                            <p><?php echo h($f['desc'] ?? ''); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="features-panel">
                <div class="features-panel-header">
                    <span class="features-panel-icon">🛡️</span>
                    <h3>技术特性</h3>
                </div>
                <div class="features-list">
                    <?php foreach ($techFeatures as $f): ?>
                    <div class="feature-item">
                        <span class="feature-item-icon"><?php echo h($f['icon'] ?? ''); ?></span>
                        <div>
                            <h4><?php echo h($f['title'] ?? ''); ?></h4>
                            <p><?php echo h($f['desc'] ?? ''); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ 使用指南 ============ -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">使用指南</h2>
            <p class="section-subtitle">三步开始芯片串口调试</p>
        </div>
        <div class="guide-grid">
            <div class="guide-card">
                <div class="guide-step">1</div>
                <h4>安装驱动</h4>
                <p>将设备通过 USB 连接至电脑，安装对应芯片的串口驱动程序</p>
            </div>
            <div class="guide-card">
                <div class="guide-step">2</div>
                <h4>打开浏览器</h4>
                <p>使用 Chrome 89+ 或其他 Chromium 内核浏览器访问本站</p>
            </div>
            <div class="guide-card">
                <div class="guide-step">3</div>
                <h4>连接调试</h4>
                <p>选择对应芯片平台，点击「进入调试」，选择 AT 端口即可开始</p>
            </div>
        </div>
    </section>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
