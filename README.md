# Rise CRM REST API Plugin

**Version:** 0.1.6 - Beta 2  
**API Version:** 1.0.0  
**Author:** [x-xusko-x](https://github.com/x-xusko-x)  
**Status:** ⚠️ **BETA** - Production use at your own risk

A comprehensive REST API plugin for Rise CRM that provides secure, rate-limited access to all major CRM resources via RESTful endpoints.

---

## ⚠️ Beta Notice

This plugin is currently in **Beta** status. While functional, it may contain bugs or incomplete features. Contributions, bug reports, and feedback are highly welcomed!

---

## 📋 Table of Contents

- [Requirements](#-requirements)
- [Installation](#-installation)
- [API Endpoints](#-api-endpoints)
- [Authentication](#-authentication)
- [Response Format](#-response-format)
- [Features](#-features)
- [Rate Limiting](#-rate-limiting)
- [Configuration](#-configuration)
- [Known Issues](#-known-issues)
- [Database Schema](#-database-schema)
- [Usage Examples](#-usage-examples)
- [File Structure](#-file-structure)
- [Version History](#-version-history)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🔧 Requirements

### Minimum Requirements

- **Rise CRM:** Version 3.6 or higher
- **PHP:** 7.4 or higher (8.0+ recommended)
- **CodeIgniter:** 4.x (included with Rise CRM)
- **Database:** MySQL 5.7+ or MariaDB 10.2+
- **SSL Certificate:** Optional (required if HTTPS enforcement is enabled)

### Recommended Environment

- PHP 8.0+
- MySQL 8.0+ or MariaDB 10.5+
- HTTPS enabled for production use
- Sufficient database storage for API logs

---

## 📦 Installation

1. **Copy Plugin Files**
   ```bash
   # Copy the Rest_api folder to your Rise CRM plugins directory
   cp -r Rest_api /path/to/rise_crm/plugins/
   ```

2. **Run Database Installation**
   - Navigate to **Settings → Plugins** in your Rise CRM admin panel
   - Locate "REST API" plugin
   - Click "Install" button
   - The installer will automatically create required database tables

3. **Configure API Settings**
   - Go to **Settings → REST API Settings**
   - Enable the API
   - Configure default rate limits
   - Set HTTPS and CORS preferences

4. **Generate API Keys**
   - Navigate to **API Keys** tab
   - Click "Add API Key"
   - Configure key permissions and limits
   - Save and securely store the generated API Key and Secret

---

## 🌐 API Endpoints

All endpoints are prefixed with `/api/v1/` and require authentication via API Key and Secret.

### Base URL
```
https://your-domain.com/api/v1/
```

### Available Resources

| Resource | Endpoints | Methods | Special Actions |
|----------|-----------|---------|-----------------|
| **Users** | `/users`, `/users/{id}` | GET, POST, PUT, DELETE | - |
| **Projects** | `/projects`, `/projects/{id}` | GET, POST, PUT, DELETE | - |
| **Tasks** | `/tasks`, `/tasks/{id}` | GET, POST, PUT, DELETE | - |
| **Clients** | `/clients`, `/clients/{id}` | GET, POST, PUT, DELETE | Convert lead: `POST /clients/{id}/convert` |
| **Invoices** | `/invoices`, `/invoices/{id}` | GET, POST, PUT, DELETE | - |
| **Estimates** | `/estimates`, `/estimates/{id}` | GET, POST, PUT, DELETE | - |
| **Proposals** | `/proposals`, `/proposals/{id}` | GET, POST, PUT, DELETE | - |
| **Contracts** | `/contracts`, `/contracts/{id}` | GET, POST, PUT, DELETE | - |
| **Expenses** | `/expenses`, `/expenses/{id}` | GET, POST, PUT, DELETE | - |
| **Tickets** | `/tickets`, `/tickets/{id}` | GET, POST, PUT, DELETE | - |
| **Timesheets** | `/timesheets`, `/timesheets/{id}` | GET, POST, PUT, DELETE | - |
| **Events** | `/events`, `/events/{id}` | GET, POST, PUT, DELETE | - |
| **Notes** | `/notes`, `/notes/{id}` | GET, POST, PUT, DELETE | - |
| **Messages** | `/messages`, `/messages/{id}` | GET, POST, DELETE | No update support |
| **Notifications** | `/notifications`, `/notifications/{id}` | GET | Mark read: `POST /notifications/{id}/mark_read` |
| **Announcements** | `/announcements`, `/announcements/{id}` | GET, POST, PUT, DELETE | - |

### Endpoint Patterns

#### List Resources (GET)
```
GET /api/v1/{resource}?limit=50&offset=0&search=keyword
```

**Query Parameters:**
- `limit` - Items per page (max: 100, default: 50)
- `offset` - Pagination offset (default: 0)
- `search` - Search keyword
- Additional resource-specific filters (e.g., `status`, `client_id`, etc.)

#### Get Single Resource (GET)
```
GET /api/v1/{resource}/{id}
```

#### Create Resource (POST)
```
POST /api/v1/{resource}
Content-Type: application/json

{
  "field1": "value1",
  "field2": "value2"
}
```

#### Update Resource (PUT)
```
PUT /api/v1/{resource}/{id}
Content-Type: application/json

{
  "field1": "updated_value1"
}
```

#### Delete Resource (DELETE)
```
DELETE /api/v1/{resource}/{id}
```

---

## 🔐 Authentication

The API uses **API Key + Secret** authentication for all requests.

### Authentication Methods

#### Method 1: Custom Headers (Recommended)
```bash
X-API-Key: your_api_key_here
X-API-Secret: your_api_secret_here
```

#### Method 2: Bearer Token
```bash
Authorization: Bearer your_api_key_here
X-API-Secret: your_api_secret_here
```

### Obtaining API Credentials

1. Log in to Rise CRM admin panel
2. Navigate to **Settings → REST API Settings → API Keys**
3. Click **"Add API Key"**
4. Configure the key settings:
   - Name and description
   - Status (active/inactive/revoked)
   - Rate limits
   - IP whitelist (optional)
   - Expiration date (optional)
5. Save and **securely store** the generated credentials
   - API Key: Used for identification
   - API Secret: Used for verification (shown only once)

⚠️ **Security Warning:** API secrets are shown only once during creation. Store them securely!

### Authentication Errors

| Status Code | Error | Description |
|-------------|-------|-------------|
| `401` | API key is required | Missing `X-API-Key` header |
| `401` | API secret is required | Missing `X-API-Secret` header |
| `401` | Invalid API key | Key not found or incorrect |
| `401` | Invalid API secret | Secret verification failed |
| `401` | API key is inactive/revoked | Key status is not active |
| `401` | API key has expired | Key expiration date passed |
| `403` | IP address not authorized | Client IP not in whitelist |
| `403` | HTTPS is required | Request made over HTTP when HTTPS is enforced |

---

## 📤 Response Format

All API responses follow a standardized JSON format.

### Success Response (2xx)

```json
{
  "success": true,
  "data": {
    "resource": { ... },
    "pagination": { ... }
  },
  "meta": {
    "timestamp": "2025-10-14T12:34:56+00:00",
    "response_time": "45.23ms",
    "version": "1.0"
  }
}
```

### Error Response (4xx, 5xx)

```json
{
  "success": false,
  "error": {
    "code": 400,
    "message": "Missing required fields",
    "details": ["field1", "field2"]
  },
  "meta": {
    "timestamp": "2025-10-14T12:34:56+00:00",
    "response_time": "12.45ms",
    "version": "1.0"
  }
}
```

### Pagination Format

List endpoints include pagination metadata:

```json
{
  "data": {
    "users": [ ... ],
    "pagination": {
      "total": 250,
      "count": 50,
      "per_page": 50,
      "current_page": 1,
      "total_pages": 5
    }
  }
}
```

### HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| `200` | OK | Successful GET, PUT, or DELETE |
| `201` | Created | Successful POST (resource created) |
| `400` | Bad Request | Invalid request data or missing fields |
| `401` | Unauthorized | Authentication failed |
| `403` | Forbidden | Authenticated but not authorized |
| `404` | Not Found | Resource doesn't exist |
| `429` | Too Many Requests | Rate limit exceeded |
| `500` | Internal Server Error | Server-side error |
| `503` | Service Unavailable | API is disabled |

---

## ✨ Features

### 🔒 Security Features

- **API Key + Secret Authentication** - Dual-factor authentication for API access
- **IP Whitelisting** - Global and per-key IP restrictions
- **HTTPS Enforcement** - Optional requirement for secure connections
- **Password Hashing** - API secrets stored using bcrypt
- **Request Validation** - Input sanitization and validation
- **Sensitive Data Filtering** - Passwords and secrets excluded from logs

### ⏱️ Rate Limiting

- **Multi-Tier Limits** - Per-minute, per-hour, and per-day limits
- **Per-Key Configuration** - Custom limits for each API key
- **Automatic Enforcement** - Requests blocked when limits exceeded
- **Configurable Defaults** - Global fallback limits

### 📊 Monitoring & Logging

- **Request/Response Logging** - Complete audit trail of API usage
- **Detailed Metrics** - Response times, status codes, endpoints
- **IP and User Agent Tracking** - Client identification
- **Automatic Log Cleanup** - Configurable retention period (default: 90 days)
- **Total Calls Counter** - Per-key usage statistics

### 🌍 CORS Support

- **Configurable Origins** - Whitelist specific domains
- **Wildcard Support** - Allow all origins with `*`
- **Per-Key Overrides** - Custom CORS settings per API key
- **Preflight Handling** - Automatic OPTIONS request handling

### 🔧 Flexibility

- **Per-Key Security Settings** - Override global settings per API key
- **Expiration Dates** - Temporary API keys with auto-expiration
- **Status Management** - Active, Inactive, Revoked states
- **Assignment Types** - Internal or External API keys

---

## ⏱️ Rate Limiting

### Default Limits

| Period | Default Limit | Description |
|--------|---------------|-------------|
| **Per Minute** | 60 requests | Short-term burst protection |
| **Per Hour** | 1,000 requests | Medium-term usage limit |
| **Per Day** | 10,000 requests | Daily usage quota |

### Rate Limit Behavior

When rate limits are exceeded:

1. **Response Code:** `429 Too Many Requests`
2. **Response Body:**
   ```json
   {
     "success": false,
     "error": {
       "code": 429,
       "message": "Rate limit exceeded. Please try again later."
     },
     "limit": {
       "per_minute": 60,
       "per_hour": 1000,
       "per_day": 10000
     },
     "retry_after": 60
   }
   ```
3. **Retry-After:** Recommended wait time in seconds

### Customizing Limits

Rate limits can be configured:
1. **Globally:** Settings → REST API Settings → General Settings
2. **Per-Key:** API Keys → Edit Key → Rate Limits section

⚠️ Per-key limits override global defaults.

---

## ⚙️ Configuration

### Admin Panel Settings

Access configuration via **Settings → REST API Settings**

#### General Settings

| Setting | Description | Default |
|---------|-------------|---------|
| **API Enabled** | Master switch to enable/disable API | `true` |
| **Default Rate Limit (per minute)** | Global per-minute request limit | `60` |
| **Default Rate Limit (per hour)** | Global per-hour request limit | `1000` |
| **Default Rate Limit (per day)** | Global per-day request limit | `10000` |
| **Log Retention Days** | Days to keep API logs before auto-cleanup | `90` |
| **Require HTTPS** | Force HTTPS for all API requests | `false` |
| **CORS Enabled** | Enable Cross-Origin Resource Sharing | `false` |
| **CORS Allowed Origins** | Whitelisted domains (one per line or `*`) | (empty) |
| **Global IP Whitelist** | IP addresses allowed for all keys | (empty) |

#### API Key Settings

Each API key can be configured with:
- **Name & Description** - Identifier and notes
- **Status** - Active, Inactive, or Revoked
- **Rate Limits** - Custom per-minute, per-hour, per-day limits
- **IP Whitelist** - Specific IPs allowed for this key
- **Expiration Date** - Optional auto-expiration
- **Security Overrides:**
  - HTTPS requirement (use global, force enabled, force disabled)
  - CORS settings (use global, force enabled, force disabled)
  - Custom CORS origins

---

## 📖 OpenAPI & Swagger Documentation

### Swagger UI

**Status:** ✅ **Fully Implemented**

Access interactive API documentation:
```
https://your-domain.com/swagger/ui
```

**Features:**
- Full OpenAPI 3.0 specification auto-generated from code annotations
- Interactive testing interface
- Try endpoints directly from browser
- Built-in authentication configuration
- Request/response examples
- Schema validation

### OpenAPI Specification

Get the raw OpenAPI JSON spec:
```
https://your-domain.com/swagger/spec
```

**Usage:**
- Import into Postman, Insomnia, or other API clients
- Generate client SDKs using OpenAPI Generator
- Automated testing with OpenAPI validators
- API contract validation

### Key Features

1. **Annotations-Based:** OpenAPI specs generated from PHP attributes
2. **Schema Validation:** Automatic request/response validation
3. **Smart Caching:** Specs cached for performance
4. **Multiple Auth Methods:** 
   - X-API-Key + X-API-Secret headers
   - Bearer token authentication
5. **Complete Documentation:** All endpoints fully documented with examples

### Testing Guide

For detailed testing instructions, see: [`OPENAPI_TESTING_GUIDE.md`](OPENAPI_TESTING_GUIDE.md)

### Other Issues

None currently documented. Please report issues via GitHub or your support channel.

---

## 🗄️ Database Schema

The plugin creates 4 database tables during installation:

### `api_keys`
Stores API key credentials and configuration.

**Key Columns:**
- `id`, `name`, `description`, `key`, `secret`
- `status` (active/inactive/revoked)
- `rate_limit_per_minute`, `rate_limit_per_hour`, `rate_limit_per_day`
- `ip_whitelist`, `total_calls`
- `require_https`, `cors_enabled`, `cors_allowed_origins`
- `expires_at`, `last_used_at`, `created_at`, `updated_at`

### `api_logs`
Audit trail of all API requests and responses.

**Key Columns:**
- `id`, `api_key_id`, `method`, `endpoint`
- `request_body`, `response_code`, `response_body`, `response_time`
- `ip_address`, `user_agent`, `created_at`

### `api_rate_limits`
Tracks rate limit counters for each API key.

**Key Columns:**
- `id`, `api_key_id`
- `minute_window`, `hour_window`, `day_window`
- `minute_count`, `hour_count`, `day_count`
- `created_at`

### `api_settings`
Plugin configuration settings.

**Key Columns:**
- `id`, `setting_name`, `setting_value`, `type`, `deleted`

---

## 💻 Usage Examples

### Example 1: List Users

**Request:**
```bash
curl -X GET "https://your-domain.com/api/v1/users?limit=10&offset=0" \
  -H "X-API-Key: your_api_key_here" \
  -H "X-API-Secret: your_api_secret_here" \
  -H "Content-Type: application/json"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "users": [
      {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "user_type": "staff",
        "status": "active"
      }
    ],
    "pagination": {
      "total": 25,
      "count": 10,
      "per_page": 10,
      "current_page": 1,
      "total_pages": 3
    }
  },
  "meta": {
    "timestamp": "2025-10-14T12:34:56+00:00",
    "response_time": "45.23ms",
    "version": "1.0"
  }
}
```

### Example 2: Create Project

**Request:**
```bash
curl -X POST "https://your-domain.com/api/v1/projects" \
  -H "X-API-Key: your_api_key_here" \
  -H "X-API-Secret: your_api_secret_here" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "New Project",
    "description": "Project description",
    "client_id": 5,
    "start_date": "2025-10-14",
    "deadline": "2025-12-31"
  }'
```

**Response:**
```json
{
  "success": true,
  "data": {
    "project": {
      "id": 42,
      "title": "New Project",
      "description": "Project description",
      "client_id": 5,
      "start_date": "2025-10-14",
      "deadline": "2025-12-31",
      "created_at": "2025-10-14T12:34:56+00:00"
    }
  },
  "meta": {
    "timestamp": "2025-10-14T12:34:56+00:00",
    "response_time": "87.12ms",
    "version": "1.0"
  }
}
```

### Example 3: Convert Lead to Client

**Request:**
```bash
curl -X POST "https://your-domain.com/api/v1/clients/15/convert" \
  -H "X-API-Key: your_api_key_here" \
  -H "X-API-Secret: your_api_secret_here" \
  -H "Content-Type: application/json"
```

### Example 4: Mark Notification as Read

**Request:**
```bash
curl -X POST "https://your-domain.com/api/v1/notifications/123/mark_read" \
  -H "X-API-Key: your_api_key_here" \
  -H "X-API-Secret: your_api_secret_here" \
  -H "Content-Type: application/json"
```

### Example 5: Search and Filter

**Request:**
```bash
curl -X GET "https://your-domain.com/api/v1/tasks?limit=20&search=urgent&status=open" \
  -H "X-API-Key: your_api_key_here" \
  -H "X-API-Secret: your_api_secret_here"
```

---

## 📁 File Structure

```
Rest_api/
├── Controllers/
│   ├── Api/                      # API endpoint controllers
│   │   ├── Api_controller.php    # Base controller (auth, rate limiting, logging)
│   │   ├── Users.php              # Users endpoint
│   │   ├── Projects.php           # Projects endpoint
│   │   ├── Tasks.php              # Tasks endpoint
│   │   └── ... (13 more resources)
│   ├── Api_keys.php              # API key management controller
│   ├── Api_logs.php              # API logs controller
│   ├── Rest_api_settings.php    # Settings controller
│   └── Swagger.php               # Swagger documentation controller
├── Models/
│   ├── Api_keys_model.php        # API keys CRUD
│   ├── Api_logs_model.php        # Logs CRUD
│   ├── Api_rate_limits_model.php # Rate limiting
│   └── Api_settings_model.php    # Settings management
├── Libraries/
│   ├── Api_rate_limiter.php      # Rate limiting logic
│   └── Swagger_generator.php     # Swagger spec generation
├── Views/
│   └── settings/                 # Admin UI views
│       ├── tabs/                 # Main content tabs
│       ├── modals/               # Dialog forms
│       └── partials/             # Reusable components
├── Config/
│   ├── Routes.php                # API route definitions
│   ├── Rest_api.php              # Plugin configuration
│   └── Events.php                # Event hooks
├── Language/
│   └── english/
│       └── default_lang.php      # Translations
├── install/
│   ├── database.sql              # Database schema
│   └── do_install.php            # Installation script
├── updates/
│   ├── README.md                 # Update instructions
│   └── update_1.0.sql            # Version 1.0 migration
├── assets/
│   ├── css/                      # Stylesheets
│   └── js/                       # JavaScript files
├── index.php                     # Plugin entry point
└── README.md                     # This file
```

---

## 📜 Version History

### Version 0.1.6 - Beta 2 (Current)
- Initial beta release
- 16 resource endpoints with full CRUD support
- API Key + Secret authentication
- Rate limiting with per-minute/hour/day limits
- Request/response logging
- IP whitelisting (global + per-key)
- CORS configuration
- HTTPS enforcement option
- Admin UI for key and log management

### Version 1.0 Update (Database Migration Available)
See `updates/README.md` for details on:
- Per-key security settings (HTTPS, CORS overrides)
- Global IP whitelist
- Total API calls tracking
- Enhanced API keys table

---

## 🤝 Contributing

Contributions are welcome! This is a **Beta** plugin and needs community input.

### Ways to Contribute

1. **Report Bugs**
   - Open an issue with detailed reproduction steps
   - Include API request/response examples
   - Check logs in `writable/logs/` for errors

2. **Feature Requests**
   - Suggest new endpoints or features
   - Describe use cases and benefits

3. **Code Contributions**
   - Fork the repository
   - Create feature branch (`feature/your-feature-name`)
   - Follow existing code style and structure
   - Test thoroughly
   - Submit pull request with clear description

4. **Documentation**
   - Improve this README
   - Add usage examples
   - Translate language files

### Development Guidelines

- Follow CodeIgniter 4 conventions
- Use PSR-4 namespacing
- Maintain backward compatibility
- Add comments for complex logic
- Update changelog for modifications

### Contact

- **GitHub:** [x-xusko-x](https://github.com/x-xusko-x)
- **Issues:** Report via GitHub Issues

---

## 📄 License

This plugin is part of Rise CRM ecosystem. Please refer to Rise CRM license terms.

---

## 🙏 Acknowledgments

Built for the Rise CRM community. Special thanks to all contributors and beta testers.

---

## 📞 Support

### Getting Help

1. **Check Documentation:** Read this README thoroughly
2. **Review Logs:** Check `writable/logs/` for error messages
3. **Test with cURL:** Isolate issues with simple command-line tests
4. **Community:** Ask in Rise CRM community forums
5. **GitHub Issues:** Report bugs or request features

### Troubleshooting

**API returns 503 "API is currently disabled"**
- Solution: Go to Settings → REST API Settings and enable the API

**Authentication fails with 401**
- Verify API Key and Secret are correct
- Check key status is "active"
- Ensure key hasn't expired
- Verify IP is whitelisted (if configured)

**Rate limit exceeded (429)**
- Wait for rate limit window to reset
- Check rate limits in API key settings
- Consider increasing limits for your use case

**Swagger documentation not loading**
- Known issue - use manual testing instead
- Refer to this README for endpoint documentation

---

**Made with ❤️ for Rise CRM**

*Last Updated: October 2025*

