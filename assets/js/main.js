/**
 * 硬件调试工具站 - 主JavaScript
 */

// 全局配置
const APP_CONFIG = {
    apiBase: '/api',
    version: '1.0.0'
};

// 页面加载完成
document.addEventListener('DOMContentLoaded', function() {
    console.log('硬件调试工具站已加载 v' + APP_CONFIG.version);
    
    // 检查Web Serial API支持
    checkSerialSupport();
});

// 检查串口API支持
function checkSerialSupport() {
    if (!('serial' in navigator)) {
        console.warn('当前浏览器不支持Web Serial API');
        // 可以在页面上显示提示
        const warning = document.createElement('div');
        warning.className = 'browser-warning';
        warning.innerHTML = `
            <div class="warning-content">
                <strong>⚠ 浏览器不支持</strong>
                <p>您的浏览器不支持Web Serial API,请使用Chrome 89+或其他chromium内核浏览器</p>
            </div>
        `;
        warning.style.cssText = `
            background: #fff7e6;
            border: 1px solid #ffd666;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #d48806;
        `;
        
        const main = document.querySelector('.site-main .container');
        if (main && !document.querySelector('.browser-warning')) {
            main.insertBefore(warning, main.firstChild);
        }
    }
}

// 工具函数
const Utils = {
    // 格式化时间
    formatTime(date) {
        return new Date(date).toLocaleString('zh-CN');
    },
    
    // 格式化数字
    formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    },
    
    // 显示提示消息
    showMessage(message, type = 'info') {
        const colors = {
            success: '#52c41a',
            error: '#ff4d4f',
            warning: '#faad14',
            info: '#1890ff'
        };
        
        const msg = document.createElement('div');
        msg.className = 'toast-message';
        msg.textContent = message;
        msg.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${colors[type]};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(msg);
        
        setTimeout(() => {
            msg.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => msg.remove(), 300);
        }, 3000);
    }
};

// API请求封装
const API = {
    // GET请求
    async get(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;
        
        try {
            const response = await fetch(fullUrl);
            return await response.json();
        } catch (error) {
            console.error('GET请求失败:', error);
            throw error;
        }
    },
    
    // POST请求
    async post(url, data = {}) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('POST请求失败:', error);
            throw error;
        }
    }
};

// 添加CSS动画
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
