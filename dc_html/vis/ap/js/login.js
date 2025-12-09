/**
 * VIS Video Inspiration System - Login Page Script
 * 文件路径: dc_html/vis/ap/js/login.js
 * 说明: VIS独立登录页面脚本（完全独立，不依赖任何其他系统）
 */

(function() {
    'use strict';

    // DOM元素
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const loginBtn = document.getElementById('loginBtn');
    const btnText = loginBtn.querySelector('.btn-text');
    const btnLoading = loginBtn.querySelector('.btn-loading');

    // 初始化
    function init() {
        // 绑定表单提交事件
        loginForm.addEventListener('submit', handleSubmit);

        // Enter键提交
        passwordInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                loginForm.dispatchEvent(new Event('submit'));
            }
        });

        // 聚焦用户名输入框
        if (usernameInput && !usernameInput.value) {
            usernameInput.focus();
        }
    }

    /**
     * 处理表单提交
     * @param {Event} e
     */
    function handleSubmit(e) {
        const username = usernameInput.value.trim();
        const password = passwordInput.value;

        // 验证输入
        if (!validateInputs(username, password)) {
            e.preventDefault(); // 只在验证失败时阻止提交
            return;
        }

        // 验证通过，显示加载状态
        setLoading(true);

        // 让表单正常提交（不需要调用submit()，因为这是submit事件处理器）
        // 表单会自动提交到 action="/vis/ap/index.php?action=do_login"
    }


    /**
     * 验证输入
     * @param {string} username
     * @param {string} password
     * @returns {boolean}
     */
    function validateInputs(username, password) {
        if (!username) {
            showError('请输入用户名');
            usernameInput.focus();
            return false;
        }

        if (username.length < 2) {
            showError('用户名至少2个字符');
            usernameInput.focus();
            return false;
        }

        if (!password) {
            showError('请输入密码');
            passwordInput.focus();
            return false;
        }

        if (password.length < 6) {
            showError('密码至少6个字符');
            passwordInput.focus();
            return false;
        }

        return true;
    }

    /**
     * 显示错误提示
     * @param {string} message
     */
    function showError(message) {
        // 移除旧的错误提示
        const oldAlert = document.querySelector('.alert-error');
        if (oldAlert && !oldAlert.hasAttribute('data-server-error')) {
            oldAlert.remove();
        }

        // 创建新的错误提示
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.innerHTML = `
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span>${escapeHtml(message)}</span>
        `;

        // 插入到表单前
        loginForm.parentNode.insertBefore(alert, loginForm);

        // 3秒后自动移除
        setTimeout(() => {
            if (alert.parentNode) {
                alert.style.transition = 'opacity 0.3s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        }, 3000);
    }

    /**
     * 设置加载状态
     * @param {boolean} loading
     */
    function setLoading(loading) {
        if (loading) {
            loginBtn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'flex';
            usernameInput.disabled = true;
            passwordInput.disabled = true;
        } else {
            loginBtn.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
            usernameInput.disabled = false;
            passwordInput.disabled = false;
        }
    }

    /**
     * HTML转义（防止XSS）
     * @param {string} text
     * @returns {string}
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // 页面加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
