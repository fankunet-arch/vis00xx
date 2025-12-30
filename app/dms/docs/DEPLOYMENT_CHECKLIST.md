# DMS Archive System - 部署检查清单

## 部署前检查

### 1. 服务器环境
- [ ] PHP 8.4 或更高版本已安装
- [ ] MariaDB 10 或更高版本已安装
- [ ] Web 服务器已配置（Apache/Nginx）
- [ ] PHP 扩展已启用：
  - [ ] PDO
  - [ ] pdo_mysql
  - [ ] mbstring
  - [ ] json
  - [ ] curl
  - [ ] fileinfo

### 2. 数据库设置
- [ ] 数据库已创建（推荐名称：`vis00xx_dms`）
- [ ] 字符集：`utf8mb4`
- [ ] 排序规则：`utf8mb4_unicode_ci`
- [ ] 数据库用户已创建并授权
- [ ] 执行 `db_schema_v1.sql` 导入表结构
- [ ] 验证所有表都以 `dms_` 前缀
- [ ] 默认 admin 用户已创建（admin/admin123）

### 3. 存储配置（QNAP QuObjects）
- [ ] S3 endpoint 已配置
- [ ] Access Key 已获取
- [ ] Secret Key 已获取
- [ ] Bucket 已创建（例如：`abcabc-docs-prod`）
- [ ] Bucket 权限设置为 **Private**
- [ ] 测试连接可用

### 4. 目录权限
```bash
# 执行这些命令
chmod -R 755 /path/to/vis00xx/dc_html/dms
chmod -R 755 /path/to/vis00xx/app/dms
chmod 700 /path/to/vis00xx/app/dms/config_dms
chmod 600 /path/to/vis00xx/app/dms/config_dms/env_dms.php
chmod 777 /path/to/vis00xx/app/dms/tmp/upload
```

- [ ] Web 目录可读
- [ ] 配置目录受保护（chmod 700）
- [ ] 配置文件受保护（chmod 600）
- [ ] 上传临时目录可写（chmod 777）

### 5. 配置文件（env_dms.php）
- [ ] 数据库连接信息已配置
- [ ] S3 存储信息已配置
- [ ] 上传临时目录路径已配置
- [ ] 时区已设置（timezone_display）
- [ ] 生产环境设置：
  - [ ] `app_env` = 'production'
  - [ ] `app_debug` = false
  - [ ] `session_cookie_secure` = true（如果使用 HTTPS）
  - [ ] `s3_verify_ssl` = true

### 6. Web 服务器配置
- [ ] 文档根目录指向正确位置
- [ ] PHP-FPM 已配置（如使用 Nginx）
- [ ] `/app` 目录访问被禁止
- [ ] `.htaccess` 生效（Apache）或 Nginx 配置正确
- [ ] URL 重写已启用
- [ ] PHP 配置已调整：
  - [ ] `upload_max_filesize` = 100M（或根据需求）
  - [ ] `post_max_size` = 105M
  - [ ] `max_execution_time` = 300
  - [ ] `memory_limit` = 256M

### 7. 安全检查
- [ ] 测试访问 `/app/dms/config_dms/env_dms.php` 应返回 403/404
- [ ] 测试访问 `/app/dms/lib/dms_lib.php` 应返回 403/404
- [ ] 测试访问 `/dc_html/dms/ap/index.php` 应正常
- [ ] 尝试直接访问 S3 对象应失败（Private bucket）
- [ ] 登录功能正常
- [ ] 审计日志正在记录

## 部署后验证

### 1. 基础功能测试
- [ ] 能够访问登录页面
- [ ] 能够使用 admin/admin123 登录
- [ ] 登录后跳转到文档列表
- [ ] 能够访问分类管理（admin）

### 2. 上传测试
- [ ] 能够上传 PDF 文件
- [ ] 能够上传图片文件（JPG/PNG）
- [ ] 能够上传文本文件
- [ ] 上传后能在列表中看到
- [ ] 能够查看文档详情
- [ ] 版本号正确（v1）

### 3. 下载测试
- [ ] 点击下载按钮能正常下载
- [ ] 下载的文件完整可用
- [ ] 下载操作被记录到审计日志
- [ ] **验证不存在直接的 S3 URL**

### 4. 预览测试
- [ ] PDF 能够在浏览器中预览
- [ ] 图片能够正常显示
- [ ] 文本文件能够查看
- [ ] 大 PDF 支持分页加载（Range 请求）
- [ ] 预览操作被记录到审计日志
- [ ] **验证预览也是通过系统代理**

### 5. 版本管理测试
- [ ] 能够上传新版本（append）
- [ ] 能够上传新版本（overwrite）
- [ ] 版本列表正确显示
- [ ] 当前版本标记正确
- [ ] 能够下载历史版本
- [ ] 同一文档只有一个当前版本

### 6. 分类管理测试
- [ ] 能够创建新分类
- [ ] 能够定义 Schema
- [ ] Schema 验证正常工作
- [ ] 上传文档时能选择分类
- [ ] 自定义属性能正确保存和显示

### 7. 权限测试
- [ ] Admin 能访问所有功能
- [ ] Editor 能上传、编辑、下载、预览
- [ ] Viewer 只能查看、下载、预览
- [ ] Viewer 不能上传或编辑
- [ ] 非 Admin 不能访问分类管理

### 8. 审计日志验证
检查 `dms_audit_log` 表：
- [ ] 登录操作已记录
- [ ] 上传操作已记录
- [ ] 下载操作已记录
- [ ] 预览操作已记录
- [ ] 删除操作已记录（如果执行了删除）
- [ ] IP 地址和 User Agent 已记录

## 性能测试

- [ ] 上传 10MB 文件正常
- [ ] 上传 50MB 文件正常
- [ ] 上传 100MB 文件正常（如果允许）
- [ ] PDF 预览响应时间 < 3秒
- [ ] 图片预览响应时间 < 1秒
- [ ] 文档列表加载时间 < 2秒

## 安全验收（最重要）

### 防直链测试
1. [ ] 上传一个文件
2. [ ] 查看数据库中的 `storage_key`
3. [ ] 尝试直接访问 S3 URL（应该失败）
4. [ ] 检查网络请求，确认没有暴露预签名 URL
5. [ ] 所有文件访问必须通过 `file_download` 或 `file_preview`

### 物理隔离测试
1. [ ] 尝试访问 `/app/dms/config_dms/env_dms.php`（应 403/404）
2. [ ] 尝试访问 `/app/dms/lib/dms_lib.php`（应 403/404）
3. [ ] 尝试访问 `/app/dms/api/do_login.php`（应 403/404）
4. [ ] 所有 API 只能通过 index.php 路由访问

### SQL 注入测试
- [ ] 在搜索框输入 `' OR '1'='1` 应无效
- [ ] 在文档标题输入特殊字符应被转义
- [ ] 所有输入都使用参数化查询

### XSS 测试
- [ ] 在标题输入 `<script>alert('xss')</script>` 应被转义
- [ ] 在描述输入 HTML 标签应被转义
- [ ] 在标签输入特殊字符应被转义

## 生产环境最终检查

- [ ] 默认 admin 密码已修改
- [ ] 错误显示已关闭（`display_errors` = Off）
- [ ] 调试模式已关闭（`app_debug` = false）
- [ ] HTTPS 已启用（推荐）
- [ ] 防火墙规则已配置
- [ ] 数据库备份计划已设置
- [ ] S3 备份策略已配置
- [ ] 监控和告警已设置

## 问题排查

如果遇到问题，检查以下日志：
- PHP 错误日志：`/var/log/php-fpm/error.log`
- Web 服务器日志：`/var/log/nginx/error.log` 或 `/var/log/apache2/error.log`
- 数据库日志：`/var/log/mysql/error.log`
- DMS 审计日志：数据库 `dms_audit_log` 表

---

**完成所有检查项后，系统即可投入生产使用。**
