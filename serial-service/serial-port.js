/**
 * 串口操作封装模块
 * 提供简化的串口通信接口
 */

const { SerialPort } = require('serialport');
const { ReadlineParser } = require('@serialport/parser-readline');

class SerialPortManager {
    constructor() {
        this.port = null;
        this.parser = null;
        this.isConnected = false;
        this.dataCallback = null;
        this.errorCallback = null;
        this.closeCallback = null;
    }

    /**
     * 列出所有可用串口
     */
    static async listPorts() {
        try {
            const ports = await SerialPort.list();
            return {
                success: true,
                ports: ports
            };
        } catch (error) {
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * 打开串口
     */
    async open(options) {
        const {
            path,
            baudRate = 115200,
            dataBits = 8,
            stopBits = 1,
            parity = 'none'
        } = options;

        try {
            // 如果已有连接,先关闭
            if (this.isConnected) {
                await this.close();
            }

            // 创建串口实例
            this.port = new SerialPort({
                path,
                baudRate: parseInt(baudRate),
                dataBits: parseInt(dataBits),
                stopBits: parseInt(stopBits),
                parity: parity
            });

            // 创建解析器
            this.parser = this.port.pipe(new ReadlineParser({ delimiter: '\r\n' }));

            // 设置数据回调
            this.parser.on('data', (data) => {
                if (this.dataCallback) {
                    this.dataCallback(data);
                }
            });

            // 设置错误回调
            this.port.on('error', (error) => {
                console.error(`串口错误: ${error.message}`);
                if (this.errorCallback) {
                    this.errorCallback(error);
                }
                this.isConnected = false;
            });

            // 设置关闭回调
            this.port.on('close', () => {
                console.log('串口已关闭');
                this.isConnected = false;
                if (this.closeCallback) {
                    this.closeCallback();
                }
            });

            // 等待串口打开
            await new Promise((resolve, reject) => {
                this.port.on('open', resolve);
                this.port.on('error', reject);
            });

            this.isConnected = true;
            return {
                success: true,
                message: '串口打开成功'
            };

        } catch (error) {
            this.isConnected = false;
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * 发送数据
     */
    async write(data, encoding = 'utf8') {
        if (!this.isConnected || !this.port) {
            return {
                success: false,
                error: '串口未打开'
            };
        }

        try {
            const buffer = Buffer.from(data + '\r\n', encoding);
            this.port.write(buffer);
            
            return {
                success: true,
                bytes: buffer.length
            };
        } catch (error) {
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * 关闭串口
     */
    async close() {
        if (this.port && this.port.isOpen) {
            try {
                await new Promise((resolve, reject) => {
                    this.port.close((error) => {
                        if (error) reject(error);
                        else resolve();
                    });
                });
                
                this.isConnected = false;
                return {
                    success: true,
                    message: '串口已关闭'
                };
            } catch (error) {
                return {
                    success: false,
                    error: error.message
                };
            }
        }
        
        return {
            success: true,
            message: '串口已经是关闭状态'
        };
    }

    /**
     * 设置数据接收回调
     */
    onData(callback) {
        this.dataCallback = callback;
    }

    /**
     * 设置错误回调
     */
    onError(callback) {
        this.errorCallback = callback;
    }

    /**
     * 设置关闭回调
     */
    onClose(callback) {
        this.closeCallback = callback;
    }

    /**
     * 获取串口状态
     */
    getStatus() {
        return {
            isConnected: this.isConnected,
            isOpen: this.port ? this.port.isOpen : false,
            path: this.port ? this.port.path : null
        };
    }

    /**
     * 销毁实例
     */
    async destroy() {
        await this.close();
        this.dataCallback = null;
        this.errorCallback = null;
        this.closeCallback = null;
    }
}

module.exports = SerialPortManager;
