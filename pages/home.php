<?php
/**
 * 首页 - 硬件调试工具站
 */
$skip_stats = false;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-home">
    <div class="hero-section">
        <h1><?php echo h($settings['site_title']); ?></h1>
        <p class="subtitle"><?php echo h($settings['site_description']); ?></p>
    </div>

    <div class="features-grid">
        <div class="feature-card" onclick="location.href='/?page=zte'">
            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
            </div>
            <h3>中兴微调试</h3>
            <p>支持中兴微芯片串口调试,AT指令测试,固件升级</p>
            <a href="/?page=zte" class="btn btn-primary">进入调试</a>
        </div>

        <div class="feature-card" onclick="location.href='/?page=asr'">
            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 14c1.66 0 2.99-1.34 2.99-3L15 5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.48 6-3.3 6-6.72h-1.7z"/>
                </svg>
            </div>
            <h3>ASR调试</h3>
            <p>ASR芯片串口调试,语音识别测试,数据传输</p>
            <a href="/?page=asr" class="btn btn-primary">进入调试</a>
        </div>

        <div class="feature-card" onclick="location.href='/?page=unisoc'">
            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 12H4V8h16v10z"/>
                </svg>
            </div>
            <h3>展锐调试</h3>
            <p>展锐芯片串口调试,网络测试,系统诊断</p>
            <a href="/?page=unisoc" class="btn btn-primary">进入调试</a>
        </div>
    </div>

    <div class="info-section">
        <div class="info-card">
            <h3>使用说明</h3>
            <ul>
                <li>需要先安装对应驱动到电脑</li>
                <li>支持基于chromium内核的浏览器(Chrome 89+)</li>
                <li>串行端口需要选择AT端口</li>
                <li>若发现数据不对,请多点几次任意读取相关的按钮刷新数据</li>
            </ul>
        </div>

        <div class="info-card">
            <h3>技术特性</h3>
            <ul>
                <li>基于Web Serial API技术</li>
                <li>浏览器直接串口通信</li>
                <li>支持AT指令集</li>
                <li>实时数据监控</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
