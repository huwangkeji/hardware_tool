/**
 * 硬件调试工具站 - 自定义对话框组件
 * 替换浏览器原生 alert/confirm/prompt
 */

const Modal = {
    // 当前对话框实例
    currentModal: null,
    
    /**
     * 显示提示对话框（替换 alert）
     * @param {string} message - 提示消息
     * @param {string} type - 类型: info, success, warning, error
     * @param {string} title - 标题（可选）
     */
    alert(message, type = 'info', title = null) {
        return new Promise((resolve) => {
            const titles = {
                'info': '提示',
                'success': '成功',
                'warning': '警告',
                'error': '错误'
            };
            
            const icons = {
                'info': 'ℹ',
                'success': '✓',
                'warning': '⚠',
                'error': '✕'
            };
            
            this.show({
                title: title || titles[type] || '提示',
                message: message,
                icon: icons[type] || icons.info,
                iconType: type,
                buttons: [
                    {
                        text: '确定',
                        type: 'primary',
                        action: () => resolve(true)
                    }
                ]
            });
        });
    },
    
    /**
     * 显示确认对话框（替换 confirm）
     * @param {string} message - 确认消息
     * @param {string} title - 标题（可选）
     * @returns {Promise<boolean>} - 用户选择 true/false
     */
    confirm(message, title = '确认操作') {
        return new Promise((resolve) => {
            this.show({
                title: title,
                message: message,
                icon: '❓',
                iconType: 'warning',
                buttons: [
                    {
                        text: '取消',
                        type: 'secondary',
                        action: () => resolve(false)
                    },
                    {
                        text: '确定',
                        type: 'primary',
                        action: () => resolve(true)
                    }
                ]
            });
        });
    },
    
    /**
     * 显示自定义对话框
     * @param {Object} options - 对话框配置
     */
    show(options) {
        const {
            title,
            message,
            icon,
            iconType = 'info',
            buttons = [],
            closable = true
        } = options;
        
        // 如果已有对话框打开，先关闭
        if (this.currentModal) {
            this.close();
        }
        
        // 创建遮罩层
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        
        // 创建对话框
        const dialog = document.createElement('div');
        dialog.className = 'modal-dialog';
        
        // 对话框HTML
        dialog.innerHTML = `
            <div class="modal-header">
                <div class="modal-icon ${iconType}">${icon}</div>
                <div class="modal-title">${title}</div>
                ${closable ? '<button class="modal-close" onclick="Modal.close()">×</button>' : ''}
            </div>
            <div class="modal-body">${message}</div>
            <div class="modal-footer">
                ${buttons.map((btn, index) => `
                    <button class="modal-btn modal-btn-${btn.type}" data-index="${index}">
                        ${btn.text}
                    </button>
                `).join('')}
            </div>
        `;
        
        overlay.appendChild(dialog);
        document.body.appendChild(overlay);
        
        // 保存当前实例
        this.currentModal = {
            overlay: overlay,
            dialog: dialog,
            options: options
        };
        
        // 触发动画
        requestAnimationFrame(() => {
            overlay.classList.add('active');
        });
        
        // 绑定按钮事件
        buttons.forEach((btn, index) => {
            const btnEl = dialog.querySelector(`[data-index="${index}"]`);
            if (btnEl) {
                btnEl.addEventListener('click', () => {
                    if (btn.action) {
                        btn.action();
                    }
                    this.close();
                });
            }
        });
        
        // 点击遮罩层关闭
        if (closable) {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    this.close();
                }
            });
        }
        
        // ESC键关闭
        if (closable) {
            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    this.close();
                    document.removeEventListener('keydown', escHandler);
                }
            };
            document.addEventListener('keydown', escHandler);
        }
    },
    
    /**
     * 关闭对话框
     */
    close() {
        if (!this.currentModal) return;
        
        const { overlay } = this.currentModal;
        
        // 触发动画
        overlay.classList.remove('active');
        
        // 动画结束后移除DOM
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
            this.currentModal = null;
        }, 300);
    }
};

// 兼容旧代码：覆盖原生 alert 函数（可选）
// 如果需要完全替换原生alert，取消下面的注释
// window.alert = function(message) {
//     Modal.alert(message, 'info');
// };

// window.confirm = function(message) {
//     return Modal.confirm(message);
// };
