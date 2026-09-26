<?php
/**
 * 芯片调试通用页面模板
 *
 * 被 asr.php / unisoc.php 复用，通过 $device_type / $device_name 区分芯片类型。
 *
 * @package HardwareDebugTool
 */
$device_type = $device_type ?? 'zte';
$device_name = $device_name ?? '中兴微';

// 白名单校验，防止非法类型注入
$validDeviceTypes = ['zte', 'asr', 'unisoc', 'esp32', 'stm32', 'qualcomm', 'eigencomm', 'mediatek', 'hisilicon', 'xinyi'];
if (!in_array($device_type, $validDeviceTypes, true)) {
    $device_type = 'zte';
}

$skip_stats = false;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-debug">
    <div class="debug-header">
        <h1><?php echo h($device_name); ?>芯片调试</h1>
        <div class="connection-status" id="connectionStatus">
            <span class="status-dot disconnected"></span>
            <span class="status-text">未连接</span>
        </div>
    </div>

    <div class="debug-container">
        <!-- 串口配置 -->
        <div class="config-panel">
            <h3>串口配置</h3>
            <div class="form-group">
                <label>波特率:</label>
                <select id="baudRate">
                    <option value="9600">9600</option>
                    <option value="19200">19200</option>
                    <option value="38400">38400</option>
                    <option value="57600">57600</option>
                    <option value="115200" selected>115200</option>
                    <option value="230400">230400</option>
                    <option value="460800">460800</option>
                    <option value="921600">921600</option>
                </select>
            </div>
            <div class="form-group">
                <label>数据位:</label>
                <select id="dataBits">
                    <option value="7">7</option>
                    <option value="8" selected>8</option>
                </select>
            </div>
            <div class="form-group">
                <label>停止位:</label>
                <select id="stopBits">
                    <option value="1" selected>1</option>
                    <option value="2">2</option>
                </select>
            </div>
            <div class="form-group">
                <label>校验位:</label>
                <select id="parity">
                    <option value="none" selected>None</option>
                    <option value="even">Even</option>
                    <option value="odd">Odd</option>
                </select>
            </div>
            <div class="button-group">
                <button class="btn btn-primary" id="btnConnect" onclick="connectSerial()">连接串口</button>
                <button class="btn btn-danger" id="btnDisconnect" onclick="disconnectSerial()" disabled>断开连接</button>
            </div>
        </div>

        <!-- AT指令区 -->
        <div class="at-panel">
            <h3>AT指令</h3>
            <div class="at-commands" id="atCommands">
                <!-- AT指令按钮由JS根据芯片类型动态生成 -->
            </div>
            <div class="custom-at">
                <input type="text" id="customAT" placeholder="输入自定义AT指令..." onkeypress="if(event.key==='Enter')sendCustomAT()">
                <button class="btn btn-primary" onclick="sendCustomAT()">发送</button>
                <?php if ($device_type === 'zte'): ?>
                <button class="btn btn-sm" onclick="sendAT('AT+CGEQOSRDP=1')">限速检测</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($device_type === 'zte' || $device_type === 'asr'): ?>
        <!-- 工厂模式与ADB控制 -->
        <div class="func-panel" id="panelFactory">
            <h3>工厂模式与ADB</h3>
            <div class="func-row">
                <?php if ($device_type === 'zte'): ?>
                <button class="btn btn-warning" onclick="sendAT('AT+ZMODE=1')">开启工厂模式</button>
                <button class="btn btn-secondary" onclick="sendAT('AT+ZMODE=0')">退出工厂模式</button>
                <span class="func-separator"></span>
                <label class="func-label">ADB IP:</label>
                <input type="text" id="adbIp" value="192.168.0.1" class="func-input-sm">
                <button class="btn btn-sm" onclick="toggleADB(1,1)">开启ADB</button>
                <button class="btn btn-sm" onclick="toggleADB(1,0)">关闭ADB</button>
                <button class="btn btn-sm" onclick="toggleADB(2,1)">开启ADB2</button>
                <button class="btn btn-sm" onclick="toggleADB(2,0)">关闭ADB2</button>
                <span class="func-separator"></span>
                <button class="btn btn-sm btn-danger" onclick="rebootDevice(1)">重启</button>
                <button class="btn btn-sm btn-danger" onclick="rebootDevice(2)">重启2</button>
                <?php elseif ($device_type === 'asr'): ?>
                <button class="btn btn-warning" onclick="sendAT('AT*PROD=1')">开启工厂模式</button>
                <button class="btn btn-secondary" onclick="sendAT('AT*PROD=0')">退出工厂模式</button>
                <?php endif; ?>
            </div>
            <p class="func-hint">需开启工厂模式才能写号，写入之前先删除</p>
        </div>

        <!-- 设备写号 (IMEI/SN/MAC) -->
        <div class="func-panel" id="panelWriteInfo">
            <h3>设备写号</h3>
            <div class="write-field">
                <label>IMEI:</label>
                <input type="text" id="fieldIMEI" class="func-input">
                <button class="btn btn-sm" onclick="readField('imei')">读取</button>
                <?php if ($device_type === 'asr'): ?>
                <button class="btn btn-sm btn-danger" onclick="sendAT('AT*MRD_IMEI=D')">删除</button>
                <button class="btn btn-sm btn-primary" onclick="writeField('imei')">写入</button>
                <?php else: ?>
                <button class="btn btn-sm btn-primary" onclick="writeField('imei')">写入</button>
                <?php endif; ?>
            </div>
            <?php if ($device_type === 'asr'): ?>
            <div class="write-field">
                <label>SN:</label>
                <input type="text" id="fieldSN" class="func-input" placeholder="仅支持有SN号的设备">
                <button class="btn btn-sm" onclick="readField('sn')">读取</button>
                <button class="btn btn-sm btn-danger" onclick="sendAT('AT*MRD_SN=D')">删除</button>
                <button class="btn btn-sm btn-primary" onclick="writeField('sn')">写入</button>
            </div>
            <?php endif; ?>
            <div class="write-field">
                <label>MAC:</label>
                <input type="text" id="fieldMAC" class="func-input">
                <button class="btn btn-sm" onclick="readField('mac')">读取</button>
                <?php if ($device_type === 'asr'): ?>
                <button class="btn btn-sm btn-danger" onclick="sendAT('AT*MRD_WIFIID=D')">删除</button>
                <?php endif; ?>
                <button class="btn btn-sm btn-primary" onclick="writeField('mac')">写入</button>
            </div>
            <?php if ($device_type === 'zte'): ?>
            <div class="write-field">
                <label>有线MAC:</label>
                <input type="text" id="fieldMAC2" class="func-input">
                <button class="btn btn-sm" onclick="sendAT('AT+MAC2?')">读取</button>
                <button class="btn btn-sm btn-primary" onclick="writeField('mac2')">写入</button>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($device_type === 'asr'): ?>
        <!-- 切卡与重启 -->
        <div class="func-panel" id="panelSimReboot">
            <h3>切卡与重启</h3>
            <div class="func-row">
                <button class="btn btn-primary" onclick="sendAT('AT+SWSIM=0')">切换到卡1</button>
                <button class="btn btn-primary" onclick="sendAT('AT+SWSIM=1')">切换到卡2</button>
                <span class="func-separator"></span>
                <button class="btn btn-danger" onclick="sendAT('AT+RESET')">重启设备</button>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($device_type === 'zte'): ?>
        <!-- 小区锁定 -->
        <div class="func-panel" id="panelCellLock">
            <h3>小区锁定</h3>
            <div class="func-row">
                <label class="func-label">频点:</label>
                <input type="text" id="lteCellArfcn" class="func-input-sm" style="width:80px;">
                <label class="func-label" style="margin-left:16px;">小区:</label>
                <input type="text" id="lteCellPci" class="func-input-sm" style="width:80px;">
                <label class="func-label" style="margin-left:16px;">是否锁定:</label>
                <input type="checkbox" id="lteCellLock">
                <span class="func-separator"></span>
                <button class="btn btn-sm" onclick="sendAT('AT+ZLC?')">读取</button>
                <button class="btn btn-sm btn-primary" onclick="writeCellLock()">写入</button>
            </div>
            <p class="func-hint">显示的仅为之前保存锁定小区的数据，并非是当前实际接入的小区</p>
        </div>
        <?php endif; ?>

        <!-- 频段选择 -->
        <div class="func-panel" id="panelBand">
            <h3>频段选择</h3>
            <div class="band-checkboxes" id="bandCheckboxes">
                <?php
                $bands = [1, 3, 5, 8, 38, 39, 40, 41];
                foreach ($bands as $b): ?>
                <label class="band-label">
                    <input type="checkbox" class="band-cb" value="<?php echo $b; ?>"> LTE B<?php echo $b; ?>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="func-row" style="margin-top:10px;">
                <button class="btn btn-sm" onclick="readBand()">读取频段</button>
                <button class="btn btn-sm btn-primary" onclick="lockBand()">锁定频段</button>
                <?php if ($device_type === 'zte'): ?>
                <button class="btn btn-sm" onclick="sendAT('AT+CFUN=4');setTimeout(()=>sendAT('AT+CFUN=1'),5000)">重启网络</button>
                <?php endif; ?>
            </div>
            <p class="func-hint">显示的频段不一定支持，能成功锁定的频段才算是支持</p>
        </div>

        <?php endif; ?>

        <!-- 数据收发区 -->
        <div class="data-panel">
            <div class="data-panel-header">
                <h3>数据收发</h3>
                <div class="font-controls">
                    <button class="btn btn-sm btn-icon" onclick="changeFontSize(-1)" title="减小字体">A-</button>
                    <span class="font-size-label" id="fontSizeLabel">13px</span>
                    <button class="btn btn-sm btn-icon" onclick="changeFontSize(1)" title="增大字体">A+</button>
                </div>
            </div>
            <div class="data-display" id="dataDisplay">
                <div class="data-log" id="dataLog"></div>
            </div>
            <div class="data-input">
                <input type="text" id="dataInput" placeholder="输入要发送的数据..." onkeypress="if(event.key==='Enter')sendData()">
                <button class="btn btn-primary" onclick="sendData()">发送数据</button>
                <button class="btn btn-secondary" onclick="clearData()">清空</button>
            </div>
        </div>
    </div>

    <!-- 提示信息 -->
    <div class="tips-section">
        <div class="tip-card">
            <h4>使用提示</h4>
            <ul>
                <li>请确保已安装<?php echo h($device_name); ?>驱动</li>
                <li>使用Chrome 89+或其他chromium内核浏览器</li>
                <li>点击"连接串口"后选择AT端口</li>
                <li>若数据异常,请多次点击读取按钮刷新</li>
            </ul>
        </div>
    </div>
</div>

<script>
// 串口配置
let serialPort = null;
let reader = null;
let writer = null;
let isConnected = false;
let currentFontSize = 13; // 数据区字体大小

// 当前芯片类型
const DEVICE_TYPE = <?php echo json_encode($device_type); ?>;

// AT指令集定义 - 按芯片类型区分
const AT_COMMANDS = {
    zte: [
        {cmd: "AT&F", desc: "恢复出厂配置"},
        {cmd: "AT&V", desc: "查看配置"},
        {cmd: "AT+CMEE=0", desc: "错误码关闭"},
        {cmd: "AT+CMEE=1", desc: "错误码数字"},
        {cmd: "AT+CMEE=2", desc: "错误码详细"},
        {cmd: "AT+GCAP", desc: "能力查询"},
        {cmd: "AT+CCLK?", desc: "查询时钟"},
        {cmd: "AT+CCLK=", desc: "设置时钟"},
        {cmd: "AT+CTZR", desc: "时区上报"},
        {cmd: "AT+CTZU", desc: "时区更新"},
        {cmd: "AT+ZCLOCK?", desc: "时钟查询"},
        {cmd: "AT+ZALARM=", desc: "闹钟设置"},
        {cmd: "AT+ZRTC?", desc: "RTC查询"},
        {cmd: "AT", desc: "基础测试"},
        {cmd: "AT+CFUN=1,1", desc: "重启模块"},
        {cmd: "AT+GMI", desc: "厂商ID"},
        {cmd: "AT+GMM", desc: "型号ID"},
        {cmd: "AT+GMR", desc: "版本ID"},
        {cmd: "AT+GSN", desc: "序列号"},
        {cmd: "AT+ZSN?", desc: "SN查询"},
        {cmd: "AT+ZVERSION?", desc: "版本查询"},
        {cmd: "AT+ZETHMAC?", desc: "以太网MAC"},
        {cmd: "ATI", desc: "设备信息"},
        {cmd: "AT+CGMI", desc: "厂商信息"},
        {cmd: "AT+CGMM", desc: "模块型号"},
        {cmd: "AT+CGMR", desc: "软件版本"},
        {cmd: "AT+CGSN", desc: "IMEI号"},
        {cmd: "AT+V", desc: "版本信息"},
        {cmd: "AT+ZFWVER?", desc: "固件版本"},
        {cmd: "AT+ZTEMP?", desc: "温度查询"},
        {cmd: "AT+CNETLIGHT", desc: "网络灯控制"},
        {cmd: "AT+COPN", desc: "运营商列表"},
        {cmd: "AT+COPS=", desc: "选择运营商"},
        {cmd: "AT+CSPN", desc: "SPN查询"},
        {cmd: "AT+ZDNS?", desc: "DNS查询"},
        {cmd: "AT+ZDNS=", desc: "DNS设置"},
        {cmd: "AT+ZPING", desc: "Ping测试"},
        {cmd: "AT+ZSNT=", desc: "网络模式设置"},
        {cmd: "AT+ZETH?", desc: "以太网状态"},
        {cmd: "AT+ZETHIP?", desc: "以太网IP"},
        {cmd: "AT+CSQ", desc: "信号质量"},
        {cmd: "AT+CREG?", desc: "网络注册"},
        {cmd: "AT+CGREG?", desc: "GPRS注册"},
        {cmd: "AT+CEREG?", desc: "LTE注册"},
        {cmd: "AT+COPS?", desc: "运营商信息"},
        {cmd: "AT+CCID", desc: "ICCID查询"},
        {cmd: "AT+CIMI", desc: "IMSI查询"},
        {cmd: "AT+CRSM", desc: "SIM访问"},
        {cmd: "AT+CSIM", desc: "通用SIM访问"},
        {cmd: "AT+CPINR", desc: "PIN重试次数"},
        {cmd: "AT+CPINC", desc: "PUK重试次数"},
        {cmd: "AT+CPIN=", desc: "输入PIN码"},
        {cmd: "AT+ZICCID?", desc: "ICCID查询"},
        {cmd: "AT+ZIMSI?", desc: "IMSI查询"},
        {cmd: "AT+CPIN?", desc: "SIM卡状态"},
        {cmd: "AT+ZSIM?", desc: "SIM卡信息"},
        {cmd: "AT+ZSIMLOCK", desc: "SIM锁控制"},
        {cmd: "AT+CMGD", desc: "删除短信"},
        {cmd: "AT+CSCS", desc: "字符集"},
        {cmd: "AT+CSCA", desc: "短信中心"},
        {cmd: "AT+CSMP", desc: "短信参数"},
        {cmd: "AT+CSDH", desc: "短信头"},
        {cmd: "AT+CNMA", desc: "短信确认"},
        {cmd: "AT+CMSS", desc: "从存储发送"},
        {cmd: "AT+CMGW", desc: "写入短信"},
        {cmd: "AT+CMGC", desc: "发送命令"},
        {cmd: "AT+CMGF=1", desc: "短信模式"},
        {cmd: "AT+CMGS", desc: "发送短信"},
        {cmd: "AT+CMGR", desc: "读取短信"},
        {cmd: "AT+CMGL", desc: "列出短信"},
        {cmd: "AT+CNMI", desc: "短信通知"},
        {cmd: "AT+CLIP", desc: "来电显示"},
        {cmd: "AT+CLIR", desc: "主叫隐藏"},
        {cmd: "AT+COLP", desc: "被叫显示"},
        {cmd: "AT+CCFC", desc: "呼叫转移"},
        {cmd: "AT+CHUP", desc: "挂断通话"},
        {cmd: "AT+VTS", desc: "DTMF发送"},
        {cmd: "AT+CCWA", desc: "呼叫等待"},
        {cmd: "AT+CTEC", desc: "通话类型"},
        {cmd: "AT+CVHU", desc: "语音挂断"},
    ],
    asr: [
        {cmd: "AT&F", desc: "恢复出厂配置"},
        {cmd: "AT&V", desc: "查看配置"},
        {cmd: "AT+CMEE=0", desc: "错误码关闭"},
        {cmd: "AT+CMEE=1", desc: "错误码数字"},
        {cmd: "AT+CMEE=2", desc: "错误码详细"},
        {cmd: "AT+GCAP", desc: "能力查询"},
        {cmd: "AT+CCLK?", desc: "查询时钟"},
        {cmd: "AT+CCLK=", desc: "设置时钟"},
        {cmd: "AT+CTZR", desc: "时区上报"},
        {cmd: "AT+CTZU", desc: "时区更新"},
        {cmd: "AT+ASRRESET", desc: "模块重启"},
        {cmd: "AT+ASRRESTORE", desc: "恢复出厂"},
        {cmd: "AT+ASRRTC?", desc: "RTC查询"},
        {cmd: "AT+ASRRTC=", desc: "RTC设置"},
        {cmd: "AT+ASRCLK?", desc: "时钟查询"},
        {cmd: "AT+GMI", desc: "厂商ID"},
        {cmd: "AT+GMM", desc: "型号ID"},
        {cmd: "AT+GMR", desc: "版本ID"},
        {cmd: "AT+GSN", desc: "序列号"},
        {cmd: "AT+ASRVER?", desc: "ASR版本"},
        {cmd: "AT+ASRMODEL?", desc: "ASR型号"},
        {cmd: "AT+ASRIMEI?", desc: "IMEI查询"},
        {cmd: "AT+ASRSN?", desc: "SN查询"},
        {cmd: "AT+ASRTEMP?", desc: "温度查询"},
        {cmd: "AT+ASRVBAT?", desc: "电压查询"},
        {cmd: "AT+ASRVEREX?", desc: "扩展版本"},
        {cmd: "ATI", desc: "设备信息"},
        {cmd: "AT+CGMI", desc: "厂商信息"},
        {cmd: "AT+CGMM", desc: "模块型号"},
        {cmd: "AT+CGMR", desc: "软件版本"},
        {cmd: "AT+CNETLIGHT", desc: "网络灯控制"},
        {cmd: "AT+COPN", desc: "运营商列表"},
        {cmd: "AT+COPS=", desc: "选择运营商"},
        {cmd: "AT+CSPN", desc: "SPN查询"},
        {cmd: "AT+ASRRSSI?", desc: "RSSI查询"},
        {cmd: "AT+ASRCELL?", desc: "小区信息"},
        {cmd: "AT+ASRBAND=", desc: "频段设置"},
        {cmd: "AT+ASRSCAN", desc: "网络扫描"},
        {cmd: "AT+ASRMODE?", desc: "网络模式"},
        {cmd: "AT+ASRMODE=", desc: "模式设置"},
        {cmd: "AT+ASRDNS?", desc: "DNS查询"},
        {cmd: "AT+ASRDNS=", desc: "DNS设置"},
        {cmd: "AT+ASRPING", desc: "Ping测试"},
        {cmd: "AT+CSQ", desc: "信号质量"},
        {cmd: "AT+CREG?", desc: "网络注册"},
        {cmd: "AT+CCID", desc: "ICCID查询"},
        {cmd: "AT+CIMI", desc: "IMSI查询"},
        {cmd: "AT+CRSM", desc: "SIM访问"},
        {cmd: "AT+CSIM", desc: "通用SIM访问"},
        {cmd: "AT+CPINR", desc: "PIN重试次数"},
        {cmd: "AT+CPINC", desc: "PUK重试次数"},
        {cmd: "AT+CPIN=", desc: "输入PIN码"},
        {cmd: "AT+ASRSIM?", desc: "SIM状态"},
        {cmd: "AT+ASRICCID?", desc: "ICCID查询"},
        {cmd: "AT+ASRIMSI?", desc: "IMSI查询"},
        {cmd: "AT+CPIN?", desc: "SIM卡状态"},
        {cmd: "AT*SIMINFO?", desc: "SIM信息"},
        {cmd: "AT+SWSIM=0", desc: "切换卡1"},
        {cmd: "AT+SWSIM=1", desc: "切换卡2"},
        {cmd: "AT+CMGD", desc: "删除短信"},
        {cmd: "AT+CSCS", desc: "字符集"},
        {cmd: "AT+CSCA", desc: "短信中心"},
        {cmd: "AT+CSMP", desc: "短信参数"},
        {cmd: "AT+CSDH", desc: "短信头"},
        {cmd: "AT+CNMA", desc: "短信确认"},
        {cmd: "AT+CMSS", desc: "从存储发送"},
        {cmd: "AT+CMGW", desc: "写入短信"},
        {cmd: "AT+CMGC", desc: "发送命令"},
        {cmd: "AT+CMGF=1", desc: "短信模式"},
        {cmd: "AT+CMGS", desc: "发送短信"},
        {cmd: "AT+CMGR", desc: "读取短信"},
        {cmd: "AT+CMGL", desc: "列出短信"},
        {cmd: "AT+CNMI", desc: "短信通知"},
        {cmd: "AT+CLIP", desc: "来电显示"},
        {cmd: "AT+CLIR", desc: "主叫隐藏"},
        {cmd: "AT+COLP", desc: "被叫显示"},
        {cmd: "AT+CCFC", desc: "呼叫转移"},
        {cmd: "AT+CHUP", desc: "挂断通话"},
        {cmd: "AT+VTS", desc: "DTMF发送"},
        {cmd: "AT+CCWA", desc: "呼叫等待"},
    ],
    unisoc: [
        {cmd: "AT&F", desc: "恢复出厂配置"},
        {cmd: "AT&V", desc: "查看配置"},
        {cmd: "AT+CMEE=0", desc: "错误码关闭"},
        {cmd: "AT+CMEE=1", desc: "错误码数字"},
        {cmd: "AT+CMEE=2", desc: "错误码详细"},
        {cmd: "AT+GCAP", desc: "能力查询"},
        {cmd: "AT+CCLK?", desc: "查询时钟"},
        {cmd: "AT+CCLK=", desc: "设置时钟"},
        {cmd: "AT+CTZR", desc: "时区上报"},
        {cmd: "AT+CTZU", desc: "时区更新"},
        {cmd: "AT+SPRESET", desc: "模块重启"},
        {cmd: "AT+SPRESTORE", desc: "恢复出厂"},
        {cmd: "AT+SPRTC?", desc: "RTC查询"},
        {cmd: "AT+SPRTC=", desc: "RTC设置"},
        {cmd: "AT+SPCLK?", desc: "时钟查询"},
        {cmd: "AT+GMI", desc: "厂商ID"},
        {cmd: "AT+GMM", desc: "型号ID"},
        {cmd: "AT+GMR", desc: "版本ID"},
        {cmd: "AT+GSN", desc: "序列号"},
        {cmd: "AT+SPVER?", desc: "展锐版本"},
        {cmd: "AT+SPMODEL?", desc: "展锐型号"},
        {cmd: "AT+SPSN?", desc: "SN查询"},
        {cmd: "AT+SPVBAT?", desc: "电压查询"},
        {cmd: "ATI", desc: "设备信息"},
        {cmd: "AT+CGMI", desc: "厂商信息"},
        {cmd: "AT+CGMM", desc: "模块型号"},
        {cmd: "AT+CGMR", desc: "软件版本"},
        {cmd: "AT+CGSN", desc: "IMEI号"},
        {cmd: "AT+V", desc: "版本信息"},
        {cmd: "AT+SPTEMP?", desc: "温度查询"},
        {cmd: "AT+CNETLIGHT", desc: "网络灯控制"},
        {cmd: "AT+COPN", desc: "运营商列表"},
        {cmd: "AT+COPS=", desc: "选择运营商"},
        {cmd: "AT+CSPN", desc: "SPN查询"},
        {cmd: "AT+SPRSSI?", desc: "RSSI查询"},
        {cmd: "AT+SPBAND=", desc: "频段设置"},
        {cmd: "AT+SPSCAN", desc: "网络扫描"},
        {cmd: "AT+SPMODE?", desc: "网络模式"},
        {cmd: "AT+SPMODE=", desc: "模式设置"},
        {cmd: "AT+SPDNS?", desc: "DNS查询"},
        {cmd: "AT+SPDNS=", desc: "DNS设置"},
        {cmd: "AT+SPPING", desc: "Ping测试"},
        {cmd: "AT+CSQ", desc: "信号质量"},
        {cmd: "AT+CREG?", desc: "网络注册"},
        {cmd: "AT+CGREG?", desc: "GPRS注册"},
        {cmd: "AT+CCID", desc: "ICCID查询"},
        {cmd: "AT+CIMI", desc: "IMSI查询"},
        {cmd: "AT+CRSM", desc: "SIM访问"},
        {cmd: "AT+CSIM", desc: "通用SIM访问"},
        {cmd: "AT+CPINR", desc: "PIN重试次数"},
        {cmd: "AT+CPINC", desc: "PUK重试次数"},
        {cmd: "AT+CPIN=", desc: "输入PIN码"},
        {cmd: "AT+SPSIM?", desc: "SIM状态"},
        {cmd: "AT+SPICCID?", desc: "ICCID查询"},
        {cmd: "AT+SPIMSI?", desc: "IMSI查询"},
        {cmd: "AT+CPIN?", desc: "SIM卡状态"},
        {cmd: "AT+SPUSIMCFG?", desc: "SIM配置"},
        {cmd: "AT+SPSIMSWAP", desc: "SIM切换"},
        {cmd: "AT+CMGD", desc: "删除短信"},
        {cmd: "AT+CSCS", desc: "字符集"},
        {cmd: "AT+CSCA", desc: "短信中心"},
        {cmd: "AT+CSMP", desc: "短信参数"},
        {cmd: "AT+CSDH", desc: "短信头"},
        {cmd: "AT+CNMA", desc: "短信确认"},
        {cmd: "AT+CMSS", desc: "从存储发送"},
        {cmd: "AT+CMGW", desc: "写入短信"},
        {cmd: "AT+CMGC", desc: "发送命令"},
        {cmd: "AT+CMGF=1", desc: "短信模式"},
        {cmd: "AT+CMGS", desc: "发送短信"},
        {cmd: "AT+CMGR", desc: "读取短信"},
        {cmd: "AT+CMGL", desc: "列出短信"},
        {cmd: "AT+CNMI", desc: "短信通知"},
        {cmd: "AT+CLIP", desc: "来电显示"},
        {cmd: "AT+CLIR", desc: "主叫隐藏"},
        {cmd: "AT+COLP", desc: "被叫显示"},
    ],
    esp32: [
        {cmd: "AT+SYSPWROFF", desc: "关机"},
        {cmd: "AT+SYSMSG", desc: "消息设置"},
        {cmd: "AT+CIPSNTPCFG", desc: "SNTP配置"},
        {cmd: "AT+CIPSNTPTIME?", desc: "SNTP时间"},
        {cmd: "AT", desc: "基础测试"},
        {cmd: "AT+RESTORE", desc: "恢复出厂"},
        {cmd: "AT+RST", desc: "重启"},
        {cmd: "AT+SLEEP", desc: "休眠模式"},
        {cmd: "AT+CIPETHMAC?", desc: "以太网MAC"},
        {cmd: "ATI", desc: "版本信息"},
        {cmd: "AT+GMR", desc: "固件信息"},
        {cmd: "AT+CIPSTAMAC?", desc: "MAC地址"},
        {cmd: "AT+SYSRAM?", desc: "内存查询"},
        {cmd: "AT+SYSADC?", desc: "ADC读取"},
        {cmd: "AT+SYSGPIO?", desc: "GPIO查询"},
        {cmd: "AT+SYSPWM", desc: "PWM输出"},
        {cmd: "AT+CIPDNS?", desc: "DNS查询"},
        {cmd: "AT+CIPDNS=", desc: "DNS设置"},
        {cmd: "AT+CIPETH", desc: "以太网设置"},
        {cmd: "AT+PING", desc: "Ping测试"},
        {cmd: "AT+MDNS", desc: "mDNS服务"},
        {cmd: "AT+MQTTUSERCFG", desc: "MQTT配置"},
        {cmd: "AT+MQTTCONN", desc: "MQTT连接"},
        {cmd: "AT+MQTTCLEAN", desc: "MQTT断开"},
        {cmd: "AT+MQTTSUB", desc: "MQTT订阅"},
        {cmd: "AT+MQTTPUB", desc: "MQTT发布"},
        {cmd: "AT+CIPSTATUS", desc: "连接状态"},
        {cmd: "AT+CIPSTART", desc: "建立连接"},
        {cmd: "AT+CIPSEND", desc: "发送数据"},
        {cmd: "AT+CIPCLOSE", desc: "关闭连接"},
        {cmd: "AT+CIPMUX?", desc: "多连接"},
        {cmd: "AT+HTTPCLIENT", desc: "HTTP客户端"},
        {cmd: "AT+HTTPGET", desc: "HTTP GET"},
        {cmd: "AT+HTTPPOST", desc: "HTTP POST"},
        {cmd: "AT+HTTPHEADER", desc: "HTTP头"},
        {cmd: "AT+HTTPSIZE", desc: "HTTP大小"},
        {cmd: "AT+FTPCLIENT", desc: "FTP客户端"},
        {cmd: "AT+FTPPUT", desc: "FTP上传"},
        {cmd: "AT+FTPGET", desc: "FTP下载"},
        {cmd: "AT+CIPMUX", desc: "多连接设置"},
        {cmd: "AT+CIPMODE", desc: "传输模式"},
        {cmd: "AT+CIPSTO", desc: "服务器超时"},
        {cmd: "AT+CIPDINFO", desc: "接收信息"},
        {cmd: "AT+CIPSSLCCONF", desc: "SSL配置"},
        {cmd: "AT+CIPSTATE?", desc: "连接详情"},
        {cmd: "AT+CIPRECVMODE", desc: "接收模式"},
        {cmd: "AT+CIPRECVLEN", desc: "接收长度"},
        {cmd: "AT+CIPRECVDATA", desc: "接收数据"},
        {cmd: "AT+CWJAP", desc: "WiFi连接"},
        {cmd: "AT+CWSAP", desc: "设置AP"},
        {cmd: "AT+CWSTAMAC?", desc: "STA MAC"},
        {cmd: "AT+CWAPMAC?", desc: "AP MAC"},
        {cmd: "AT+WPS", desc: "WPS连接"},
        {cmd: "AT+SMARTCONFIG", desc: "SmartConfig"},
        {cmd: "AT+CWHOSTNAME", desc: "主机名设置"},
        {cmd: "AT+CWAUTOCONN", desc: "自动连接"},
        {cmd: "AT+CWDHCP", desc: "DHCP设置"},
        {cmd: "AT+CIPSTA", desc: "STA IP设置"},
        {cmd: "AT+CIPAP", desc: "AP IP设置"},
        {cmd: "AT+CWMODE?", desc: "WiFi模式"},
        {cmd: "AT+CWMODE=", desc: "设置WiFi模式"},
        {cmd: "AT+CWJAP?", desc: "连接信息"},
        {cmd: "AT+CWJAP=", desc: "连接WiFi"},
        {cmd: "AT+SYSGPIOWRITE", desc: "GPIO写入"},
        {cmd: "AT+SYSGPIOREAD", desc: "GPIO读取"},
    ],
    stm32: [
        {cmd: "AT+RESET", desc: "模块重启"},
        {cmd: "AT+RTC?", desc: "RTC查询"},
        {cmd: "AT+RTC=", desc: "RTC设置"},
        {cmd: "AT+RTCALARM", desc: "RTC闹钟"},
        {cmd: "AT+CLK?", desc: "时钟查询"},
        {cmd: "AT+CLKSET", desc: "时钟设置"},
        {cmd: "AT+PLL?", desc: "PLL查询"},
        {cmd: "AT", desc: "基础测试"},
        {cmd: "AT+RST", desc: "重启"},
        {cmd: "AT+SAVE", desc: "保存配置"},
        {cmd: "AT+RESTORE", desc: "恢复出厂"},
        {cmd: "AT+GMR", desc: "版本查询"},
        {cmd: "AT+DEVID?", desc: "设备ID"},
        {cmd: "AT+UID?", desc: "唯一ID"},
        {cmd: "AT+VBAT?", desc: "电压查询"},
        {cmd: "AT+VREF?", desc: "参考电压"},
        {cmd: "AT+ETHMAC?", desc: "以太网MAC"},
        {cmd: "ATI", desc: "设备信息"},
        {cmd: "AT+TEMP?", desc: "温度查询"},
        {cmd: "AT+SYSCLK?", desc: "时钟查询"},
        {cmd: "AT+ETH?", desc: "以太网状态"},
        {cmd: "AT+ETHIP?", desc: "以太网IP"},
        {cmd: "AT+TIMERSET", desc: "定时器设置"},
        {cmd: "AT+TIMERSTART", desc: "定时器启动"},
        {cmd: "AT+TIMERSTOP", desc: "定时器停止"},
        {cmd: "AT+INT?", desc: "中断查询"},
        {cmd: "AT+INTEN", desc: "中断使能"},
        {cmd: "AT+INTDIS", desc: "中断禁用"},
        {cmd: "AT+WDG?", desc: "看门狗查询"},
        {cmd: "AT+WDGSET", desc: "看门狗设置"},
        {cmd: "AT+WDGFEED", desc: "看门狗喂狗"},
        {cmd: "AT+POWER?", desc: "电源查询"},
        {cmd: "AT+PWRMODE", desc: "电源模式"},
        {cmd: "AT+SLEEP", desc: "睡眠模式"},
        {cmd: "AT+WAKE", desc: "唤醒"},
        {cmd: "AT+DBG=1", desc: "调试开启"},
        {cmd: "AT+LOG?", desc: "日志查询"},
        {cmd: "AT+GPIOREAD", desc: "GPIO读取"},
        {cmd: "AT+GPIOWRITE", desc: "GPIO写入"},
        {cmd: "AT+KEY?", desc: "按键查询"},
        {cmd: "AT+GPIO?", desc: "GPIO查询"},
        {cmd: "AT+GPIO=", desc: "GPIO设置"},
        {cmd: "AT+GPIOMODE", desc: "GPIO模式"},
        {cmd: "AT+LED=", desc: "LED控制"},
        {cmd: "AT+BTN?", desc: "按键查询"},
        {cmd: "AT+ADC=", desc: "ADC读取"},
        {cmd: "AT+ADCCAL", desc: "ADC校准"},
        {cmd: "AT+ADCVREF", desc: "ADC参考"},
        {cmd: "AT+DAC", desc: "DAC输出"},
        {cmd: "AT+ADC?", desc: "ADC读取"},
        {cmd: "AT+ADCMODE", desc: "ADC模式"},
        {cmd: "AT+PWM", desc: "PWM设置"},
        {cmd: "AT+PWMFREQ", desc: "PWM频率"},
        {cmd: "AT+PWMDUTY", desc: "PWM占空比"},
        {cmd: "AT+PWM?", desc: "PWM查询"},
    ],
    qualcomm: [
        {cmd: "AT&F", desc: "恢复出厂配置"},
        {cmd: "AT&V", desc: "查看配置"},
        {cmd: "AT+CMEE=0", desc: "错误码关闭"},
        {cmd: "AT+CMEE=1", desc: "错误码数字"},
        {cmd: "AT+CMEE=2", desc: "错误码详细"},
        {cmd: "AT+GCAP", desc: "能力查询"},
        {cmd: "AT+CCLK?", desc: "查询时钟"},
        {cmd: "AT+CCLK=", desc: "设置时钟"},
        {cmd: "AT+CTZR", desc: "时区上报"},
        {cmd: "AT+CTZU", desc: "时区更新"},
        {cmd: "AT+QCRESET", desc: "模块重启"},
        {cmd: "AT+QCRESTORE", desc: "恢复出厂"},
        {cmd: "AT+QCRTC?", desc: "RTC查询"},
        {cmd: "AT+QCRTC=", desc: "RTC设置"},
        {cmd: "AT", desc: "基础测试"},
        {cmd: "AT+GMI", desc: "厂商ID"},
        {cmd: "AT+GMM", desc: "型号ID"},
        {cmd: "AT+GMR", desc: "版本ID"},
        {cmd: "AT+GSN", desc: "序列号"},
        {cmd: "AT+QCVER?", desc: "高通版本"},
        {cmd: "AT+QCMODEL?", desc: "高通型号"},
        {cmd: "AT$QCCHIP?", desc: "芯片查询"},
        {cmd: "AT+QCIMEI?", desc: "IMEI查询"},
        {cmd: "AT+QCSN?", desc: "SN查询"},
        {cmd: "AT+QCTEMP?", desc: "温度查询"},
        {cmd: "AT+QCVBAT?", desc: "电压查询"},
        {cmd: "ATI", desc: "设备信息"},
        {cmd: "AT+CGMI", desc: "厂商信息"},
        {cmd: "AT+CGMM", desc: "模块型号"},
        {cmd: "AT+CGMR", desc: "软件版本"},
        {cmd: "AT+CNETLIGHT", desc: "网络灯控制"},
        {cmd: "AT+COPN", desc: "运营商列表"},
        {cmd: "AT+COPS=", desc: "选择运营商"},
        {cmd: "AT+CSPN", desc: "SPN查询"},
        {cmd: "AT$QCPWR?", desc: "功率查询"},
        {cmd: "AT+QCRSSI?", desc: "RSSI查询"},
        {cmd: "AT+QCCELL?", desc: "小区信息"},
        {cmd: "AT+QCBAND?", desc: "频段查询"},
        {cmd: "AT+QCBAND=", desc: "频段设置"},
        {cmd: "AT+QCSCAN", desc: "网络扫描"},
        {cmd: "AT+QCMODE?", desc: "网络模式"},
        {cmd: "AT+QCMODE=", desc: "模式设置"},
        {cmd: "AT+QCDNS?", desc: "DNS查询"},
        {cmd: "AT+QCDNS=", desc: "DNS设置"},
        {cmd: "AT+QCPING", desc: "Ping测试"},
        {cmd: "AT+CCID", desc: "ICCID查询"},
        {cmd: "AT+CIMI", desc: "IMSI查询"},
        {cmd: "AT+CRSM", desc: "SIM访问"},
        {cmd: "AT+CSIM", desc: "通用SIM访问"},
        {cmd: "AT+CPINR", desc: "PIN重试次数"},
        {cmd: "AT+CPINC", desc: "PUK重试次数"},
        {cmd: "AT+CPIN=", desc: "输入PIN码"},
        {cmd: "AT$QCPIN?", desc: "PIN状态"},
        {cmd: "AT$QCSIM?", desc: "SIM信息"},
        {cmd: "AT+QCSIM?", desc: "SIM状态"},
        {cmd: "AT+QCICCID?", desc: "ICCID查询"},
        {cmd: "AT+QCIMSI?", desc: "IMSI查询"},
        {cmd: "AT+CPIN?", desc: "SIM卡状态"},
        {cmd: "AT+QSIMDET?", desc: "SIM检测"},
        {cmd: "AT+QSIMHOT?", desc: "热插拔"},
        {cmd: "AT+CMGD", desc: "删除短信"},
        {cmd: "AT+CSCS", desc: "字符集"},
        {cmd: "AT+CSCA", desc: "短信中心"},
        {cmd: "AT+CSMP", desc: "短信参数"},
        {cmd: "AT+CSDH", desc: "短信头"},
        {cmd: "AT+CNMA", desc: "短信确认"},
        {cmd: "AT+CMSS", desc: "从存储发送"},
        {cmd: "AT+CMGW", desc: "写入短信"},
        {cmd: "AT+CMGC", desc: "发送命令"},
        {cmd: "AT+CMGF=1", desc: "短信模式"},
        {cmd: "AT+CMGS", desc: "发送短信"},
        {cmd: "AT+CMGR", desc: "读取短信"},
        {cmd: "AT+CMGL", desc: "列出短信"},
        {cmd: "AT+CNMI", desc: "短信通知"},
        {cmd: "AT+CLIP", desc: "来电显示"},
    ],
    eigencomm: [
        {cmd: "AT&F", desc: "恢复出厂配置"},
        {cmd: "AT&V", desc: "查看配置"},
        {cmd: "AT+CMEE=0", desc: "错误码关闭"},
        {cmd: "AT+CMEE=1", desc: "错误码数字"},
        {cmd: "AT+CMEE=2", desc: "错误码详细"},
        {cmd: "AT+GCAP", desc: "能力查询"},
        {cmd: "AT+CCLK?", desc: "查询时钟"},
        {cmd: "AT+CCLK=", desc: "设置时钟"},
        {cmd: "AT+CTZR", desc: "时区上报"},
        {cmd: "AT+CTZU", desc: "时区更新"},
        {cmd: "AT+ECRESET", desc: "模块重启"},
        {cmd: "AT+ECRESTORE", desc: "恢复出厂"},
        {cmd: "AT+ECRTC?", desc: "RTC查询"},
        {cmd: "AT+ECRTC=", desc: "RTC设置"},
        {cmd: "AT", desc: "基础测试"},
        {cmd: "AT+GMI", desc: "厂商ID"},
        {cmd: "AT+GMM", desc: "型号ID"},
        {cmd: "AT+GMR", desc: "版本ID"},
        {cmd: "AT+GSN", desc: "序列号"},
        {cmd: "AT+ECVER?", desc: "奕讯版本"},
        {cmd: "AT+ECMODEL?", desc: "奕讯型号"},
        {cmd: "AT+ECSN?", desc: "SN查询"},
        {cmd: "AT+ECVBAT?", desc: "电压查询"},
        {cmd: "ATI", desc: "设备信息"},
        {cmd: "AT+CGMI", desc: "厂商信息"},
        {cmd: "AT+CGMM", desc: "模块型号"},
        {cmd: "AT+CGMR", desc: "软件版本"},
        {cmd: "AT+CGSN", desc: "IMEI号"},
        {cmd: "AT+V", desc: "版本信息"},
        {cmd: "AT+ECDEVINFO?", desc: "设备信息"},
        {cmd: "AT+CNETLIGHT", desc: "网络灯控制"},
        {cmd: "AT+COPN", desc: "运营商列表"},
        {cmd: "AT+COPS=", desc: "选择运营商"},
        {cmd: "AT+CSPN", desc: "SPN查询"},
        {cmd: "AT+ECRSSI?", desc: "RSSI查询"},
        {cmd: "AT+ECSCAN", desc: "网络扫描"},
        {cmd: "AT+ECMODE?", desc: "网络模式"},
        {cmd: "AT+ECMODE=", desc: "模式设置"},
        {cmd: "AT+ECDNS?", desc: "DNS查询"},
        {cmd: "AT+ECDNS=", desc: "DNS设置"},
        {cmd: "AT+ECPING", desc: "Ping测试"},
        {cmd: "AT+CSQ", desc: "信号质量"},
        {cmd: "AT+CREG?", desc: "网络注册"},
        {cmd: "AT+CGREG?", desc: "GPRS注册"},
        {cmd: "AT+CEREG?", desc: "LTE注册"},
        {cmd: "AT+CCID", desc: "ICCID查询"},
        {cmd: "AT+CIMI", desc: "IMSI查询"},
        {cmd: "AT+CRSM", desc: "SIM访问"},
        {cmd: "AT+CSIM", desc: "通用SIM访问"},
        {cmd: "AT+CPINR", desc: "PIN重试次数"},
        {cmd: "AT+CPINC", desc: "PUK重试次数"},
        {cmd: "AT+CPIN=", desc: "输入PIN码"},
        {cmd: "AT+ECSIM?", desc: "SIM状态"},
        {cmd: "AT+ECICCID?", desc: "ICCID查询"},
        {cmd: "AT+ECIMSI?", desc: "IMSI查询"},
        {cmd: "AT+CPIN?", desc: "SIM卡状态"},
        {cmd: "AT+ECSIMINFO?", desc: "SIM信息"},
        {cmd: "AT+CMGD", desc: "删除短信"},
        {cmd: "AT+CSCS", desc: "字符集"},
        {cmd: "AT+CSCA", desc: "短信中心"},
        {cmd: "AT+CSMP", desc: "短信参数"},
        {cmd: "AT+CSDH", desc: "短信头"},
        {cmd: "AT+CNMA", desc: "短信确认"},
        {cmd: "AT+CMSS", desc: "从存储发送"},
        {cmd: "AT+CMGW", desc: "写入短信"},
        {cmd: "AT+CMGC", desc: "发送命令"},
        {cmd: "AT+CMGF=1", desc: "短信模式"},
        {cmd: "AT+CMGS", desc: "发送短信"},
        {cmd: "AT+CMGR", desc: "读取短信"},
        {cmd: "AT+CMGL", desc: "列出短信"},
    ],
    mediatek: [
        {cmd: "AT&F", desc: "恢复出厂配置"},
        {cmd: "AT&V", desc: "查看配置"},
        {cmd: "AT+CMEE=0", desc: "错误码关闭"},
        {cmd: "AT+CMEE=1", desc: "错误码数字"},
        {cmd: "AT+CMEE=2", desc: "错误码详细"},
        {cmd: "AT+GCAP", desc: "能力查询"},
        {cmd: "AT+CCLK?", desc: "查询时钟"},
        {cmd: "AT+CCLK=", desc: "设置时钟"},
        {cmd: "AT+CTZR", desc: "时区上报"},
        {cmd: "AT+CTZU", desc: "时区更新"},
        {cmd: "AT+MTRESET", desc: "模块重启"},
        {cmd: "AT+MTRESTORE", desc: "恢复出厂"},
        {cmd: "AT+MTRTC?", desc: "RTC查询"},
        {cmd: "AT+MTRTC=", desc: "RTC设置"},
        {cmd: "AT", desc: "基础测试"},
        {cmd: "AT+GMI", desc: "厂商ID"},
        {cmd: "AT+GMM", desc: "型号ID"},
        {cmd: "AT+GMR", desc: "版本ID"},
        {cmd: "AT+GSN", desc: "序列号"},
        {cmd: "AT+MTVER?", desc: "联发科版本"},
        {cmd: "AT+MTMODEL?", desc: "联发科型号"},
        {cmd: "AT+MTSN?", desc: "SN查询"},
        {cmd: "AT+MTVBAT?", desc: "电压查询"},
        {cmd: "ATI", desc: "设备信息"},
        {cmd: "AT+CGMI", desc: "厂商信息"},
        {cmd: "AT+CGMM", desc: "模块型号"},
        {cmd: "AT+CGMR", desc: "软件版本"},
        {cmd: "AT+CGSN", desc: "IMEI号"},
        {cmd: "AT+V", desc: "版本信息"},
        {cmd: "AT+MTDEVINFO?", desc: "设备信息"},
        {cmd: "AT+CNETLIGHT", desc: "网络灯控制"},
        {cmd: "AT+COPN", desc: "运营商列表"},
        {cmd: "AT+COPS=", desc: "选择运营商"},
        {cmd: "AT+CSPN", desc: "SPN查询"},
        {cmd: "AT+MTRSSI?", desc: "RSSI查询"},
        {cmd: "AT+MTSCAN", desc: "网络扫描"},
        {cmd: "AT+MTMODE?", desc: "网络模式"},
        {cmd: "AT+MTMODE=", desc: "模式设置"},
        {cmd: "AT+MTDNS?", desc: "DNS查询"},
        {cmd: "AT+MTDNS=", desc: "DNS设置"},
        {cmd: "AT+MTPING", desc: "Ping测试"},
        {cmd: "AT+CSQ", desc: "信号质量"},
        {cmd: "AT+CREG?", desc: "网络注册"},
        {cmd: "AT+CGREG?", desc: "GPRS注册"},
        {cmd: "AT+CEREG?", desc: "LTE注册"},
        {cmd: "AT+CCID", desc: "ICCID查询"},
        {cmd: "AT+CIMI", desc: "IMSI查询"},
        {cmd: "AT+CRSM", desc: "SIM访问"},
        {cmd: "AT+CSIM", desc: "通用SIM访问"},
        {cmd: "AT+CPINR", desc: "PIN重试次数"},
        {cmd: "AT+CPINC", desc: "PUK重试次数"},
        {cmd: "AT+CPIN=", desc: "输入PIN码"},
        {cmd: "AT+MTSIM?", desc: "SIM状态"},
        {cmd: "AT+MTICCID?", desc: "ICCID查询"},
        {cmd: "AT+MTIMSI?", desc: "IMSI查询"},
        {cmd: "AT+CPIN?", desc: "SIM卡状态"},
        {cmd: "AT+MTSIMINFO?", desc: "SIM信息"},
        {cmd: "AT+CMGD", desc: "删除短信"},
        {cmd: "AT+CSCS", desc: "字符集"},
        {cmd: "AT+CSCA", desc: "短信中心"},
        {cmd: "AT+CSMP", desc: "短信参数"},
        {cmd: "AT+CSDH", desc: "短信头"},
        {cmd: "AT+CNMA", desc: "短信确认"},
        {cmd: "AT+CMSS", desc: "从存储发送"},
        {cmd: "AT+CMGW", desc: "写入短信"},
        {cmd: "AT+CMGC", desc: "发送命令"},
        {cmd: "AT+CMGF=1", desc: "短信模式"},
        {cmd: "AT+CMGS", desc: "发送短信"},
        {cmd: "AT+CMGR", desc: "读取短信"},
        {cmd: "AT+CMGL", desc: "列出短信"},
    ],
    hisilicon: [
        {cmd: "AT&F", desc: "恢复出厂配置"},
        {cmd: "AT&V", desc: "查看配置"},
        {cmd: "AT+CMEE=0", desc: "错误码关闭"},
        {cmd: "AT+CMEE=1", desc: "错误码数字"},
        {cmd: "AT+CMEE=2", desc: "错误码详细"},
        {cmd: "AT+GCAP", desc: "能力查询"},
        {cmd: "AT+CCLK?", desc: "查询时钟"},
        {cmd: "AT+CCLK=", desc: "设置时钟"},
        {cmd: "AT+CTZR", desc: "时区上报"},
        {cmd: "AT+CTZU", desc: "时区更新"},
        {cmd: "AT+HIRESET", desc: "模块重启"},
        {cmd: "AT+HIRESTORE", desc: "恢复出厂"},
        {cmd: "AT+HIRTC?", desc: "RTC查询"},
        {cmd: "AT+HIRTC=", desc: "RTC设置"},
        {cmd: "AT", desc: "基础测试"},
        {cmd: "AT+GMI", desc: "厂商ID"},
        {cmd: "AT+GMM", desc: "型号ID"},
        {cmd: "AT+GMR", desc: "版本ID"},
        {cmd: "AT+GSN", desc: "序列号"},
        {cmd: "AT+HIVER?", desc: "海思版本"},
        {cmd: "AT+HIMODEL?", desc: "海思型号"},
        {cmd: "AT+HIIMEI?", desc: "IMEI查询"},
        {cmd: "AT+HISN?", desc: "SN查询"},
        {cmd: "AT+HITEMP?", desc: "温度查询"},
        {cmd: "AT+HIVBAT?", desc: "电压查询"},
        {cmd: "ATI", desc: "设备信息"},
        {cmd: "AT+CGMI", desc: "厂商信息"},
        {cmd: "AT+CGMM", desc: "模块型号"},
        {cmd: "AT+CGMR", desc: "软件版本"},
        {cmd: "AT+CGSN", desc: "IMEI号"},
        {cmd: "AT+CNETLIGHT", desc: "网络灯控制"},
        {cmd: "AT+COPN", desc: "运营商列表"},
        {cmd: "AT+COPS=", desc: "选择运营商"},
        {cmd: "AT+CSPN", desc: "SPN查询"},
        {cmd: "AT+HIRSSI?", desc: "RSSI查询"},
        {cmd: "AT+HICELL?", desc: "小区信息"},
        {cmd: "AT+HIBAND?", desc: "频段查询"},
        {cmd: "AT+HIBAND=", desc: "频段设置"},
        {cmd: "AT+HISCAN", desc: "网络扫描"},
        {cmd: "AT+HIMODE?", desc: "网络模式"},
        {cmd: "AT+HIMODE=", desc: "模式设置"},
        {cmd: "AT+HIDNS?", desc: "DNS查询"},
        {cmd: "AT+HIDNS=", desc: "DNS设置"},
        {cmd: "AT+HIPING", desc: "Ping测试"},
        {cmd: "AT+CSQ", desc: "信号质量"},
        {cmd: "AT+CCID", desc: "ICCID查询"},
        {cmd: "AT+CIMI", desc: "IMSI查询"},
        {cmd: "AT+CRSM", desc: "SIM访问"},
        {cmd: "AT+CSIM", desc: "通用SIM访问"},
        {cmd: "AT+CPINR", desc: "PIN重试次数"},
        {cmd: "AT+CPINC", desc: "PUK重试次数"},
        {cmd: "AT+CPIN=", desc: "输入PIN码"},
        {cmd: "AT+HISIM?", desc: "SIM状态"},
        {cmd: "AT+HIICCID?", desc: "ICCID查询"},
        {cmd: "AT+HIIMSI?", desc: "IMSI查询"},
        {cmd: "AT+CPIN?", desc: "SIM卡状态"},
        {cmd: "AT+HSIMINFO?", desc: "SIM信息"},
        {cmd: "AT+CMGD", desc: "删除短信"},
        {cmd: "AT+CSCS", desc: "字符集"},
        {cmd: "AT+CSCA", desc: "短信中心"},
        {cmd: "AT+CSMP", desc: "短信参数"},
        {cmd: "AT+CSDH", desc: "短信头"},
        {cmd: "AT+CNMA", desc: "短信确认"},
        {cmd: "AT+CMSS", desc: "从存储发送"},
        {cmd: "AT+CMGW", desc: "写入短信"},
        {cmd: "AT+CMGC", desc: "发送命令"},
        {cmd: "AT+CMGF=1", desc: "短信模式"},
        {cmd: "AT+CMGS", desc: "发送短信"},
        {cmd: "AT+CMGR", desc: "读取短信"},
        {cmd: "AT+CMGL", desc: "列出短信"},
    ],
    xinyi: [
        {cmd: "AT&F", desc: "恢复出厂配置"},
        {cmd: "AT&V", desc: "查看配置"},
        {cmd: "AT+CMEE=0", desc: "错误码关闭"},
        {cmd: "AT+CMEE=1", desc: "错误码数字"},
        {cmd: "AT+CMEE=2", desc: "错误码详细"},
        {cmd: "AT+GCAP", desc: "能力查询"},
        {cmd: "AT+CCLK?", desc: "查询时钟"},
        {cmd: "AT+CCLK=", desc: "设置时钟"},
        {cmd: "AT+CTZR", desc: "时区上报"},
        {cmd: "AT+CTZU", desc: "时区更新"},
        {cmd: "AT+XYRESET", desc: "模块重启"},
        {cmd: "AT+XYRESTORE", desc: "恢复出厂"},
        {cmd: "AT+XYRTC?", desc: "RTC查询"},
        {cmd: "AT+XYRTC=", desc: "RTC设置"},
        {cmd: "AT", desc: "基础测试"},
        {cmd: "AT+GMI", desc: "厂商ID"},
        {cmd: "AT+GMM", desc: "型号ID"},
        {cmd: "AT+GMR", desc: "版本ID"},
        {cmd: "AT+GSN", desc: "序列号"},
        {cmd: "AT+XYVER?", desc: "芯翼版本"},
        {cmd: "AT+XYMODEL?", desc: "芯翼型号"},
        {cmd: "AT+XYSN?", desc: "SN查询"},
        {cmd: "AT+XYVBAT?", desc: "电压查询"},
        {cmd: "ATI", desc: "设备信息"},
        {cmd: "AT+CGMI", desc: "厂商信息"},
        {cmd: "AT+CGMM", desc: "模块型号"},
        {cmd: "AT+CGMR", desc: "软件版本"},
        {cmd: "AT+CGSN", desc: "IMEI号"},
        {cmd: "AT+V", desc: "版本信息"},
        {cmd: "AT+XYDEVINFO?", desc: "设备信息"},
        {cmd: "AT+CNETLIGHT", desc: "网络灯控制"},
        {cmd: "AT+COPN", desc: "运营商列表"},
        {cmd: "AT+COPS=", desc: "选择运营商"},
        {cmd: "AT+CSPN", desc: "SPN查询"},
        {cmd: "AT+XYRSSI?", desc: "RSSI查询"},
        {cmd: "AT+XYSCAN", desc: "网络扫描"},
        {cmd: "AT+XYMODE?", desc: "网络模式"},
        {cmd: "AT+XYMODE=", desc: "模式设置"},
        {cmd: "AT+XYDNS?", desc: "DNS查询"},
        {cmd: "AT+XYDNS=", desc: "DNS设置"},
        {cmd: "AT+XYPING", desc: "Ping测试"},
        {cmd: "AT+CSQ", desc: "信号质量"},
        {cmd: "AT+CREG?", desc: "网络注册"},
        {cmd: "AT+CGREG?", desc: "GPRS注册"},
        {cmd: "AT+CEREG?", desc: "LTE注册"},
        {cmd: "AT+CCID", desc: "ICCID查询"},
        {cmd: "AT+CIMI", desc: "IMSI查询"},
        {cmd: "AT+CRSM", desc: "SIM访问"},
        {cmd: "AT+CSIM", desc: "通用SIM访问"},
        {cmd: "AT+CPINR", desc: "PIN重试次数"},
        {cmd: "AT+CPINC", desc: "PUK重试次数"},
        {cmd: "AT+CPIN=", desc: "输入PIN码"},
        {cmd: "AT+XYSIM?", desc: "SIM状态"},
        {cmd: "AT+XYICCID?", desc: "ICCID查询"},
        {cmd: "AT+XYIMSI?", desc: "IMSI查询"},
        {cmd: "AT+CPIN?", desc: "SIM卡状态"},
        {cmd: "AT+XYSIMINFO?", desc: "SIM信息"},
        {cmd: "AT+CMGD", desc: "删除短信"},
        {cmd: "AT+CSCS", desc: "字符集"},
        {cmd: "AT+CSCA", desc: "短信中心"},
        {cmd: "AT+CSMP", desc: "短信参数"},
        {cmd: "AT+CSDH", desc: "短信头"},
        {cmd: "AT+CNMA", desc: "短信确认"},
        {cmd: "AT+CMSS", desc: "从存储发送"},
        {cmd: "AT+CMGW", desc: "写入短信"},
        {cmd: "AT+CMGC", desc: "发送命令"},
        {cmd: "AT+CMGF=1", desc: "短信模式"},
        {cmd: "AT+CMGS", desc: "发送短信"},
        {cmd: "AT+CMGR", desc: "读取短信"},
        {cmd: "AT+CMGL", desc: "列出短信"},
    ],
};

// 根据芯片类型生成AT指令按钮
function renderATCommands() {
    const container = document.getElementById('atCommands');
    const commands = AT_COMMANDS[DEVICE_TYPE] || AT_COMMANDS.zte;
    container.innerHTML = commands.map(item =>
        `<button class="btn btn-sm" onclick="sendAT('${item.cmd}')" title="${item.cmd}">${item.cmd} (${item.desc})</button>`
    ).join('');
}

// 连接串口
async function connectSerial() {
    try {
        if (!('serial' in navigator)) {
            await Modal.alert('您的浏览器不支持Web Serial API，请使用Chrome 89+或其他chromium内核浏览器', 'warning');
            return;
        }

        serialPort = await navigator.serial.requestPort();
        
        const options = {
            baudRate: parseInt(document.getElementById('baudRate').value),
            dataBits: parseInt(document.getElementById('dataBits').value),
            stopBits: parseInt(document.getElementById('stopBits').value),
            parity: document.getElementById('parity').value
        };

        await serialPort.open(options);
        
        writer = serialPort.writable.getWriter();
        reader = serialPort.readable.getReader();
        
        isConnected = true;
        updateConnectionStatus(true);
        
        addLog('系统', '串口连接成功', 'success', '连接成功');
        
        // 记录日志
        fetch('/api/stats.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'connect',
                device_type: <?php echo json_encode($device_type); ?>,
                status: 1
            })
        });
        
        // 开始读取数据
        readData();
        
    } catch (error) {
        console.error('连接失败:', error);
        addLog('系统', '连接失败: ' + error.message, 'error', '连接失败');
        
        // 记录日志
        fetch('/api/stats.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'connect',
                device_type: <?php echo json_encode($device_type); ?>,
                status: 0,
                error: error.message
            })
        });
    }
}

// 断开串口
async function disconnectSerial() {
    try {
        if (reader) {
            await reader.cancel();
            reader = null;
        }
        
        if (writer) {
            await writer.releaseLock();
            writer = null;
        }
        
        if (serialPort) {
            await serialPort.close();
            serialPort = null;
        }
        
        isConnected = false;
        updateConnectionStatus(false);
        addLog('系统', '串口已断开', 'info', '断开连接');
        
    } catch (error) {
        console.error('断开失败:', error);
        addLog('系统', '断开失败: ' + error.message, 'error', '断开失败');
    }
}

// 发送AT指令
async function sendAT(command) {
    if (!isConnected) {
        await Modal.alert('请先连接串口', 'warning');
        return;
    }
    
    try {
        const data = command + '\r\n';
        await writer.write(new TextEncoder().encode(data));
        addLog('发送', command, 'send', getATDesc(command));
        
    } catch (error) {
        console.error('发送失败:', error);
        addLog('发送', '失败: ' + error.message, 'error', '发送失败');
    }
}

// 发送自定义AT指令
function sendCustomAT() {
    const input = document.getElementById('customAT');
    const command = input.value.trim();
    if (command) {
        sendAT(command);
        input.value = '';
    }
}

// 发送数据
async function sendData() {
    if (!isConnected) {
        await Modal.alert('请先连接串口', 'warning');
        return;
    }
    
    const input = document.getElementById('dataInput');
    const data = input.value;
    if (!data) return;
    
    try {
        await writer.write(new TextEncoder().encode(data + '\r\n'));
        addLog('发送', data, 'send', getATDesc(data));
        input.value = '';
        
    } catch (error) {
        console.error('发送失败:', error);
        addLog('发送', '失败: ' + error.message, 'error', '发送失败');
    }
}

// 读取数据
async function readData() {
    try {
        while (serialPort && serialPort.readable) {
            const { value, done } = await reader.read();
            if (done) break;
            
            const text = new TextDecoder().decode(value);
            addLog('接收', text.trim(), 'receive', getResponseLabel(text));
            handleResponseData(text);
        }
    } catch (error) {
        if (isConnected) {
            console.error('读取错误:', error);
            addLog('系统', '读取错误: ' + error.message, 'error', '读取错误');
        }
    }
}

// 更新连接状态
function updateConnectionStatus(connected) {
    const status = document.getElementById('connectionStatus');
    const dot = status.querySelector('.status-dot');
    const text = status.querySelector('.status-text');
    const btnConnect = document.getElementById('btnConnect');
    const btnDisconnect = document.getElementById('btnDisconnect');
    
    if (connected) {
        dot.className = 'status-dot connected';
        text.textContent = '已连接';
        btnConnect.disabled = true;
        btnDisconnect.disabled = false;
    } else {
        dot.className = 'status-dot disconnected';
        text.textContent = '未连接';
        btnConnect.disabled = false;
        btnDisconnect.disabled = true;
    }
}

function getATDesc(command) {
    const cmd = command.trim().replace(/\r?\n$/, '');
    const commands = AT_COMMANDS[DEVICE_TYPE] || AT_COMMANDS.zte;
    const found = commands.find(item => item.cmd === cmd);
    if (found) return found.desc;
    const cmdBase = cmd.replace(/=.+$/, '').replace(/\?.*$/, '?');
    const foundBase = commands.find(item => {
        const base = item.cmd.replace(/=.+$/, '').replace(/\?.*$/, '?');
        return base === cmdBase;
    });
    return foundBase ? foundBase.desc : '';
}

const RESPONSE_LABELS = [
    { pattern: /^OK$/i, label: '执行成功' },
    { pattern: /^ERROR$/i, label: '执行失败' },
    { pattern: /^\+CME ERROR:\s*(\d+)/i, label: '模块错误' },
    { pattern: /^\+CMS ERROR:\s*(\d+)/i, label: '短信错误' },
    { pattern: /^\+CREG:/i, label: '网络注册状态' },
    { pattern: /^\+CGREG:/i, label: 'GPRS注册状态' },
    { pattern: /^\+CEREG:/i, label: 'LTE注册状态' },
    { pattern: /^\+CSQ:/i, label: '信号质量' },
    { pattern: /^\+CPIN:/i, label: 'SIM卡状态' },
    { pattern: /^\+COPS:/i, label: '运营商信息' },
    { pattern: /^\+CGMI:/i, label: '厂商信息' },
    { pattern: /^\+CGMM:/i, label: '模块型号' },
    { pattern: /^\+CGMR:/i, label: '软件版本' },
    { pattern: /^\+CGSN:/i, label: 'IMEI号' },
    { pattern: /^\+CGDCONT:/i, label: 'PDP上下文' },
    { pattern: /^\+ZSNT:/i, label: '网络模式' },
    { pattern: /^\+ZGSR:/i, label: '服务报告' },
    { pattern: /^\+ZSIM:/i, label: 'SIM卡信息' },
    { pattern: /^\+ZCDRUN:/i, label: '开发模式' },
    { pattern: /^\+ZLC:/i, label: '小区锁定' },
    { pattern: /^ZLTEAMTBAND:/i, label: '支持频段' },
    { pattern: /^\+ZLTEBAND:/i, label: '锁定频段' },
    { pattern: /^\+ASRSTK:/i, label: 'STK信息' },
    { pattern: /^\+ASRNET:/i, label: '网络信息' },
    { pattern: /^\+ASRCSQ:/i, label: '详细信号' },
    { pattern: /^\+ASRBAND:/i, label: '频段信息' },
    { pattern: /^\+ASRLOCK:/i, label: '频段锁定' },
    { pattern: /^\+ASRNV:/i, label: 'NV信息' },
    { pattern: /^\+SPNWNAME:/i, label: '网络名称' },
    { pattern: /^\+SPUSIMCFG:/i, label: 'SIM配置' },
    { pattern: /^\+SPNVMREAD:/i, label: 'NV读取' },
    { pattern: /^\+SPBAND:/i, label: '频段信息' },
    { pattern: /^\+SPNETMODE:/i, label: '网络模式' },
    { pattern: /^\+SPAPNCFG:/i, label: 'APN配置' },
    { pattern: /^RING$/i, label: '来电振铃' },
    { pattern: /^NO CARRIER$/i, label: '无载波' },
    { pattern: /^BUSY$/i, label: '线路忙' },
    { pattern: /^NO DIALTONE$/i, label: '无拨号音' },
    { pattern: /^NO ANSWER$/i, label: '无应答' },
    { pattern: /^CONNECT/i, label: '已建立连接' },
];

function getResponseLabel(text) {
    const line = text.trim().split('\n')[0].trim();
    for (const item of RESPONSE_LABELS) {
        if (item.pattern.test(line)) return item.label;
    }
    return '';
}

function addLog(type, message, level, desc) {
    const log = document.getElementById('dataLog');
    const time = new Date().toLocaleTimeString();
    const entry = document.createElement('div');
    entry.className = `log-entry log-${level}`;
    const descHtml = desc ? `<span class="log-desc">[${desc}]</span> ` : '';
    entry.innerHTML = `<span class="log-time">[${time}]</span> <span class="log-type">[${type}]</span> ${descHtml}${message}`;
    log.insertBefore(entry, log.firstChild);
    log.scrollTop = 0;
}

// 清空数据
function clearData() {
    document.getElementById('dataLog').innerHTML = '';
}

// 字体大小控制
function changeFontSize(delta) {
    currentFontSize = Math.max(9, Math.min(24, currentFontSize + delta));
    const dataLog = document.getElementById('dataLog');
    dataLog.style.fontSize = currentFontSize + 'px';
    document.getElementById('fontSizeLabel').textContent = currentFontSize + 'px';
}

// 页面加载完成
document.addEventListener('DOMContentLoaded', function() {
    renderATCommands();
    addLog('系统', '页面已加载,请连接串口', 'info', '页面就绪');
});

const FIELD_CMDS = {
    zte: {
        imei:  { read: 'AT+CGSN',    write: (v) => 'AT+MODIMEI=' + v },
        mac:   { read: 'AT+MAC?',     write: (v) => 'AT+MAC=' + v },
        mac2:  { read: 'AT+MAC2?',    write: (v) => 'AT+MAC2=' + v },
    },
    asr: {
        imei:  { read: 'AT+CGSN',                  write: (v) => 'AT*MRD_IMEI=W,0,01JAN1970,' + v },
        sn:    { read: 'AT*MRD_SN?',                write: (v) => 'AT*MRD_SN=W,0,01JAN1970,' + v },
        mac:   { read: 'AT*MRD_WIFIID?',            write: (v) => 'AT*MRD_WIFIID=W,0,01JAN1970,' + v },
    }
};

function readField(field) {
    const cmds = FIELD_CMDS[DEVICE_TYPE];
    if (cmds && cmds[field]) {
        sendAT(cmds[field].read);
    }
}

function writeField(field) {
    const cmds = FIELD_CMDS[DEVICE_TYPE];
    if (!cmds || !cmds[field]) return;
    const inputId = 'field' + field.toUpperCase();
    const val = document.getElementById(inputId)?.value?.trim();
    if (!val) {
        Modal.alert('请先输入要写入的值', 'warning');
        return;
    }
    sendAT(cmds[field].write(val));
}

function toggleADB(mode, enable) {
    const ip = document.getElementById('adbIp')?.value?.trim() || '192.168.0.1';
    const debugEnable = enable ? 1 : 0;
    if (mode === 1) {
        window.open(`http://${ip}/goform/goform_set_cmd_process?goformId=SET_DEVICE_MODE&debug_enable=${debugEnable}`);
    } else {
        window.open(`http://${ip}/reqproc/proc_post?goformId=SET_DEVICE_MODE&debug_enable=${debugEnable}`);
    }
}

function rebootDevice(mode) {
    const ip = document.getElementById('adbIp')?.value?.trim() || '192.168.0.1';
    if (mode === 1) {
        window.open(`http://${ip}/goform/goform_set_cmd_process?goformId=REBOOT_DEVICE`);
    } else {
        window.open(`http://${ip}/reqproc/proc_post?isTest=false&goformId=REBOOT_DEVICE`);
    }
}

function writeCellLock() {
    const isLock = document.getElementById('lteCellLock')?.checked ? 1 : 0;
    const arfcn = document.getElementById('lteCellArfcn')?.value?.trim() || '0';
    const pci = document.getElementById('lteCellPci')?.value?.trim() || '0';
    sendAT(`AT+ZLC=${isLock},${arfcn},${pci}`);
}

function readBand() {
    if (DEVICE_TYPE === 'zte') {
        sendAT('AT+ZLTEAMTBAND?');
        setTimeout(() => sendAT('AT+ZLTEBAND?'), 300);
    } else if (DEVICE_TYPE === 'asr') {
        sendAT('AT*BAND?');
    }
}

function lockBand() {
    const checked = [...document.querySelectorAll('.band-cb:checked')].map(cb => parseInt(cb.value));
    if (!checked.length) {
        Modal.alert('请至少选择一个频段', 'warning');
        return;
    }
    if (DEVICE_TYPE === 'zte') {
        const arr = [];
        for (let i = 0; i < 8; i++) {
            let str = '';
            for (let j = 0; j < 8; j++) {
                str = (checked.includes(i * 8 + j + 1) ? '1' : '0') + str;
            }
            arr.push(parseInt(str, 2));
        }
        sendAT('AT+ZLTEBAND=' + arr.join(','));
    } else if (DEVICE_TYPE === 'asr') {
        let bandH = 0, bandL = 0;
        checked.forEach(item => {
            if (item <= 20) bandL += Math.pow(2, item - 1);
            if (item >= 38 && item <= 41) bandH += Math.pow(2, item - 33);
        });
        const bs = window._asrBandStr;
        if (bs) {
            const bandArr = bs.split(',');
            bandArr[3] = bandH;
            bandArr[4] = bandL;
            sendAT('AT*BAND=' + bandArr.join(','));
        } else {
            sendAT('AT*BAND=0,0,0,' + bandH + ',' + bandL);
        }
    }
}

window._zteSupportedBands = [];
window._asrBandStr = '';

function handleResponseData(text) {
    const t = text.trim();
    if (DEVICE_TYPE === 'zte') {
        if (t.includes('ZLTEAMTBAND:') && t.length > 20) {
            const raw = t.split('ZLTEAMTBAND:')[1].split('\r\n')[0];
            window._zteSupportedBands = parseZteBands(raw);
        }
        if (t.includes('+ZLTEBAND:') && t.length > 20) {
            const raw = t.split('+ZLTEBAND:')[1].split('\r\n')[0];
            const locked = parseZteBands(raw).filter(b => window._zteSupportedBands.includes(b));
            setBandCheckboxes(window._zteSupportedBands.length ? window._zteSupportedBands : [1,3,5,8,38,39,40,41], locked);
        }
        if (t.includes('+CGSN:')) {
            const v = t.split('+CGSN:')[1].split('\r\n')[0].trim();
            const el = document.getElementById('fieldIMEI'); if (el) el.value = v;
        }
        if (t.includes('+MAC:')) {
            const v = t.split('+MAC:')[1].split('\r\n')[0].trim();
            const el = document.getElementById('fieldMAC'); if (el) el.value = v;
        }
        if (t.includes('+MAC2:')) {
            const v = t.split('+MAC2:')[1].split('\r\n')[0].trim();
            const el = document.getElementById('fieldMAC2'); if (el) el.value = v;
        }
        if (t.includes('+ZLC:')) {
            const arr = t.split('+ZLC:')[1].split('\r\n')[0].trim().split(',');
            const lockEl = document.getElementById('lteCellLock');
            const arfcnEl = document.getElementById('lteCellArfcn');
            const pciEl = document.getElementById('lteCellPci');
            if (lockEl) lockEl.checked = arr[0] === '1';
            if (arfcnEl) arfcnEl.value = arr[1] || '';
            if (pciEl) pciEl.value = arr[2] || '';
        }
    } else if (DEVICE_TYPE === 'asr') {
        if (t.includes('*BAND:') && t.length > 20) {
            const raw = t.split('*BAND:')[1].split('\r\n')[0];
            window._asrBandStr = raw;
            const arr = raw.split(',');
            const bandH = parseInt(arr[3]).toString(2).split('').reverse();
            const bandL = parseInt(arr[4]).toString(2).split('').reverse();
            const checked = [];
            bandH.forEach((bit, idx) => {
                if (bit === '1') { if(idx===5) checked.push(38); if(idx===6) checked.push(39); if(idx===7) checked.push(40); if(idx===8) checked.push(41); }
            });
            bandL.forEach((bit, idx) => {
                if (bit === '1' && [1,3,5,8].includes(idx+1)) checked.push(idx+1);
            });
            setBandCheckboxes([1,3,5,8,38,39,40,41], checked);
        }
        if (t.includes('+CGSN:')) {
            const v = t.split('+CGSN:')[1].split('\r\n')[0].trim();
            const el = document.getElementById('fieldIMEI'); if (el) el.value = v;
        }
        if (t.includes('AT+CGSN') && !t.includes('+CGSN:')) {
            const v = t.split('AT+CGSN\r\r\n')[1]?.split('\r\n')[0]?.trim();
            if (v) { const el = document.getElementById('fieldIMEI'); if (el) el.value = v; }
        }
        if (t.includes('*MRD_SN:')) {
            const v = t.split('*MRD_SN:')[1].split('\r\n')[0].trim();
            const el = document.getElementById('fieldSN'); if (el) el.value = v;
        }
        if (t.includes('*MRD_WIFIID:')) {
            const v = t.split('*MRD_WIFIID:')[1].split('\r\n')[0].trim().replaceAll(':','');
            const el = document.getElementById('fieldMAC'); if (el) el.value = v;
        }
    }
}

function parseZteBands(str) {
    return str.split(',').reduce((p, c, i) => {
        const bits = parseInt(c).toString(2);
        for (let j = 0; j < bits.length; j++) {
            if (parseInt(bits[bits.length - j - 1])) p.push(i * 8 + j + 1);
        }
        return p;
    }, []);
}

function setBandCheckboxes(allBands, lockedBands) {
    const container = document.getElementById('bandCheckboxes');
    if (!container) return;
    container.innerHTML = allBands.map(b =>
        `<label class="band-label"><input type="checkbox" class="band-cb" value="${b}" ${lockedBands.includes(b)?'checked':''}> LTE B${b}</label>`
    ).join('');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
