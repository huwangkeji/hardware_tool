-- =============================================
-- 硬件调试工具站 数据库安装脚本
-- =============================================

-- 创建数据库
CREATE DATABASE IF NOT EXISTS hardware_tool 
DEFAULT CHARACTER SET utf8mb4 
DEFAULT COLLATE utf8mb4_unicode_ci;

USE hardware_tool;

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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站设置';

-- 插入默认设置（TEXT字段在INSERT中赋值）
INSERT INTO settings (id, site_title, site_description) 
VALUES (1, '硬件调试工具', '支持中兴微、ASR、展锐芯片的串口调试工具')
ON DUPLICATE KEY UPDATE id=id;

-- =============================================
-- 表2: 页面访问统计表
-- =============================================
CREATE TABLE IF NOT EXISTS page_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_name VARCHAR(50) NOT NULL COMMENT '页面名称(home/zte/asr/unisoc)',
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
    device_type VARCHAR(50) NOT NULL COMMENT '设备类型(zte/asr/unisoc)',
    port_name VARCHAR(100) DEFAULT NULL COMMENT '端口名称',
    action VARCHAR(50) NOT NULL COMMENT '操作类型(connect/send/receive/disconnect)',
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
-- 密码哈希使用 password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO admin_users (username, password_hash, email) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com')
ON DUPLICATE KEY UPDATE username=username;

-- =============================================
-- 表5: 系统配置表(可选)
-- =============================================
CREATE TABLE IF NOT EXISTS system_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    config_type VARCHAR(20) DEFAULT 'string',
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置';

-- 插入默认配置
INSERT INTO system_config (config_key, config_value, config_type, description) VALUES
('maintenance_mode', '0', 'boolean', '维护模式'),
('allow_registration', '0', 'boolean', '允许注册'),
('session_timeout', '3600', 'integer', '会话超时时间(秒)'),
('max_upload_size', '10485760', 'integer', '最大上传大小(字节)')
ON DUPLICATE KEY UPDATE config_key=config_key;

-- =============================================
-- 完成提示
-- =============================================
SELECT '数据库安装完成!' AS message;
SELECT '默认管理员账号: admin / admin123' AS info;
SELECT '请及时修改管理员密码!' AS warning;
