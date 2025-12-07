/**
 * VIS Mobile Menu Control
 * 文件路径: dc_html/vis/ap/js/mobile-menu.js
 * 说明: 移动端菜单控制脚本（汉堡菜单、侧边栏、遮罩层）
 */

(function() {
    'use strict';

    // 获取DOM元素
    const sidebar = document.querySelector('.sidebar');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileOverlay = document.getElementById('mobileOverlay');

    if (!sidebar || !mobileMenuBtn || !mobileOverlay) {
        // 如果关键元素不存在，直接返回
        return;
    }

    // 切换菜单显示/隐藏
    function toggleMobileMenu() {
        sidebar.classList.toggle('active');
        mobileOverlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }

    // 关闭移动端菜单
    function closeMobileMenu() {
        sidebar.classList.remove('active');
        mobileOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // 汉堡菜单按钮点击事件
    mobileMenuBtn.addEventListener('click', toggleMobileMenu);

    // 遮罩层点击关闭菜单
    mobileOverlay.addEventListener('click', closeMobileMenu);

    // 点击侧边栏链接后关闭菜单（仅移动端）
    document.querySelectorAll('.sidebar .nav-item').forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                closeMobileMenu();
            }
        });
    });

    // 窗口大小改变时，桌面端自动关闭菜单
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            closeMobileMenu();
        }
    });

    // ESC键关闭菜单
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            closeMobileMenu();
        }
    });
})();
