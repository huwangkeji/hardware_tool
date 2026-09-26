<?php
/**
 * 数据库 Schema 与种子数据
 *
 * 由 initDatabase() 调用，负责建表和写入初始数据。
 * 所有 CREATE TABLE / INSERT 均使用 IF NOT EXISTS / ON CONFLICT，
 * 保证可重复执行（幂等）。
 *
 * @package HardwareDebugTool
 */

// 加载扩展 AT 指令数据
require_once __DIR__ . '/at_commands_data.php';

// ===========================================
// 建表 + 种子数据
// ===========================================

/**
 * 初始化数据库：建表 + 种子数据
 *
 * @param PDO $db
 */
function initDatabase(PDO $db) {
    // =========================================
    // 1. 建表
    // =========================================

    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            email TEXT NOT NULL,
            last_login TEXT,
            created_at TEXT NOT NULL
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            site_title TEXT NOT NULL,
            site_description TEXT NOT NULL,
            site_text_logo TEXT NOT NULL DEFAULT '硬件调试工具',
            about_content TEXT NOT NULL DEFAULT '',
            theme_color TEXT NOT NULL DEFAULT '#1890ff',
            contact_email TEXT NOT NULL DEFAULT '',
            contact_qq TEXT NOT NULL DEFAULT '',
            contact_qq_group TEXT NOT NULL DEFAULT '',
            contact_doc_url TEXT NOT NULL DEFAULT '',
            douyin_text TEXT NOT NULL DEFAULT '',
            douyin_url TEXT NOT NULL DEFAULT '',
            bilibili_text TEXT NOT NULL DEFAULT '',
            bilibili_url TEXT NOT NULL DEFAULT '',
            footer_copyright TEXT NOT NULL DEFAULT '',
            footer_version TEXT NOT NULL DEFAULT '1.0.0',
            core_features TEXT NOT NULL DEFAULT '',
            tech_features TEXT NOT NULL DEFAULT ''
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS page_stats (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_name TEXT NOT NULL,
            visit_count INTEGER NOT NULL DEFAULT 0,
            success_count INTEGER NOT NULL DEFAULT 0,
            fail_count INTEGER NOT NULL DEFAULT 0,
            last_visit TEXT,
            date TEXT NOT NULL,
            UNIQUE(page_name, date)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS serial_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            device_type TEXT NOT NULL,
            action TEXT NOT NULL,
            status INTEGER NOT NULL,
            data TEXT NOT NULL,
            port_name TEXT NOT NULL,
            error_msg TEXT NOT NULL,
            ip_address TEXT NOT NULL,
            created_at TEXT NOT NULL
        )
    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_serial_logs_device ON serial_logs(device_type)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_serial_logs_created ON serial_logs(created_at)");

    $db->exec("
        CREATE TABLE IF NOT EXISTS help_pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            category TEXT NOT NULL,
            content TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            is_published INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )
    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_help_pages_published ON help_pages(is_published)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_help_pages_sort ON help_pages(sort_order)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_help_pages_category ON help_pages(category)");

    $db->exec("
        CREATE TABLE IF NOT EXISTS chip_platforms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            short_name TEXT NOT NULL DEFAULT '',
            description TEXT NOT NULL DEFAULT '',
            icon TEXT NOT NULL DEFAULT '🔧',
            chips TEXT NOT NULL DEFAULT '[]',
            has_debug INTEGER NOT NULL DEFAULT 1,
            sort_order INTEGER NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS at_commands (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chip_type TEXT NOT NULL,
            command TEXT NOT NULL,
            title TEXT NOT NULL DEFAULT '',
            description TEXT NOT NULL DEFAULT '',
            syntax TEXT NOT NULL DEFAULT '',
            parameters TEXT NOT NULL DEFAULT '',
            response TEXT NOT NULL DEFAULT '',
            example TEXT NOT NULL DEFAULT '',
            category TEXT NOT NULL DEFAULT '基础',
            sort_order INTEGER NOT NULL DEFAULT 0,
            is_published INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )
    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_at_commands_chip ON at_commands(chip_type)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_at_commands_category ON at_commands(category)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_at_commands_published ON at_commands(is_published)");

    // =========================================
    // 2. 列迁移（为旧数据库添加新列）
    // =========================================
    migrateColumns($db, 'settings', [
        ['site_text_logo', 'TEXT NOT NULL DEFAULT "硬件调试工具"'],
        ['about_content', 'TEXT NOT NULL DEFAULT ""'],
        ['contact_doc_url', 'TEXT NOT NULL DEFAULT ""'],
        ['douyin_text', 'TEXT NOT NULL DEFAULT ""'],
        ['douyin_url', 'TEXT NOT NULL DEFAULT ""'],
        ['bilibili_text', 'TEXT NOT NULL DEFAULT ""'],
        ['bilibili_url', 'TEXT NOT NULL DEFAULT ""'],
        ['core_features', 'TEXT NOT NULL DEFAULT ""'],
        ['tech_features', 'TEXT NOT NULL DEFAULT ""'],
    ]);
    migrateColumns($db, 'chip_platforms', [
        ['short_name', 'TEXT NOT NULL DEFAULT ""'],
        ['has_debug', 'INTEGER NOT NULL DEFAULT 1'],
    ]);

    // =========================================
    // 3. 种子数据
    // =========================================
    seedDefaultData($db);
}

/**
 * 为已有表添加缺失的列（安全迁移）
 *
 * @param PDO    $db
 * @param string $table
 * @param array  $columns  [['column_name', 'column_def'], ...]
 */
function migrateColumns(PDO $db, string $table, array $columns) {
    $existing = [];
    $stmt = $db->prepare("PRAGMA table_info($table)");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $col) {
        $existing[] = $col['name'];
    }
    foreach ($columns as [$name, $def]) {
        if (!in_array($name, $existing)) {
            $db->exec("ALTER TABLE $table ADD COLUMN $name $def");
        }
    }
}

/**
 * 写入种子数据（幂等：已存在则跳过）
 *
 * @param PDO $db
 */
function seedDefaultData(PDO $db) {
    $now = date('Y-m-d H:i:s');

    // --- 管理员 ---
    $stmt = $db->prepare("SELECT COUNT(*) FROM admin_users");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $db->prepare("
            INSERT INTO admin_users (username, password_hash, email, created_at)
            VALUES (?, ?, ?, ?)
        ")->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin@example.com', $now]);
    }

    // --- 网站设置 ---
    $stmt = $db->prepare("SELECT COUNT(*) FROM settings");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $db->prepare("
            INSERT INTO settings (
                site_title, site_description, site_text_logo, about_content,
                theme_color, contact_email, contact_qq, contact_qq_group,
                contact_doc_url, douyin_text, douyin_url, bilibili_text, bilibili_url,
                footer_copyright, footer_version, core_features, tech_features
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?
            )
        ")->execute([
            '专业的随身WiFi硬件调试工具', '支持中兴微、ASR、展锐芯片的串口调试工具-沧州虎王科技开发', '硬件调试工具', '<p><strong>随身WiFi硬件调试工具</strong>是一个在线专业的串口调试平台，支持中兴微、ASR、展锐等多种芯片的串口通信调试。通过现代化的Web界面，开发者可以方便地进行AT指令测试、数据传输、固件升级等操作。<br /><strong>特别鸣谢:</strong><span style="color: #f1c40f;">抖音@阿诺WiFi</span>，<span style="color: #e03e2d;">抖音@小白随身Wi-Fi</span>，<span style="color: #169179;">抖音@你的阿宇</span>，<span style="color: #b96ad9;">抖音@海洋哦</span>，<span style="color: #3598db;">抖音@阿年的随身WiFi</span>，<span style="color: #843fa1;">抖音@流年</span>，<span style="color: #e03e2d;">抖音@随身WiFi研究社</span>，<span style="color: #e67e23;">抖音@六竹書生</span>，<span style="color: #2dc26b;">抖音@贪污腐化</span>等等</p>',
            '#1890ff', 'zesso@qq.com', '1559804340', '159603899',
            'https://www.douyin.com/user/self/search/NCYXF', '我的抖音', 'https://www.douyin.com/user/self/search/NCYXF', '我的B站', 'https://space.bilibili.com/352977016',
            '虎王科技 © 2026 随身WiFi在线调试工具', '2.0.5', '[{"icon":"🔌","title":"串口通信","desc":"支持Web Serial API，直接在浏览器中进行串口通信"},{"icon":"📡","title":"AT指令","desc":"内置常用AT指令集，支持自定义指令发送"},{"icon":"📊","title":"实时监控","desc":"实时显示串口数据，支持数据过滤和搜索"},{"icon":"🔄","title":"固件升级","desc":"支持芯片固件在线升级和版本管理"}]', '[{"icon":"🌐","title":"纯Web应用","desc":"基于浏览器运行，无需安装客户端软件"},{"icon":"🔒","title":"安全可靠","desc":"本地数据处理，不上传到服务器"},{"icon":"📱","title":"响应式设计","desc":"支持PC和移动端访问"},{"icon":"⚡","title":"高性能","desc":"优化的数据传输，低延迟通信"}]'
        ]);
    }

    // --- 帮助页面 ---
    $stmt = $db->prepare("SELECT COUNT(*) FROM help_pages");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $helpPages = [
        ['驱动安装说明', 'driver', '<h3>驱动安装步骤</h3><ol><li>下载对应芯片的驱动程序</li><li>关闭所有杀毒软件和防火墙</li><li>以管理员身份运行驱动安装程序</li><li>安装完成后重启电脑</li><li>设备管理器中确认COM端口已识别</li></ol><p>如遇安装失败，请尝试更换USB接口或使用USB2.0接口。</p>', 1, 1],
        ['浏览器使用要求', 'tool', '<h3>浏览器要求</h3><ul><li>必须使用Chrome 89+或基于Chromium内核的浏览器</li><li>需要开启Web Serial API支持</li><li>建议使用最新版Chrome或Edge浏览器</li></ul><h3>常见问题</h3><ul><li>串口无法识别：请确认浏览器版本并检查驱动是否正常</li><li>数据乱码：请检查波特率设置是否与设备一致</li><li>连接超时：请尝试重新插拔USB线缆</li></ul>', 2, 1],
        ['中兴微芯片调试指南', 'zte', '<h3>基本操作</h3><ol><li>进入中兴微调试页面</li><li>选择正确的波特率（默认115200）</li><li>点击连接串口选择AT端口</li><li>使用AT指令按钮或自定义指令进行调试</li></ol>', 3, 1],
        ['ASR芯片调试指南', 'asr', '<h3>基本操作</h3><ol><li>进入ASR调试页面</li><li>选择正确的波特率（默认115200）</li><li>点击连接串口选择AT端口</li><li>使用AT指令按钮或自定义指令进行调试</li></ol>', 4, 1],
        ['展锐芯片调试指南', 'unisoc', '<h3>基本操作</h3><ol><li>进入展锐调试页面</li><li>选择正确的波特率（默认115200）</li><li>点击连接串口选择AT端口</li><li>使用AT指令按钮或自定义指令进行调试</li></ol>', 5, 1],
        ];
        $insertHelp = $db->prepare("
            INSERT INTO help_pages (title, category, content, sort_order, is_published, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($helpPages as $hp) {
            $insertHelp->execute([$hp[0], $hp[1], $hp[2], $hp[3], $hp[4], $now, $now]);
        }
    }

    // --- 芯片平台 ---
    $stmt = $db->prepare("SELECT COUNT(*) FROM chip_platforms");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $platforms = [
        ['zte', '中兴微', 'ZTE', '中兴微芯片串口调试，AT指令测试，固件升级', '🔧', '["ZX2975","ZX2965","ZX2961"]', 1, 1],
        ['asr', 'ASR', 'ASR', 'ASR芯片串口调试，语音识别测试，数据传输', '📡', '["ASR1603","ASR1605","ASR1606","ASR3601"]', 2, 1],
        ['unisoc', '展锐', 'Unisoc', '展锐芯片串口调试，网络测试，系统诊断', '📱', '["UIS8910","UIS8920","UIS8950","T8310"]', 3, 1],
        ['esp32', 'ESP32', 'ESP32', 'ESP32单片机WiFi/蓝牙串口调试，AT固件通信', '🔌', '["ESP32","ESP32-C3","ESP32-S2","ESP32-S3"]', 4, 1],
        ['stm32', 'STM32', 'STM32', 'STM32单片机串口调试，外设控制，AT指令通信', '⚙️', '["STM32F103","STM32F407","STM32H7","STM32L4"]', 5, 1],
        ['qualcomm', '高通', 'Qualcomm', '高通芯片调试，MDM9205/9207及骁龙X系列5G模组', '🚀', '["MDM9205","MDM9207","SDX55","SDX65","X55","X65"]', 6, 1],
        ['eigencomm', '移芯通信', 'Eigencomm', '移芯通信EC618/EC619芯片调试，NB-IoT/Cat.1', '🔋', '["EC618","EC619"]', 7, 1],
        ['mediatek', '联发科', 'MediaTek', '联发科MT2625/MT2731/T750芯片调试', '🎯', '["MT2625","MT2731","T750"]', 8, 1],
        ['hisilicon', '海思', 'HiSilicon', '海思Boudica及巴龙系列芯片调试', '🐉', '["Boudica120","Boudica150","Boudica200","Balong5000","Balong765"]', 9, 1],
        ['xinyi', '芯翼信息', 'Xinyi', '芯翼信息XY1100/XY1200芯片调试，NB-IoT', '💡', '["XY1100","XY1200"]', 10, 1],
        ];
        $insertPlat = $db->prepare("
            INSERT INTO chip_platforms (type, name, short_name, description, icon, chips, sort_order, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($platforms as $p) {
            $insertPlat->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7]]);
        }
    }

    // --- AT 指令百科 ---
    $stmt = $db->prepare("SELECT COUNT(*) FROM at_commands");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $atCmds = getDefaultATCommands();
        $insertCmd = $db->prepare("
            INSERT INTO at_commands (chip_type, command, title, description, syntax, parameters, response, example, category, sort_order, is_published, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $sort = 0;
        foreach ($atCmds as $cmd) {
            $sort++;
            $insertCmd->execute([$cmd[0], $cmd[1], $cmd[2], $cmd[3], $cmd[4], $cmd[5], $cmd[6], $cmd[7], $cmd[8], $sort, 1, $now, $now]);
        }
    }
}

// ===========================================
// 默认数据辅助函数
// ===========================================

/**
 * 获取默认核心功能
 *
 * @return string JSON 字符串
 */
function getDefaultCoreFeatures() {
    return '[{"icon":"🔌","title":"串口通信","desc":"支持Web Serial API，直接在浏览器中进行串口通信"},{"icon":"📡","title":"AT指令","desc":"内置常用AT指令集，支持自定义指令发送"},{"icon":"📊","title":"实时监控","desc":"实时显示串口数据，支持数据过滤和搜索"},{"icon":"🔄","title":"固件升级","desc":"支持芯片固件在线升级和版本管理"}]';
}

/**
 * 获取默认技术特性
 *
 * @return string JSON 字符串
 */
function getDefaultTechFeatures() {
    return '[{"icon":"🌐","title":"纯Web应用","desc":"基于浏览器运行，无需安装客户端软件"},{"icon":"🔒","title":"安全可靠","desc":"本地数据处理，不上传到服务器"},{"icon":"📱","title":"响应式设计","desc":"支持PC和移动端访问"},{"icon":"⚡","title":"高性能","desc":"优化的数据传输，低延迟通信"}]';
}

/**
 * 获取默认AT指令百科数据
 *
 * 返回数组，每项格式：
 * [chip_type, command, title, description, syntax, parameters, response, example, category]
 *
 * @return array
 */
function getDefaultATCommands() {
    $base = [
        ['zte', 'AT', '基础测试', '检测模块是否响应', 'AT', '无', 'OK', 'AT
OK', '基础'],
        ['zte', 'ATI', '设备信息', '查询模块厂商信息', 'ATI', '无', '厂商信息文本', 'ATI
ZTE Corporation
OK', '设备信息'],
        ['zte', 'AT+CGMI', '厂商信息', '查询模块制造商名称', 'AT+CGMI', '无', '制造商名称', 'AT+CGMI
ZTE INCORPORATED
OK', '设备信息'],
        ['zte', 'AT+CGMM', '模块型号', '查询模块型号标识', 'AT+CGMM', '无', '模块型号字符串', 'AT+CGMM
ZX2975
OK', '设备信息'],
        ['zte', 'AT+CGMR', '软件版本', '查询模块软件版本号', 'AT+CGMR', '无', '软件版本字符串', 'AT+CGMR
ZX2975_V1.0.0
OK', '设备信息'],
        ['zte', 'AT+CGSN', 'IMEI号', '查询模块IMEI序列号', 'AT+CGSN', '无', '15位IMEI号码', 'AT+CGSN
865123456789012
OK', '设备信息'],
        ['zte', 'AT+CSQ', '信号质量', '查询当前信号质量', 'AT+CSQ', '无', '+CSQ: <rssi>,<ber>', '+CSQ: 25,0
OK', '网络'],
        ['zte', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡PIN码状态', 'AT+CPIN?', '无', '+CPIN: <code>', '+CPIN: READY
OK', 'SIM卡'],
        ['zte', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡是否需要PIN码', 'AT+CPIN?', '无', 'READY/SIM PIN/SIM PUK', '+CPIN: READY
OK', 'SIM卡'],
        ['zte', 'AT+CREG?', '网络注册', '查询CS网络注册状态', 'AT+CREG?', '无', '+CREG: <n>,<stat>', '+CREG: 0,1
OK', '网络'],
        ['zte', 'AT+CGREG?', 'GPRS注册', '查询GPRS网络注册状态', 'AT+CGREG?', '无', '+CGREG: <n>,<stat>', '+CGREG: 0,1
OK', '网络'],
        ['zte', 'AT+CEREG?', 'LTE注册', '查询EPS网络注册状态', 'AT+CEREG?', '无', '+CEREG: <n>,<stat>', '+CEREG: 0,1
OK', '网络'],
        ['zte', 'AT+COPS?', '运营商信息', '查询当前运营商信息', 'AT+COPS?', '无', '+COPS: <mode>,<format>,<oper>', '+COPS: 0,0,"CHINA MOBILE"
OK', '网络'],
        ['zte', 'AT+CGDCONT?', 'PDP上下文', '查询PDP上下文定义', 'AT+CGDCONT?', '无', '+CGDCONT: <cid>,<PDP_type>,<APN>', '+CGDCONT: 1,"IP","CMNET"
OK', '数据'],
        ['zte', 'AT+CGACT?', 'PDP激活状态', '查询PDP上下文激活状态', 'AT+CGACT?', '无', '+CGACT: <cid>,<state>', '+CGACT: 1,1
OK', '数据'],
        ['zte', 'AT+CFUN=1,1', '重启模块', '重启模块', 'AT+CFUN=1,1', '无', 'OK', 'AT+CFUN=1,1
OK', '基础'],
        ['zte', 'AT+CFUN=0', '最小功能', '设置模块为最小功能模式', 'AT+CFUN=0', '无', 'OK', 'AT+CFUN=0
OK', '基础'],
        ['zte', 'AT+CFUN=1', '全功能', '设置模块为全功能模式', 'AT+CFUN=1', '无', 'OK', 'AT+CFUN=1
OK', '基础'],
        ['zte', 'AT+CMGF=1', '短信模式', '设置短信为文本模式', 'AT+CMGF=1', '1=文本,0=PDU', 'OK', 'AT+CMGF=1
OK', '短信'],
        ['zte', 'AT+CMGS', '发送短信', '发送文本短信', 'AT+CMGS="<phone>"', '目标手机号', '> 提示输入内容，Ctrl+Z结束', 'AT+CMGS="10086"
>Hello
OK', '短信'],
        ['zte', 'AT+CMGR', '读取短信', '读取指定索引的短信', 'AT+CMGR=<index>', '短信索引号', '+CMGR: <stat>,<oa>,...,<data>', 'AT+CMGR=1
+CMGR: "REC READ","10086",...
OK', '短信'],
        ['zte', 'AT+CMGL', '列出短信', '列出指定状态的短信', 'AT+CMGL="<stat>"', 'ALL/REC UNREAD/REC READ', '+CMGL: ...', 'AT+CMGL="ALL"
OK', '短信'],
        ['zte', 'AT+CNMI', '短信通知', '设置新短信指示方式', 'AT+CNMI=<mode>,<mt>', 'mode,mt参数', 'OK', 'AT+CNMI=2,1
OK', '短信'],
        ['zte', 'AT+CLCC', '当前通话', '查询当前通话列表', 'AT+CLCC', '无', '+CLCC: <id>,<dir>,<stat>,<mode>,...,<number>', 'AT+CLCC
OK', '通话'],
        ['zte', 'ATD', '拨号', '拨打电话号码', 'ATD<number>;', '目标号码，分号结尾语音', 'OK/CONNECT', 'ATD10086;
OK', '通话'],
        ['zte', 'ATH', '挂断', '挂断当前通话', 'ATH', '无', 'OK', 'ATH
OK', '通话'],
        ['zte', 'ATA', '接听', '接听来电', 'ATA', '无', 'OK', 'ATA
OK', '通话'],
        ['zte', 'AT+CGATT?', 'GPRS附着', '查询GPRS附着状态', 'AT+CGATT?', '无', '+CGATT: <state>', '+CGATT: 1
OK', '数据'],
        ['zte', 'AT+CGATT=1', 'GPRS附着', '激活GPRS附着', 'AT+CGATT=1', '无', 'OK', 'AT+CGATT=1
OK', '数据'],
        ['zte', 'AT+CGATT=0', 'GPRS去附着', '取消GPRS附着', 'AT+CGATT=0', '无', 'OK', 'AT+CGATT=0
OK', '数据'],
        ['zte', 'AT+CESQ', '扩展信号', '查询扩展信号质量', 'AT+CESQ', '无', '+CESQ: <rq>,<rqlp>,<rqhp>,<rsrp>,<rsrq>', '+CESQ: 99,99,99,75,65
OK', '网络'],
        ['zte', 'AT+CGCONTRDP', 'PDP信息', '查询PDP上下文信息', 'AT+CGCONTRDP', '无', '+CGCONTRDP: ...', 'AT+CGCONTRDP
OK', '数据'],
        ['zte', 'AT+CGSCONT', 'PDP范围', '查询PDP上下文范围', 'AT+CGSCONT', '无', '+CGSCONT: ...', 'AT+CGSCONT
OK', '数据'],
        ['zte', 'AT+V', '版本信息', '查询模块版本信息', 'AT+V', '无', '版本信息', 'AT+V
OK', '设备信息'],
        ['zte', 'ATE0', '回显关闭', '关闭命令回显', 'ATE0', '0=关闭', 'OK', 'ATE0
OK', '基础'],
        ['zte', 'ATE1', '回显开启', '开启命令回显', 'ATE1', '1=开启', 'OK', 'ATE1
OK', '基础'],
        ['zte', 'AT&W', '保存配置', '保存当前配置到用户配置文件', 'AT&W', '无', 'OK', 'AT&W
OK', '基础'],
        ['zte', 'ATZ', '复位', '复位模块到默认配置', 'ATZ', '无', 'OK', 'ATZ
OK', '基础'],
        ['asr', 'AT', '基础测试', '检测模块是否响应', 'AT', '无', 'OK', 'AT
OK', '基础'],
        ['asr', 'ATI', '设备信息', '查询模块厂商信息', 'ATI', '无', '厂商信息文本', 'ATI
ZTE Corporation
OK', '设备信息'],
        ['asr', 'AT+CGMI', '厂商信息', '查询模块制造商名称', 'AT+CGMI', '无', '制造商名称', 'AT+CGMI
ZTE INCORPORATED
OK', '设备信息'],
        ['asr', 'AT+CGMM', '模块型号', '查询模块型号标识', 'AT+CGMM', '无', '模块型号字符串', 'AT+CGMM
ZX2975
OK', '设备信息'],
        ['asr', 'AT+CGMR', '软件版本', '查询模块软件版本号', 'AT+CGMR', '无', '软件版本字符串', 'AT+CGMR
ZX2975_V1.0.0
OK', '设备信息'],
        ['asr', 'AT+CGSN', 'IMEI号', '查询模块IMEI序列号', 'AT+CGSN', '无', '15位IMEI号码', 'AT+CGSN
865123456789012
OK', '设备信息'],
        ['asr', 'AT+CSQ', '信号质量', '查询当前信号质量', 'AT+CSQ', '无', '+CSQ: <rssi>,<ber>', '+CSQ: 25,0
OK', '网络'],
        ['asr', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡PIN码状态', 'AT+CPIN?', '无', '+CPIN: <code>', '+CPIN: READY
OK', 'SIM卡'],
        ['asr', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡是否需要PIN码', 'AT+CPIN?', '无', 'READY/SIM PIN/SIM PUK', '+CPIN: READY
OK', 'SIM卡'],
        ['asr', 'AT+CREG?', '网络注册', '查询CS网络注册状态', 'AT+CREG?', '无', '+CREG: <n>,<stat>', '+CREG: 0,1
OK', '网络'],
        ['asr', 'AT+CGREG?', 'GPRS注册', '查询GPRS网络注册状态', 'AT+CGREG?', '无', '+CGREG: <n>,<stat>', '+CGREG: 0,1
OK', '网络'],
        ['asr', 'AT+CEREG?', 'LTE注册', '查询EPS网络注册状态', 'AT+CEREG?', '无', '+CEREG: <n>,<stat>', '+CEREG: 0,1
OK', '网络'],
        ['asr', 'AT+COPS?', '运营商信息', '查询当前运营商信息', 'AT+COPS?', '无', '+COPS: <mode>,<format>,<oper>', '+COPS: 0,0,"CHINA MOBILE"
OK', '网络'],
        ['asr', 'AT+CGDCONT?', 'PDP上下文', '查询PDP上下文定义', 'AT+CGDCONT?', '无', '+CGDCONT: <cid>,<PDP_type>,<APN>', '+CGDCONT: 1,"IP","CMNET"
OK', '数据'],
        ['asr', 'AT+CGACT?', 'PDP激活状态', '查询PDP上下文激活状态', 'AT+CGACT?', '无', '+CGACT: <cid>,<state>', '+CGACT: 1,1
OK', '数据'],
        ['asr', 'AT+CFUN=1,1', '重启模块', '重启模块', 'AT+CFUN=1,1', '无', 'OK', 'AT+CFUN=1,1
OK', '基础'],
        ['asr', 'AT+CFUN=0', '最小功能', '设置模块为最小功能模式', 'AT+CFUN=0', '无', 'OK', 'AT+CFUN=0
OK', '基础'],
        ['asr', 'AT+CFUN=1', '全功能', '设置模块为全功能模式', 'AT+CFUN=1', '无', 'OK', 'AT+CFUN=1
OK', '基础'],
        ['asr', 'AT+CMGF=1', '短信模式', '设置短信为文本模式', 'AT+CMGF=1', '1=文本,0=PDU', 'OK', 'AT+CMGF=1
OK', '短信'],
        ['asr', 'AT+CMGS', '发送短信', '发送文本短信', 'AT+CMGS="<phone>"', '目标手机号', '> 提示输入内容，Ctrl+Z结束', 'AT+CMGS="10086"
>Hello
OK', '短信'],
        ['asr', 'AT+CMGR', '读取短信', '读取指定索引的短信', 'AT+CMGR=<index>', '短信索引号', '+CMGR: <stat>,<oa>,...,<data>', 'AT+CMGR=1
+CMGR: "REC READ","10086",...
OK', '短信'],
        ['asr', 'AT+CMGL', '列出短信', '列出指定状态的短信', 'AT+CMGL="<stat>"', 'ALL/REC UNREAD/REC READ', '+CMGL: ...', 'AT+CMGL="ALL"
OK', '短信'],
        ['asr', 'AT+CNMI', '短信通知', '设置新短信指示方式', 'AT+CNMI=<mode>,<mt>', 'mode,mt参数', 'OK', 'AT+CNMI=2,1
OK', '短信'],
        ['asr', 'AT+CLCC', '当前通话', '查询当前通话列表', 'AT+CLCC', '无', '+CLCC: <id>,<dir>,<stat>,<mode>,...,<number>', 'AT+CLCC
OK', '通话'],
        ['asr', 'ATD', '拨号', '拨打电话号码', 'ATD<number>;', '目标号码，分号结尾语音', 'OK/CONNECT', 'ATD10086;
OK', '通话'],
        ['asr', 'ATH', '挂断', '挂断当前通话', 'ATH', '无', 'OK', 'ATH
OK', '通话'],
        ['asr', 'ATA', '接听', '接听来电', 'ATA', '无', 'OK', 'ATA
OK', '通话'],
        ['asr', 'AT+CGATT?', 'GPRS附着', '查询GPRS附着状态', 'AT+CGATT?', '无', '+CGATT: <state>', '+CGATT: 1
OK', '数据'],
        ['asr', 'AT+CGATT=1', 'GPRS附着', '激活GPRS附着', 'AT+CGATT=1', '无', 'OK', 'AT+CGATT=1
OK', '数据'],
        ['asr', 'AT+CGATT=0', 'GPRS去附着', '取消GPRS附着', 'AT+CGATT=0', '无', 'OK', 'AT+CGATT=0
OK', '数据'],
        ['asr', 'AT+CESQ', '扩展信号', '查询扩展信号质量', 'AT+CESQ', '无', '+CESQ: <rq>,<rqlp>,<rqhp>,<rsrp>,<rsrq>', '+CESQ: 99,99,99,75,65
OK', '网络'],
        ['asr', 'AT+CGCONTRDP', 'PDP信息', '查询PDP上下文信息', 'AT+CGCONTRDP', '无', '+CGCONTRDP: ...', 'AT+CGCONTRDP
OK', '数据'],
        ['asr', 'AT+CGSCONT', 'PDP范围', '查询PDP上下文范围', 'AT+CGSCONT', '无', '+CGSCONT: ...', 'AT+CGSCONT
OK', '数据'],
        ['asr', 'AT+V', '版本信息', '查询模块版本信息', 'AT+V', '无', '版本信息', 'AT+V
OK', '设备信息'],
        ['asr', 'ATE0', '回显关闭', '关闭命令回显', 'ATE0', '0=关闭', 'OK', 'ATE0
OK', '基础'],
        ['asr', 'ATE1', '回显开启', '开启命令回显', 'ATE1', '1=开启', 'OK', 'ATE1
OK', '基础'],
        ['asr', 'AT&W', '保存配置', '保存当前配置到用户配置文件', 'AT&W', '无', 'OK', 'AT&W
OK', '基础'],
        ['asr', 'ATZ', '复位', '复位模块到默认配置', 'ATZ', '无', 'OK', 'ATZ
OK', '基础'],
        ['unisoc', 'AT', '基础测试', '检测模块是否响应', 'AT', '无', 'OK', 'AT
OK', '基础'],
        ['unisoc', 'ATI', '设备信息', '查询模块厂商信息', 'ATI', '无', '厂商信息文本', 'ATI
ZTE Corporation
OK', '设备信息'],
        ['unisoc', 'AT+CGMI', '厂商信息', '查询模块制造商名称', 'AT+CGMI', '无', '制造商名称', 'AT+CGMI
ZTE INCORPORATED
OK', '设备信息'],
        ['unisoc', 'AT+CGMM', '模块型号', '查询模块型号标识', 'AT+CGMM', '无', '模块型号字符串', 'AT+CGMM
ZX2975
OK', '设备信息'],
        ['unisoc', 'AT+CGMR', '软件版本', '查询模块软件版本号', 'AT+CGMR', '无', '软件版本字符串', 'AT+CGMR
ZX2975_V1.0.0
OK', '设备信息'],
        ['unisoc', 'AT+CGSN', 'IMEI号', '查询模块IMEI序列号', 'AT+CGSN', '无', '15位IMEI号码', 'AT+CGSN
865123456789012
OK', '设备信息'],
        ['unisoc', 'AT+CSQ', '信号质量', '查询当前信号质量', 'AT+CSQ', '无', '+CSQ: <rssi>,<ber>', '+CSQ: 25,0
OK', '网络'],
        ['unisoc', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡PIN码状态', 'AT+CPIN?', '无', '+CPIN: <code>', '+CPIN: READY
OK', 'SIM卡'],
        ['unisoc', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡是否需要PIN码', 'AT+CPIN?', '无', 'READY/SIM PIN/SIM PUK', '+CPIN: READY
OK', 'SIM卡'],
        ['unisoc', 'AT+CREG?', '网络注册', '查询CS网络注册状态', 'AT+CREG?', '无', '+CREG: <n>,<stat>', '+CREG: 0,1
OK', '网络'],
        ['unisoc', 'AT+CGREG?', 'GPRS注册', '查询GPRS网络注册状态', 'AT+CGREG?', '无', '+CGREG: <n>,<stat>', '+CGREG: 0,1
OK', '网络'],
        ['unisoc', 'AT+CEREG?', 'LTE注册', '查询EPS网络注册状态', 'AT+CEREG?', '无', '+CEREG: <n>,<stat>', '+CEREG: 0,1
OK', '网络'],
        ['unisoc', 'AT+COPS?', '运营商信息', '查询当前运营商信息', 'AT+COPS?', '无', '+COPS: <mode>,<format>,<oper>', '+COPS: 0,0,"CHINA MOBILE"
OK', '网络'],
        ['unisoc', 'AT+CGDCONT?', 'PDP上下文', '查询PDP上下文定义', 'AT+CGDCONT?', '无', '+CGDCONT: <cid>,<PDP_type>,<APN>', '+CGDCONT: 1,"IP","CMNET"
OK', '数据'],
        ['unisoc', 'AT+CGACT?', 'PDP激活状态', '查询PDP上下文激活状态', 'AT+CGACT?', '无', '+CGACT: <cid>,<state>', '+CGACT: 1,1
OK', '数据'],
        ['unisoc', 'AT+CFUN=1,1', '重启模块', '重启模块', 'AT+CFUN=1,1', '无', 'OK', 'AT+CFUN=1,1
OK', '基础'],
        ['unisoc', 'AT+CFUN=0', '最小功能', '设置模块为最小功能模式', 'AT+CFUN=0', '无', 'OK', 'AT+CFUN=0
OK', '基础'],
        ['unisoc', 'AT+CFUN=1', '全功能', '设置模块为全功能模式', 'AT+CFUN=1', '无', 'OK', 'AT+CFUN=1
OK', '基础'],
        ['unisoc', 'AT+CMGF=1', '短信模式', '设置短信为文本模式', 'AT+CMGF=1', '1=文本,0=PDU', 'OK', 'AT+CMGF=1
OK', '短信'],
        ['unisoc', 'AT+CMGS', '发送短信', '发送文本短信', 'AT+CMGS="<phone>"', '目标手机号', '> 提示输入内容，Ctrl+Z结束', 'AT+CMGS="10086"
>Hello
OK', '短信'],
        ['unisoc', 'AT+CMGR', '读取短信', '读取指定索引的短信', 'AT+CMGR=<index>', '短信索引号', '+CMGR: <stat>,<oa>,...,<data>', 'AT+CMGR=1
+CMGR: "REC READ","10086",...
OK', '短信'],
        ['unisoc', 'AT+CMGL', '列出短信', '列出指定状态的短信', 'AT+CMGL="<stat>"', 'ALL/REC UNREAD/REC READ', '+CMGL: ...', 'AT+CMGL="ALL"
OK', '短信'],
        ['unisoc', 'AT+CNMI', '短信通知', '设置新短信指示方式', 'AT+CNMI=<mode>,<mt>', 'mode,mt参数', 'OK', 'AT+CNMI=2,1
OK', '短信'],
        ['unisoc', 'AT+CLCC', '当前通话', '查询当前通话列表', 'AT+CLCC', '无', '+CLCC: <id>,<dir>,<stat>,<mode>,...,<number>', 'AT+CLCC
OK', '通话'],
        ['unisoc', 'ATD', '拨号', '拨打电话号码', 'ATD<number>;', '目标号码，分号结尾语音', 'OK/CONNECT', 'ATD10086;
OK', '通话'],
        ['unisoc', 'ATH', '挂断', '挂断当前通话', 'ATH', '无', 'OK', 'ATH
OK', '通话'],
        ['unisoc', 'ATA', '接听', '接听来电', 'ATA', '无', 'OK', 'ATA
OK', '通话'],
        ['unisoc', 'AT+CGATT?', 'GPRS附着', '查询GPRS附着状态', 'AT+CGATT?', '无', '+CGATT: <state>', '+CGATT: 1
OK', '数据'],
        ['unisoc', 'AT+CGATT=1', 'GPRS附着', '激活GPRS附着', 'AT+CGATT=1', '无', 'OK', 'AT+CGATT=1
OK', '数据'],
        ['unisoc', 'AT+CGATT=0', 'GPRS去附着', '取消GPRS附着', 'AT+CGATT=0', '无', 'OK', 'AT+CGATT=0
OK', '数据'],
        ['unisoc', 'AT+CESQ', '扩展信号', '查询扩展信号质量', 'AT+CESQ', '无', '+CESQ: <rq>,<rqlp>,<rqhp>,<rsrp>,<rsrq>', '+CESQ: 99,99,99,75,65
OK', '网络'],
        ['unisoc', 'AT+CGCONTRDP', 'PDP信息', '查询PDP上下文信息', 'AT+CGCONTRDP', '无', '+CGCONTRDP: ...', 'AT+CGCONTRDP
OK', '数据'],
        ['unisoc', 'AT+CGSCONT', 'PDP范围', '查询PDP上下文范围', 'AT+CGSCONT', '无', '+CGSCONT: ...', 'AT+CGSCONT
OK', '数据'],
        ['unisoc', 'AT+V', '版本信息', '查询模块版本信息', 'AT+V', '无', '版本信息', 'AT+V
OK', '设备信息'],
        ['unisoc', 'ATE0', '回显关闭', '关闭命令回显', 'ATE0', '0=关闭', 'OK', 'ATE0
OK', '基础'],
        ['unisoc', 'ATE1', '回显开启', '开启命令回显', 'ATE1', '1=开启', 'OK', 'ATE1
OK', '基础'],
        ['unisoc', 'AT&W', '保存配置', '保存当前配置到用户配置文件', 'AT&W', '无', 'OK', 'AT&W
OK', '基础'],
        ['unisoc', 'ATZ', '复位', '复位模块到默认配置', 'ATZ', '无', 'OK', 'ATZ
OK', '基础'],
        ['qualcomm', 'AT', '基础测试', '检测模块是否响应', 'AT', '无', 'OK', 'AT
OK', '基础'],
        ['qualcomm', 'ATI', '设备信息', '查询模块厂商信息', 'ATI', '无', '厂商信息文本', 'ATI
ZTE Corporation
OK', '设备信息'],
        ['qualcomm', 'AT+CGMI', '厂商信息', '查询模块制造商名称', 'AT+CGMI', '无', '制造商名称', 'AT+CGMI
ZTE INCORPORATED
OK', '设备信息'],
        ['qualcomm', 'AT+CGMM', '模块型号', '查询模块型号标识', 'AT+CGMM', '无', '模块型号字符串', 'AT+CGMM
ZX2975
OK', '设备信息'],
        ['qualcomm', 'AT+CGMR', '软件版本', '查询模块软件版本号', 'AT+CGMR', '无', '软件版本字符串', 'AT+CGMR
ZX2975_V1.0.0
OK', '设备信息'],
        ['qualcomm', 'AT+CGSN', 'IMEI号', '查询模块IMEI序列号', 'AT+CGSN', '无', '15位IMEI号码', 'AT+CGSN
865123456789012
OK', '设备信息'],
        ['qualcomm', 'AT+CSQ', '信号质量', '查询当前信号质量', 'AT+CSQ', '无', '+CSQ: <rssi>,<ber>', '+CSQ: 25,0
OK', '网络'],
        ['qualcomm', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡PIN码状态', 'AT+CPIN?', '无', '+CPIN: <code>', '+CPIN: READY
OK', 'SIM卡'],
        ['qualcomm', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡是否需要PIN码', 'AT+CPIN?', '无', 'READY/SIM PIN/SIM PUK', '+CPIN: READY
OK', 'SIM卡'],
        ['qualcomm', 'AT+CREG?', '网络注册', '查询CS网络注册状态', 'AT+CREG?', '无', '+CREG: <n>,<stat>', '+CREG: 0,1
OK', '网络'],
        ['qualcomm', 'AT+CGREG?', 'GPRS注册', '查询GPRS网络注册状态', 'AT+CGREG?', '无', '+CGREG: <n>,<stat>', '+CGREG: 0,1
OK', '网络'],
        ['qualcomm', 'AT+CEREG?', 'LTE注册', '查询EPS网络注册状态', 'AT+CEREG?', '无', '+CEREG: <n>,<stat>', '+CEREG: 0,1
OK', '网络'],
        ['qualcomm', 'AT+COPS?', '运营商信息', '查询当前运营商信息', 'AT+COPS?', '无', '+COPS: <mode>,<format>,<oper>', '+COPS: 0,0,"CHINA MOBILE"
OK', '网络'],
        ['qualcomm', 'AT+CGDCONT?', 'PDP上下文', '查询PDP上下文定义', 'AT+CGDCONT?', '无', '+CGDCONT: <cid>,<PDP_type>,<APN>', '+CGDCONT: 1,"IP","CMNET"
OK', '数据'],
        ['qualcomm', 'AT+CGACT?', 'PDP激活状态', '查询PDP上下文激活状态', 'AT+CGACT?', '无', '+CGACT: <cid>,<state>', '+CGACT: 1,1
OK', '数据'],
        ['qualcomm', 'AT+CFUN=1,1', '重启模块', '重启模块', 'AT+CFUN=1,1', '无', 'OK', 'AT+CFUN=1,1
OK', '基础'],
        ['qualcomm', 'AT+CFUN=0', '最小功能', '设置模块为最小功能模式', 'AT+CFUN=0', '无', 'OK', 'AT+CFUN=0
OK', '基础'],
        ['qualcomm', 'AT+CFUN=1', '全功能', '设置模块为全功能模式', 'AT+CFUN=1', '无', 'OK', 'AT+CFUN=1
OK', '基础'],
        ['qualcomm', 'AT+CMGF=1', '短信模式', '设置短信为文本模式', 'AT+CMGF=1', '1=文本,0=PDU', 'OK', 'AT+CMGF=1
OK', '短信'],
        ['qualcomm', 'AT+CMGS', '发送短信', '发送文本短信', 'AT+CMGS="<phone>"', '目标手机号', '> 提示输入内容，Ctrl+Z结束', 'AT+CMGS="10086"
>Hello
OK', '短信'],
        ['qualcomm', 'AT+CMGR', '读取短信', '读取指定索引的短信', 'AT+CMGR=<index>', '短信索引号', '+CMGR: <stat>,<oa>,...,<data>', 'AT+CMGR=1
+CMGR: "REC READ","10086",...
OK', '短信'],
        ['qualcomm', 'AT+CMGL', '列出短信', '列出指定状态的短信', 'AT+CMGL="<stat>"', 'ALL/REC UNREAD/REC READ', '+CMGL: ...', 'AT+CMGL="ALL"
OK', '短信'],
        ['qualcomm', 'AT+CNMI', '短信通知', '设置新短信指示方式', 'AT+CNMI=<mode>,<mt>', 'mode,mt参数', 'OK', 'AT+CNMI=2,1
OK', '短信'],
        ['qualcomm', 'AT+CLCC', '当前通话', '查询当前通话列表', 'AT+CLCC', '无', '+CLCC: <id>,<dir>,<stat>,<mode>,...,<number>', 'AT+CLCC
OK', '通话'],
        ['qualcomm', 'ATD', '拨号', '拨打电话号码', 'ATD<number>;', '目标号码，分号结尾语音', 'OK/CONNECT', 'ATD10086;
OK', '通话'],
        ['qualcomm', 'ATH', '挂断', '挂断当前通话', 'ATH', '无', 'OK', 'ATH
OK', '通话'],
        ['qualcomm', 'ATA', '接听', '接听来电', 'ATA', '无', 'OK', 'ATA
OK', '通话'],
        ['qualcomm', 'AT+CGATT?', 'GPRS附着', '查询GPRS附着状态', 'AT+CGATT?', '无', '+CGATT: <state>', '+CGATT: 1
OK', '数据'],
        ['qualcomm', 'AT+CGATT=1', 'GPRS附着', '激活GPRS附着', 'AT+CGATT=1', '无', 'OK', 'AT+CGATT=1
OK', '数据'],
        ['qualcomm', 'AT+CGATT=0', 'GPRS去附着', '取消GPRS附着', 'AT+CGATT=0', '无', 'OK', 'AT+CGATT=0
OK', '数据'],
        ['qualcomm', 'AT+CESQ', '扩展信号', '查询扩展信号质量', 'AT+CESQ', '无', '+CESQ: <rq>,<rqlp>,<rqhp>,<rsrp>,<rsrq>', '+CESQ: 99,99,99,75,65
OK', '网络'],
        ['qualcomm', 'AT+CGCONTRDP', 'PDP信息', '查询PDP上下文信息', 'AT+CGCONTRDP', '无', '+CGCONTRDP: ...', 'AT+CGCONTRDP
OK', '数据'],
        ['qualcomm', 'AT+CGSCONT', 'PDP范围', '查询PDP上下文范围', 'AT+CGSCONT', '无', '+CGSCONT: ...', 'AT+CGSCONT
OK', '数据'],
        ['qualcomm', 'AT+V', '版本信息', '查询模块版本信息', 'AT+V', '无', '版本信息', 'AT+V
OK', '设备信息'],
        ['qualcomm', 'ATE0', '回显关闭', '关闭命令回显', 'ATE0', '0=关闭', 'OK', 'ATE0
OK', '基础'],
        ['qualcomm', 'ATE1', '回显开启', '开启命令回显', 'ATE1', '1=开启', 'OK', 'ATE1
OK', '基础'],
        ['qualcomm', 'AT&W', '保存配置', '保存当前配置到用户配置文件', 'AT&W', '无', 'OK', 'AT&W
OK', '基础'],
        ['qualcomm', 'ATZ', '复位', '复位模块到默认配置', 'ATZ', '无', 'OK', 'ATZ
OK', '基础'],
        ['eigencomm', 'AT', '基础测试', '检测模块是否响应', 'AT', '无', 'OK', 'AT
OK', '基础'],
        ['eigencomm', 'ATI', '设备信息', '查询模块厂商信息', 'ATI', '无', '厂商信息文本', 'ATI
ZTE Corporation
OK', '设备信息'],
        ['eigencomm', 'AT+CGMI', '厂商信息', '查询模块制造商名称', 'AT+CGMI', '无', '制造商名称', 'AT+CGMI
ZTE INCORPORATED
OK', '设备信息'],
        ['eigencomm', 'AT+CGMM', '模块型号', '查询模块型号标识', 'AT+CGMM', '无', '模块型号字符串', 'AT+CGMM
ZX2975
OK', '设备信息'],
        ['eigencomm', 'AT+CGMR', '软件版本', '查询模块软件版本号', 'AT+CGMR', '无', '软件版本字符串', 'AT+CGMR
ZX2975_V1.0.0
OK', '设备信息'],
        ['eigencomm', 'AT+CGSN', 'IMEI号', '查询模块IMEI序列号', 'AT+CGSN', '无', '15位IMEI号码', 'AT+CGSN
865123456789012
OK', '设备信息'],
        ['eigencomm', 'AT+CSQ', '信号质量', '查询当前信号质量', 'AT+CSQ', '无', '+CSQ: <rssi>,<ber>', '+CSQ: 25,0
OK', '网络'],
        ['eigencomm', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡PIN码状态', 'AT+CPIN?', '无', '+CPIN: <code>', '+CPIN: READY
OK', 'SIM卡'],
        ['eigencomm', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡是否需要PIN码', 'AT+CPIN?', '无', 'READY/SIM PIN/SIM PUK', '+CPIN: READY
OK', 'SIM卡'],
        ['eigencomm', 'AT+CREG?', '网络注册', '查询CS网络注册状态', 'AT+CREG?', '无', '+CREG: <n>,<stat>', '+CREG: 0,1
OK', '网络'],
        ['eigencomm', 'AT+CGREG?', 'GPRS注册', '查询GPRS网络注册状态', 'AT+CGREG?', '无', '+CGREG: <n>,<stat>', '+CGREG: 0,1
OK', '网络'],
        ['eigencomm', 'AT+CEREG?', 'LTE注册', '查询EPS网络注册状态', 'AT+CEREG?', '无', '+CEREG: <n>,<stat>', '+CEREG: 0,1
OK', '网络'],
        ['eigencomm', 'AT+COPS?', '运营商信息', '查询当前运营商信息', 'AT+COPS?', '无', '+COPS: <mode>,<format>,<oper>', '+COPS: 0,0,"CHINA MOBILE"
OK', '网络'],
        ['eigencomm', 'AT+CGDCONT?', 'PDP上下文', '查询PDP上下文定义', 'AT+CGDCONT?', '无', '+CGDCONT: <cid>,<PDP_type>,<APN>', '+CGDCONT: 1,"IP","CMNET"
OK', '数据'],
        ['eigencomm', 'AT+CGACT?', 'PDP激活状态', '查询PDP上下文激活状态', 'AT+CGACT?', '无', '+CGACT: <cid>,<state>', '+CGACT: 1,1
OK', '数据'],
        ['eigencomm', 'AT+CFUN=1,1', '重启模块', '重启模块', 'AT+CFUN=1,1', '无', 'OK', 'AT+CFUN=1,1
OK', '基础'],
        ['eigencomm', 'AT+CFUN=0', '最小功能', '设置模块为最小功能模式', 'AT+CFUN=0', '无', 'OK', 'AT+CFUN=0
OK', '基础'],
        ['eigencomm', 'AT+CFUN=1', '全功能', '设置模块为全功能模式', 'AT+CFUN=1', '无', 'OK', 'AT+CFUN=1
OK', '基础'],
        ['eigencomm', 'AT+CMGF=1', '短信模式', '设置短信为文本模式', 'AT+CMGF=1', '1=文本,0=PDU', 'OK', 'AT+CMGF=1
OK', '短信'],
        ['eigencomm', 'AT+CMGS', '发送短信', '发送文本短信', 'AT+CMGS="<phone>"', '目标手机号', '> 提示输入内容，Ctrl+Z结束', 'AT+CMGS="10086"
>Hello
OK', '短信'],
        ['eigencomm', 'AT+CMGR', '读取短信', '读取指定索引的短信', 'AT+CMGR=<index>', '短信索引号', '+CMGR: <stat>,<oa>,...,<data>', 'AT+CMGR=1
+CMGR: "REC READ","10086",...
OK', '短信'],
        ['eigencomm', 'AT+CMGL', '列出短信', '列出指定状态的短信', 'AT+CMGL="<stat>"', 'ALL/REC UNREAD/REC READ', '+CMGL: ...', 'AT+CMGL="ALL"
OK', '短信'],
        ['eigencomm', 'AT+CNMI', '短信通知', '设置新短信指示方式', 'AT+CNMI=<mode>,<mt>', 'mode,mt参数', 'OK', 'AT+CNMI=2,1
OK', '短信'],
        ['eigencomm', 'AT+CLCC', '当前通话', '查询当前通话列表', 'AT+CLCC', '无', '+CLCC: <id>,<dir>,<stat>,<mode>,...,<number>', 'AT+CLCC
OK', '通话'],
        ['eigencomm', 'ATD', '拨号', '拨打电话号码', 'ATD<number>;', '目标号码，分号结尾语音', 'OK/CONNECT', 'ATD10086;
OK', '通话'],
        ['eigencomm', 'ATH', '挂断', '挂断当前通话', 'ATH', '无', 'OK', 'ATH
OK', '通话'],
        ['eigencomm', 'ATA', '接听', '接听来电', 'ATA', '无', 'OK', 'ATA
OK', '通话'],
        ['eigencomm', 'AT+CGATT?', 'GPRS附着', '查询GPRS附着状态', 'AT+CGATT?', '无', '+CGATT: <state>', '+CGATT: 1
OK', '数据'],
        ['eigencomm', 'AT+CGATT=1', 'GPRS附着', '激活GPRS附着', 'AT+CGATT=1', '无', 'OK', 'AT+CGATT=1
OK', '数据'],
        ['eigencomm', 'AT+CGATT=0', 'GPRS去附着', '取消GPRS附着', 'AT+CGATT=0', '无', 'OK', 'AT+CGATT=0
OK', '数据'],
        ['eigencomm', 'AT+CESQ', '扩展信号', '查询扩展信号质量', 'AT+CESQ', '无', '+CESQ: <rq>,<rqlp>,<rqhp>,<rsrp>,<rsrq>', '+CESQ: 99,99,99,75,65
OK', '网络'],
        ['eigencomm', 'AT+CGCONTRDP', 'PDP信息', '查询PDP上下文信息', 'AT+CGCONTRDP', '无', '+CGCONTRDP: ...', 'AT+CGCONTRDP
OK', '数据'],
        ['eigencomm', 'AT+CGSCONT', 'PDP范围', '查询PDP上下文范围', 'AT+CGSCONT', '无', '+CGSCONT: ...', 'AT+CGSCONT
OK', '数据'],
        ['eigencomm', 'AT+V', '版本信息', '查询模块版本信息', 'AT+V', '无', '版本信息', 'AT+V
OK', '设备信息'],
        ['eigencomm', 'ATE0', '回显关闭', '关闭命令回显', 'ATE0', '0=关闭', 'OK', 'ATE0
OK', '基础'],
        ['eigencomm', 'ATE1', '回显开启', '开启命令回显', 'ATE1', '1=开启', 'OK', 'ATE1
OK', '基础'],
        ['eigencomm', 'AT&W', '保存配置', '保存当前配置到用户配置文件', 'AT&W', '无', 'OK', 'AT&W
OK', '基础'],
        ['eigencomm', 'ATZ', '复位', '复位模块到默认配置', 'ATZ', '无', 'OK', 'ATZ
OK', '基础'],
        ['mediatek', 'AT', '基础测试', '检测模块是否响应', 'AT', '无', 'OK', 'AT
OK', '基础'],
        ['mediatek', 'ATI', '设备信息', '查询模块厂商信息', 'ATI', '无', '厂商信息文本', 'ATI
ZTE Corporation
OK', '设备信息'],
        ['mediatek', 'AT+CGMI', '厂商信息', '查询模块制造商名称', 'AT+CGMI', '无', '制造商名称', 'AT+CGMI
ZTE INCORPORATED
OK', '设备信息'],
        ['mediatek', 'AT+CGMM', '模块型号', '查询模块型号标识', 'AT+CGMM', '无', '模块型号字符串', 'AT+CGMM
ZX2975
OK', '设备信息'],
        ['mediatek', 'AT+CGMR', '软件版本', '查询模块软件版本号', 'AT+CGMR', '无', '软件版本字符串', 'AT+CGMR
ZX2975_V1.0.0
OK', '设备信息'],
        ['mediatek', 'AT+CGSN', 'IMEI号', '查询模块IMEI序列号', 'AT+CGSN', '无', '15位IMEI号码', 'AT+CGSN
865123456789012
OK', '设备信息'],
        ['mediatek', 'AT+CSQ', '信号质量', '查询当前信号质量', 'AT+CSQ', '无', '+CSQ: <rssi>,<ber>', '+CSQ: 25,0
OK', '网络'],
        ['mediatek', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡PIN码状态', 'AT+CPIN?', '无', '+CPIN: <code>', '+CPIN: READY
OK', 'SIM卡'],
        ['mediatek', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡是否需要PIN码', 'AT+CPIN?', '无', 'READY/SIM PIN/SIM PUK', '+CPIN: READY
OK', 'SIM卡'],
        ['mediatek', 'AT+CREG?', '网络注册', '查询CS网络注册状态', 'AT+CREG?', '无', '+CREG: <n>,<stat>', '+CREG: 0,1
OK', '网络'],
        ['mediatek', 'AT+CGREG?', 'GPRS注册', '查询GPRS网络注册状态', 'AT+CGREG?', '无', '+CGREG: <n>,<stat>', '+CGREG: 0,1
OK', '网络'],
        ['mediatek', 'AT+CEREG?', 'LTE注册', '查询EPS网络注册状态', 'AT+CEREG?', '无', '+CEREG: <n>,<stat>', '+CEREG: 0,1
OK', '网络'],
        ['mediatek', 'AT+COPS?', '运营商信息', '查询当前运营商信息', 'AT+COPS?', '无', '+COPS: <mode>,<format>,<oper>', '+COPS: 0,0,"CHINA MOBILE"
OK', '网络'],
        ['mediatek', 'AT+CGDCONT?', 'PDP上下文', '查询PDP上下文定义', 'AT+CGDCONT?', '无', '+CGDCONT: <cid>,<PDP_type>,<APN>', '+CGDCONT: 1,"IP","CMNET"
OK', '数据'],
        ['mediatek', 'AT+CGACT?', 'PDP激活状态', '查询PDP上下文激活状态', 'AT+CGACT?', '无', '+CGACT: <cid>,<state>', '+CGACT: 1,1
OK', '数据'],
        ['mediatek', 'AT+CFUN=1,1', '重启模块', '重启模块', 'AT+CFUN=1,1', '无', 'OK', 'AT+CFUN=1,1
OK', '基础'],
        ['mediatek', 'AT+CFUN=0', '最小功能', '设置模块为最小功能模式', 'AT+CFUN=0', '无', 'OK', 'AT+CFUN=0
OK', '基础'],
        ['mediatek', 'AT+CFUN=1', '全功能', '设置模块为全功能模式', 'AT+CFUN=1', '无', 'OK', 'AT+CFUN=1
OK', '基础'],
        ['mediatek', 'AT+CMGF=1', '短信模式', '设置短信为文本模式', 'AT+CMGF=1', '1=文本,0=PDU', 'OK', 'AT+CMGF=1
OK', '短信'],
        ['mediatek', 'AT+CMGS', '发送短信', '发送文本短信', 'AT+CMGS="<phone>"', '目标手机号', '> 提示输入内容，Ctrl+Z结束', 'AT+CMGS="10086"
>Hello
OK', '短信'],
        ['mediatek', 'AT+CMGR', '读取短信', '读取指定索引的短信', 'AT+CMGR=<index>', '短信索引号', '+CMGR: <stat>,<oa>,...,<data>', 'AT+CMGR=1
+CMGR: "REC READ","10086",...
OK', '短信'],
        ['mediatek', 'AT+CMGL', '列出短信', '列出指定状态的短信', 'AT+CMGL="<stat>"', 'ALL/REC UNREAD/REC READ', '+CMGL: ...', 'AT+CMGL="ALL"
OK', '短信'],
        ['mediatek', 'AT+CNMI', '短信通知', '设置新短信指示方式', 'AT+CNMI=<mode>,<mt>', 'mode,mt参数', 'OK', 'AT+CNMI=2,1
OK', '短信'],
        ['mediatek', 'AT+CLCC', '当前通话', '查询当前通话列表', 'AT+CLCC', '无', '+CLCC: <id>,<dir>,<stat>,<mode>,...,<number>', 'AT+CLCC
OK', '通话'],
        ['mediatek', 'ATD', '拨号', '拨打电话号码', 'ATD<number>;', '目标号码，分号结尾语音', 'OK/CONNECT', 'ATD10086;
OK', '通话'],
        ['mediatek', 'ATH', '挂断', '挂断当前通话', 'ATH', '无', 'OK', 'ATH
OK', '通话'],
        ['mediatek', 'ATA', '接听', '接听来电', 'ATA', '无', 'OK', 'ATA
OK', '通话'],
        ['mediatek', 'AT+CGATT?', 'GPRS附着', '查询GPRS附着状态', 'AT+CGATT?', '无', '+CGATT: <state>', '+CGATT: 1
OK', '数据'],
        ['mediatek', 'AT+CGATT=1', 'GPRS附着', '激活GPRS附着', 'AT+CGATT=1', '无', 'OK', 'AT+CGATT=1
OK', '数据'],
        ['mediatek', 'AT+CGATT=0', 'GPRS去附着', '取消GPRS附着', 'AT+CGATT=0', '无', 'OK', 'AT+CGATT=0
OK', '数据'],
        ['mediatek', 'AT+CESQ', '扩展信号', '查询扩展信号质量', 'AT+CESQ', '无', '+CESQ: <rq>,<rqlp>,<rqhp>,<rsrp>,<rsrq>', '+CESQ: 99,99,99,75,65
OK', '网络'],
        ['mediatek', 'AT+CGCONTRDP', 'PDP信息', '查询PDP上下文信息', 'AT+CGCONTRDP', '无', '+CGCONTRDP: ...', 'AT+CGCONTRDP
OK', '数据'],
        ['mediatek', 'AT+CGSCONT', 'PDP范围', '查询PDP上下文范围', 'AT+CGSCONT', '无', '+CGSCONT: ...', 'AT+CGSCONT
OK', '数据'],
        ['mediatek', 'AT+V', '版本信息', '查询模块版本信息', 'AT+V', '无', '版本信息', 'AT+V
OK', '设备信息'],
        ['mediatek', 'ATE0', '回显关闭', '关闭命令回显', 'ATE0', '0=关闭', 'OK', 'ATE0
OK', '基础'],
        ['mediatek', 'ATE1', '回显开启', '开启命令回显', 'ATE1', '1=开启', 'OK', 'ATE1
OK', '基础'],
        ['mediatek', 'AT&W', '保存配置', '保存当前配置到用户配置文件', 'AT&W', '无', 'OK', 'AT&W
OK', '基础'],
        ['mediatek', 'ATZ', '复位', '复位模块到默认配置', 'ATZ', '无', 'OK', 'ATZ
OK', '基础'],
        ['hisilicon', 'AT', '基础测试', '检测模块是否响应', 'AT', '无', 'OK', 'AT
OK', '基础'],
        ['hisilicon', 'ATI', '设备信息', '查询模块厂商信息', 'ATI', '无', '厂商信息文本', 'ATI
ZTE Corporation
OK', '设备信息'],
        ['hisilicon', 'AT+CGMI', '厂商信息', '查询模块制造商名称', 'AT+CGMI', '无', '制造商名称', 'AT+CGMI
ZTE INCORPORATED
OK', '设备信息'],
        ['hisilicon', 'AT+CGMM', '模块型号', '查询模块型号标识', 'AT+CGMM', '无', '模块型号字符串', 'AT+CGMM
ZX2975
OK', '设备信息'],
        ['hisilicon', 'AT+CGMR', '软件版本', '查询模块软件版本号', 'AT+CGMR', '无', '软件版本字符串', 'AT+CGMR
ZX2975_V1.0.0
OK', '设备信息'],
        ['hisilicon', 'AT+CGSN', 'IMEI号', '查询模块IMEI序列号', 'AT+CGSN', '无', '15位IMEI号码', 'AT+CGSN
865123456789012
OK', '设备信息'],
        ['hisilicon', 'AT+CSQ', '信号质量', '查询当前信号质量', 'AT+CSQ', '无', '+CSQ: <rssi>,<ber>', '+CSQ: 25,0
OK', '网络'],
        ['hisilicon', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡PIN码状态', 'AT+CPIN?', '无', '+CPIN: <code>', '+CPIN: READY
OK', 'SIM卡'],
        ['hisilicon', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡是否需要PIN码', 'AT+CPIN?', '无', 'READY/SIM PIN/SIM PUK', '+CPIN: READY
OK', 'SIM卡'],
        ['hisilicon', 'AT+CREG?', '网络注册', '查询CS网络注册状态', 'AT+CREG?', '无', '+CREG: <n>,<stat>', '+CREG: 0,1
OK', '网络'],
        ['hisilicon', 'AT+CGREG?', 'GPRS注册', '查询GPRS网络注册状态', 'AT+CGREG?', '无', '+CGREG: <n>,<stat>', '+CGREG: 0,1
OK', '网络'],
        ['hisilicon', 'AT+CEREG?', 'LTE注册', '查询EPS网络注册状态', 'AT+CEREG?', '无', '+CEREG: <n>,<stat>', '+CEREG: 0,1
OK', '网络'],
        ['hisilicon', 'AT+COPS?', '运营商信息', '查询当前运营商信息', 'AT+COPS?', '无', '+COPS: <mode>,<format>,<oper>', '+COPS: 0,0,"CHINA MOBILE"
OK', '网络'],
        ['hisilicon', 'AT+CGDCONT?', 'PDP上下文', '查询PDP上下文定义', 'AT+CGDCONT?', '无', '+CGDCONT: <cid>,<PDP_type>,<APN>', '+CGDCONT: 1,"IP","CMNET"
OK', '数据'],
        ['hisilicon', 'AT+CGACT?', 'PDP激活状态', '查询PDP上下文激活状态', 'AT+CGACT?', '无', '+CGACT: <cid>,<state>', '+CGACT: 1,1
OK', '数据'],
        ['hisilicon', 'AT+CFUN=1,1', '重启模块', '重启模块', 'AT+CFUN=1,1', '无', 'OK', 'AT+CFUN=1,1
OK', '基础'],
        ['hisilicon', 'AT+CFUN=0', '最小功能', '设置模块为最小功能模式', 'AT+CFUN=0', '无', 'OK', 'AT+CFUN=0
OK', '基础'],
        ['hisilicon', 'AT+CFUN=1', '全功能', '设置模块为全功能模式', 'AT+CFUN=1', '无', 'OK', 'AT+CFUN=1
OK', '基础'],
        ['hisilicon', 'AT+CMGF=1', '短信模式', '设置短信为文本模式', 'AT+CMGF=1', '1=文本,0=PDU', 'OK', 'AT+CMGF=1
OK', '短信'],
        ['hisilicon', 'AT+CMGS', '发送短信', '发送文本短信', 'AT+CMGS="<phone>"', '目标手机号', '> 提示输入内容，Ctrl+Z结束', 'AT+CMGS="10086"
>Hello
OK', '短信'],
        ['hisilicon', 'AT+CMGR', '读取短信', '读取指定索引的短信', 'AT+CMGR=<index>', '短信索引号', '+CMGR: <stat>,<oa>,...,<data>', 'AT+CMGR=1
+CMGR: "REC READ","10086",...
OK', '短信'],
        ['hisilicon', 'AT+CMGL', '列出短信', '列出指定状态的短信', 'AT+CMGL="<stat>"', 'ALL/REC UNREAD/REC READ', '+CMGL: ...', 'AT+CMGL="ALL"
OK', '短信'],
        ['hisilicon', 'AT+CNMI', '短信通知', '设置新短信指示方式', 'AT+CNMI=<mode>,<mt>', 'mode,mt参数', 'OK', 'AT+CNMI=2,1
OK', '短信'],
        ['hisilicon', 'AT+CLCC', '当前通话', '查询当前通话列表', 'AT+CLCC', '无', '+CLCC: <id>,<dir>,<stat>,<mode>,...,<number>', 'AT+CLCC
OK', '通话'],
        ['hisilicon', 'ATD', '拨号', '拨打电话号码', 'ATD<number>;', '目标号码，分号结尾语音', 'OK/CONNECT', 'ATD10086;
OK', '通话'],
        ['hisilicon', 'ATH', '挂断', '挂断当前通话', 'ATH', '无', 'OK', 'ATH
OK', '通话'],
        ['hisilicon', 'ATA', '接听', '接听来电', 'ATA', '无', 'OK', 'ATA
OK', '通话'],
        ['hisilicon', 'AT+CGATT?', 'GPRS附着', '查询GPRS附着状态', 'AT+CGATT?', '无', '+CGATT: <state>', '+CGATT: 1
OK', '数据'],
        ['hisilicon', 'AT+CGATT=1', 'GPRS附着', '激活GPRS附着', 'AT+CGATT=1', '无', 'OK', 'AT+CGATT=1
OK', '数据'],
        ['hisilicon', 'AT+CGATT=0', 'GPRS去附着', '取消GPRS附着', 'AT+CGATT=0', '无', 'OK', 'AT+CGATT=0
OK', '数据'],
        ['hisilicon', 'AT+CESQ', '扩展信号', '查询扩展信号质量', 'AT+CESQ', '无', '+CESQ: <rq>,<rqlp>,<rqhp>,<rsrp>,<rsrq>', '+CESQ: 99,99,99,75,65
OK', '网络'],
        ['hisilicon', 'AT+CGCONTRDP', 'PDP信息', '查询PDP上下文信息', 'AT+CGCONTRDP', '无', '+CGCONTRDP: ...', 'AT+CGCONTRDP
OK', '数据'],
        ['hisilicon', 'AT+CGSCONT', 'PDP范围', '查询PDP上下文范围', 'AT+CGSCONT', '无', '+CGSCONT: ...', 'AT+CGSCONT
OK', '数据'],
        ['hisilicon', 'AT+V', '版本信息', '查询模块版本信息', 'AT+V', '无', '版本信息', 'AT+V
OK', '设备信息'],
        ['hisilicon', 'ATE0', '回显关闭', '关闭命令回显', 'ATE0', '0=关闭', 'OK', 'ATE0
OK', '基础'],
        ['hisilicon', 'ATE1', '回显开启', '开启命令回显', 'ATE1', '1=开启', 'OK', 'ATE1
OK', '基础'],
        ['hisilicon', 'AT&W', '保存配置', '保存当前配置到用户配置文件', 'AT&W', '无', 'OK', 'AT&W
OK', '基础'],
        ['hisilicon', 'ATZ', '复位', '复位模块到默认配置', 'ATZ', '无', 'OK', 'ATZ
OK', '基础'],
        ['xinyi', 'AT', '基础测试', '检测模块是否响应', 'AT', '无', 'OK', 'AT
OK', '基础'],
        ['xinyi', 'ATI', '设备信息', '查询模块厂商信息', 'ATI', '无', '厂商信息文本', 'ATI
ZTE Corporation
OK', '设备信息'],
        ['xinyi', 'AT+CGMI', '厂商信息', '查询模块制造商名称', 'AT+CGMI', '无', '制造商名称', 'AT+CGMI
ZTE INCORPORATED
OK', '设备信息'],
        ['xinyi', 'AT+CGMM', '模块型号', '查询模块型号标识', 'AT+CGMM', '无', '模块型号字符串', 'AT+CGMM
ZX2975
OK', '设备信息'],
        ['xinyi', 'AT+CGMR', '软件版本', '查询模块软件版本号', 'AT+CGMR', '无', '软件版本字符串', 'AT+CGMR
ZX2975_V1.0.0
OK', '设备信息'],
        ['xinyi', 'AT+CGSN', 'IMEI号', '查询模块IMEI序列号', 'AT+CGSN', '无', '15位IMEI号码', 'AT+CGSN
865123456789012
OK', '设备信息'],
        ['xinyi', 'AT+CSQ', '信号质量', '查询当前信号质量', 'AT+CSQ', '无', '+CSQ: <rssi>,<ber>', '+CSQ: 25,0
OK', '网络'],
        ['xinyi', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡PIN码状态', 'AT+CPIN?', '无', '+CPIN: <code>', '+CPIN: READY
OK', 'SIM卡'],
        ['xinyi', 'AT+CPIN?', 'SIM卡状态', '查询SIM卡是否需要PIN码', 'AT+CPIN?', '无', 'READY/SIM PIN/SIM PUK', '+CPIN: READY
OK', 'SIM卡'],
        ['xinyi', 'AT+CREG?', '网络注册', '查询CS网络注册状态', 'AT+CREG?', '无', '+CREG: <n>,<stat>', '+CREG: 0,1
OK', '网络'],
        ['xinyi', 'AT+CGREG?', 'GPRS注册', '查询GPRS网络注册状态', 'AT+CGREG?', '无', '+CGREG: <n>,<stat>', '+CGREG: 0,1
OK', '网络'],
        ['xinyi', 'AT+CEREG?', 'LTE注册', '查询EPS网络注册状态', 'AT+CEREG?', '无', '+CEREG: <n>,<stat>', '+CEREG: 0,1
OK', '网络'],
        ['xinyi', 'AT+COPS?', '运营商信息', '查询当前运营商信息', 'AT+COPS?', '无', '+COPS: <mode>,<format>,<oper>', '+COPS: 0,0,"CHINA MOBILE"
OK', '网络'],
        ['xinyi', 'AT+CGDCONT?', 'PDP上下文', '查询PDP上下文定义', 'AT+CGDCONT?', '无', '+CGDCONT: <cid>,<PDP_type>,<APN>', '+CGDCONT: 1,"IP","CMNET"
OK', '数据'],
        ['xinyi', 'AT+CGACT?', 'PDP激活状态', '查询PDP上下文激活状态', 'AT+CGACT?', '无', '+CGACT: <cid>,<state>', '+CGACT: 1,1
OK', '数据'],
        ['xinyi', 'AT+CFUN=1,1', '重启模块', '重启模块', 'AT+CFUN=1,1', '无', 'OK', 'AT+CFUN=1,1
OK', '基础'],
        ['xinyi', 'AT+CFUN=0', '最小功能', '设置模块为最小功能模式', 'AT+CFUN=0', '无', 'OK', 'AT+CFUN=0
OK', '基础'],
        ['xinyi', 'AT+CFUN=1', '全功能', '设置模块为全功能模式', 'AT+CFUN=1', '无', 'OK', 'AT+CFUN=1
OK', '基础'],
        ['xinyi', 'AT+CMGF=1', '短信模式', '设置短信为文本模式', 'AT+CMGF=1', '1=文本,0=PDU', 'OK', 'AT+CMGF=1
OK', '短信'],
        ['xinyi', 'AT+CMGS', '发送短信', '发送文本短信', 'AT+CMGS="<phone>"', '目标手机号', '> 提示输入内容，Ctrl+Z结束', 'AT+CMGS="10086"
>Hello
OK', '短信'],
        ['xinyi', 'AT+CMGR', '读取短信', '读取指定索引的短信', 'AT+CMGR=<index>', '短信索引号', '+CMGR: <stat>,<oa>,...,<data>', 'AT+CMGR=1
+CMGR: "REC READ","10086",...
OK', '短信'],
        ['xinyi', 'AT+CMGL', '列出短信', '列出指定状态的短信', 'AT+CMGL="<stat>"', 'ALL/REC UNREAD/REC READ', '+CMGL: ...', 'AT+CMGL="ALL"
OK', '短信'],
        ['xinyi', 'AT+CNMI', '短信通知', '设置新短信指示方式', 'AT+CNMI=<mode>,<mt>', 'mode,mt参数', 'OK', 'AT+CNMI=2,1
OK', '短信'],
        ['xinyi', 'AT+CLCC', '当前通话', '查询当前通话列表', 'AT+CLCC', '无', '+CLCC: <id>,<dir>,<stat>,<mode>,...,<number>', 'AT+CLCC
OK', '通话'],
        ['xinyi', 'ATD', '拨号', '拨打电话号码', 'ATD<number>;', '目标号码，分号结尾语音', 'OK/CONNECT', 'ATD10086;
OK', '通话'],
        ['xinyi', 'ATH', '挂断', '挂断当前通话', 'ATH', '无', 'OK', 'ATH
OK', '通话'],
        ['xinyi', 'ATA', '接听', '接听来电', 'ATA', '无', 'OK', 'ATA
OK', '通话'],
        ['xinyi', 'AT+CGATT?', 'GPRS附着', '查询GPRS附着状态', 'AT+CGATT?', '无', '+CGATT: <state>', '+CGATT: 1
OK', '数据'],
        ['xinyi', 'AT+CGATT=1', 'GPRS附着', '激活GPRS附着', 'AT+CGATT=1', '无', 'OK', 'AT+CGATT=1
OK', '数据'],
        ['xinyi', 'AT+CGATT=0', 'GPRS去附着', '取消GPRS附着', 'AT+CGATT=0', '无', 'OK', 'AT+CGATT=0
OK', '数据'],
        ['xinyi', 'AT+CESQ', '扩展信号', '查询扩展信号质量', 'AT+CESQ', '无', '+CESQ: <rq>,<rqlp>,<rqhp>,<rsrp>,<rsrq>', '+CESQ: 99,99,99,75,65
OK', '网络'],
        ['xinyi', 'AT+CGCONTRDP', 'PDP信息', '查询PDP上下文信息', 'AT+CGCONTRDP', '无', '+CGCONTRDP: ...', 'AT+CGCONTRDP
OK', '数据'],
        ['xinyi', 'AT+CGSCONT', 'PDP范围', '查询PDP上下文范围', 'AT+CGSCONT', '无', '+CGSCONT: ...', 'AT+CGSCONT
OK', '数据'],
        ['xinyi', 'AT+V', '版本信息', '查询模块版本信息', 'AT+V', '无', '版本信息', 'AT+V
OK', '设备信息'],
        ['xinyi', 'ATE0', '回显关闭', '关闭命令回显', 'ATE0', '0=关闭', 'OK', 'ATE0
OK', '基础'],
        ['xinyi', 'ATE1', '回显开启', '开启命令回显', 'ATE1', '1=开启', 'OK', 'ATE1
OK', '基础'],
        ['xinyi', 'AT&W', '保存配置', '保存当前配置到用户配置文件', 'AT&W', '无', 'OK', 'AT&W
OK', '基础'],
        ['xinyi', 'ATZ', '复位', '复位模块到默认配置', 'ATZ', '无', 'OK', 'ATZ
OK', '基础'],
        ['zte', 'AT+ZSNT?', '网络模式', '查询网络模式设置', 'AT+ZSNT?', '无', '+ZSNT: <mode>', '+ZSNT: 0,0,2
OK', '厂商专有'],
        ['zte', 'AT+ZGSR?', '服务报告', '查询通用服务报告', 'AT+ZGSR?', '无', '+ZGSR: ...', '+ZGSR: 1,1,1,1
OK', '厂商专有'],
        ['zte', 'AT+ZSIM?', 'SIM卡信息', '查询SIM卡详细信息', 'AT+ZSIM?', '无', '+ZSIM: ...', '+ZSIM: 1,1,"CMCC"
OK', 'SIM卡'],
        ['zte', 'AT+ZCDRUN?', '开发模式', '查询开发模式状态', 'AT+ZCDRUN?', '无', '+ZCDRUN: <state>', '+ZCDRUN: 0
OK', '厂商专有'],
        ['zte', 'AT+ZCDRUN=0', '开启开发模式', '开启开发调试模式', 'AT+ZCDRUN=0', '0=开启', 'OK', 'AT+ZCDRUN=0
OK', '厂商专有'],
        ['zte', 'AT+ZCDRUN=1', '关闭开发模式', '关闭开发调试模式', 'AT+ZCDRUN=1', '1=关闭', 'OK', 'AT+ZCDRUN=1
OK', '厂商专有'],
        ['zte', 'AT+ZRESET', '重启模块', '重启ZTE模块', 'AT+ZRESET', '无', 'OK', 'AT+ZRESET
OK', '基础'],
        ['zte', 'AT+ZMODE=1', '工厂模式', '开启工厂模式', 'AT+ZMODE=1', '1=开启', 'OK', 'AT+ZMODE=1
OK', '厂商专有'],
        ['zte', 'AT+ZMODE=0', '退出工厂', '退出工厂模式', 'AT+ZMODE=0', '0=退出', 'OK', 'AT+ZMODE=0
OK', '厂商专有'],
        ['zte', 'AT+ZSPD', '限速设置', '设置上下行限速', 'AT+ZSPD=<ul>,<dl>', '上行/下行速率', 'OK', 'AT+ZSPD=10240,10240
OK', '厂商专有'],
        ['zte', 'AT+ZSPD?', '限速查询', '查询当前限速设置', 'AT+ZSPD?', '无', '+ZSPD: <ul>,<dl>', '+ZSPD: 10240,10240
OK', '厂商专有'],
        ['zte', 'AT+ZSIMLOCK', 'SIM锁控制', '设置SIM卡锁定状态', 'AT+ZSIMLOCK=<mode>', '0=解锁,1=锁定', 'OK', 'AT+ZSIMLOCK=0
OK', 'SIM卡'],
        ['zte', 'AT+ZFWVER?', '固件版本', '查询固件版本信息', 'AT+ZFWVER?', '无', '+ZFWVER: <version>', '+ZFWVER: V1.0.0B05
OK', '设备信息'],
        ['zte', 'AT+ZLOG?', '日志状态', '查询日志记录状态', 'AT+ZLOG?', '无', '+ZLOG: <state>', '+ZLOG: 1
OK', '厂商专有'],
        ['zte', 'AT+ZLOG=1', '开启日志', '开启日志记录', 'AT+ZLOG=1', '1=开启', 'OK', 'AT+ZLOG=1
OK', '厂商专有'],
        ['zte', 'AT+ZTEMP?', '温度查询', '查询模块温度', 'AT+ZTEMP?', '无', '+ZTEMP: <temp>', '+ZTEMP: 45
OK', '设备信息'],
        ['zte', 'AT+ZVOLT?', '电压查询', '查询模块供电电压', 'AT+ZVOLT?', '无', '+ZVOLT: <voltage>', '+ZVOLT: 3700
OK', '设备信息'],
        ['zte', 'AT+ZLC?', '小区锁定', '查询小区锁定状态', 'AT+ZLC?', '无', '+ZLC: <lock>,<arfcn>,<pci>', '+ZLC: 0,0,0
OK', '网络'],
        ['zte', 'AT+ZLC=', '设置小区锁定', '设置小区锁定参数', 'AT+ZLC=<lock>,<arfcn>,<pci>', '锁定开关,频点,PCI', 'OK', 'AT+ZLC=1,1850,100
OK', '网络'],
        ['zte', 'AT+ZLTEBAND?', 'LTE频段', '查询LTE锁定频段', 'AT+ZLTEBAND?', '无', '+ZLTEBAND: <band1>,...,<band8>', '+ZLTEBAND: 1,0,0,0,0,0,0,0
OK', '网络'],
        ['zte', 'AT+ZLTEAMTBAND?', '支持频段', '查询模块支持的LTE频段', 'AT+ZLTEAMTBAND?', '无', '+ZLTEAMTBAND: ...', '+ZLTEAMTBAND: 1,0,0,0,0,0,0,0
OK', '网络'],
        ['zte', 'AT+MAC?', 'MAC地址', '查询WiFi MAC地址', 'AT+MAC?', '无', '+MAC: <mac>', '+MAC: AA:BB:CC:DD:EE:FF
OK', '设备信息'],
        ['zte', 'AT+MAC=', '设置MAC', '设置WiFi MAC地址', 'AT+MAC=<mac>', 'MAC地址', 'OK', 'AT+MAC=AABBCCDDEEFF
OK', '厂商专有'],
        ['zte', 'AT+MAC2?', '有线MAC', '查询有线网口MAC地址', 'AT+MAC2?', '无', '+MAC2: <mac>', '+MAC2: AA:BB:CC:DD:EE:FF
OK', '设备信息'],
        ['zte', 'AT+MODIMEI=', '写入IMEI', '写入IMEI号', 'AT+MODIMEI=<imei>', '15位IMEI', 'OK', 'AT+MODIMEI=865123456789012
OK', '厂商专有'],
        ['zte', 'AT+CGEQOSRDP=1', 'QoS查询', '查询QoS参数', 'AT+CGEQOSRDP=1', 'CID=1', '+CGEQOSRDP: ...', '+CGEQOSRDP: 1,1,0,0
OK', '数据'],
        ['zte', 'AT+ZPCID?', 'PCI查询', '查询当前小区PCI', 'AT+ZPCID?', '无', '+ZPCID: <pci>', '+ZPCID: 100
OK', '网络'],
        ['zte', 'AT+ZCELL?', '小区信息', '查询当前小区信息', 'AT+ZCELL?', '无', '+ZCELL: ...', '+ZCELL: 100,1850,-90
OK', '网络'],
        ['zte', 'AT+ZRSRP?', 'RSRP查询', '查询参考信号接收功率', 'AT+ZRSRP?', '无', '+ZRSRP: <value>', '+ZRSRP: -95
OK', '网络'],
        ['zte', 'AT+ZRSRQ?', 'RSRQ查询', '查询参考信号接收质量', 'AT+ZRSRQ?', '无', '+ZRSRQ: <value>', '+ZRSRQ: -12
OK', '网络'],
        ['asr', 'AT+ASRSTK?', 'STK信息', '查询SIM STK信息', 'AT+ASRSTK?', '无', '+ASRSTK: ...', '+ASRSTK: 1
OK', '厂商专有'],
        ['asr', 'AT+ASRNET?', '网络信息', '查询ASR网络信息', 'AT+ASRNET?', '无', '+ASRNET: ...', '+ASRNET: 1,1,"CMCC"
OK', '网络'],
        ['asr', 'AT+ASRCSQ?', '详细信号', '查询详细信号质量', 'AT+ASRCSQ?', '无', '+ASRCSQ: <rssi>,<rsrp>,<rsrq>', '+ASRCSQ: 25,-95,-12
OK', '网络'],
        ['asr', 'AT+ASRBAND?', '频段信息', '查询当前频段信息', 'AT+ASRBAND?', '无', '+ASRBAND: <band>', '+ASRBAND: 38
OK', '网络'],
        ['asr', 'AT+ASRLOCK?', '频段锁定', '查询频段锁定状态', 'AT+ASRLOCK?', '无', '+ASRLOCK: ...', '+ASRLOCK: 0
OK', '网络'],
        ['asr', 'AT+ASRNV?', 'NV信息', '查询NV存储信息', 'AT+ASRNV?', '无', '+ASRNV: ...', '+ASRNV: 0
OK', '厂商专有'],
        ['asr', 'AT*PROD=1', '工厂模式', '开启ASR工厂模式', 'AT*PROD=1', '1=开启', 'OK', 'AT*PROD=1
OK', '厂商专有'],
        ['asr', 'AT*PROD=0', '退出工厂', '退出ASR工厂模式', 'AT*PROD=0', '0=退出', 'OK', 'AT*PROD=0
OK', '厂商专有'],
        ['asr', 'AT*FACTORY', '工厂复位', '恢复出厂设置', 'AT*FACTORY', '无', 'OK', 'AT*FACTORY
OK', '厂商专有'],
        ['asr', 'AT*NVBACKUP', 'NV备份', '备份NV数据', 'AT*NVBACKUP', '无', 'OK', 'AT*NVBACKUP
OK', '厂商专有'],
        ['asr', 'AT*NVRESTORE', 'NV恢复', '恢复NV数据', 'AT*NVRESTORE', '无', 'OK', 'AT*NVRESTORE
OK', '厂商专有'],
        ['asr', 'AT*SIMINFO?', 'SIM信息', '查询SIM卡详细信息', 'AT*SIMINFO?', '无', '*SIMINFO: ...', '*SIMINFO: 1,"CMCC",...
OK', 'SIM卡'],
        ['asr', 'AT*RFINFO?', 'RF信息', '查询射频信息', 'AT*RFINFO?', '无', '*RFINFO: ...', '*RFINFO: -95,-12,38
OK', '厂商专有'],
        ['asr', 'AT*MRD_IMEI?', '读取IMEI', '读取IMEI号', 'AT*MRD_IMEI?', '无', '*MRD_IMEI: <imei>', '*MRD_IMEI: 865123456789012
OK', '设备信息'],
        ['asr', 'AT*MRD_IMEI=W', '写入IMEI', '写入IMEI号', 'AT*MRD_IMEI=W,0,<date>,<imei>', '日期,IMEI', 'OK', 'AT*MRD_IMEI=W,0,01JAN1970,865123456789012
OK', '厂商专有'],
        ['asr', 'AT*MRD_IMEI=D', '删除IMEI', '删除IMEI号', 'AT*MRD_IMEI=D', 'D=删除', 'OK', 'AT*MRD_IMEI=D
OK', '厂商专有'],
        ['asr', 'AT*MRD_SN?', '读取SN', '读取SN序列号', 'AT*MRD_SN?', '无', '*MRD_SN: <sn>', '*MRD_SN: ABC123456
OK', '设备信息'],
        ['asr', 'AT*MRD_SN=W', '写入SN', '写入SN序列号', 'AT*MRD_SN=W,0,<date>,<sn>', '日期,SN', 'OK', 'AT*MRD_SN=W,0,01JAN1970,ABC123456
OK', '厂商专有'],
        ['asr', 'AT*MRD_SN=D', '删除SN', '删除SN序列号', 'AT*MRD_SN=D', 'D=删除', 'OK', 'AT*MRD_SN=D
OK', '厂商专有'],
        ['asr', 'AT*MRD_WIFIID?', '读取MAC', '读取WiFi MAC地址', 'AT*MRD_WIFIID?', '无', '*MRD_WIFIID: <mac>', '*MRD_WIFIID: AA:BB:CC:DD:EE:FF
OK', '设备信息'],
        ['asr', 'AT*MRD_WIFIID=W', '写入MAC', '写入WiFi MAC地址', 'AT*MRD_WIFIID=W,0,<date>,<mac>', '日期,MAC', 'OK', 'AT*MRD_WIFIID=W,0,01JAN1970,AABBCCDDEEFF
OK', '厂商专有'],
        ['asr', 'AT*MRD_WIFIID=D', '删除MAC', '删除WiFi MAC地址', 'AT*MRD_WIFIID=D', 'D=删除', 'OK', 'AT*MRD_WIFIID=D
OK', '厂商专有'],
        ['asr', 'AT+SWSIM=0', '切换卡1', '切换到SIM卡1', 'AT+SWSIM=0', '0=卡1', 'OK', 'AT+SWSIM=0
OK', 'SIM卡'],
        ['asr', 'AT+SWSIM=1', '切换卡2', '切换到SIM卡2', 'AT+SWSIM=1', '1=卡2', 'OK', 'AT+SWSIM=1
OK', 'SIM卡'],
        ['asr', 'AT+RESET', '重启设备', '重启ASR设备', 'AT+RESET', '无', 'OK', 'AT+RESET
OK', '基础'],
        ['asr', 'AT*BAND?', '频段查询', '查询频段设置', 'AT*BAND?', '无', '*BAND: <gsm>,<tdscdma>,<wcdma>,<tdd>,<fdd>', '*BAND: 0,0,0,1,1
OK', '网络'],
        ['asr', 'AT*BAND=', '频段设置', '设置频段', 'AT*BAND=<g>,<t>,<w>,<tdd>,<fdd>', '各制式频段位图', 'OK', 'AT*BAND=0,0,0,1,1
OK', '网络'],
        ['asr', 'AT+FLASHBP=0', '解锁Flash', '解锁Flash保护', 'AT+FLASHBP=0', '0=解锁', 'OK', 'AT+FLASHBP=0
OK', '厂商专有'],
        ['asr', 'AT*TEMP?', '温度查询', '查询模块温度', 'AT*TEMP?', '无', '*TEMP: <temp>', '*TEMP: 42
OK', '设备信息'],
        ['asr', 'AT*VOLT?', '电压查询', '查询模块电压', 'AT*VOLT?', '无', '*VOLT: <voltage>', '*VOLT: 3700
OK', '设备信息'],
        ['unisoc', 'AT+SPNWNAME?', '网络名称', '查询当前网络名称', 'AT+SPNWNAME?', '无', '+SPNWNAME: <name>', '+SPNWNAME: "CHINA MOBILE"
OK', '网络'],
        ['unisoc', 'AT+SPUSIMCFG?', 'SIM配置', '查询SIM卡配置', 'AT+SPUSIMCFG?', '无', '+SPUSIMCFG: ...', '+SPUSIMCFG: 0,1
OK', 'SIM卡'],
        ['unisoc', 'AT+SPNVMREAD?', 'NV读取', '读取NV存储数据', 'AT+SPNVMREAD=<id>', 'NV项ID', '+SPNVMREAD: <data>', '+SPNVMREAD: 0,0,0
OK', '厂商专有'],
        ['unisoc', 'AT+SPBAND?', '频段信息', '查询频段信息', 'AT+SPBAND?', '无', '+SPBAND: <band>', '+SPBAND: 38
OK', '网络'],
        ['unisoc', 'AT+SPNETMODE?', '网络模式', '查询网络模式', 'AT+SPNETMODE?', '无', '+SPNETMODE: <mode>', '+SPNETMODE: 4
OK', '网络'],
        ['unisoc', 'AT+SPNETMODE=', '设置网络模式', '设置网络模式', 'AT+SPNETMODE=<mode>', '2G/3G/4G/自动', 'OK', 'AT+SPNETMODE=4
OK', '网络'],
        ['unisoc', 'AT+SPAPNCFG?', 'APN配置', '查询APN配置', 'AT+SPAPNCFG?', '无', '+SPAPNCFG: <apn>', '+SPAPNCFG: "CMNET"
OK', '数据'],
        ['unisoc', 'AT+SPAPNCFG=', '设置APN', '设置APN配置', 'AT+SPAPNCFG=<apn>', 'APN名称', 'OK', 'AT+SPAPNCFG="CMNET"
OK', '数据'],
        ['unisoc', 'AT+SPFACTORY', '工厂模式', '进入工厂模式', 'AT+SPFACTORY', '无', 'OK', 'AT+SPFACTORY
OK', '厂商专有'],
        ['unisoc', 'AT+SPNVBACK', 'NV备份', '备份NV数据', 'AT+SPNVBACK', '无', 'OK', 'AT+SPNVBACK
OK', '厂商专有'],
        ['unisoc', 'AT+SPNVREST', 'NV恢复', '恢复NV数据', 'AT+SPNVREST', '无', 'OK', 'AT+SPNVREST
OK', '厂商专有'],
        ['unisoc', 'AT+SPRFINFO?', 'RF信息', '查询射频信息', 'AT+SPRFINFO?', '无', '+SPRFINFO: ...', '+SPRFINFO: -95,-12,38
OK', '厂商专有'],
        ['unisoc', 'AT+SPLOGCTRL', '日志控制', '控制日志输出', 'AT+SPLOGCTRL=<mode>', '0=关闭,1=开启', 'OK', 'AT+SPLOGCTRL=1
OK', '厂商专有'],
        ['unisoc', 'AT+SPLOGCTRL?', '日志状态', '查询日志状态', 'AT+SPLOGCTRL?', '无', '+SPLOGCTRL: <mode>', '+SPLOGCTRL: 1
OK', '厂商专有'],
        ['unisoc', 'AT+SPSIMSWAP', 'SIM切换', '切换SIM卡', 'AT+SPSIMSWAP=<sim>', '0=卡1,1=卡2', 'OK', 'AT+SPSIMSWAP=0
OK', 'SIM卡'],
        ['unisoc', 'AT+SPTEMP?', '温度查询', '查询模块温度', 'AT+SPTEMP?', '无', '+SPTEMP: <temp>', '+SPTEMP: 43
OK', '设备信息'],
        ['unisoc', 'AT+SPVOLT?', '电压查询', '查询模块电压', 'AT+SPVOLT?', '无', '+SPVOLT: <voltage>', '+SPVOLT: 3700
OK', '设备信息'],
        ['unisoc', 'AT+SPCELL?', '小区信息', '查询当前小区信息', 'AT+SPCELL?', '无', '+SPCELL: <pci>,<arfcn>,<rsrp>', '+SPCELL: 100,1850,-95
OK', '网络'],
        ['unisoc', 'AT+SPLOCKCELL', '小区锁定', '锁定指定小区', 'AT+SPLOCKCELL=<arfcn>,<pci>', '频点,PCI', 'OK', 'AT+SPLOCKCELL=1850,100
OK', '网络'],
        ['unisoc', 'AT+SPBANDSET', '频段设置', '设置支持频段', 'AT+SPBANDSET=<bands>', '频段列表', 'OK', 'AT+SPBANDSET=1,3,5,8,38,39,40,41
OK', '网络'],
        ['unisoc', 'AT+SPREBOOT', '重启模块', '重启展锐模块', 'AT+SPREBOOT', '无', 'OK', 'AT+SPREBOOT
OK', '基础'],
        ['unisoc', 'AT+SPFWVER?', '固件版本', '查询固件版本', 'AT+SPFWVER?', '无', '+SPFWVER: <version>', '+SPFWVER: V1.0.0
OK', '设备信息'],
        ['unisoc', 'AT+SPIMEI?', 'IMEI查询', '查询IMEI号', 'AT+SPIMEI?', '无', '+SPIMEI: <imei>', '+SPIMEI: 865123456789012
OK', '设备信息'],
        ['unisoc', 'AT+SPIMEI=', '写入IMEI', '写入IMEI号', 'AT+SPIMEI=<imei>', '15位IMEI', 'OK', 'AT+SPIMEI=865123456789012
OK', '厂商专有'],
        ['unisoc', 'AT+SPMAC?', 'MAC查询', '查询MAC地址', 'AT+SPMAC?', '无', '+SPMAC: <mac>', '+SPMAC: AA:BB:CC:DD:EE:FF
OK', '设备信息'],
        ['unisoc', 'AT+SPMAC=', '写入MAC', '写入MAC地址', 'AT+SPMAC=<mac>', 'MAC地址', 'OK', 'AT+SPMAC=AABBCCDDEEFF
OK', '厂商专有'],
        ['qualcomm', 'AT$QCRMCALL', '数据呼叫', '建立/释放数据呼叫', 'AT$QCRMCALL=<type>,<cid>', '1=建立,0=释放', 'OK', 'AT=1,1
OK', '数据'],
        ['qualcomm', 'AT+QCFG?', '配置查询', '查询模块配置', 'AT+QCFG?', '无', '+QCFG: ...', '+QCFG: "airplanecontrol",1
OK', '厂商专有'],
        ['qualcomm', 'AT+QCSQ', '信号质量', '查询高通信号质量', 'AT+QCSQ', '无', '+QCSQ: <rssi>,<rsrp>,<rsrq>,<snr>', '+QCSQ: 25,-95,-12,15
OK', '网络'],
        ['qualcomm', 'AT+QENG?', '工程模式', '查询工程模式信息', 'AT+QENG?', '无', '+QENG: ...', '+QENG: "servingcell",...
OK', '厂商专有'],
        ['qualcomm', 'AT+QNWINFO?', '网络信息', '查询网络详细信息', 'AT+QNWINFO?', '无', '+QNWINFO: <mode>,<band>,<channel>', '+QNWINFO: "LTE",38,1850
OK', '网络'],
        ['qualcomm', 'AT+QSIMDET?', 'SIM检测', '查询SIM卡检测状态', 'AT+QSIMDET?', '无', '+QSIMDET: <state>', '+QSIMDET: 1
OK', 'SIM卡'],
        ['qualcomm', 'AT+QSIMHOT?', '热插拔', '查询SIM热插拔状态', 'AT+QSIMHOT?', '无', '+QSIMHOT: <state>', '+QSIMHOT: 1
OK', 'SIM卡'],
        ['qualcomm', 'AT+QSIMLOCK?', 'SIM锁', '查询SIM锁状态', 'AT+QSIMLOCK?', '无', '+QSIMLOCK: ...', '+QSIMLOCK: 0
OK', 'SIM卡'],
        ['qualcomm', 'AT+QFLN', '文件列表', '查询文件系统列表', 'AT+QFLN', '无', '+QFLN: <name>,<size>', '+QFLN: "config",1024
OK', '厂商专有'],
        ['qualcomm', 'AT+QFOPEN', '打开文件', '打开文件系统文件', 'AT+QFOPEN="<name>"', '文件名', '+QFOPEN: <handle>', '+QFOPEN: 1
OK', '厂商专有'],
        ['qualcomm', 'AT+QFREAD', '读取文件', '读取文件内容', 'AT+QFREAD=<handle>,<len>', '句柄,长度', '文件内容', 'AT+QFREAD=1,100
OK', '厂商专有'],
        ['qualcomm', 'AT+QFWRITE', '写入文件', '写入文件内容', 'AT+QFWRITE=<handle>,<data>', '句柄,数据', '+QFWRITE: <len>', '+QFWRITE: 100
OK', '厂商专有'],
        ['qualcomm', 'AT+QFCLOSE', '关闭文件', '关闭文件句柄', 'AT+QFCLOSE=<handle>', '句柄', 'OK', 'AT+QFCLOSE=1
OK', '厂商专有'],
        ['qualcomm', 'AT+QFDEL', '删除文件', '删除文件系统文件', 'AT+QFDEL="<name>"', '文件名', 'OK', 'AT+QFDEL="config"
OK', '厂商专有'],
        ['qualcomm', 'AT+QSSCAN', '网络扫描', '扫描可用网络', 'AT+QSSCAN', '无', '+QSSCAN: ...', '+QSSCAN: "46000",...
OK', '网络'],
        ['qualcomm', 'AT+QSPN?', '运营商名', '查询运营商名称', 'AT+QSPN?', '无', '+QSPN: <name>', '+QSPN: "CHINA MOBILE"
OK', '网络'],
        ['qualcomm', 'AT+QTEMP?', '温度查询', '查询模块温度', 'AT+QTEMP?', '无', '+QTEMP: <temp>', '+QTEMP: 48
OK', '设备信息'],
        ['qualcomm', 'AT+QVOLT?', '电压查询', '查询模块电压', 'AT+QVOLT?', '无', '+QVOLT: <voltage>', '+QVOLT: 3700
OK', '设备信息'],
        ['qualcomm', 'AT+QPCID?', 'PCI查询', '查询当前PCI', 'AT+QPCID?', '无', '+QPCID: <pci>', '+QPCID: 100
OK', '网络'],
        ['qualcomm', 'AT+QBAND?', '频段查询', '查询频段信息', 'AT+QBAND?', '无', '+QBAND: <band>', '+QBAND: 38
OK', '网络'],
        ['qualcomm', 'AT+QBAND=', '频段设置', '设置频段', 'AT+QBAND=<bands>', '频段列表', 'OK', 'AT+QBAND=1,3,5,8,38,39,40,41
OK', '网络'],
        ['qualcomm', 'AT+QREBOOT', '重启模块', '重启高通模块', 'AT+QREBOOT', '无', 'OK', 'AT+QREBOOT
OK', '基础'],
        ['qualcomm', 'AT+QFACTORY', '工厂复位', '恢复出厂设置', 'AT+QFACTORY', '无', 'OK', 'AT+QFACTORY
OK', '厂商专有'],
        ['qualcomm', 'AT+QNVFR?', 'NV读取', '读取NV项', 'AT+QNVFR=<id>', 'NV项ID', '+QNVFR: <data>', '+QNVFR: 0,0,0
OK', '厂商专有'],
        ['qualcomm', 'AT+QNVFW=', 'NV写入', '写入NV项', 'AT+QNVFW=<id>,<data>', 'NV项ID,数据', 'OK', 'AT+QNVFW=1,0
OK', '厂商专有'],
        ['qualcomm', 'AT+QIMEI?', 'IMEI查询', '查询IMEI号', 'AT+QIMEI?', '无', '+QIMEI: <imei>', '+QIMEI: 865123456789012
OK', '设备信息'],
        ['qualcomm', 'AT+QIMEI=', '写入IMEI', '写入IMEI号', 'AT+QIMEI=<imei>', '15位IMEI', 'OK', 'AT+QIMEI=865123456789012
OK', '厂商专有'],
        ['eigencomm', 'AT+ECDEVINFO?', '设备信息', '查询EC设备信息', 'AT+ECDEVINFO?', '无', '+ECDEVINFO: ...', '+ECDEVINFO: "EC618","V1.0"
OK', '设备信息'],
        ['eigencomm', 'AT+ECNWSCAN', '网络扫描', '扫描可用网络', 'AT+ECNWSCAN', '无', '+ECNWSCAN: ...', '+ECNWSCAN: "46000",...
OK', '网络'],
        ['eigencomm', 'AT+ECBAND?', '频段查询', '查询频段信息', 'AT+ECBAND?', '无', '+ECBAND: <band>', '+ECBAND: 5
OK', '网络'],
        ['eigencomm', 'AT+ECBAND=', '频段设置', '设置频段', 'AT+ECBAND=<bands>', '频段列表', 'OK', 'AT+ECBAND=5,8
OK', '网络'],
        ['eigencomm', 'AT+ECNWINFO?', '网络信息', '查询网络信息', 'AT+ECNWINFO?', '无', '+ECNWINFO: ...', '+ECNWINFO: "LTE",5,-95
OK', '网络'],
        ['eigencomm', 'AT+ECSIMINFO?', 'SIM信息', '查询SIM卡信息', 'AT+ECSIMINFO?', '无', '+ECSIMINFO: ...', '+ECSIMINFO: 1,"CMCC"
OK', 'SIM卡'],
        ['eigencomm', 'AT+ECFACTORY', '工厂复位', '恢复出厂设置', 'AT+ECFACTORY', '无', 'OK', 'AT+ECFACTORY
OK', '厂商专有'],
        ['eigencomm', 'AT+ECNVBACK', 'NV备份', '备份NV数据', 'AT+ECNVBACK', '无', 'OK', 'AT+ECNVBACK
OK', '厂商专有'],
        ['eigencomm', 'AT+ECNVREST', 'NV恢复', '恢复NV数据', 'AT+ECNVREST', '无', 'OK', 'AT+ECNVREST
OK', '厂商专有'],
        ['eigencomm', 'AT+ECREBOOT', '重启模块', '重启EC模块', 'AT+ECREBOOT', '无', 'OK', 'AT+ECREBOOT
OK', '基础'],
        ['eigencomm', 'AT+ECIMEI?', 'IMEI查询', '查询IMEI号', 'AT+ECIMEI?', '无', '+ECIMEI: <imei>', '+ECIMEI: 865123456789012
OK', '设备信息'],
        ['eigencomm', 'AT+ECIMEI=', '写入IMEI', '写入IMEI号', 'AT+ECIMEI=<imei>', '15位IMEI', 'OK', 'AT+ECIMEI=865123456789012
OK', '厂商专有'],
        ['eigencomm', 'AT+ECTEMP?', '温度查询', '查询模块温度', 'AT+ECTEMP?', '无', '+ECTEMP: <temp>', '+ECTEMP: 44
OK', '设备信息'],
        ['eigencomm', 'AT+ECVOLT?', '电压查询', '查询模块电压', 'AT+ECVOLT?', '无', '+ECVOLT: <voltage>', '+ECVOLT: 3700
OK', '设备信息'],
        ['eigencomm', 'AT+ECCSQ?', '信号质量', '查询EC信号质量', 'AT+ECCSQ?', '无', '+ECCSQ: <rssi>,<rsrp>,<rsrq>', '+ECCSQ: 25,-95,-12
OK', '网络'],
        ['eigencomm', 'AT+ECCELL?', '小区信息', '查询当前小区', 'AT+ECCELL?', '无', '+ECCELL: <pci>,<arfcn>,<rsrp>', '+ECCELL: 100,1850,-95
OK', '网络'],
        ['eigencomm', 'AT+ECLOCKCELL', '小区锁定', '锁定指定小区', 'AT+ECLOCKCELL=<arfcn>,<pci>', '频点,PCI', 'OK', 'AT+ECLOCKCELL=1850,100
OK', '网络'],
        ['eigencomm', 'AT+ECAPN?', 'APN查询', '查询APN配置', 'AT+ECAPN?', '无', '+ECAPN: <apn>', '+ECAPN: "CMNET"
OK', '数据'],
        ['eigencomm', 'AT+ECAPN=', 'APN设置', '设置APN配置', 'AT+ECAPN=<apn>', 'APN名称', 'OK', 'AT+ECAPN="CMNET"
OK', '数据'],
        ['mediatek', 'AT+MTDEVINFO?', '设备信息', '查询MT设备信息', 'AT+MTDEVINFO?', '无', '+MTDEVINFO: ...', '+MTDEVINFO: "MT2625","V1.0"
OK', '设备信息'],
        ['mediatek', 'AT+MTNWREG?', '网络注册', '查询MT网络注册状态', 'AT+MTNWREG?', '无', '+MTNWREG: <state>', '+MTNWREG: 1
OK', '网络'],
        ['mediatek', 'AT+MTBAND?', '频段查询', '查询频段信息', 'AT+MTBAND?', '无', '+MTBAND: <band>', '+MTBAND: 5
OK', '网络'],
        ['mediatek', 'AT+MTBAND=', '频段设置', '设置频段', 'AT+MTBAND=<bands>', '频段列表', 'OK', 'AT+MTBAND=5,8
OK', '网络'],
        ['mediatek', 'AT+MTNWINFO?', '网络信息', '查询网络信息', 'AT+MTNWINFO?', '无', '+MTNWINFO: ...', '+MTNWINFO: "LTE",5,-95
OK', '网络'],
        ['mediatek', 'AT+MTSIMINFO?', 'SIM信息', '查询SIM卡信息', 'AT+MTSIMINFO?', '无', '+MTSIMINFO: ...', '+MTSIMINFO: 1,"CMCC"
OK', 'SIM卡'],
        ['mediatek', 'AT+MTFACTORY', '工厂复位', '恢复出厂设置', 'AT+MTFACTORY', '无', 'OK', 'AT+MTFACTORY
OK', '厂商专有'],
        ['mediatek', 'AT+MTNVBACK', 'NV备份', '备份NV数据', 'AT+MTNVBACK', '无', 'OK', 'AT+MTNVBACK
OK', '厂商专有'],
        ['mediatek', 'AT+MTNVREST', 'NV恢复', '恢复NV数据', 'AT+MTNVREST', '无', 'OK', 'AT+MTNVREST
OK', '厂商专有'],
        ['mediatek', 'AT+MTREBOOT', '重启模块', '重启MTK模块', 'AT+MTREBOOT', '无', 'OK', 'AT+MTREBOOT
OK', '基础'],
        ['mediatek', 'AT+MTIMEI?', 'IMEI查询', '查询IMEI号', 'AT+MTIMEI?', '无', '+MTIMEI: <imei>', '+MTIMEI: 865123456789012
OK', '设备信息'],
        ['mediatek', 'AT+MTIMEI=', '写入IMEI', '写入IMEI号', 'AT+MTIMEI=<imei>', '15位IMEI', 'OK', 'AT+MTIMEI=865123456789012
OK', '厂商专有'],
        ['mediatek', 'AT+MTTEMP?', '温度查询', '查询模块温度', 'AT+MTTEMP?', '无', '+MTTEMP: <temp>', '+MTTEMP: 46
OK', '设备信息'],
        ['mediatek', 'AT+MTVOLT?', '电压查询', '查询模块电压', 'AT+MTVOLT?', '无', '+MTVOLT: <voltage>', '+MTVOLT: 3700
OK', '设备信息'],
        ['mediatek', 'AT+MTCSQ?', '信号质量', '查询MT信号质量', 'AT+MTCSQ?', '无', '+MTCSQ: <rssi>,<rsrp>,<rsrq>', '+MTCSQ: 25,-95,-12
OK', '网络'],
        ['mediatek', 'AT+MTCELL?', '小区信息', '查询当前小区', 'AT+MTCELL?', '无', '+MTCELL: <pci>,<arfcn>,<rsrp>', '+MTCELL: 100,1850,-95
OK', '网络'],
        ['mediatek', 'AT+MTLOCKCELL', '小区锁定', '锁定指定小区', 'AT+MTLOCKCELL=<arfcn>,<pci>', '频点,PCI', 'OK', 'AT+MTLOCKCELL=1850,100
OK', '网络'],
        ['mediatek', 'AT+MTAPN?', 'APN查询', '查询APN配置', 'AT+MTAPN?', '无', '+MTAPN: <apn>', '+MTAPN: "CMNET"
OK', '数据'],
        ['mediatek', 'AT+MTAPN=', 'APN设置', '设置APN配置', 'AT+MTAPN=<apn>', 'APN名称', 'OK', 'AT+MTAPN="CMNET"
OK', '数据'],
        ['hisilicon', 'AT+HDEVINFO?', '设备信息', '查询海思设备信息', 'AT+HDEVINFO?', '无', '+HDEVINFO: ...', '+HDEVINFO: "Boudica150","V1.0"
OK', '设备信息'],
        ['hisilicon', 'AT+HNWSTATUS?', '网络状态', '查询网络状态', 'AT+HNWSTATUS?', '无', '+HNWSTATUS: <state>', '+HNWSTATUS: 1
OK', '网络'],
        ['hisilicon', 'AT+HBAND?', '频段查询', '查询频段信息', 'AT+HBAND?', '无', '+HBAND: <band>', '+HBAND: 5
OK', '网络'],
        ['hisilicon', 'AT+HBAND=', '频段设置', '设置频段', 'AT+HBAND=<bands>', '频段列表', 'OK', 'AT+HBAND=5,8
OK', '网络'],
        ['hisilicon', 'AT+HNWINFO?', '网络信息', '查询网络详细信息', 'AT+HNWINFO?', '无', '+HNWINFO: ...', '+HNWINFO: "LTE",5,-95
OK', '网络'],
        ['hisilicon', 'AT+HSIMINFO?', 'SIM信息', '查询SIM卡信息', 'AT+HSIMINFO?', '无', '+HSIMINFO: ...', '+HSIMINFO: 1,"CMCC"
OK', 'SIM卡'],
        ['hisilicon', 'AT+HFACTORY', '工厂复位', '恢复出厂设置', 'AT+HFACTORY', '无', 'OK', 'AT+HFACTORY
OK', '厂商专有'],
        ['hisilicon', 'AT+HNVBACK', 'NV备份', '备份NV数据', 'AT+HNVBACK', '无', 'OK', 'AT+HNVBACK
OK', '厂商专有'],
        ['hisilicon', 'AT+HNVREST', 'NV恢复', '恢复NV数据', 'AT+HNVREST', '无', 'OK', 'AT+HNVREST
OK', '厂商专有'],
        ['hisilicon', 'AT+HREBOOT', '重启模块', '重启海思模块', 'AT+HREBOOT', '无', 'OK', 'AT+HREBOOT
OK', '基础'],
        ['hisilicon', 'AT+HIMEI?', 'IMEI查询', '查询IMEI号', 'AT+HIMEI?', '无', '+HIMEI: <imei>', '+HIMEI: 865123456789012
OK', '设备信息'],
        ['hisilicon', 'AT+HIMEI=', '写入IMEI', '写入IMEI号', 'AT+HIMEI=<imei>', '15位IMEI', 'OK', 'AT+HIMEI=865123456789012
OK', '厂商专有'],
        ['hisilicon', 'AT+HTEMP?', '温度查询', '查询模块温度', 'AT+HTEMP?', '无', '+HTEMP: <temp>', '+HTEMP: 47
OK', '设备信息'],
        ['hisilicon', 'AT+HVOLT?', '电压查询', '查询模块电压', 'AT+HVOLT?', '无', '+HVOLT: <voltage>', '+HVOLT: 3700
OK', '设备信息'],
        ['hisilicon', 'AT+HCSQ?', '信号质量', '查询海思信号质量', 'AT+HCSQ?', '无', '+HCSQ: <rssi>,<rsrp>,<rsrq>', '+HCSQ: 25,-95,-12
OK', '网络'],
        ['hisilicon', 'AT+HCELL?', '小区信息', '查询当前小区', 'AT+HCELL?', '无', '+HCELL: <pci>,<arfcn>,<rsrp>', '+HCELL: 100,1850,-95
OK', '网络'],
        ['hisilicon', 'AT+HLOCKCELL', '小区锁定', '锁定指定小区', 'AT+HLOCKCELL=<arfcn>,<pci>', '频点,PCI', 'OK', 'AT+HLOCKCELL=1850,100
OK', '网络'],
        ['hisilicon', 'AT+HAPN?', 'APN查询', '查询APN配置', 'AT+HAPN?', '无', '+HAPN: <apn>', '+HAPN: "CMNET"
OK', '数据'],
        ['hisilicon', 'AT+HAPN=', 'APN设置', '设置APN配置', 'AT+HAPN=<apn>', 'APN名称', 'OK', 'AT+HAPN="CMNET"
OK', '数据'],
        ['xinyi', 'AT+XYDEVINFO?', '设备信息', '查询芯翼设备信息', 'AT+XYDEVINFO?', '无', '+XYDEVINFO: ...', '+XYDEVINFO: "XY1100","V1.0"
OK', '设备信息'],
        ['xinyi', 'AT+XYNWINFO?', '网络信息', '查询网络信息', 'AT+XYNWINFO?', '无', '+XYNWINFO: ...', '+XYNWINFO: "LTE",5,-95
OK', '网络'],
        ['xinyi', 'AT+XYBAND?', '频段查询', '查询频段信息', 'AT+XYBAND?', '无', '+XYBAND: <band>', '+XYBAND: 5
OK', '网络'],
        ['xinyi', 'AT+XYBAND=', '频段设置', '设置频段', 'AT+XYBAND=<bands>', '频段列表', 'OK', 'AT+XYBAND=5,8
OK', '网络'],
        ['xinyi', 'AT+XYSIMINFO?', 'SIM信息', '查询SIM卡信息', 'AT+XYSIMINFO?', '无', '+XYSIMINFO: ...', '+XYSIMINFO: 1,"CMCC"
OK', 'SIM卡'],
        ['xinyi', 'AT+XYFACTORY', '工厂复位', '恢复出厂设置', 'AT+XYFACTORY', '无', 'OK', 'AT+XYFACTORY
OK', '厂商专有'],
        ['xinyi', 'AT+XYNVBACK', 'NV备份', '备份NV数据', 'AT+XYNVBACK', '无', 'OK', 'AT+XYNVBACK
OK', '厂商专有'],
        ['xinyi', 'AT+XYNVREST', 'NV恢复', '恢复NV数据', 'AT+XYNVREST', '无', 'OK', 'AT+XYNVREST
OK', '厂商专有'],
        ['xinyi', 'AT+XYREBOOT', '重启模块', '重启芯翼模块', 'AT+XYREBOOT', '无', 'OK', 'AT+XYREBOOT
OK', '基础'],
        ['xinyi', 'AT+XYIMEI?', 'IMEI查询', '查询IMEI号', 'AT+XYIMEI?', '无', '+XYIMEI: <imei>', '+XYIMEI: 865123456789012
OK', '设备信息'],
        ['xinyi', 'AT+XYIMEI=', '写入IMEI', '写入IMEI号', 'AT+XYIMEI=<imei>', '15位IMEI', 'OK', 'AT+XYIMEI=865123456789012
OK', '厂商专有'],
        ['xinyi', 'AT+XYTEMP?', '温度查询', '查询模块温度', 'AT+XYTEMP?', '无', '+XYTEMP: <temp>', '+XYTEMP: 45
OK', '设备信息'],
        ['xinyi', 'AT+XYVOLT?', '电压查询', '查询模块电压', 'AT+XYVOLT?', '无', '+XYVOLT: <voltage>', '+XYVOLT: 3700
OK', '设备信息'],
        ['xinyi', 'AT+XYCSQ?', '信号质量', '查询芯翼信号质量', 'AT+XYCSQ?', '无', '+XYCSQ: <rssi>,<rsrp>,<rsrq>', '+XYCSQ: 25,-95,-12
OK', '网络'],
        ['xinyi', 'AT+XYCELL?', '小区信息', '查询当前小区', 'AT+XYCELL?', '无', '+XYCELL: <pci>,<arfcn>,<rsrp>', '+XYCELL: 100,1850,-95
OK', '网络'],
        ['xinyi', 'AT+XYLOCKCELL', '小区锁定', '锁定指定小区', 'AT+XYLOCKCELL=<arfcn>,<pci>', '频点,PCI', 'OK', 'AT+XYLOCKCELL=1850,100
OK', '网络'],
        ['xinyi', 'AT+XYAPN?', 'APN查询', '查询APN配置', 'AT+XYAPN?', '无', '+XYAPN: <apn>', '+XYAPN: "CMNET"
OK', '数据'],
        ['xinyi', 'AT+XYAPN=', 'APN设置', '设置APN配置', 'AT+XYAPN=<apn>', 'APN名称', 'OK', 'AT+XYAPN="CMNET"
OK', '数据'],
        ['esp32', 'AT', '基础测试', '检测ESP32 AT固件响应', 'AT', '无', 'OK', 'AT
OK', '基础'],
        ['esp32', 'ATI', '版本信息', '查询AT固件版本', 'ATI', '无', '版本信息', 'ATI
ESP32 AT v2.2.0
OK', '设备信息'],
        ['esp32', 'AT+GMR', '固件信息', '查询固件编译信息', 'AT+GMR', '无', '版本/编译时间/SDK', 'AT+GMR
AT version: 2.2.0
OK', '设备信息'],
        ['esp32', 'AT+RESTORE', '恢复出厂', '恢复出厂设置', 'AT+RESTORE', '无', 'OK', 'AT+RESTORE
OK', '基础'],
        ['esp32', 'AT+RST', '重启', '重启ESP32模块', 'AT+RST', '无', 'OK', 'AT+RST
OK', '基础'],
        ['esp32', 'AT+SLEEP', '休眠模式', '设置深度休眠', 'AT+SLEEP=<time>', '休眠时间(ms)', 'OK', 'AT+SLEEP=5000
OK', '基础'],
        ['esp32', 'AT+CWMODE?', 'WiFi模式', '查询WiFi工作模式', 'AT+CWMODE?', '无', '+CWMODE: <mode>', '+CWMODE: 1
OK', 'WiFi'],
        ['esp32', 'AT+CWMODE=', '设置WiFi模式', '设置WiFi模式', 'AT+CWMODE=<mode>', '1=STA,2=AP,3=STA+AP', 'OK', 'AT+CWMODE=1
OK', 'WiFi'],
        ['esp32', 'AT+CWJAP?', '连接信息', '查询当前WiFi连接信息', 'AT+CWJAP?', '无', '+CWJAP: <ssid>,<bssid>,<channel>,<rssi>', '+CWJAP: "MyWiFi","aa:bb:cc:dd:ee:ff",6,-45
OK', 'WiFi'],
        ['esp32', 'AT+CWJAP=', '连接WiFi', '连接指定WiFi', 'AT+CWJAP="<ssid>","<pwd>"', 'SSID,密码', 'OK/WIFI CONNECTED', 'AT+CWJAP="MyWiFi","12345678"
OK', 'WiFi'],
        ['esp32', 'AT+CWQAP', '断开WiFi', '断开WiFi连接', 'AT+CWQAP', '无', 'OK', 'AT+CWQAP
OK', 'WiFi'],
        ['esp32', 'AT+CWLAP', '扫描WiFi', '扫描可用WiFi列表', 'AT+CWLAP', '无', '+CWLAP: <ecn>,<ssid>,<rssi>,<mac>,<channel>', '+CWLAP: 4,"MyWiFi",-45,"aa:bb:cc:dd:ee:ff",6
OK', 'WiFi'],
        ['esp32', 'AT+CWSAP?', '热点信息', '查询AP热点配置', 'AT+CWSAP?', '无', '+CWSAP: <ssid>,<pwd>,<channel>,<ecn>', '+CWSAP: "ESP32AP","12345678",5,4
OK', 'WiFi'],
        ['esp32', 'AT+CWSAP=', '设置热点', '设置AP热点参数', 'AT+CWSAP="<ssid>","<pwd>",<ch>,<ecn>', 'SSID,密码,通道,加密', 'OK', 'AT+CWSAP="ESP32AP","12345678",5,4
OK', 'WiFi'],
        ['esp32', 'AT+CWLIF', '连接列表', '查询连接到AP的设备', 'AT+CWLIF', '无', '+CWLIF: <ip>,<mac>', '+CWLIF: "192.168.4.2","aa:bb:cc:dd:ee:ff"
OK', 'WiFi'],
        ['esp32', 'AT+CIPSTATUS', '连接状态', '查询TCP/UDP连接状态', 'AT+CIPSTATUS', '无', 'STATUS: <stat>', 'STATUS: 4
OK', '网络'],
        ['esp32', 'AT+CIPSTART', '建立连接', '建立TCP/UDP连接', 'AT+CIPSTART="<type>","<host>",<port>', '类型,主机,端口', 'OK/CONNECT', 'AT+CIPSTART="TCP","192.168.1.1",8080
OK', '网络'],
        ['esp32', 'AT+CIPSEND', '发送数据', '发送TCP/UDP数据', 'AT+CIPSEND=<length>', '数据长度', '> 提示输入数据', 'AT+CIPSEND=10
>HelloWorld
OK', '网络'],
        ['esp32', 'AT+CIPCLOSE', '关闭连接', '关闭TCP/UDP连接', 'AT+CIPCLOSE', '无', 'OK', 'AT+CIPCLOSE
OK', '网络'],
        ['esp32', 'AT+CIPMUX?', '多连接', '查询多连接模式', 'AT+CIPMUX?', '无', '+CIPMUX: <mode>', '+CIPMUX: 0
OK', '网络'],
        ['esp32', 'AT+CIPMUX=1', '开启多连接', '开启多连接模式', 'AT+CIPMUX=1', '1=多连接', 'OK', 'AT+CIPMUX=1
OK', '网络'],
        ['esp32', 'AT+CIPSERVER', 'TCP服务器', '创建/关闭TCP服务器', 'AT+CIPSERVER=<mode>[,<port>]', '1=创建,0=关闭,端口', 'OK', 'AT+CIPSERVER=1,8080
OK', '网络'],
        ['esp32', 'AT+CIFSR', 'IP地址', '查询本机IP地址', 'AT+CIFSR', '无', '+CIFSR: <type>,<ip>', '+CIFSR: STAIP,"192.168.1.100"
OK', '网络'],
        ['esp32', 'AT+CIPSTAMAC?', 'MAC地址', '查询STA MAC地址', 'AT+CIPSTAMAC?', '无', '+CIPSTAMAC: <mac>', '+CIPSTAMAC: "aa:bb:cc:dd:ee:ff"
OK', '设备信息'],
        ['esp32', 'AT+BLEINIT?', '蓝牙初始化', '查询蓝牙初始化状态', 'AT+BLEINIT?', '无', '+BLEINIT: <state>', '+BLEINIT: 1
OK', '蓝牙'],
        ['esp32', 'AT+BLEINIT=1', '初始化蓝牙', '初始化BLE蓝牙', 'AT+BLEINIT=1', '1=BLE', 'OK', 'AT+BLEINIT=1
OK', '蓝牙'],
        ['esp32', 'AT+BLEGAPSCAN', '蓝牙扫描', '扫描BLE设备', 'AT+BLEGAPSCAN=<time>', '扫描时间(秒)', '+BLEGAP: <addr>,<rssi>,<name>', 'AT+BLEGAPSCAN=5
OK', '蓝牙'],
        ['esp32', 'AT+BLEADV?', '广播查询', '查询BLE广播状态', 'AT+BLEADV?', '无', '+BLEADV: <state>', '+BLEADV: 0
OK', '蓝牙'],
        ['esp32', 'AT+BLEADV=1', '开启广播', '开启BLE广播', 'AT+BLEADV=1', '1=开启', 'OK', 'AT+BLEADV=1
OK', '蓝牙'],
        ['esp32', 'AT+SYSRAM?', '内存查询', '查询剩余内存', 'AT+SYSRAM?', '无', '+SYSRAM: <bytes>', '+SYSRAM: 200000
OK', '设备信息'],
        ['esp32', 'AT+SYSADC?', 'ADC读取', '读取ADC值', 'AT+SYSADC?', '无', '+SYSADC: <value>', '+SYSADC: 1234
OK', '设备信息'],
        ['esp32', 'AT+SYSGPIO?', 'GPIO查询', '查询GPIO状态', 'AT+SYSGPIO?', '无', '+SYSGPIO: <value>', '+SYSGPIO: 0,1,0,1
OK', '设备信息'],
        ['esp32', 'AT+SYSPWM', 'PWM输出', '设置PWM输出', 'AT+SYSPWM=<ch>,<freq>,<duty>', '通道,频率,占空比', 'OK', 'AT+SYSPWM=0,1000,50
OK', '设备信息'],
        ['stm32', 'AT', '基础测试', '检测STM32 AT响应', 'AT', '无', 'OK', 'AT
OK', '基础'],
        ['stm32', 'ATI', '设备信息', '查询STM32型号信息', 'ATI', '无', '型号信息', 'ATI
STM32F407
OK', '设备信息'],
        ['stm32', 'AT+RST', '重启', '重启STM32', 'AT+RST', '无', 'OK', 'AT+RST
OK', '基础'],
        ['stm32', 'AT+UART?', '串口配置', '查询串口配置', 'AT+UART?', '无', '+UART: <baud>,<data>,<stop>,<parity>', '+UART: 115200,8,1,0
OK', '串口'],
        ['stm32', 'AT+UART=', '设置串口', '设置串口参数', 'AT+UART=<baud>,<data>,<stop>,<parity>', '波特率,数据位,停止位,校验', 'OK', 'AT+UART=9600,8,1,0
OK', '串口'],
        ['stm32', 'AT+GPIO?', 'GPIO查询', '查询GPIO状态', 'AT+GPIO?', '无', '+GPIO: <port>,<pin>,<state>', '+GPIO: A,5,1
OK', 'GPIO'],
        ['stm32', 'AT+GPIO=', 'GPIO设置', '设置GPIO输出', 'AT+GPIO=<port>,<pin>,<state>', '端口,引脚,状态', 'OK', 'AT+GPIO=A,5,1
OK', 'GPIO'],
        ['stm32', 'AT+GPIOMODE', 'GPIO模式', '设置GPIO模式', 'AT+GPIOMODE=<port>,<pin>,<mode>', '端口,引脚,模式(0-3)', 'OK', 'AT+GPIOMODE=A,5,1
OK', 'GPIO'],
        ['stm32', 'AT+ADC?', 'ADC读取', '读取ADC值', 'AT+ADC=<channel>', 'ADC通道号', '+ADC: <value>', '+ADC: 2048
OK', 'ADC'],
        ['stm32', 'AT+ADCMODE', 'ADC模式', '设置ADC采样模式', 'AT+ADCMODE=<mode>', '0=单次,1=连续,2=DMA', 'OK', 'AT+ADCMODE=1
OK', 'ADC'],
        ['stm32', 'AT+PWM?', 'PWM查询', '查询PWM配置', 'AT+PWM?', '无', '+PWM: <ch>,<freq>,<duty>', '+PWM: 0,1000,50
OK', 'PWM'],
        ['stm32', 'AT+PWM=', 'PWM设置', '设置PWM输出', 'AT+PWM=<ch>,<freq>,<duty>', '通道,频率,占空比', 'OK', 'AT+PWM=0,1000,50
OK', 'PWM'],
        ['stm32', 'AT+I2C?', 'I2C查询', '查询I2C配置', 'AT+I2C?', '无', '+I2C: <speed>', '+I2C: 100000
OK', 'I2C'],
        ['stm32', 'AT+I2CWR', 'I2C写', 'I2C写数据', 'AT+I2CWR=<addr>,<reg>,<data>', '设备地址,寄存器,数据', 'OK', 'AT+I2CWR=0x50,0x00,0x01
OK', 'I2C'],
        ['stm32', 'AT+I2CRD', 'I2C读', 'I2C读数据', 'AT+I2CRD=<addr>,<reg>,<len>', '设备地址,寄存器,长度', '+I2CRD: <data>', '+I2CRD: 0x01
OK', 'I2C'],
        ['stm32', 'AT+SPI?', 'SPI查询', '查询SPI配置', 'AT+SPI?', '无', '+SPI: <speed>,<mode>', '+SPI: 1000000,0
OK', 'SPI'],
        ['stm32', 'AT+SPIWR', 'SPI写', 'SPI写数据', 'AT+SPIWR=<data>', '十六进制数据', '+SPIWR: <response>', '+SPIWR: 0xFF
OK', 'SPI'],
        ['stm32', 'AT+TIMER?', '定时器查询', '查询定时器配置', 'AT+TIMER?', '无', '+TIMER: <ch>,<freq>', '+TIMER: 0,1000
OK', '定时器'],
        ['stm32', 'AT+TIMER=', '定时器设置', '设置定时器', 'AT+TIMER=<ch>,<freq>', '通道,频率', 'OK', 'AT+TIMER=0,1000
OK', '定时器'],
        ['stm32', 'AT+FLASH?', 'Flash查询', '查询Flash信息', 'AT+FLASH?', '无', '+FLASH: <size>,<used>', '+FLASH: 1024,512
OK', 'Flash'],
        ['stm32', 'AT+FLASHWR', 'Flash写', '写入Flash数据', 'AT+FLASHWR=<addr>,<data>', '地址,数据', 'OK', 'AT+FLASHWR=0x08000000,0x01
OK', 'Flash'],
        ['stm32', 'AT+FLASHRD', 'Flash读', '读取Flash数据', 'AT+FLASHRD=<addr>,<len>', '地址,长度', '+FLASHRD: <data>', '+FLASHRD: 0x01
OK', 'Flash'],
        ['stm32', 'AT+LED=', 'LED控制', '控制LED灯', 'AT+LED=<id>,<state>', 'LED编号,状态', 'OK', 'AT+LED=0,1
OK', 'GPIO'],
        ['stm32', 'AT+BTN?', '按键查询', '查询按键状态', 'AT+BTN?', '无', '+BTN: <id>,<state>', '+BTN: 0,1
OK', 'GPIO'],
        ['stm32', 'AT+TEMP?', '温度查询', '查询芯片温度', 'AT+TEMP?', '无', '+TEMP: <temp>', '+TEMP: 35.5
OK', '设备信息'],
        ['stm32', 'AT+SYSCLK?', '时钟查询', '查询系统时钟', 'AT+SYSCLK?', '无', '+SYSCLK: <freq>', '+SYSCLK: 168000000
OK', '设备信息'],
        ['stm32', 'AT+SAVE', '保存配置', '保存配置到Flash', 'AT+SAVE', '无', 'OK', 'AT+SAVE
OK', '基础'],
        ['stm32', 'AT+RESTORE', '恢复出厂', '恢复出厂设置', 'AT+RESTORE', '无', 'OK', 'AT+RESTORE
OK', '基础'],
    ];

    // Merge expanded AT command set from at_commands_data.php
    if (function_exists('getAllExtraATCommands')) {
        $extra = getAllExtraATCommands();
        $existing = [];
        foreach ($base as $c) {
            $existing[$c[0] . '|' . $c[1]] = true;
        }
        foreach ($extra as $c) {
            $key = $c[0] . '|' . $c[1];
            if (!isset($existing[$key])) {
                $base[] = $c;
                $existing[$key] = true;
            }
        }
    }

    // Deduplicate within $base itself (keep first occurrence by chip_type|command key)
    $seen = [];
    $unique = [];
    foreach ($base as $c) {
        $key = $c[0] . '|' . $c[1];
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $unique[] = $c;
        }
    }

    return $unique;
}
