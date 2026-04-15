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
            <div class="at-commands">
                <button class="btn btn-sm" onclick="sendAT('AT')">AT</button>
                <button class="btn btn-sm" onclick="sendAT('ATI')">ATI (设备信息)</button>
                <button class="btn btn-sm" onclick="sendAT('AT+CSQ')">AT+CSQ (信号质量)</button>
                <button class="btn btn-sm" onclick="sendAT('AT+CPIN?')">AT+CPIN? (SIM卡状态)</button>
                <button class="btn btn-sm" onclick="sendAT('AT+CREG?')">AT+CREG? (网络注册)</button>
                <button class="btn btn-sm" onclick="sendAT('AT+COPS?')">AT+COPS? (运营商)</button>
            </div>
            <div class="custom-at">
                <input type="text" id="customAT" placeholder="输入自定义AT指令..." onkeypress="if(event.key==='Enter')sendCustomAT()">
                <button class="btn btn-primary" onclick="sendCustomAT()">发送</button>
            </div>
        </div>

        <!-- 数据收发区 -->
        <div class="data-panel">
            <h3>数据收发</h3>
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

// 页面加载完成
document.addEventListener('DOMContentLoaded', function() {
    addLog('系统', '页面已加载,请连接串口', 'info');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
