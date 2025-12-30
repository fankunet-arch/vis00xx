# DMS系统升级指南 - v1.1 项目分类功能

## 功能概述

本次升级为DMS文档管理系统添加了**项目分类**功能，现在可以：

- ✅ 在系统设置中管理项目（添加、修改、删除）
- ✅ 上传文档时选择所属项目
- ✅ 按项目区分文档（例如：A项目的合同、B项目的合同）
- ✅ 删除保护：包含文档的项目无法被删除

## 升级步骤

### 1. 运行数据库迁移

使用MySQL客户端执行迁移脚本：

```bash
mysql -u root -p vis00xx_dms < /home/user/vis00xx/app/dms/docs/migrations/add_projects_v1.1.sql
```

或者使用phpMyAdmin：
1. 登录phpMyAdmin
2. 选择数据库 `vis00xx_dms`
3. 点击"SQL"标签
4. 复制并粘贴 `/app/dms/docs/migrations/add_projects_v1.1.sql` 的内容
5. 点击"执行"

### 2. 验证升级

升级完成后，检查以下内容：

#### 检查数据库表
```sql
-- 检查项目表是否创建成功
SHOW TABLES LIKE 'dms_projects';

-- 检查文档表是否添加了project_id字段
DESCRIBE dms_documents;

-- 查看示例项目数据
SELECT * FROM dms_projects;
```

#### 检查系统功能
1. 登录DMS系统
2. 导航栏应该显示"项目管理"链接（仅管理员可见）
3. 访问项目管理页面，应该能看到示例项目（A项目、B项目）
4. 上传新文档时，应该能看到项目选择下拉框

## 数据库变更详情

### 新增表：dms_projects

```sql
CREATE TABLE `dms_projects` (
  `project_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `org_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `name` VARCHAR(100) NOT NULL,              -- 项目名称
  `code` VARCHAR(50) NULL,                   -- 项目代码（可选）
  `description` TEXT NULL,                   -- 项目描述
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',  -- active/archived/closed
  `is_active` TINYINT NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`project_id`),
  UNIQUE KEY `idx_org_name` (`org_id`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 修改表：dms_documents

新增字段：
```sql
ALTER TABLE `dms_documents`
  ADD COLUMN `project_id` INT UNSIGNED NULL COMMENT 'Related project ID' AFTER `category_id`,
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_org_project` (`org_id`, `project_id`);
```

## 新增功能说明

### 1. 项目管理界面

**访问路径**：设置 → 项目管理

**功能**：
- 查看所有项目列表
- 创建新项目（名称、代码、描述、状态）
- 编辑现有项目
- 删除项目（仅当项目下没有文档时）

**项目状态**：
- `active` (进行中)：在上传文档时可选
- `archived` (已归档)：不在上传列表中显示
- `closed` (已关闭)：不在上传列表中显示

### 2. 文档项目关联

**上传文档时**：
- 新增"项目"下拉选择框
- 只显示状态为"active"的项目
- 项目为可选字段（可以不选）

**文档列表**：
- 新增"项目"列，显示文档所属项目

**文档详情**：
- 在文档信息中显示所属项目

### 3. 删除保护

当尝试删除包含文档的项目时：
- 系统会阻止删除操作
- 显示错误提示：该项目下有X个有效文档，无法删除
- 必须先删除或移除项目下的所有文档才能删除项目

## 代码变更清单

### 新增文件

**数据库相关**：
- `/app/dms/docs/migrations/add_projects_v1.1.sql` - 数据库迁移脚本

**视图文件**：
- `/app/dms/views/project_list.php` - 项目列表页面
- `/app/dms/views/project_edit.php` - 项目编辑页面

**API文件**：
- `/app/dms/api/project_save.php` - 项目保存API
- `/app/dms/api/project_delete.php` - 项目删除API

### 修改文件

**数据库函数** (`/app/dms/lib/dms_db.php`)：
- 新增：`dms_db_get_projects()` - 获取项目列表
- 新增：`dms_db_get_project()` - 获取单个项目
- 新增：`dms_db_save_project()` - 保存项目
- 新增：`dms_db_delete_project()` - 删除项目
- 新增：`dms_db_count_project_documents()` - 统计项目文档数
- 修改：`dms_db_get_documents()` - 添加项目信息查询
- 修改：`dms_db_get_document()` - 添加项目信息查询
- 修改：`dms_db_create_document()` - 支持project_id参数
- 修改：`dms_db_update_document()` - 支持project_id参数

**视图文件**：
- `/app/dms/views/doc_upload.php` - 添加项目选择器
- `/app/dms/views/doc_view.php` - 显示项目信息
- `/app/dms/views/doc_list.php` - 显示项目列
- `/app/dms/views/_header.php` - 添加项目管理链接

**API文件**：
- `/app/dms/api/doc_upload_submit.php` - 处理project_id
- `/app/dms/api/doc_update_meta_submit.php` - 处理project_id

**路由文件**：
- `/dc_html/dms/ap/index.php` - 添加项目管理路由

## 使用示例

### 创建项目

1. 以管理员身份登录
2. 点击导航栏的"项目管理"
3. 点击"创建新项目"
4. 填写：
   - 项目名称：A项目
   - 项目代码：PROJ-A
   - 描述：示例项目A
   - 状态：进行中
5. 点击"保存项目"

### 上传带项目的文档

1. 点击"上传文档"
2. 填写标题：合同文件
3. 选择分类：合同
4. **选择项目：A项目**
5. 上传文件
6. 保存

### 查看项目文档

- 在文档列表中，可以看到每个文档的项目列
- 在文档详情中，可以看到所属项目信息

## 回滚说明

如果需要回滚此功能，执行以下SQL：

```sql
-- 警告：这将删除所有项目数据和文档的项目关联

-- 删除文档表的project_id字段
ALTER TABLE dms_documents
  DROP KEY idx_project,
  DROP KEY idx_org_project,
  DROP COLUMN project_id;

-- 删除项目表
DROP TABLE IF EXISTS dms_projects;

-- 更新schema版本
UPDATE dms_schema_version SET version = '1.0' WHERE version = '1.1';
```

## 注意事项

1. **现有文档**：升级后，所有现有文档的`project_id`为NULL（未分配项目）
2. **权限**：只有管理员可以访问项目管理功能
3. **项目状态**：已归档/已关闭的项目不会在上传文档时显示
4. **删除保护**：有文档的项目无法删除，必须先处理文档

## 技术支持

如有问题，请检查：
1. 数据库迁移是否成功执行
2. 浏览器控制台是否有JavaScript错误
3. PHP错误日志：检查服务器日志文件
