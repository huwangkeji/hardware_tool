-- =============================================
-- 硬件调试工具站 SQLite 数据库安装脚本
-- =============================================

-- 启用外键约束
PRAGMA foreign_keys = ON;

-- =============================================
-- 表1: 网站设置表
-- =============================================
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_title VARCHAR(100) NOT NULL DEFAULT '硬件调试工具',
    site_description TEXT DEFAULT '支持中兴微、ASR、展锐芯片的串口调试工具',
    site_text_logo VARCHAR(100) DEFAULT '硬件调试工具',
    about_content TEXT,
    contact_email VARCHAR(100) DEFAULT 'contact@example.com',
    contact_qq VARCHAR(20) DEFAULT '',
    contact_qq_group VARCHAR(50) DEFAULT '',
    contact_doc_url VARCHAR(255) DEFAULT '',
    site_logo VARCHAR(255) DEFAULT '/assets/images/logo.png',
    site_favicon VARCHAR(255) DEFAULT '/assets/images/favicon.ico',
    theme_color VARCHAR(20) DEFAULT '#1890ff',
    updated_at DATETIME DEFAULT (datetime('now', 'localtime'))
);

-- 插入默认设置
INSERT OR IGNORE INTO settings (id, site_title, site_description) 
VALUES (1, '硬件调试工具', '支持中兴微、ASR、展锐芯片的串口调试工具');

-- =============================================
-- 表2: 页面访问统计表
-- =============================================
CREATE TABLE IF NOT EXISTS page_stats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_name VARCHAR(50) NOT NULL,
    visit_count INTEGER DEFAULT 0,
    success_count INTEGER DEFAULT 0,
    fail_count INTEGER DEFAULT 0,
    last_visit DATETIME,
    date DATE NOT NULL,
    UNIQUE(page_name, date)
);

-- 创建索引
CREATE INDEX IF NOT EXISTS idx_page_stats_date ON page_stats(date);
CREATE INDEX IF NOT EXISTS idx_page_stats_page ON page_stats(page_name);

-- =============================================
-- 表3: 串口操作日志表
-- =============================================
CREATE TABLE IF NOT EXISTS serial_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_type VARCHAR(50) NOT NULL,
    port_name VARCHAR(100),
    action VARCHAR(50) NOT NULL,
    data TEXT,
    status INTEGER DEFAULT 1,
    error_msg VARCHAR(500),
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT (datetime('now', 'localtime'))
);

-- 创建索引
CREATE INDEX IF NOT EXISTS idx_serial_logs_device ON serial_logs(device_type);
CREATE INDEX IF NOT EXISTS idx_serial_logs_created ON serial_logs(created_at);

-- =============================================
-- 表4: 管理员表
-- =============================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    last_login DATETIME,
    created_at DATETIME DEFAULT (datetime('now', 'localtime'))
);

-- 插入默认管理员 (用户名: admin, 密码: admin123)
-- 密码哈希使用 password_hash('admin123', PASSWORD_DEFAULT)
INSERT OR IGNORE INTO admin_users (username, password_hash, email) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');

-- =============================================
-- 表5: 帮助文章表
-- =============================================
CREATE TABLE IF NOT EXISTS help_pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'general',
    content TEXT NOT NULL,
    sort_order INTEGER DEFAULT 0,
    is_published INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT (datetime('now', 'localtime')),
    updated_at DATETIME DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX IF NOT EXISTS idx_help_pages_category ON help_pages(category);
CREATE INDEX IF NOT EXISTS idx_help_pages_sort ON help_pages(sort_order);

INSERT OR IGNORE INTO help_pages (id, title, category, content, sort_order, is_published) VALUES
(1, '驱动安装说明', 'driver', '<h3>驱动安装步骤</h3><ol><li>下载对应芯片的驱动程序</li><li>关闭所有杀毒软件和防火墙</li><li>以管理员身份运行驱动安装程序</li><li>安装完成后重启电脑</li><li>设备管理器中确认COM端口已识别</li></ol><p>如遇安装失败，请尝试更换USB接口或使用USB2.0接口。</p>', 1, 1),
(2, '浏览器使用要求', 'tool', '<h3>浏览器要求</h3><ul><li>必须使用Chrome 89+或基于Chromium内核的浏览器</li><li>需要开启Web Serial API支持</li><li>建议使用最新版Chrome或Edge浏览器</li></ul><h3>常见问题</h3><ul><li>串口无法识别：请确认浏览器版本并检查驱动是否正常</li><li>数据乱码：请检查波特率设置是否与设备一致</li><li>连接超时：请尝试重新插拔USB线缆</li></ul>', 2, 1),
(3, '中兴微芯片调试指南', 'zte', '<h3>基本操作</h3><ol><li>进入中兴微调试页面</li><li>选择正确的波特率（默认115200）</li><li>点击"连接串口"选择AT端口</li><li>使用AT指令按钮或自定义指令进行调试</li></ol>', 3, 1),
(4, 'ASR芯片调试指南', 'asr', '<h3>基本操作</h3><ol><li>进入ASR调试页面</li><li>选择正确的波特率（默认115200）</li><li>点击"连接串口"选择AT端口</li><li>使用AT指令按钮或自定义指令进行调试</li></ol>', 4, 1),
(5, '展锐芯片调试指南', 'unisoc', '<h3>基本操作</h3><ol><li>进入展锐调试页面</li><li>选择正确的波特率（默认115200）</li><li>点击"连接串口"选择AT端口</li><li>使用AT指令按钮或自定义指令进行调试</li></ol>', 5, 1);

-- =============================================
-- 表6: 系统配置表(可选)
-- =============================================
CREATE TABLE IF NOT EXISTS system_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    config_type VARCHAR(20) DEFAULT 'string',
    description VARCHAR(255),
    updated_at DATETIME DEFAULT (datetime('now', 'localtime'))
);

-- 插入默认配置
INSERT OR IGNORE INTO system_config (config_key, config_value, config_type, description) VALUES
('maintenance_mode', '0', 'boolean', '维护模式'),
('allow_registration', '0', 'boolean', '允许注册'),
('session_timeout', '3600', 'integer', '会话超时时间(秒)'),
('max_upload_size', '10485760', 'integer', '最大上传大小(字节)');

-- =============================================
-- 创建视图(可选,用于简化查询)
-- =============================================

-- 页面统计汇总视图
CREATE VIEW IF NOT EXISTS v_page_stats_summary AS
SELECT 
    page_name,
    SUM(visit_count) as total_visits,
    SUM(success_count) as total_success,
    SUM(fail_count) as total_fail,
    MAX(last_visit) as last_visit
FROM page_stats
GROUP BY page_name;

-- 每日统计视图
CREATE VIEW IF NOT EXISTS v_daily_stats AS
SELECT 
    date,
    SUM(visit_count) as daily_visits,
    SUM(success_count) as daily_success,
    SUM(fail_count) as daily_fail
FROM page_stats
GROUP BY date
ORDER BY date DESC;

-- =============================================
-- 完成提示
-- =============================================
-- SQLite安装脚本执行完成
-- 数据库文件: database/hardware_tool.db
-- 默认管理员账号: admin / admin123
-- 请及时修改管理员密码!
