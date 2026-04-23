<?php
/**
 * 芯片调试通用页面模板
 */
$device_type = $device_type ?? 'zte';
$device_name = $device_name ?? '中兴微';
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
            </div>
        </div>

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
const DEVICE_TYPE = '<?php echo $device_type; ?>';

// AT指令集定义 - 按芯片类型区分
const AT_COMMANDS = {
    // 中兴微(ZTE)芯片AT指令
    zte: [
        { cmd: 'AT', desc: '基础测试' },
        { cmd: 'ATI', desc: '设备信息' },
        { cmd: 'AT+CGMI', desc: '厂商信息' },
        { cmd: 'AT+CGMM', desc: '模块型号' },
        { cmd: 'AT+CGMR', desc: '软件版本' },
        { cmd: 'AT+CGSN', desc: 'IMEI号' },
        { cmd: 'AT+CSQ', desc: '信号质量' },
        { cmd: 'AT+CPIN?', desc: 'SIM卡状态' },
        { cmd: 'AT+CREG?', desc: '网络注册' },
        { cmd: 'AT+CGREG?', desc: 'GPRS注册' },
        { cmd: 'AT+COPS?', desc: '运营商信息' },
        { cmd: 'AT+CEREG?', desc: 'LTE注册状态' },
        { cmd: 'AT+CGDCONT?', desc: 'PDP上下文' },
        { cmd: 'AT+ZSNT?', desc: '网络模式' },
        { cmd: 'AT+ZGSR?', desc: '服务报告' },
        { cmd: 'AT+ZSIM?', desc: 'SIM卡信息' },
        { cmd: 'AT+ZCDRUN?', desc: '开发模式' },
        { cmd: 'AT+ZCDRUN=0', desc: '开启开发模式' },
        { cmd: 'AT+ZCDRUN=1', desc: '关闭开发模式' },
        { cmd: 'AT+ZRESET', desc: '重启模块' },
    ],
    // ASR芯片AT指令
    asr: [
        { cmd: 'AT', desc: '基础测试' },
        { cmd: 'ATI', desc: '设备信息' },
        { cmd: 'AT+CGMI', desc: '厂商信息' },
        { cmd: 'AT+CGMM', desc: '模块型号' },
        { cmd: 'AT+CGMR', desc: '软件版本' },
        { cmd: 'AT+CGSN', desc: 'IMEI号' },
        { cmd: 'AT+CSQ', desc: '信号质量' },
        { cmd: 'AT+CPIN?', desc: 'SIM卡状态' },
        { cmd: 'AT+CREG?', desc: '网络注册' },
        { cmd: 'AT+CGREG?', desc: 'GPRS注册' },
        { cmd: 'AT+COPS?', desc: '运营商信息' },
        { cmd: 'AT+CEREG?', desc: 'LTE注册状态' },
        { cmd: 'AT+CGDCONT?', desc: 'PDP上下文' },
        { cmd: 'AT+ASRSTK?', desc: 'STK信息' },
        { cmd: 'AT+ASRNET?', desc: '网络信息' },
        { cmd: 'AT+ASRCSQ?', desc: '详细信号' },
        { cmd: 'AT+ASRBAND?', desc: '频段信息' },
        { cmd: 'AT+ASRLOCK?', desc: '频段锁定' },
        { cmd: 'AT+ASRNV?', desc: 'NV信息' },
        { cmd: 'AT+CFUN=1,1', desc: '重启模块' },
    ],
    // 展锐(Unisoc)芯片AT指令
    unisoc: [
        { cmd: 'AT', desc: '基础测试' },
        { cmd: 'ATI', desc: '设备信息' },
        { cmd: 'AT+CGMI', desc: '厂商信息' },
        { cmd: 'AT+CGMM', desc: '模块型号' },
        { cmd: 'AT+CGMR', desc: '软件版本' },
        { cmd: 'AT+CGSN', desc: 'IMEI号' },
        { cmd: 'AT+CSQ', desc: '信号质量' },
        { cmd: 'AT+CPIN?', desc: 'SIM卡状态' },
        { cmd: 'AT+CREG?', desc: '网络注册' },
        { cmd: 'AT+CGREG?', desc: 'GPRS注册' },
        { cmd: 'AT+COPS?', desc: '运营商信息' },
        { cmd: 'AT+CEREG?', desc: 'LTE注册状态' },
        { cmd: 'AT+CGDCONT?', desc: 'PDP上下文' },
        { cmd: 'AT+SPNWNAME?', desc: '网络名称' },
        { cmd: 'AT+SPUSIMCFG?', desc: 'SIM配置' },
        { cmd: 'AT+SPNVMREAD?', desc: 'NV读取' },
        { cmd: 'AT+SPBAND?', desc: '频段信息' },
        { cmd: 'AT+SPNETMODE?', desc: '网络模式' },
        { cmd: 'AT+SPAPNCFG?', desc: 'APN配置' },
        { cmd: 'AT+CFUN=1,1', desc: '重启模块' },
    ]
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
        
        addLog('系统', '串口连接成功', 'success');
        
        // 记录日志
        fetch('/api/stats.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'connect',
                device_type: '<?php echo $device_type; ?>',
                status: 1
            })
        });
        
        // 开始读取数据
        readData();
        
    } catch (error) {
        console.error('连接失败:', error);
        addLog('系统', '连接失败: ' + error.message, 'error');
        
        // 记录日志
        fetch('/api/stats.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'connect',
                device_type: '<?php echo $device_type; ?>',
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
        addLog('系统', '串口已断开', 'info');
        
    } catch (error) {
        console.error('断开失败:', error);
        addLog('系统', '断开失败: ' + error.message, 'error');
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
        addLog('发送', command, 'send');
        
    } catch (error) {
        console.error('发送失败:', error);
        addLog('发送', '失败: ' + error.message, 'error');
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
        addLog('发送', data, 'send');
        input.value = '';
        
    } catch (error) {
        console.error('发送失败:', error);
        addLog('发送', '失败: ' + error.message, 'error');
    }
}

// 读取数据
async function readData() {
    try {
        while (serialPort && serialPort.readable) {
            const { value, done } = await reader.read();
            if (done) break;
            
            const text = new TextDecoder().decode(value);
            addLog('接收', text.trim(), 'receive');
        }
    } catch (error) {
        if (isConnected) {
            console.error('读取错误:', error);
            addLog('系统', '读取错误: ' + error.message, 'error');
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

// 添加日志
function addLog(type, message, level) {
    const log = document.getElementById('dataLog');
    const time = new Date().toLocaleTimeString();
    const entry = document.createElement('div');
    entry.className = `log-entry log-${level}`;
    entry.innerHTML = `<span class="log-time">[${time}]</span> <span class="log-type">[${type}]</span> ${message}`;
    log.appendChild(entry);
    log.scrollTop = log.scrollHeight;
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
    addLog('系统', '页面已加载,请连接串口', 'info');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
