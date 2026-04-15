/**
 * Node.js WebSocket 串口服务
 * 提供浏览器与串口设备之间的通信桥梁
 */

const WebSocket = require('ws');
const { SerialPort } = require('serialport');
const { ReadlineParser } = require('@serialport/parser-readline');
const fs = require('fs');
const path = require('path');

// 配置
const CONFIG = {
    port: 8080,                    // WebSocket服务端口
    allowedOrigins: ['http://localhost', 'http://127.0.0.1'],  // 允许的来源
    logFile: path.join(__dirname, 'logs', 'serial.log'),  // 日志文件路径
    maxConnections: 10,            // 最大连接数
    heartbeatInterval: 30000       // 心跳间隔(30秒)
};

// 确保日志目录存在
const logDir = path.join(__dirname, 'logs');
if (!fs.existsSync(logDir)) {
    fs.mkdirSync(logDir, { recursive: true });
}

// 日志函数
function log(message, level = 'INFO') {
    const timestamp = new Date().toISOString();
    const logMessage = `[${timestamp}] [${level}] ${message}\n`;
    
    // 输出到控制台
    console.log(logMessage.trim());
    
    // 写入日志文件
    fs.appendFileSync(CONFIG.logFile, logMessage);
}

// 存储活跃的串口连接
const activePorts = new Map();

// 创建WebSocket服务器
const wss = new WebSocket.Server({ 
    port: CONFIG.port,
    perMessageDeflate: false
});

log(`WebSocket服务器启动在端口 ${CONFIG.port}`);

// 处理新连接
wss.on('connection', (ws, req) => {
    const clientId = Date.now().toString(36) + Math.random().toString(36).substr(2);
    log(`客户端连接: ${clientId}`);
    
    // 验证连接数
    if (wss.clients.size > CONFIG.maxConnections) {
        ws.close(1013, '连接数已达上限');
        log(`连接被拒绝: ${clientId} (超过最大连接数)`, 'WARN');
        return;
    }
    
    ws.clientId = clientId;
    ws.isConnected = false;
    ws.currentPort = null;
    
    // 心跳检测
    const heartbeat = setInterval(() => {
        if (ws.readyState === WebSocket.OPEN) {
            ws.ping();
        } else {
            clearInterval(heartbeat);
        }
    }, CONFIG.heartbeatInterval);
    
    // 处理接收到的消息
    ws.on('message', async (message) => {
        try {
            const data = JSON.parse(message.toString());
            log(`收到消息 [${clientId}]: ${data.action}`);
            
            switch (data.action) {
                case 'list_ports':
                    await handleListPorts(ws, data);
                    break;
                    
                case 'open_port':
                    await handleOpenPort(ws, data, clientId);
                    break;
                    
                case 'send_data':
                    await handleSendData(ws, data, clientId);
                    break;
                    
                case 'close_port':
                    await handleClosePort(ws, clientId);
                    break;
                    
                case 'get_config':
                    await handleGetConfig(ws, data);
                    break;
                    
                default:
                    sendResponse(ws, 'error', { message: '未知的操作' });
            }
        } catch (error) {
            log(`消息处理错误: ${error.message}`, 'ERROR');
            sendResponse(ws, 'error', { message: error.message });
        }
    });
    
    // 处理连接关闭
    ws.on('close', () => {
        log(`客户端断开: ${clientId}`);
        clearInterval(heartbeat);
        
        // 关闭该客户端的串口连接
        if (activePorts.has(clientId)) {
            closePort(clientId);
        }
    });
    
    // 处理错误
    ws.on('error', (error) => {
        log(`WebSocket错误 [${clientId}]: ${error.message}`, 'ERROR');
    });
    
    // 发送欢迎消息
    sendResponse(ws, 'connected', { 
        clientId,
        message: '连接成功',
        serverVersion: '1.0.0'
    });
});

// 列出可用串口
async function handleListPorts(ws, data) {
    try {
        const ports = await SerialPort.list();
        sendResponse(ws, 'port_list', { ports });
    } catch (error) {
        sendResponse(ws, 'error', { message: `获取串口列表失败: ${error.message}` });
    }
}

// 打开串口
async function handleOpenPort(ws, data, clientId) {
    try {
        const { path: portPath, baudRate = 115200, dataBits = 8, stopBits = 1, parity = 'none' } = data;
        
        if (!portPath) {
            sendResponse(ws, 'error', { message: '缺少串口路径' });
            return;
        }
        
        // 检查是否已经打开
        if (activePorts.has(clientId)) {
            closePort(clientId);
        }
        
        // 创建串口实例
        const port = new SerialPort({
            path: portPath,
            baudRate: parseInt(baudRate),
            dataBits: parseInt(dataBits),
            stopBits: parseInt(stopBits),
            parity: parity
        });
        
        // 创建解析器
        const parser = port.pipe(new ReadlineParser({ delimiter: '\r\n' }));
        
        // 监听数据
        parser.on('data', (chunk) => {
            sendResponse(ws, 'data_received', { 
                data: chunk,
                timestamp: Date.now()
            });
        });
        
        // 监听错误
        port.on('error', (error) => {
            log(`串口错误 [${clientId}]: ${error.message}`, 'ERROR');
            sendResponse(ws, 'port_error', { message: error.message });
        });
        
        // 监听关闭
        port.on('close', () => {
            log(`串口已关闭 [${clientId}]`);
            activePorts.delete(clientId);
            sendResponse(ws, 'port_closed', { message: '串口已关闭' });
        });
        
        // 等待串口打开
        await new Promise((resolve, reject) => {
            port.on('open', resolve);
            port.on('error', reject);
        });
        
        // 存储连接信息
        activePorts.set(clientId, { port, parser });
        
        sendResponse(ws, 'port_opened', { 
            path: portPath,
            baudRate,
            message: '串口打开成功'
        });
        
        log(`串口已打开 [${clientId}]: ${portPath} @ ${baudRate}`);
        
    } catch (error) {
        log(`打开串口失败 [${clientId}]: ${error.message}`, 'ERROR');
        sendResponse(ws, 'error', { message: `打开串口失败: ${error.message}` });
    }
}

// 发送数据
async function handleSendData(ws, data, clientId) {
    try {
        const { data: sendData, encoding = 'utf8' } = data;
        
        if (!activePorts.has(clientId)) {
            sendResponse(ws, 'error', { message: '串口未打开' });
            return;
        }
        
        const { port } = activePorts.get(clientId);
        
        // 发送数据
        const buffer = Buffer.from(sendData + '\r\n', encoding);
        port.write(buffer);
        
        sendResponse(ws, 'data_sent', { 
            data: sendData,
            bytes: buffer.length,
            timestamp: Date.now()
        });
        
        log(`数据已发送 [${clientId}]: ${sendData.substring(0, 50)}...`);
        
    } catch (error) {
        log(`发送数据失败 [${clientId}]: ${error.message}`, 'ERROR');
        sendResponse(ws, 'error', { message: `发送数据失败: ${error.message}` });
    }
}

// 关闭串口
async function handleClosePort(ws, clientId) {
    if (activePorts.has(clientId)) {
        closePort(clientId);
        sendResponse(ws, 'port_closed', { message: '串口已关闭' });
    } else {
        sendResponse(ws, 'error', { message: '串口未打开' });
    }
}

// 获取配置
async function handleGetConfig(ws, data) {
    sendResponse(ws, 'config', { 
        version: '1.0.0',
        maxConnections: CONFIG.maxConnections,
        supportedBaudRates: [9600, 19200, 38400, 57600, 115200, 230400, 460800, 921600]
    });
}

// 关闭串口
function closePort(clientId) {
    try {
        const connection = activePorts.get(clientId);
        if (connection) {
            if (connection.port.isOpen) {
                connection.port.close();
            }
            activePorts.delete(clientId);
            log(`串口连接已清理: ${clientId}`);
        }
    } catch (error) {
        log(`关闭串口错误 [${clientId}]: ${error.message}`, 'ERROR');
    }
}

// 发送响应
function sendResponse(ws, action, data) {
    if (ws.readyState === WebSocket.OPEN) {
        const response = JSON.stringify({
            action,
            data,
            timestamp: Date.now()
        });
        ws.send(response);
    }
}

// 优雅关闭
process.on('SIGTERM', () => {
    log('收到SIGTERM信号,准备关闭服务...');
    shutdown();
});

process.on('SIGINT', () => {
    log('收到SIGINT信号,准备关闭服务...');
    shutdown();
});

function shutdown() {
    // 关闭所有串口连接
    for (const [clientId, connection] of activePorts) {
        try {
            if (connection.port.isOpen) {
                connection.port.close();
            }
        } catch (error) {
            log(`关闭串口失败 [${clientId}]: ${error.message}`, 'ERROR');
        }
    }
    activePorts.clear();
    
    // 关闭WebSocket服务器
    wss.close(() => {
        log('WebSocket服务器已关闭');
        process.exit(0);
    });
}

log('Node.js串口服务已启动,等待连接...');
