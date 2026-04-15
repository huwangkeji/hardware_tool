<?php
/**
 * 关于页面
 */
$skip_stats = false;
require_once __DIR__ . '/../includes/header.php';

// 从数据库读取关于页面内容和联系信息（容错处理）
$aboutContent = '';
$contactEmail = 'contact@example.com';
$contactQQ = '';
$contactQQGroup = '';
$contactDocUrl = '';
$douyinText = '我的抖音';
$douyinUrl = '';
$bilibiliText = '我的B站';
$bilibiliUrl = '';
try {
    $db = getDB();
    $stmt = $db->query("SELECT about_content, contact_email, contact_qq, contact_qq_group, contact_doc_url, douyin_text, douyin_url, bilibili_text, bilibili_url FROM settings WHERE id = 1");
    $settingsData = $stmt->fetch();
    $aboutContent = $settingsData['about_content'] ?? '';
    $contactEmail = $settingsData['contact_email'] ?? 'contact@example.com';
    $contactQQ = $settingsData['contact_qq'] ?? '';
    $contactQQGroup = $settingsData['contact_qq_group'] ?? '';
    $contactDocUrl = $settingsData['contact_doc_url'] ?? '';
    $douyinText = $settingsData['douyin_text'] ?? '我的抖音';
    $douyinUrl = $settingsData['douyin_url'] ?? '';
    $bilibiliText = $settingsData['bilibili_text'] ?? '我的B站';
    $bilibiliUrl = $settingsData['bilibili_url'] ?? '';
} catch (Exception $e) {
    // 字段不存在时，使用默认内容
    $aboutContent = '';
}
?>

<style>
    .about-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 60px 0;
        text-align: center;
        color: white;
        margin-bottom: 40px;
        border-radius: 12px;
    }
    
    .about-hero h1 {
        font-size: 42px;
        margin-bottom: 15px;
        font-weight: 700;
    }
    
    .about-hero p {
        font-size: 18px;
        opacity: 0.9;
    }
    
    .about-content {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 20px 60px;
    }
    
    .about-section {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .about-section h2 {
        color: #333;
        font-size: 24px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #667eea;
    }
    
    .about-section p {
        color: #666;
        line-height: 1.8;
        font-size: 16px;
    }
    
    .feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .feature-item {
        background: #f5f7fa;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }
    
    .feature-item h3 {
        color: #667eea;
        margin-bottom: 10px;
        font-size: 18px;
    }
    
    .feature-item p {
        color: #666;
        font-size: 14px;
        margin: 0;
    }
    
    .contact-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    
    .contact-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 15px;
        background: #f5f7fa;
        border-radius: 8px;
    }
    
    .contact-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
    }
    
    .contact-text h4 {
        margin: 0 0 5px 0;
        color: #333;
        font-size: 14px;
    }
    
    .contact-text p {
        margin: 0;
        color: #666;
        font-size: 14px;
    }
</style>

<!-- Hero 区域 -->
<div class="about-hero">
    <div class="container">
        <h1>关于我们</h1>
        <p>专业的硬件调试工具，支持多种芯片串口调试</p>
    </div>
</div>

<!-- 关于内容 -->
<div class="about-content">
    <!-- 项目介绍（支持自定义内容） -->
    <div class="about-section">
        <h2>项目介绍</h2>
        <?php if (!empty($aboutContent)): ?>
            <!-- 显示自定义HTML内容 -->
            <div class="custom-content">
                <?php echo $aboutContent; ?>
            </div>
        <?php else: ?>
            <!-- 默认内容 -->
            <p>硬件调试工具是一个专业的串口调试平台，支持中兴微、ASR、展锐等多种芯片的串口通信调试。通过现代化的Web界面，开发者可以方便地进行AT指令测试、数据传输、固件升级等操作。</p>
        <?php endif; ?>
    </div>
    
    <!-- 核心功能（固定模板） -->
    <div class="about-section">
        <h2>核心功能</h2>
        <div class="feature-grid">
            <div class="feature-item">
                <h3>🔌 串口通信</h3>
                <p>支持Web Serial API，直接在浏览器中进行串口通信</p>
            </div>
            <div class="feature-item">
                <h3>📡 AT指令</h3>
                <p>内置常用AT指令集，支持自定义指令发送</p>
            </div>
            <div class="feature-item">
                <h3>📊 实时监控</h3>
                <p>实时显示串口数据，支持数据过滤和搜索</p>
            </div>
            <div class="feature-item">
                <h3>🔄 固件升级</h3>
                <p>支持芯片固件在线升级和版本管理</p>
            </div>
        </div>
    </div>
    
    <!-- 技术特性（固定模板） -->
    <div class="about-section">
        <h2>技术特性</h2>
        <div class="feature-grid">
            <div class="feature-item">
                <h3>🌐 纯Web应用</h3>
                <p>基于浏览器运行，无需安装客户端软件</p>
            </div>
            <div class="feature-item">
                <h3>🔒 安全可靠</h3>
                <p>本地数据处理，不上传到服务器</p>
            </div>
            <div class="feature-item">
                <h3>📱 响应式设计</h3>
                <p>支持PC和移动端访问</p>
            </div>
            <div class="feature-item">
                <h3>⚡ 高性能</h3>
                <p>优化的数据传输，低延迟通信</p>
            </div>
        </div>
    </div>
    
    <!-- 支持芯片（固定模板） -->
    <div class="about-section">
        <h2>支持芯片</h2>
        <div class="feature-grid">
            <div class="feature-item">
                <h3>中兴微芯片</h3>
                <p>串口调试、AT指令测试、固件升级</p>
            </div>
            <div class="feature-item">
                <h3>ASR芯片</h3>
                <p>串口调试、语音识别测试、数据传输</p>
            </div>
            <div class="feature-item">
                <h3>展锐芯片</h3>
                <p>串口调试、网络测试、系统诊断</p>
            </div>
        </div>
    </div>
    
    <!-- 联系我们（后台自定义配置） -->
    <div class="about-section">
        <h2>联系我们</h2>
        <div class="contact-info">
            <?php if (!empty($contactEmail)): ?>
            <div class="contact-item">
                <div class="contact-icon">📧</div>
                <div class="contact-text">
                    <h4>联系邮箱</h4>
                    <p><?php echo htmlspecialchars($contactEmail); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($contactQQ)): ?>
            <div class="contact-item">
                <div class="contact-icon">💬</div>
                <div class="contact-text">
                    <h4>联系QQ</h4>
                    <p><?php echo htmlspecialchars($contactQQ); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($contactQQGroup)): ?>
            <div class="contact-item">
                <div class="contact-icon">👥</div>
                <div class="contact-text">
                    <h4>加入QQ群</h4>
                    <p><?php echo htmlspecialchars($contactQQGroup); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($contactDocUrl)): ?>
            <div class="contact-item">
                <div class="contact-icon">📖</div>
                <div class="contact-text">
                    <h4>文档</h4>
                    <p><a href="<?php echo htmlspecialchars($contactDocUrl); ?>" target="_blank" style="color: #667eea; text-decoration: none;">查看使用手册</a></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($douyinUrl)): ?>
            <div class="contact-item">
                <div class="contact-icon">🎵</div>
                <div class="contact-text">
                    <h4><?php echo htmlspecialchars($douyinText); ?></h4>
                    <p><a href="<?php echo htmlspecialchars($douyinUrl); ?>" target="_blank" style="color: #1890ff; text-decoration: none;">点击进入</a></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($bilibiliUrl)): ?>
            <div class="contact-item">
                <div class="contact-icon">📺</div>
                <div class="contact-text">
                    <h4><?php echo htmlspecialchars($bilibiliText); ?></h4>
                    <p><a href="<?php echo htmlspecialchars($bilibiliUrl); ?>" target="_blank" style="color: #1890ff; text-decoration: none;">点击进入</a></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
