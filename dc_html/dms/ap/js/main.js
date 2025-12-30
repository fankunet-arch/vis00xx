/**
 * DMS档案管理系统 - 主JavaScript文件
 * DMS Archive System - Main JavaScript
 */

(function() {
    'use strict';

    // ========================================
    // 工具函数
    // ========================================

    /**
     * 显示Toast通知
     */
    function showToast(message, type = 'info') {
        // 创建toast容器（如果不存在）
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 10px;
            `;
            document.body.appendChild(container);
        }

        // 创建toast元素
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
            min-width: 300px;
            padding: 16px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-left: 4px solid ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            animation: slideInRight 0.3s ease-out;
            font-size: 14px;
            color: #374151;
        `;
        toast.textContent = message;

        container.appendChild(toast);

        // 自动移除
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                container.removeChild(toast);
                if (container.children.length === 0) {
                    document.body.removeChild(container);
                }
            }, 300);
        }, 3000);
    }

    // 添加CSS动画
    if (!document.getElementById('toast-animations')) {
        const style = document.createElement('style');
        style.id = 'toast-animations';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(100px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            @keyframes slideOutRight {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100px);
                }
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * 确认对话框
     */
    function confirm(message, callback) {
        if (window.confirm(message)) {
            callback();
        }
    }

    // ========================================
    // 表单增强
    // ========================================

    /**
     * 文件上传预览
     */
    function enhanceFileInputs() {
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
                    showToast(`已选择文件: ${file.name} (${sizeInMB} MB)`, 'info');
                }
            });
        });
    }

    /**
     * 表单验证增强
     */
    function enhanceFormValidation() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let hasError = false;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        hasError = true;
                        field.style.borderColor = '#ef4444';
                        field.addEventListener('input', function() {
                            this.style.borderColor = '';
                        }, { once: true });
                    }
                });

                if (hasError) {
                    e.preventDefault();
                    showToast('请填写所有必填字段', 'error');
                }
            });
        });
    }

    /**
     * 输入框焦点效果
     */
    function enhanceInputFocus() {
        const inputs = document.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    }

    // ========================================
    // 表格增强
    // ========================================

    /**
     * 表格行点击高亮
     */
    function enhanceTableRows() {
        const tables = document.querySelectorAll('.data-table tbody tr');
        tables.forEach(row => {
            row.style.cursor = 'pointer';

            // 点击行时，如果有链接则跳转
            row.addEventListener('click', function(e) {
                if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
                    const link = this.querySelector('a.doc-title');
                    if (link) {
                        window.location.href = link.href;
                    }
                }
            });
        });
    }

    // ========================================
    // 搜索/筛选增强
    // ========================================

    /**
     * 实时搜索提示
     */
    function enhanceSearch() {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();

                if (query.length >= 2) {
                    searchTimeout = setTimeout(() => {
                        // 可以在这里添加实时搜索建议功能
                        console.log('搜索:', query);
                    }, 500);
                }
            });
        }
    }

    // ========================================
    // 按钮增强
    // ========================================

    /**
     * 删除按钮确认
     */
    function enhanceDeleteButtons() {
        const deleteButtons = document.querySelectorAll('[data-action="delete"]');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const message = this.dataset.confirmMessage || '确定要删除吗？此操作无法撤销。';

                if (window.confirm(message)) {
                    // 显示加载状态
                    const originalText = this.textContent;
                    this.textContent = '删除中...';
                    this.disabled = true;

                    // 执行删除操作
                    const url = this.href || this.dataset.url;
                    if (url) {
                        window.location.href = url;
                    }
                }
            });
        });
    }

    /**
     * 提交按钮加载状态
     */
    function enhanceSubmitButtons() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    const originalText = submitBtn.textContent;
                    submitBtn.innerHTML = `
                        <span style="display: inline-flex; align-items: center; gap: 8px;">
                            <svg style="width: 16px; height: 16px; animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <circle cx="12" cy="12" r="10" stroke-width="3" stroke-opacity="0.25"></circle>
                                <path d="M12 2a10 10 0 0 1 10 10" stroke-width="3" stroke-linecap="round"></path>
                            </svg>
                            <span>处理中...</span>
                        </span>
                    `;
                    submitBtn.disabled = true;
                }
            });
        });
    }

    // ========================================
    // 页面加载动画
    // ========================================

    /**
     * 添加页面加载淡入效果
     */
    function addPageTransition() {
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.3s ease-out';

        window.addEventListener('load', function() {
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 50);
        });
    }

    // ========================================
    // 键盘快捷键
    // ========================================

    /**
     * 添加键盘快捷键
     */
    function addKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K: 聚焦到搜索框
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }

            // ESC: 清除搜索
            if (e.key === 'Escape') {
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput && searchInput === document.activeElement) {
                    searchInput.value = '';
                    searchInput.blur();
                }
            }
        });
    }

    // ========================================
    // 初始化
    // ========================================

    function init() {
        // 等待DOM加载完成
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }

        // 执行所有增强功能
        enhanceFileInputs();
        enhanceFormValidation();
        enhanceInputFocus();
        enhanceTableRows();
        enhanceSearch();
        enhanceDeleteButtons();
        enhanceSubmitButtons();
        addPageTransition();
        addKeyboardShortcuts();

        console.log('DMS系统已初始化');
    }

    // 启动初始化
    init();

    // 导出公共API
    window.DMS = {
        showToast: showToast,
        confirm: confirm
    };

})();
