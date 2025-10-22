# Rise CRM REST API Plugin

**Version:** 0.1.6 - Beta 2  
**API Version:** 1.0.0  
**Status:** BETA - Production use at your own risk
---

## Beta Notice

This plugin is currently in **Beta** status. While functional, it may contain bugs or incomplete features. Contributions, bug reports, and feedback are highly welcomed!

## Requirements

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

## Installation

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

## API Endpoints

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

**Security Warning:** API secrets are shown only once during creation. Store them securely!

## Features

### Security Features

- **API Key + Secret Authentication** - Dual-factor authentication for API access
- **IP Whitelisting** - Global and per-key IP restrictions
- **HTTPS Enforcement** - Optional requirement for secure connections
- **Password Hashing** - API secrets stored using bcrypt
- **Request Validation** - Input sanitization and validation
- **Sensitive Data Filtering** - Passwords and secrets excluded from logs

### Rate Limiting

- **Multi-Tier Limits** - Per-minute, per-hour, and per-day limits
- **Per-Key Configuration** - Custom limits for each API key
- **Automatic Enforcement** - Requests blocked when limits exceeded
- **Configurable Defaults** - Global fallback limits

### Monitoring & Logging

- **Request/Response Logging** - Complete audit trail of API usage
- **Detailed Metrics** - Response times, status codes, endpoints
- **IP and User Agent Tracking** - Client identification
- **Automatic Log Cleanup** - Configurable retention period (default: 90 days)
- **Total Calls Counter** - Per-key usage statistics

### CORS Support

- **Configurable Origins** - Whitelist specific domains
- **Wildcard Support** - Allow all origins with `*`
- **Per-Key Overrides** - Custom CORS settings per API key
- **Preflight Handling** - Automatic OPTIONS request handling

### Flexibility

- **Per-Key Security Settings** - Override global settings per API key
- **Expiration Dates** - Temporary API keys with auto-expiration
- **Status Management** - Active, Inactive, Revoked states
- **Assignment Types** - Internal or External API keys

---

## OpenAPI & Swagger Documentation

### Swagger UI

**Status:** Not implemented (Contributions welcomed)

---

## Contributing

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


**Issues:** Report via GitHub Issues

---

## 📄 License

This plugin is part of Rise CRM ecosystem. Please refer to Rise CRM license terms.

---

**Made with ❤️ for Rise CRM**

*Last Updated: October 2025*

