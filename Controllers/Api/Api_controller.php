<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\App_Controller;
use Rest_api\Models\Api_keys_model;
use Rest_api\Models\Api_logs_model;
use Rest_api\Models\Api_settings_model;
use Rest_api\Models\Api_permission_groups_model;
use Rest_api\Libraries\Api_rate_limiter;
use OpenApi\Attributes as OA;

/**
 * Base API Controller
 * Handles authentication, rate limiting, logging, and common API functionality
 * 
 * @OA\OpenApi(
 *     security={
 *         {"ApiKeyAuth": {}, "ApiSecretAuth": {}},
 *         {"BearerAuth": {}}
 *     }
 * )
 */
class Api_controller extends App_Controller {

    public $Api_keys_model;
    public $Api_logs_model;
    public $Api_settings_model;
    public $Api_permission_groups_model;
    public $Api_rate_limiter;
    
    public $api_key_info = null;
    public $permission_group = null;
    public $authenticated_user_id = null;
    public $start_time;
    public $request_method;
    public $request_uri;
    public $request_data;
    public $request; // Store request instance
    public $response; // Store response instance

    function __construct() {
        parent::__construct();
        
        // Get request and response instances via Services (more reliable in constructor)
        $this->request = \Config\Services::request();
        $this->response = \Config\Services::response();
        
        $this->start_time = microtime(true);
        $this->request_method = $this->request->getMethod();
        $this->request_uri = $this->request->getUri()->getPath();
        
        // Initialize models
        $this->Api_keys_model = new Api_keys_model();
        $this->Api_logs_model = new Api_logs_model();
        $this->Api_settings_model = new Api_settings_model();
        $this->Api_permission_groups_model = new Api_permission_groups_model();
        $this->Api_rate_limiter = new Api_rate_limiter();
        
        // Check if API is enabled
        if ($this->Api_settings_model->get_setting('api_enabled') != '1') {
            $this->_api_response([
                'success' => false,
                'message' => 'API is currently disabled'
            ], 503);
        }
        
        // Set CORS headers BEFORE authentication (required for preflight)
        $this->_set_cors_headers();
        
        // Handle OPTIONS preflight requests early (before authentication)
        if ($this->request_method === 'OPTIONS') {
            $this->response->setStatusCode(200);
            $this->response->send();
            exit;
        }
        
        // Authenticate (we need api_key_info for per-key settings)
        $this->_authenticate();
        
        // Load permission group if assigned
        $this->_load_permission_group();
        
        // Check endpoint permissions
        $this->_check_endpoint_permission();
        
        // Check HTTPS requirement (per-key overrides global)
        $require_https = $this->_get_effective_setting('require_https');
        if ($require_https && !$this->request->isSecure()) {
            $this->_api_response([
                'success' => false,
                'message' => 'HTTPS is required for API access'
            ], 403);
        }
        
        // Check rate limits
        $this->_check_rate_limit();
        
        // Get request data
        $this->request_data = $this->_get_request_data();
        
        // Auto-cleanup old logs (once per day)
        $this->_auto_cleanup_logs();
    }

    /**
     * Authenticate API request using API key and secret
     * Supports two methods:
     * 1. X-API-Key + X-API-Secret headers (legacy/current)
     * 2. Authorization: Bearer {base64(api_key:api_secret)} (OpenAPI standard)
     */
    private function _authenticate() {
        $api_key = null;
        $api_secret = null;
        
        // Method 1: Check for X-API-Key and X-API-Secret headers
        $api_key = $this->request->getHeaderLine('X-API-Key');
        $api_secret = $this->request->getHeaderLine('X-API-Secret');
        
        // Method 2: Check for Bearer token (for OpenAPI/standard auth)
        if (!$api_key || !$api_secret) {
            $auth_header = $this->request->getHeaderLine('Authorization');
            if ($auth_header && strpos($auth_header, 'Bearer ') === 0) {
                $bearer_token = substr($auth_header, 7);
                
                // Decode base64 token (format: base64(api_key:api_secret))
                $decoded = base64_decode($bearer_token);
                if ($decoded && strpos($decoded, ':') !== false) {
                    list($api_key, $api_secret) = explode(':', $decoded, 2);
                }
            }
        }
        
        if (!$api_key) {
            $this->_api_response([
                'success' => false,
                'message' => 'API key is required (use X-API-Key header or Bearer token)'
            ], 401);
        }
        
        if (!$api_secret) {
            $this->_api_response([
                'success' => false,
                'message' => 'API secret is required (use X-API-Secret header or Bearer token)'
            ], 401);
        }
        
        // Validate API key
        $key_info = $this->Api_keys_model->get_one_where(['key' => $api_key, 'deleted' => 0]);
        
        if (!$key_info) {
            // Log with api_key_id = 0 for invalid/unknown keys (security monitoring)
            $this->_log_request(0, 401, 'Invalid API key');
            $this->_api_response([
                'success' => false,
                'message' => 'Invalid API key'
            ], 401);
        }
        
        // Check if key is active
        if ($key_info->status !== 'active') {
            $status = $key_info->status ?: 'inactive';
            $this->_log_request($key_info->id, 401, 'API key is ' . $status);
            $this->_api_response([
                'success' => false,
                'message' => 'Invalid API key: ' . $status
            ], 401);
        }
        
        // Check if key has expired
        if ($key_info->expires_at) {
            // Parse as UTC since database stores UTC timestamps
            $expires_timestamp = strtotime($key_info->expires_at . ' UTC');
            $current_timestamp = time();
            
            if ($expires_timestamp < $current_timestamp) {
                $this->_log_request($key_info->id, 401, 'API key has expired');
                $this->_api_response([
                    'success' => false,
                    'message' => 'Invalid API key: expired'
                ], 401);
            }
        }
        
        // Verify secret (supports both Argon2ID and bcrypt with SHA-256 pre-hash)
        $secret_valid = false;
        
        // Try direct verification first (works for Argon2ID and standard bcrypt)
        if (password_verify($api_secret, $key_info->secret)) {
            $secret_valid = true;
        } 
        // Fallback: Try SHA-256 pre-hash for bcrypt (for keys hashed with fallback method)
        else if (!defined('PASSWORD_ARGON2ID')) {
            $hashed = hash('sha256', $api_secret, true);
            if (password_verify(base64_encode($hashed), $key_info->secret)) {
                $secret_valid = true;
            }
        }
        
        if (!$secret_valid) {
            $this->_log_request($key_info->id, 401, 'Invalid API secret');
            $this->_api_response([
                'success' => false,
                'message' => 'Invalid API secret'
            ], 401);
        }
        
        // Check IP whitelist (both global and per-key must pass if configured)
        $client_ip = $this->request->getIPAddress();
        
        // Check global IP whitelist
        $global_whitelist = $this->Api_settings_model->get_setting('default_ip_whitelist');
        if ($global_whitelist) {
            $global_ips = array_filter(array_map('trim', explode("\n", $global_whitelist)));
            if (!empty($global_ips) && !in_array($client_ip, $global_ips)) {
                $this->_log_request($key_info->id, 403, 'IP address not in global whitelist');
                $this->_api_response([
                    'success' => false,
                    'message' => 'IP address not authorized'
                ], 403);
            }
        }
        
        // Check per-key IP whitelist
        if ($key_info->ip_whitelist) {
            $allowed_ips = array_filter(array_map('trim', explode("\n", $key_info->ip_whitelist)));
            if (!in_array($client_ip, $allowed_ips)) {
                $this->_log_request($key_info->id, 403, 'IP address not in key whitelist');
                $this->_api_response([
                    'success' => false,
                    'message' => 'IP address not authorized'
                ], 403);
            }
        }
        
        // Store key info for later use
        $this->api_key_info = $key_info;
        
        // Set authenticated user ID (use the created_by from API key)
        $this->authenticated_user_id = $key_info->created_by;
        
        // Update last used timestamp and increment total calls
        $update_data = array(
            'last_used_at' => get_current_utc_time(),
            'total_calls' => ($key_info->total_calls ?? 0) + 1
        );
        $this->Api_keys_model->ci_save($update_data, $key_info->id);
    }

    /**
     * Load permission group if API key has one assigned
     */
    private function _load_permission_group() {
        if (!$this->api_key_info || !$this->api_key_info->permission_group_id) {
            // No permission group assigned = full access
            $this->permission_group = null;
            return;
        }

        $group = $this->Api_permission_groups_model->get_one($this->api_key_info->permission_group_id);
        
        if ($group && $group->deleted == 0) {
            $this->permission_group = $group;
        } else {
            // Permission group was deleted - treat as no permissions
            $this->permission_group = null;
        }
    }

    /**
     * Check if current endpoint and method are allowed by permission group
     */
    private function _check_endpoint_permission() {
        // If no permission group assigned, allow full access (backward compatibility)
        if (!$this->permission_group) {
            return;
        }

        // Parse permissions JSON
        $permissions = json_decode($this->permission_group->permissions, true);
        if (!$permissions) {
            // Invalid permissions JSON - deny access
            $this->_api_response([
                'success' => false,
                'message' => 'Permission configuration error'
            ], 403);
        }

        // Extract endpoint name from URI (e.g., /api/v1/users/123 -> users)
        $endpoint = $this->_extract_endpoint_from_uri($this->request_uri);
        
        if (!$endpoint) {
            // Unable to determine endpoint
            return;
        }

        // Map HTTP method to CRUD operation
        $operation = $this->_map_method_to_operation($this->request_method);
        
        if (!$operation) {
            // Unknown method
            return;
        }

        // Check if endpoint exists in permissions
        if (!isset($permissions[$endpoint])) {
            // Endpoint not in permissions - deny access
            $this->_api_response([
                'success' => false,
                'message' => 'Access denied: Endpoint ' . strtoupper($endpoint) . ' is not allowed for this API key'
            ], 403);
        }

        // Check if operation is allowed
        if (!isset($permissions[$endpoint][$operation]) || $permissions[$endpoint][$operation] !== true) {
            $this->_api_response([
                'success' => false,
                'message' => 'Access denied: ' . ucfirst($operation) . ' operation not allowed for ' . strtoupper($endpoint) . ' endpoint'
            ], 403);
        }

        // Permission granted - continue
    }

    /**
     * Extract endpoint name from URI
     * Example: /api/v1/users/123 -> users
     */
    private function _extract_endpoint_from_uri($uri) {
        // Remove leading/trailing slashes
        $uri = trim($uri, '/');
        
        // Split by /
        $parts = explode('/', $uri);
        
        // Expected format: api/v1/{endpoint}/...
        if (count($parts) >= 3 && $parts[0] === 'api' && $parts[1] === 'v1') {
            return $parts[2];
        }
        
        return null;
    }

    /**
     * Map HTTP method to CRUD operation
     */
    private function _map_method_to_operation($method) {
        $method = strtoupper($method);
        
        $map = array(
            'POST' => 'create',
            'GET' => 'read',
            'PUT' => 'update',
            'DELETE' => 'delete',
            'PATCH' => 'update'
        );
        
        return isset($map[$method]) ? $map[$method] : null;
    }

    /**
     * Check rate limits
     */
    private function _check_rate_limit() {
        if (!$this->api_key_info) {
            return;
        }
        
        $api_key_id = $this->api_key_info->id;
        $limits = [
            'per_minute' => $this->api_key_info->rate_limit_per_minute,
            'per_hour' => $this->api_key_info->rate_limit_per_hour,
            'per_day' => $this->api_key_info->rate_limit_per_day ?? 10000
        ];
        
        $exceeded = $this->Api_rate_limiter->check_rate_limit($api_key_id, $limits);
        
        if ($exceeded) {
            $this->_log_request($api_key_id, 429, 'Rate limit exceeded');
            $this->_api_response([
                'success' => false,
                'message' => 'Rate limit exceeded. Please try again later.',
                'limit' => $limits,
                'retry_after' => 60 // seconds
            ], 429);
        }
    }

    /**
     * Set CORS headers (per-key overrides global)
     */
    private function _set_cors_headers() {
        // Check if CORS is enabled (per-key overrides global)
        // Note: For preflight requests, we check global settings since API key is not authenticated yet
        $cors_enabled = false;
        if ($this->api_key_info) {
            $cors_enabled = $this->_get_effective_setting('cors_enabled');
        } else {
            $cors_enabled = $this->Api_settings_model->get_setting('cors_enabled') == '1';
        }
        
        if (!$cors_enabled) {
            return;
        }
        
        // Get allowed origins (per-key overrides global)
        $allowed_origins = null;
        if ($this->api_key_info && $this->api_key_info->cors_allowed_origins !== null) {
            $allowed_origins = $this->api_key_info->cors_allowed_origins;
        } else {
            $allowed_origins = $this->Api_settings_model->get_setting('cors_allowed_origins');
        }
        
        if ($allowed_origins) {
            $origins = array_filter(array_map('trim', explode("\n", $allowed_origins)));
            $origin = $this->request->getHeaderLine('Origin');
            
            if (in_array($origin, $origins) || in_array('*', $origins)) {
                $this->response->setHeader('Access-Control-Allow-Origin', $origin ?: '*');
                $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
                $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, X-API-Key, X-API-Secret, Authorization');
                $this->response->setHeader('Access-Control-Max-Age', '3600');
            }
        }
    }

    /**
     * Get request data from body
     */
    private function _get_request_data() {
        $content_type = $this->request->getHeaderLine('Content-Type');
        
        if (strpos($content_type, 'application/json') !== false) {
            $raw_data = $this->request->getBody();
            // Only decode if body is not empty
            if ($raw_data && $raw_data !== '') {
                return json_decode($raw_data, true) ?? [];
            }
            return [];
        }
        
        return $this->request->getPost() ?? [];
    }

    /**
     * Send API response
     */
    protected function _api_response($data, $status_code = 200, $log_message = null) {
        // Calculate response time
        $response_time = round((microtime(true) - $this->start_time) * 1000, 2); // in milliseconds
        
        // Add metadata to response
        $response = [
            'success' => $status_code >= 200 && $status_code < 300,
            'data' => $data,
            'meta' => [
                'timestamp' => date('c'),
                'response_time' => $response_time . 'ms',
                'version' => '1.0'
            ]
        ];
        
        // For error responses, move error details to root level
        if (!$response['success']) {
            $response['error'] = [
                'code' => $status_code,
                'message' => $data['message'] ?? 'An error occurred'
            ];
            // Include validation errors
            if (isset($data['errors'])) {
                $response['error']['details'] = $data['errors'];
            }
            // Include enhanced error details (database errors, missing fields, etc.)
            if (isset($data['error_details'])) {
                $response['error']['error_details'] = $data['error_details'];
            }
            unset($response['data']);
        }
        
        // Log the request (pass full response for detailed logging)
        if ($this->api_key_info) {
            $this->_log_request(
                $this->api_key_info->id,
                $status_code,
                $response, // Pass entire response array, not just message
                $response_time
            );
        }
        
        // Send response
        $this->response->setStatusCode($status_code);
        $this->response->setHeader('Content-Type', 'application/json');
        $this->response->setBody(json_encode($response, JSON_PRETTY_PRINT));
        $this->response->send();
        exit;
    }

    /**
     * Log API request with body truncation
     */
    private function _log_request($api_key_id, $status_code, $response_data = null, $response_time = null) {
        try {
            // Check if body logging is enabled
            $log_bodies_enabled = $this->Api_settings_model->get_setting('log_bodies_enabled');
            if ($log_bodies_enabled === '0') {
                // Skip body logging entirely
                $request_body_str = '[Body logging disabled]';
                $response_body_str = '[Body logging disabled]';
            } else {
                // Get max body size setting (default 10KB)
                $max_body_size = (int)($this->Api_settings_model->get_setting('log_max_body_size') ?: 10240);
                
                // Prepare request body (sanitize sensitive data)
                $request_body = $this->request_data;
                if (is_array($request_body) && isset($request_body['password'])) {
                    $request_body['password'] = '***HIDDEN***'; // Don't log passwords
                }
                if (is_array($request_body) && isset($request_body['api_secret'])) {
                    $request_body['api_secret'] = '***HIDDEN***'; // Don't log secrets
                }
                
                $request_body_str = json_encode($request_body);
                
                // Truncate request body if too large
                if (strlen($request_body_str) > $max_body_size) {
                    $request_body_str = substr($request_body_str, 0, $max_body_size) . '... [TRUNCATED - Original size: ' . strlen($request_body_str) . ' bytes]';
                }
                
                // Prepare response body (full response for detailed logs)
                $response_body = $response_data;
                if (is_string($response_data)) {
                    // Legacy: if string passed, use as-is
                    $response_body_str = $response_data;
                } else if (is_array($response_data)) {
                    // New: if array passed, it's the full response - encode it
                    $response_body_str = json_encode($response_data);
                } else {
                    $response_body_str = '';
                }
                
                // Truncate response body if too large
                if (strlen($response_body_str) > $max_body_size) {
                    $response_body_str = substr($response_body_str, 0, $max_body_size) . '... [TRUNCATED - Original size: ' . strlen($response_body_str) . ' bytes]';
                }
            }
            
            $log_data = array(
                'api_key_id' => $api_key_id ?: 0,
                'endpoint' => $this->request_uri,
                'method' => $this->request_method,
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => $this->request->getUserAgent(),
                'request_body' => $request_body_str,
                'response_code' => $status_code,
                'response_body' => $response_body_str,
                'response_time' => $response_time ?? round((microtime(true) - $this->start_time) * 1000, 2),
                'created_at' => get_current_utc_time()
            );
            
            // Clean data before saving (Rise CRM convention)
            $log_data = clean_data($log_data);
            
            $saved = $this->Api_logs_model->ci_save($log_data);
            
            if (!$saved) {
                log_message('error', 'API log ci_save returned false. Data: ' . json_encode($log_data));
            }
        } catch (\Exception $e) {
            // Don't fail the request if logging fails
            log_message('error', 'Failed to log API request: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Auto-cleanup old logs (runs once per day)
     */
    private function _auto_cleanup_logs() {
        try {
            // Check if cleanup was run today
            $last_cleanup = $this->Api_settings_model->get_setting('last_log_cleanup');
            $today = date('Y-m-d');
            
            if ($last_cleanup !== $today) {
                // Get retention days
                $retention_days = $this->Api_settings_model->get_setting('log_retention_days');
                if (!$retention_days || !is_numeric($retention_days)) {
                    $retention_days = 90;
                }
                
                // Clean old logs
                $deleted = $this->Api_logs_model->clean_old_logs($retention_days);
                
                // Update last cleanup date
                $this->Api_settings_model->save_setting('last_log_cleanup', $today);
                
                // Log the cleanup
                if ($deleted > 0) {
                    log_message('info', "API: Auto-cleanup deleted $deleted old log(s) older than $retention_days days");
                }
            }
        } catch (\Exception $e) {
            // Don't fail requests if cleanup fails
            log_message('error', 'API: Auto-cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate required fields in request data
     */
    protected function _validate_required_fields($fields) {
        $missing = [];
        
        foreach ($fields as $field) {
            if (!isset($this->request_data[$field]) || $this->request_data[$field] === '') {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->_api_response([
                'message' => 'Missing required fields',
                'errors' => $missing
            ], 400);
        }
    }

    /**
     * Check if authenticated user has admin privileges
     */
    protected function _require_admin() {
        // For now, only admin API keys can access the API
        // This can be extended to check user permissions based on the API key's associated user
        return true;
    }

    /**
     * Sanitize output - remove sensitive fields
     */
    protected function _sanitize_user_data($user) {
        $sensitive_fields = ['password', 'password_reset_token', 'password_reset_token_expires'];
        
        if (is_array($user)) {
            foreach ($sensitive_fields as $field) {
                unset($user[$field]);
            }
            return $user;
        } elseif (is_object($user)) {
            $user = (array) $user;
            foreach ($sensitive_fields as $field) {
                unset($user[$field]);
            }
            return (object) $user;
        }
        
        return $user;
    }

    /**
     * Convert object/array to clean array
     */
    protected function _to_array($data) {
        return json_decode(json_encode($data), true);
    }

    /**
     * Get effective setting value (per-key overrides global)
     * 
     * @param string $setting_name Setting name (require_https, cors_enabled)
     * @return bool Effective setting value
     */
    private function _get_effective_setting($setting_name) {
        // If API key has the setting explicitly set (not NULL), use it
        if ($this->api_key_info && property_exists($this->api_key_info, $setting_name)) {
            $key_setting = $this->api_key_info->$setting_name;
            if ($key_setting !== null) {
                return $key_setting == '1' || $key_setting === 1 || $key_setting === true;
            }
        }
        
        // Otherwise, use global setting
        return $this->Api_settings_model->get_setting($setting_name) == '1';
    }

    /**
     * Get standardized pagination parameters from request
     * Enforces global limits and provides defaults
     * 
     * @param int $default_limit Default limit if not specified
     * @param int $max_limit Maximum allowed limit
     * @return array ['limit' => int, 'offset' => int, 'page' => int]
     */
    protected function _get_pagination_params($default_limit = 50, $max_limit = 100) {
        // Support both offset-based and page-based pagination
        $limit = (int)($this->request->getGet('limit') ?? $default_limit);
        $offset = (int)($this->request->getGet('offset') ?? 0);
        $page = (int)($this->request->getGet('page') ?? null);
        
        // Enforce limits
        $limit = min(max($limit, 1), $max_limit);
        $offset = max($offset, 0);
        
        // If page is specified, calculate offset from page
        if ($page !== null && $page > 0) {
            $offset = ($page - 1) * $limit;
        }
        
        return [
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page ?? (int)floor($offset / $limit) + 1
        ];
    }

    /**
     * Send standardized list/collection response
     * Automatically handles single-object unwrapping when limit=1
     * 
     * @param array $items Array of items
     * @param int $total_count Total count of items (before pagination)
     * @param string $resource_name Resource name (e.g., 'expenses', 'users')
     * @param array $pagination Pagination params from _get_pagination_params()
     * @param bool $unwrap_single If true, return single object when limit=1
     */
    protected function _api_list_response($items, $total_count, $resource_name, $pagination, $unwrap_single = true) {
        $limit = $pagination['limit'];
        $offset = $pagination['offset'];
        $page = $pagination['page'];
        
        $returned_count = count($items);
        
        // If limit=1 and unwrap_single=true, return single object instead of array
        if ($unwrap_single && $limit === 1 && $returned_count === 1) {
            $this->_api_response([
                rtrim($resource_name, 's') => $items[0], // Singular: expenses -> expense
                'meta' => [
                    'total' => $total_count,
                    'note' => 'Single resource returned due to limit=1'
                ]
            ], 200);
        }
        
        // Standard list response
        $this->_api_response([
            $resource_name => $items,
            'pagination' => [
                'total' => $total_count,
                'count' => $returned_count,
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => (int)ceil($total_count / $limit),
                'offset' => $offset,
                'has_more' => ($offset + $returned_count) < $total_count
            ]
        ], 200);
    }

    /**
     * Send standardized single-resource response
     * 
     * @param mixed $item Single item/resource
     * @param string $resource_name Resource name in singular (e.g., 'expense', 'user')
     */
    protected function _api_single_response($item, $resource_name) {
        if (!$item) {
            $this->_api_response([
                'message' => ucfirst($resource_name) . ' not found'
            ], 404);
        }
        
        $this->_api_response([
            $resource_name => $item
        ], 200);
    }

    /**
     * Send standardized created response
     * 
     * @param mixed $item Created item
     * @param string $resource_name Resource name in singular
     */
    protected function _api_created_response($item, $resource_name) {
        $this->_api_response([
            'message' => ucfirst($resource_name) . ' created successfully',
            $resource_name => $item
        ], 201);
    }

    /**
     * Send standardized updated response
     * 
     * @param mixed $item Updated item
     * @param string $resource_name Resource name in singular
     */
    protected function _api_updated_response($item, $resource_name) {
        $this->_api_response([
            'message' => ucfirst($resource_name) . ' updated successfully',
            $resource_name => $item
        ], 200);
    }

    /**
     * Send standardized deleted response
     * 
     * @param string $resource_name Resource name in singular
     */
    protected function _api_deleted_response($resource_name) {
        $this->_api_response([
            'message' => ucfirst($resource_name) . ' deleted successfully'
        ], 200);
    }

    /**
     * Send standardized error response
     * 
     * @param string $message Error message
     * @param int $status_code HTTP status code
     * @param array $details Additional error details
     */
    protected function _api_error_response($message, $status_code = 400, $details = []) {
        $response = ['message' => $message];
        if (!empty($details)) {
            $response['errors'] = $details;
        }
        $this->_api_response($response, $status_code);
    }

    /**
     * Validate request data against OpenAPI schema
     * 
     * @param string $resource_name Resource name (e.g., 'User', 'Client')
     * @param string $schema_type Schema type ('Create', 'Update')
     * @return void Sends error response if validation fails
     */
    protected function _validate_request_schema(string $resource_name, string $schema_type): void {
        $config = \Rest_api\Config\OpenAPI::getConfig();
        
        if (!$config['validation_enabled']) {
            return; // Validation disabled
        }

        require_once PLUGINPATH . 'Rest_api/vendor/autoload.php';
        $validator = new \Rest_api\Libraries\OpenAPI_validator();
        
        $schema = $validator->getSchema($resource_name, $schema_type);
        
        if (!$schema) {
            log_message('warning', "OpenAPI: Schema not found for {$resource_name}::{$schema_type}");
            return; // Schema not found, skip validation
        }

        $validation_result = $validator->validateRequest($this->request_data, $schema);
        
        if (!$validation_result['valid']) {
            $formatted_errors = $validator->formatValidationErrors($validation_result['errors']);
            
            $this->_api_response([
                'message' => 'Request validation failed',
                'errors' => $formatted_errors
            ], 400);
        }
    }

    /**
     * Validate response data against OpenAPI schema (development mode only)
     * 
     * @param mixed $data Response data
     * @param string $resource_name Resource name
     * @param string $response_type Response type ('Response', 'ListResponse')
     * @return void Logs warning if validation fails
     */
    protected function _validate_response_schema($data, string $resource_name, string $response_type): void {
        $config = \Rest_api\Config\OpenAPI::getConfig();
        
        if (!$config['validate_responses']) {
            return; // Response validation disabled (only in dev mode)
        }

        require_once PLUGINPATH . 'Rest_api/vendor/autoload.php';
        $validator = new \Rest_api\Libraries\OpenAPI_validator();
        
        $schema = $validator->getSchema($resource_name, $response_type);
        
        if (!$schema) {
            return; // Schema not found
        }

        $validation_result = $validator->validateResponse($data, $schema);
        
        if (!$validation_result['valid']) {
            log_message('error', "OpenAPI: Response validation failed for {$resource_name}::{$response_type}");
            log_message('error', 'Validation errors: ' . json_encode($validation_result['errors']));
        }
    }
}

