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
-- 表5: 系统配置表(可选)
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
