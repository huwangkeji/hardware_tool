-- =============================================
-- 硬件调试工具站 数据库安装脚本
-- 适用于 phpMyAdmin 导入
-- 请先在 phpMyAdmin 中创建数据库 hardware_tool (utf8mb4_unicode_ci)
-- 然后选择该数据库后导入此文件
-- =============================================

-- =============================================
-- 表1: 网站设置表
-- =============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_title VARCHAR(100) NOT NULL DEFAULT '硬件调试工具',
    site_description TEXT COMMENT '网站描述',
    site_text_logo VARCHAR(100) DEFAULT '硬件调试工具' COMMENT '网站文字Logo',
    about_content TEXT COMMENT '关于页面HTML内容',
    contact_email VARCHAR(100) DEFAULT 'contact@example.com' COMMENT '联系邮箱',
    contact_qq VARCHAR(20) DEFAULT '' COMMENT '联系QQ',
    contact_qq_group VARCHAR(50) DEFAULT '' COMMENT '联系QQ群',
    contact_doc_url VARCHAR(255) DEFAULT '' COMMENT '文档链接',
    site_logo VARCHAR(255) DEFAULT '/assets/images/logo.svg',
    site_favicon VARCHAR(255) DEFAULT '/assets/images/favicon.svg',
    theme_color VARCHAR(20) DEFAULT '#1890ff',
    footer_copyright VARCHAR(255) DEFAULT '',
    footer_version VARCHAR(20) DEFAULT '1.0.0',
    douyin_text VARCHAR(100) DEFAULT '我的抖音',
    douyin_url VARCHAR(255) DEFAULT '',
    bilibili_text VARCHAR(100) DEFAULT '我的B站',
    bilibili_url VARCHAR(255) DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站设置';

INSERT IGNORE INTO settings (id, site_title, site_description)
VALUES (1, '硬件调试工具', '支持中兴微、ASR、展锐芯片的串口调试工具');

-- =============================================
-- 表2: 页面访问统计表
-- =============================================
CREATE TABLE IF NOT EXISTS page_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_name VARCHAR(50) NOT NULL COMMENT '页面名称',
    visit_count INT DEFAULT 0 COMMENT '访问次数',
    success_count INT DEFAULT 0 COMMENT '成功连接次数',
    fail_count INT DEFAULT 0 COMMENT '失败次数',
    last_visit TIMESTAMP NULL COMMENT '最后访问时间',
    date DATE NOT NULL COMMENT '统计日期',
    UNIQUE KEY uk_page_date (page_name, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='页面访问统计';

-- =============================================
-- 表3: 串口操作日志表
-- =============================================
CREATE TABLE IF NOT EXISTS serial_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_type VARCHAR(50) NOT NULL COMMENT '设备类型',
    port_name VARCHAR(100) DEFAULT NULL COMMENT '端口名称',
    action VARCHAR(50) NOT NULL COMMENT '操作类型',
    data TEXT COMMENT '操作数据',
    status TINYINT DEFAULT 1 COMMENT '状态(1成功/0失败)',
    error_msg VARCHAR(500) DEFAULT NULL COMMENT '错误信息',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP地址',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device (device_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='串口操作日志';

-- =============================================
-- 表4: 管理员表
-- =============================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    password_hash VARCHAR(255) NOT NULL COMMENT '密码哈希',
    email VARCHAR(100) DEFAULT NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 插入默认管理员 (用户名: admin, 密码: admin123)
INSERT IGNORE INTO admin_users (username, password_hash, email)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');

-- =============================================
-- 表5: 帮助文章表
-- =============================================
CREATE TABLE IF NOT EXISTS help_pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL COMMENT '文章标题',
    category VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT '分类',
    content TEXT NOT NULL COMMENT '文章内容(支持HTML)',
    sort_order INT DEFAULT 0 COMMENT '排序权重',
    is_published TINYINT DEFAULT 1 COMMENT '是否发布',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帮助文章';

INSERT IGNORE INTO help_pages (id, title, category, content, sort_order, is_published) VALUES
(1, '驱动安装说明', 'driver', '<h3>驱动安装步骤</h3><ol><li>下载对应芯片的驱动程序</li><li>关闭所有杀毒软件和防火墙</li><li>以管理员身份运行驱动安装程序</li><li>安装完成后重启电脑</li><li>设备管理器中确认COM端口已识别</li></ol><p>如遇安装失败，请尝试更换USB接口或使用USB2.0接口。</p>', 1, 1);

INSERT IGNORE INTO help_pages (id, title, category, content, sort_order, is_published) VALUES
(2, '浏览器使用要求', 'tool', '<h3>浏览器要求</h3><ul><li>必须使用Chrome 89+或基于Chromium内核的浏览器</li><li>需要开启Web Serial API支持</li><li>建议使用最新版Chrome或Edge浏览器</li></ul><h3>常见问题</h3><ul><li>串口无法识别：请确认浏览器版本并检查驱动是否正常</li><li>数据乱码：请检查波特率设置是否与设备一致</li><li>连接超时：请尝试重新插拔USB线缆</li></ul>', 2, 1);

INSERT IGNORE INTO help_pages (id, title, category, content, sort_order, is_published) VALUES
(3, '中兴微芯片调试指南', 'zte', '<h3>基本操作</h3><ol><li>进入中兴微调试页面</li><li>选择正确的波特率（默认115200）</li><li>点击连接串口选择AT端口</li><li>使用AT指令按钮或自定义指令进行调试</li></ol>', 3, 1);

INSERT IGNORE INTO help_pages (id, title, category, content, sort_order, is_published) VALUES
(4, 'ASR芯片调试指南', 'asr', '<h3>基本操作</h3><ol><li>进入ASR调试页面</li><li>选择正确的波特率（默认115200）</li><li>点击连接串口选择AT端口</li><li>使用AT指令按钮或自定义指令进行调试</li></ol>', 4, 1);

INSERT IGNORE INTO help_pages (id, title, category, content, sort_order, is_published) VALUES
(5, '展锐芯片调试指南', 'unisoc', '<h3>基本操作</h3><ol><li>进入展锐调试页面</li><li>选择正确的波特率（默认115200）</li><li>点击连接串口选择AT端口</li><li>使用AT指令按钮或自定义指令进行调试</li></ol>', 5, 1);

-- =============================================
-- 表6: 系统配置表
-- =============================================
CREATE TABLE IF NOT EXISTS system_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    config_type VARCHAR(20) DEFAULT 'string',
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置';

INSERT IGNORE INTO system_config (config_key, config_value, config_type, description) VALUES
('maintenance_mode', '0', 'boolean', '维护模式'),
('allow_registration', '0', 'boolean', '允许注册'),
('session_timeout', '3600', 'integer', '会话超时时间(秒)'),
('max_upload_size', '10485760', 'integer', '最大上传大小(字节)');
