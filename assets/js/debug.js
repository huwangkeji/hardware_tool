/**
 * 调试增强 - 自动捕获错误和日志
 * 在所有页面中引入此文件即可获得完整的调试输出
 */

// 全局错误处理
const DebugConsole = {
    enabled: true,
    logFile: [],
    
    // 初始化
    init() {
        console.log('%c🔧 调试系统已加载', 'color: #1890ff; font-weight: bold; font-size: 14px');
        console.log('%c📍 页面URL:', 'color: #52c41a', window.location.href);
        console.log('%c🔙 来源页面:', 'color: #52c41a', document.referrer || '无');
        console.log('%c⏰ 加载时间:', 'color: #52c41a', new Date().toLocaleString('zh-CN'));
        console.log('%c📦 User Agent:', 'color: #52c41a', navigator.userAgent);
        
        // 检查Web Serial API支持
        if ('serial' in navigator) {
            console.log('%c✅ Web Serial API 可用', 'color: #52c41a');
        } else {
            console.warn('%c⚠️ Web Serial API 不可用', 'color: #faad14');
        }
        
        // 检查WebSocket支持
        if ('WebSocket' in window) {
            console.log('%c✅ WebSocket 可用', 'color: #52c41a');
        } else {
            console.warn('%c⚠️ WebSocket 不可用', 'color: #faad14');
        }
        
        // 开始性能监控
        if (window.performance) {
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const timing = performance.timing;
                    const loadTime = timing.loadEventEnd - timing.navigationStart;
                    const domReady = timing.domContentLoadedEventEnd - timing.navigationStart;
                    
                    console.group('%c⚡ 性能统计', 'color: #722ed1; font-weight: bold');
                    console.log('页面加载完成: %c' + loadTime + 'ms', 'color: #1890ff; font-weight: bold');
                    console.log('DOM就绪: %c' + domReady + 'ms', 'color: #1890ff');
                    console.log('DNS查询: ' + (timing.domainLookupEnd - timing.domainLookupStart) + 'ms');
                    console.log('TCP连接: ' + (timing.connectEnd - timing.connectStart) + 'ms');
                    console.log('服务器响应: ' + (timing.responseStart - timing.requestStart) + 'ms');
                    console.log('DOM解析: ' + (timing.domComplete - timing.domLoading) + 'ms');
                    console.groupEnd();
                }, 0);
            });
        }
        
        // 拦截fetch请求
        this.interceptFetch();
        
        // 监听未捕获的错误
        this.listenErrors();
        
        // 监听未处理的Promise拒绝
        this.listenPromiseRejections();
        
        console.log('%c━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'color: #d9d9d9');
    },
    
    // 日志输出
    log(...args) {
        if (!this.enabled) return;
        console.log('%c📝 LOG', 'background: #1890ff; color: white; padding: 2px 6px; border-radius: 3px', ...args);
    },
    
    // 信息输出
    info(...args) {
        if (!this.enabled) return;
        console.info('%cℹ️ INFO', 'background: #52c41a; color: white; padding: 2px 6px; border-radius: 3px', ...args);
    },
    
    // 警告输出
    warn(...args) {
        if (!this.enabled) return;
        console.warn('%c⚠️ WARN', 'background: #faad14; color: white; padding: 2px 6px; border-radius: 3px', ...args);
    },
    
    // 错误输出
    error(...args) {
        if (!this.enabled) return;
        console.error('%c❌ ERROR', 'background: #ff4d4f; color: white; padding: 2px 6px; border-radius: 3px', ...args);
    },
    
    // 调试输出
    debug(...args) {
        if (!this.enabled) return;
        console.debug('%c🐛 DEBUG', 'background: #722ed1; color: white; padding: 2px 6px; border-radius: 3px', ...args);
    },
    
    // 拦截fetch请求
    interceptFetch() {
        const originalFetch = window.fetch;
        window.fetch = async function(...args) {
            const startTime = performance.now();
            const url = args[0];
            const method = (args[1]?.method || 'GET').toUpperCase();
            
            DebugConsole.log(`📤 ${method} ${url}`);
            
            try {
                const response = await originalFetch.apply(this, args);
                const duration = (performance.now() - startTime).toFixed(2);
                
                if (response.ok) {
                    DebugConsole.info(`✅ ${method} ${url} - ${response.status} (${duration}ms)`);
                } else {
                    DebugConsole.warn(`⚠️ ${method} ${url} - ${response.status} (${duration}ms)`);
                }
                
                return response;
            } catch (error) {
                DebugConsole.error(`❌ ${method} ${url} 失败:`, error);
                throw error;
            }
        };
    },
    
    // 监听JavaScript错误
    listenErrors() {
        window.addEventListener('error', (event) => {
            console.group('%c❌ JavaScript错误', 'color: #ff4d4f; font-weight: bold');
            console.error('消息:', event.message);
            console.error('文件:', event.filename);
            console.error('行号:', event.lineno);
            console.error('列号:', event.colno);
            console.error('错误对象:', event.error);
            console.groupEnd();
        });
    },
    
    // 监听Promise拒绝
    listenPromiseRejections() {
        window.addEventListener('unhandledrejection', (event) => {
            console.group('%c❌ 未处理的Promise拒绝', 'color: #ff4d4f; font-weight: bold');
            console.error('原因:', event.reason);
            console.groupEnd();
        });
    },
    
    // 输出PHP调试信息（如果存在）
    showPHPDebug() {
        // 查找页面上的PHP调试输出
        const phpDebug = document.getElementById('php-debug-output');
        if (phpDebug) {
            console.group('%c🐘 PHP调试信息', 'color: #8892b0; font-weight: bold');
            console.log(phpDebug.textContent);
            console.groupEnd();
        }
    }
};

// 自动初始化
document.addEventListener('DOMContentLoaded', () => {
    DebugConsole.init();
    DebugConsole.showPHPDebug();
});

// 如果DOM已经加载完成
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setTimeout(() => {
        DebugConsole.init();
        DebugConsole.showPHPDebug();
    }, 0);
}
