# Node.js 串口服务

这是一个可选的增强服务,提供WebSocket接口来处理复杂的串口通信逻辑。

## 功能特性

- ✅ WebSocket实时通信
- ✅ 串口设备自动发现
- ✅ 多串口并发支持
- ✅ 数据收发和日志记录
- ✅ 心跳保活机制
- ✅ 优雅关闭和资源清理

## 安装

### 1. 安装Node.js

确保已安装 Node.js 14.0 或更高版本:

```bash
node --version
```

### 2. 安装依赖

```bash
cd serial-service
npm install
```

### 3. 启动服务

```bash
# 正常启动
npm start

# 开发模式(自动重启)
npm run dev

# 查看日志
npm run logs
```

服务将在 `ws://localhost:8080` 启动。

## 配置

编辑 `server.js` 中的 `CONFIG` 对象:

```javascript
const CONFIG = {
    port: 8080,                    // WebSocket服务端口
    allowedOrigins: ['http://localhost', 'http://127.0.0.1'],
    logFile: './logs/serial.log',
    maxConnections: 10,
    heartbeatInterval: 30000
};
```

## API 接口

### WebSocket 消息格式

所有消息使用JSON格式:

```json
{
    "action": "操作类型",
    "data": {
        // 操作参数
    }
}
```

### 1. 列出可用串口

**请求:**
```json
{
    "action": "list_ports"
}
```

**响应:**
```json
{
    "action": "port_list",
    "data": {
        "ports": [
            {
                "path": "COM3",
                "manufacturer": "Prolific",
                "serialNumber": null,
                "pnpId": null,
                "locationId": null,
                "productId": "2303",
                "vendorId": "067b"
            }
        ]
    },
    "timestamp": 1234567890
}
```

### 2. 打开串口

**请求:**
```json
{
    "action": "open_port",
    "path": "COM3",
    "baudRate": 115200,
    "dataBits": 8,
    "stopBits": 1,
    "parity": "none"
}
```

**响应:**
```json
{
    "action": "port_opened",
    "data": {
        "path": "COM3",
        "baudRate": 115200,
        "message": "串口打开成功"
    },
    "timestamp": 1234567890
}
```

### 3. 发送数据

**请求:**
```json
{
    "action": "send_data",
    "data": "AT+CGMI\r\n",
    "encoding": "utf8"
}
```

**响应:**
```json
{
    "action": "data_sent",
    "data": {
        "data": "AT+CGMI\r\n",
        "bytes": 11,
        "timestamp": 1234567890
    },
    "timestamp": 1234567890
}
```

### 4. 接收数据

当串口有数据返回时,会自动推送:

```json
{
    "action": "data_received",
    "data": {
        "data": "OK",
        "timestamp": 1234567890
    },
    "timestamp": 1234567890
}
```

### 5. 关闭串口

**请求:**
```json
{
    "action": "close_port"
}
```

**响应:**
```json
{
    "action": "port_closed",
    "data": {
        "message": "串口已关闭"
    },
    "timestamp": 1234567890
}
```

### 6. 获取服务配置

**请求:**
```json
{
    "action": "get_config"
}
```

**响应:**
```json
{
    "action": "config",
    "data": {
        "version": "1.0.0",
        "maxConnections": 10,
        "supportedBaudRates": [9600, 19200, 38400, 57600, 115200, 230400, 460800, 921600]
    },
    "timestamp": 1234567890
}
```

## 前端集成示例

### JavaScript 客户端

```javascript
// 创建WebSocket连接
const ws = new WebSocket('ws://localhost:8080');

// 连接成功
ws.onopen = () => {
    console.log('已连接到串口服务');
    
    // 列出可用串口
    ws.send(JSON.stringify({ action: 'list_ports' }));
};

// 接收消息
ws.onmessage = (event) => {
    const response = JSON.parse(event.data);
    
    switch (response.action) {
        case 'port_list':
            console.log('可用串口:', response.data.ports);
            break;
            
        case 'port_opened':
            console.log('串口已打开:', response.data);
            break;
            
        case 'data_received':
            console.log('收到数据:', response.data.data);
            break;
            
        case 'data_sent':
            console.log('数据已发送');
            break;
            
        case 'port_closed':
            console.log('串口已关闭');
            break;
            
        case 'port_error':
            console.error('串口错误:', response.data.message);
            break;
            
        case 'error':
            console.error('错误:', response.data.message);
            break;
    }
};

// 打开串口
function openSerialPort(portPath) {
    ws.send(JSON.stringify({
        action: 'open_port',
        path: portPath,
        baudRate: 115200,
        dataBits: 8,
        stopBits: 1,
        parity: 'none'
    }));
}

// 发送数据
function sendData(data) {
    ws.send(JSON.stringify({
        action: 'send_data',
        data: data,
        encoding: 'utf8'
    }));
}

// 关闭串口
function closeSerialPort() {
    ws.send(JSON.stringify({
        action: 'close_port'
    }));
}

// 连接断开
ws.onclose = () => {
    console.log('WebSocket连接已断开');
};

// 错误处理
ws.onerror = (error) => {
    console.error('WebSocket错误:', error);
};
```

### 使用示例

```javascript
// 1. 列出串口
ws.send(JSON.stringify({ action: 'list_ports' }));

// 2. 打开COM3串口
openSerialPort('COM3');

// 3. 发送AT指令
sendData('AT+CGMI');

// 4. 等待接收数据(自动触发data_received事件)

// 5. 关闭串口
closeSerialPort();
```

## 日志

日志文件保存在 `logs/serial.log`,包含:

- 连接/断开事件
- 串口打开/关闭
- 数据收发记录
- 错误信息

查看日志:

```bash
# Linux/macOS
tail -f logs/serial.log

# Windows (PowerShell)
Get-Content logs/serial.log -Wait
```

## 错误处理

### 常见问题

**1. 端口被占用**

```
Error: listen EADDRINUSE: address already in use :::8080
```

**解决:** 修改 `CONFIG.port` 为其他端口,或关闭占用端口的程序。

**2. 串口不存在**

```
Error: Opening COM99: File not found
```

**解决:** 使用 `list_ports` 查看可用串口,确认端口号正确。

**3. 串口权限不足**

```
Error: Permission denied, cannot open COM3
```

**解决:** 
- Windows: 以管理员身份运行
- Linux: 将用户添加到 dialout 组
  ```bash
  sudo usermod -a -G dialout $USER
  ```

**4. 波特率不支持**

```
Error: Invalid baudRate
```

**解决:** 使用支持的波特率: 9600, 19200, 38400, 57600, 115200, 230400, 460800, 921600

## 性能优化

### 生产环境建议

1. **使用PM2管理进程**
   ```bash
   npm install -g pm2
   pm2 start server.js --name serial-service
   pm2 save
   pm2 startup
   ```

2. **配置日志轮转**
   使用 logrotate 或 winston 进行日志管理

3. **启用SSL/TLS**
   生产环境建议使用 WSS (WebSocket Secure)

4. **监控和告警**
   - 监控内存使用
   - 监控连接数
   - 设置错误告警

## 系统要求

- **Node.js**: 14.0+
- **操作系统**: Windows / Linux / macOS
- **串口驱动**: 需要安装对应设备的驱动程序

## 许可证

MIT License

## 技术支持

如有问题,请查看:
- 日志文件: `logs/serial.log`
- 项目文档: 上级目录的 `README.md`
