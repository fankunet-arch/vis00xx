# DMS (Document Management System) System Design Document

**Version**: 1.0
**Last Updated**: 2025-12-29
**Target Audience**: AI Engineers & Backend Developers

## 1. System Overview

DMS is a lightweight, secure document archiving system built on **PHP 8.4**, **MariaDB 10**, and **S3-Compatible Object Storage**. It is designed for strict version control, auditability, and secure file access (no direct public links).

### 1.1 Technology Stack
*   **Language**: PHP 8.4 (Strict Types, PDO)
*   **Database**: MariaDB 10 (InnoDB, JSON support)
*   **Storage**: S3 API (MinIO / QNAP QuObjects / AWS S3)
*   **Frontend**: Native PHP Views (Server-Side Rendering) with minimal JS.

---

## 2. Architecture & Directory Structure

The system separates **Application Logic** (safe from web access) from **Public Gateway**.

### 2.1 Directory Layout

```text
/
├── app/dms/                  # CORE LOGIC (Outside Web Root)
│   ├── api/                  # API Handlers (JSON responses)
│   ├── config_dms/           # Environment Configuration
│   ├── lib/                  # Library Functions (DB, Auth, S3)
│   ├── views/                # HTML Templates
│   ├── docs/                 # Schema & Deployment docs
│   └── bootstrap.php         # App Initialization
│
├── dc_html/dms/              # WEB ROOT (Public)
│   └── ap/
│       └── index.php         # FRONT CONTROLLER (Single Entry Point)
│
└── docs/                     # General Documentation
```

### 2.2 Request Flow
1.  **Entry**: All requests hit `dc_html/dms/ap/index.php`.
2.  **Routing**: `action` parameter determines the handler (e.g., `?action=doc_list`).
3.  **Security Check**:
    *   **Whitelist**: Action must be in `$allowed_actions`.
    *   **Auth**: Action must be in `$public_actions` OR user must be logged in.
4.  **Dispatch**:
    *   **View Actions**: Render templates from `app/dms/views/`.
    *   **API Actions**: Execute scripts in `app/dms/api/` and return JSON.

---

## 3. Database Design

The database schema (`db_schema_v1.sql`) focuses on document versioning and audit trails.

### 3.1 Core Entities

#### `dms_documents` (The "Container")
Represents the abstract concept of a document.
*   **Key Fields**: `doc_id` (UUID), `title`, `category_id`, `current_version_id`.
*   **Purpose**: Holds metadata and tracks the current active version.

#### `dms_document_versions` (The "Content")
Immutable history of file versions.
*   **Key Fields**: `version_id`, `doc_id`, `version_no`, `storage_key`, `sha256`.
*   **Storage Key Format**: `org/{org_id}/doc/{doc_id}/v/{version_no}/{filename}`.
*   **Logic**: Every upload (append or overwrite) creates a NEW row here.

#### `dms_categories`
*   **Key Fields**: `schema_json`.
*   **Feature**: Supports dynamic attribute validation based on JSON schema.

### 3.2 Audit & Security

#### `dms_audit_log`
Records *every* significant action.
*   **Actions**: `login`, `upload`, `download`, `preview`, `delete`.
*   **Details**: Stores JSON payload with filename, size, and IP.

---

## 4. Key Components

### 4.1 Authentication (`lib/dms_auth.php`)
*   **Session Based**: Uses native PHP sessions with strict cookie settings (`HttpOnly`, `SameSite`).
*   **Password Hashing**: `password_hash()` (Bcrypt).
*   **Role Based**: `admin`, `editor`, `viewer` (Enforced via `dms_require_permission()`).

### 4.2 File Storage (`lib/dms_s3_client.php`)
*   **Proxy Pattern**: The application acts as a proxy.
    *   **Upload**: Client -> PHP -> S3.
    *   **Download**: S3 -> PHP (Stream) -> Client.
*   **Security**: No Pre-signed URLs are exposed. Users never communicate directly with S3.
*   **Deduplication**: `sha256` is calculated on upload but currently used for verification/integrity, not storage deduplication (though ready for it).

### 4.3 Input Validation (`lib/dms_validator.php`)
*   **Strict MIME Checking**: Uses `finfo_file` to detect real MIME type.
*   **Extension Whitelist**: Validates file extension against config.
*   **Forbidden Content**: Rejects `text/html` or script-like content disguised as text to prevent XSS.

---

## 5. Critical Workflows

### 5.1 Document Upload
1.  **POST `doc_upload_submit`**: Receives file & metadata.
2.  **Validate**: Check MIME, Size, Permissions.
3.  **Transaction Start**:
4.  **DB**: Create `dms_documents` row (if new).
5.  **S3**: `PUT` object to Storage.
6.  **DB**: Create `dms_document_versions` row.
7.  **Transaction Commit**.

### 5.2 File Preview (Streaming)
1.  **GET `file_preview`**: Request `version_id`.
2.  **Auth**: Check permission.
3.  **S3 Fetch**: Get object stream from S3.
4.  **Range Handling**: If `Range` header exists, seek stream and return `206 Partial Content` (Critical for PDF/Video).
5.  **Output**: Stream to browser with `Content-Disposition: inline`.

---

## 6. Configuration & Environment

Configuration is located in `app/dms/config_dms/env_dms.php`.
*   **Sensitive Data**: DB Credentials, S3 Keys (Never commit real values).
*   **Toggles**: `app_debug`, `s3_verify_ssl` (Disable for MinIO/Self-signed).

### 6.1 Local Development (Dev Mode)
*   **Database**: Can adapt to SQLite (with schema conversion) if MariaDB is unavailable.
*   **Storage**: MinIO (S3 Compatible).

---

## 7. Troubleshooting / Common Issues

*   **Login 404/500**: Check `$allowed_actions` in `index.php`. `do_login` must be whitelisted.
*   **Preview Failed**: Check if `Content-Type` is allowed in `preview_types` config.
*   **Large File Download Fails**: Check `php.ini` (`memory_limit`, `post_max_size`) and `upload_max_mb` in config.
*   **Range Requests**: Ensure Web Server (Nginx/Apache) does not strip `Range` headers before they reach PHP.

---
**Document Status**: Draft
**Author**: JULES
