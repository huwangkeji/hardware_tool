-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- 主机： localhost:3306
-- 生成日期： 2026-04-15 19:49:59
-- 服务器版本： 5.7.38-log
-- PHP 版本： 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `hardware_tool`
--

-- --------------------------------------------------------

--
-- 表的结构 `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password_hash` varchar(255) NOT NULL COMMENT '密码哈希',
  `email` varchar(100) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

--
-- 转存表中的数据 `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `email`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$12$vMli.PzC1blUblwQIryOZO4TvisitOLOkp2iuxyTmAUEFi//D2nRy', 'admin@example.com', '2026-04-13 08:56:30', '2026-04-13 05:24:48');

-- --------------------------------------------------------

--
-- 表的结构 `page_stats`
--

CREATE TABLE `page_stats` (
  `id` int(11) NOT NULL,
  `page_name` varchar(50) NOT NULL COMMENT '页面名称(home/zte/asr/unisoc)',
  `visit_count` int(11) DEFAULT '0' COMMENT '访问次数',
  `success_count` int(11) DEFAULT '0' COMMENT '成功连接次数',
  `fail_count` int(11) DEFAULT '0' COMMENT '失败次数',
  `last_visit` timestamp NULL DEFAULT NULL COMMENT '最后访问时间',
  `date` date NOT NULL COMMENT '统计日期'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='页面访问统计';

--
-- 转存表中的数据 `page_stats`
--

INSERT INTO `page_stats` (`id`, `page_name`, `visit_count`, `success_count`, `fail_count`, `last_visit`, `date`) VALUES
(1, 'home', 209, 0, 0, '2026-04-13 09:28:25', '2026-04-13'),
(42, 'asr', 6, 0, 0, '2026-04-13 09:17:58', '2026-04-13'),
(46, 'unisoc', 3, 0, 0, '2026-04-13 09:17:59', '2026-04-13'),
(50, 'about', 10, 0, 0, '2026-04-13 09:28:25', '2026-04-13'),
(62, 'zte', 11, 0, 0, '2026-04-13 09:18:00', '2026-04-13'),
(240, 'about', 16, 0, 0, '2026-04-15 05:13:11', '2026-04-15'),
(241, 'home', 162, 0, 0, '2026-04-15 05:13:13', '2026-04-15'),
(316, 'zte', 5, 0, 0, '2026-04-15 05:13:09', '2026-04-15'),
(335, 'asr', 5, 0, 0, '2026-04-15 05:13:10', '2026-04-15'),
(380, 'unisoc', 3, 0, 0, '2026-04-15 05:13:13', '2026-04-15');

-- --------------------------------------------------------

--
-- 表的结构 `serial_logs`
--

CREATE TABLE `serial_logs` (
  `id` int(11) NOT NULL,
  `device_type` varchar(50) NOT NULL COMMENT '设备类型(zte/asr/unisoc)',
  `port_name` varchar(100) DEFAULT NULL COMMENT '端口名称',
  `action` varchar(50) NOT NULL COMMENT '操作类型',
  `data` text COMMENT '操作数据',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态(1成功/0失败)',
  `error_msg` varchar(500) DEFAULT NULL COMMENT '错误信息',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='串口操作日志';

-- --------------------------------------------------------

--
-- 表的结构 `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `site_title` varchar(100) NOT NULL DEFAULT '硬件调试工具',
  `site_description` text COMMENT '网站描述',
  `site_text_logo` varchar(100) DEFAULT '硬件调试工具' COMMENT '网站文字Logo',
  `about_content` text COMMENT '关于页面HTML内容',
  `site_logo` varchar(255) DEFAULT '/assets/images/logo.png',
  `site_favicon` varchar(255) DEFAULT '/assets/images/favicon.ico',
  `theme_color` varchar(20) DEFAULT '#1890ff',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `contact_email` varchar(100) DEFAULT 'contact@example.com' COMMENT '联系邮箱',
  `contact_qq` varchar(20) DEFAULT '' COMMENT '联系QQ',
  `contact_qq_group` varchar(50) DEFAULT '' COMMENT '联系QQ群',
  `contact_doc_url` varchar(255) DEFAULT '' COMMENT '文档链接',
  `douyin_text` varchar(50) DEFAULT '我的抖音' COMMENT '抖音链接文字',
  `bilibili_text` varchar(50) DEFAULT '我的B站' COMMENT 'B站链接文字',
  `douyin_url` varchar(255) DEFAULT '' COMMENT '抖音链接URL',
  `bilibili_url` varchar(255) DEFAULT '' COMMENT 'B站链接URL',
  `footer_copyright` varchar(255) DEFAULT '' COMMENT '底部版权信息',
  `footer_version` varchar(50) DEFAULT '1.0.0' COMMENT '底部版本号'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站设置';

--
-- 转存表中的数据 `settings`
--

INSERT INTO `settings` (`id`, `site_title`, `site_description`, `site_text_logo`, `about_content`, `site_logo`, `site_favicon`, `theme_color`, `updated_at`, `contact_email`, `contact_qq`, `contact_qq_group`, `contact_doc_url`, `douyin_text`, `bilibili_text`, `douyin_url`, `bilibili_url`, `footer_copyright`, `footer_version`) VALUES
(1, '硬件调试工具11', '111', '硬件调试工具11', '1硬件调试工具是一个专业的串口调试平台，支持中兴微、ASR、展锐等多种芯片的串口通信调试。通过现代化的Web界面，开发者可以方便地进行AT指令测试、数据传输、固件升级等操作。11', '/assets/images/logo.png', '/assets/images/favicon.ico', '#000000', '2026-04-15 05:06:27', 'contact@example.com', '111', '111', 'http://192.168.0.100:8888/home', '我的抖音', '我的B站', 'https://www.baidu.com/', 'https://www.baidu.com/', '', '2.0.0');

-- --------------------------------------------------------

--
-- 表的结构 `system_config`
--

CREATE TABLE `system_config` (
  `id` int(11) NOT NULL,
  `config_key` varchar(100) NOT NULL,
  `config_value` text,
  `config_type` varchar(20) DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置';

--
-- 转存表中的数据 `system_config`
--

INSERT INTO `system_config` (`id`, `config_key`, `config_value`, `config_type`, `description`, `updated_at`) VALUES
(1, 'maintenance_mode', '0', 'boolean', '维护模式', '2026-04-13 05:24:48'),
(2, 'allow_registration', '0', 'boolean', '允许注册', '2026-04-13 05:24:48'),
(3, 'session_timeout', '3600', 'integer', '会话超时时间(秒)', '2026-04-13 05:24:48'),
(4, 'max_upload_size', '10485760', 'integer', '最大上传大小(字节)', '2026-04-13 05:24:48');

--
-- 转储表的索引
--

--
-- 表的索引 `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 表的索引 `page_stats`
--
ALTER TABLE `page_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_page_date` (`page_name`,`date`);

--
-- 表的索引 `serial_logs`
--
ALTER TABLE `serial_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_device` (`device_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- 表的索引 `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_key` (`config_key`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `page_stats`
--
ALTER TABLE `page_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=431;

--
-- 使用表AUTO_INCREMENT `serial_logs`
--
ALTER TABLE `serial_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `system_config`
--
ALTER TABLE `system_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
