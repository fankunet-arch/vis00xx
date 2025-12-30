# DMS 文件库存档系统 v1.0

完整的文档管理与存档系统，支持在线预览和严格的安全控制。

## 核心特性

✅ **安全第一**
- 文件存储在私有 S3 桶中（QNAP QuObjects）
- **严禁直链下载**：所有文件访问必须经过系统鉴权
- 代理下载/预览：不返回预签名 URL
- 物理隔离：业务代码不可直接 Web 访问

✅ **版本管理**
- 完整的版本历史追踪
- Append / Overwrite 模式（均保留历史）
- 单一当前版本策略
- 事务化上传流程

✅ **在线预览**
- PDF：浏览器内嵌预览（支持 Range 请求）
- 图片：JPG/PNG/GIF/BMP/WEBP
- 文本：TXT/CSV（带大小限制）
- DOC/DOCX：默认不支持（可扩展）

✅ **分类与属性**
- 自定义分类（Category）
- JSON Schema 定义属性字段
- 支持类型：text、number、date、enum、bool
- 严格的属性验证

✅ **审计日志**
- 完整记录所有操作
- 登录、上传、下载、预览、删除
- IP 地址、User Agent 追踪

✅ **角色权限**
- **Admin**：所有权限（含分类管理、删除）
- **Editor**：上传、编辑、下载、预览
- **Viewer**：只读、下载、预览

## 技术栈

- **后端**：PHP 8.4（过程式编程，VIS 风格）
- **数据库**：MariaDB 10
- **存储**：QNAP QuObjects（S3 兼容）
- **架构**：Front Controller 单入口 + 白名单路由

## 目录结构

```
/dc_html/dms/
  ap/
    index.php          # 唯一 Web 入口
    css/
    js/
    assets/

/app/dms/              # 私有目录（严禁 Web 访问）
  bootstrap.php        # 系统初始化
  config_dms/
    env_dms.php        # 配置文件（需修改）
  api/                 # API 端点
    do_login.php
    do_logout.php
    doc_upload_submit.php
    version_upload_submit.php
    file_download.php       # 代理下载
    file_preview.php        # 代理预览
    doc_update_meta_submit.php
    doc_delete_submit.php
    version_delete_submit.php
    category_save.php
  views/               # 视图模板
    login.php
    doc_list.php
    doc_view.php
    doc_upload.php
    category_list.php
    category_edit.php
  lib/                 # 核心库
    dms_lib.php
    dms_db.php
    dms_auth.php
    dms_s3_client.php
    dms_validator.php
  docs/
    db_schema_v1.sql   # 数据库结构
  tmp/
    upload/            # 临时上传目录
```

## 部署步骤

### 1. 环境要求

- PHP 8.4+
- MariaDB 10+
- Web 服务器（Apache/Nginx）
- QNAP QuObjects（或任何 S3 兼容存储）

### 2. 数据库设置

```bash
# 创建数据库
mysql -u root -p -e "CREATE DATABASE vis00xx_dms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 导入结构
mysql -u root -p vis00xx_dms < app/dms/docs/db_schema_v1.sql
```

### 3. 配置文件

编辑 `/app/dms/config_dms/env_dms.php`：

```php
// 数据库
'db_dsn' => 'mysql:host=localhost;dbname=vis00xx_dms;charset=utf8mb4',
'db_user' => 'your_db_user',
'db_pass' => 'your_db_password',

// S3 存储（QNAP QuObjects）
's3_endpoint' => 'https://your-qnap.com:8080',
's3_access_key' => 'YOUR_ACCESS_KEY',
's3_secret_key' => 'YOUR_SECRET_KEY',
's3_bucket' => 'abcabc-docs-prod',
's3_use_path_style' => true,
's3_verify_ssl' => true,  // 自签名证书设为 false

// 上传限制
'upload_max_mb' => 100,
'upload_tmp_dir' => '/full/path/to/app/dms/tmp/upload',
```

### 4. 目录权限

```bash
# 确保 app/dms 不可 Web 访问（通过 .htaccess 或 Nginx 配置）
chmod -R 755 app/dms
chmod 700 app/dms/config_dms
chmod 600 app/dms/config_dms/env_dms.php
chmod 777 app/dms/tmp/upload

# Web 目录
chmod -R 755 dc_html/dms
```

### 5. Web 服务器配置

**Apache (.htaccess 已包含在 app/ 目录)**

在 `/app/.htaccess` 中添加：
```apache
Deny from all
```

**Nginx**

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/dc_html;

    # 阻止访问 /app
    location /app/ {
        deny all;
        return 404;
    }

    # DMS 应用
    location /dc_html/dms/ap/ {
        index index.php;
        try_files $uri $uri/ /dc_html/dms/ap/index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 6. PHP 配置

编辑 `php.ini`：

```ini
upload_max_filesize = 100M
post_max_size = 105M
max_execution_time = 300
memory_limit = 256M
```

### 7. 访问系统

访问：`http://your-domain.com/dc_html/dms/ap/`

**默认账号**：
- 用户名：`admin`
- 密码：`admin123`

**重要**：首次登录后立即修改密码！

## 使用说明

### 上传文档

1. 点击"Upload New Document"
2. 填写标题、描述、分类、标签
3. 选择文件上传
4. 选择上传模式（Append / Overwrite）

### 在线预览

- 支持的文件类型会显示"Preview"按钮
- 点击后在新标签页打开预览
- PDF 支持分页加载（Range 请求）

### 版本管理

- 每次上传都会创建新版本
- 在文档详情页查看所有版本
- 可下载或预览任意历史版本
- 仅管理员可删除非当前版本

### 分类管理（仅管理员）

1. 进入"Categories"
2. 创建或编辑分类
3. 定义 JSON Schema：

```json
{
  "fields": [
    {
      "name": "contract_date",
      "type": "date",
      "required": true
    },
    {
      "name": "status",
      "type": "enum",
      "required": true,
      "options": ["draft", "active", "expired"]
    }
  ]
}
```

## 安全验收清单

- [ ] `/app/dms` 目录不可通过 URL 访问
- [ ] S3 桶为 Private，无法匿名访问
- [ ] 文件下载必须通过 `file_download` API
- [ ] 文件预览必须通过 `file_preview` API
- [ ] 无法获取到任何直接的 S3 URL
- [ ] PDF 预览支持 Range 请求
- [ ] 所有操作记录在审计日志
- [ ] 版本切换正确（单一 current）

## 数据库表说明

所有表均以 `dms_` 前缀：

- `dms_users` - 用户账号
- `dms_user_sessions` - 会话追踪
- `dms_categories` - 分类定义
- `dms_documents` - 文档主表（UUID）
- `dms_document_versions` - 版本历史
- `dms_audit_log` - 审计日志
- `dms_deleted_object_queue` - 删除队列（可选）

## 存储键格式

```
org/{org_id}/doc/{doc_id}/v/{version_no}/{stored_file_name}
```

示例：
```
org/1/doc/a1b2c3d4-e5f6-4789-a0b1-c2d3e4f5g6h7/v/1/contract_2024__a1b2c3.pdf
```

## 故障排查

### 文件上传失败

1. 检查 PHP 上传限制
2. 检查临时目录权限
3. 检查 S3 连接配置
4. 查看 PHP 错误日志

### 预览失败

1. 检查文件 MIME 类型是否支持
2. 检查 S3 对象是否存在
3. 查看浏览器控制台错误

### 数据库连接失败

1. 检查 DSN 配置
2. 检查用户权限
3. 检查 MariaDB 是否运行

## 许可证

内部使用系统 - 版权所有

## 支持

如有问题，请联系系统管理员。

---

**DMS Archive System v1.0**
构建日期：2025-12-29
