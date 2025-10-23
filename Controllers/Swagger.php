<?php

namespace Rest_api\Controllers;

use App\Controllers\Security_Controller;
use Rest_api\Libraries\Swagger_generator;

/**
 * Swagger Documentation Controller
 * 
 * Serves Swagger/OpenAPI specification
 */
class Swagger extends Security_Controller
{
    protected $Swagger_generator;
    protected $response;
    protected $cache_file;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_admin();
        
        $this->Swagger_generator = new Swagger_generator();
        $this->response = \Config\Services::response();
        
        // Simple file-based caching
        $this->cache_file = APPPATH . '../writable/cache/swagger_spec.json';
    }

    /**
     * Serve OpenAPI JSON specification
     * @param bool $force_refresh Force regeneration (bypass cache)
     */
    public function spec()
    {
        // Set proper status code even for errors
        $this->response->setStatusCode(200);
        
        try {
            $force_refresh = $this->request->getGet('force_refresh') === '1';
            
            // Generate spec (uses internal caching)
            $spec = $this->Swagger_generator->generate($force_refresh);
            
            $this->_output_json($spec);
            
        } catch (\Throwable $e) {
            log_message('error', 'Swagger spec generation error: ' . $e->getMessage());
            log_message('error', 'Error in: ' . $e->getFile() . ':' . $e->getLine());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $error_spec = [
                'openapi' => '3.0.0',
                'info' => [
                    'title' => 'REST API',
                    'description' => 'Error generating specification',
                    'version' => '1.0.0'
                ],
                'paths' => [],
                'components' => [
                    'securitySchemes' => [
                        'ApiKeyAuth' => [
                            'type' => 'apiKey',
                            'in' => 'header',
                            'name' => 'X-API-Key'
                        ],
                        'ApiSecretAuth' => [
                            'type' => 'apiKey',
                            'in' => 'header',
                            'name' => 'X-API-Secret'
                        ],
                        'BearerAuth' => [
                            'type' => 'http',
                            'scheme' => 'bearer'
                        ]
                    ]
                ],
                'x-error' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'hint' => 'Check server logs for details. Common causes: missing composer vendor directory, PHP version incompatibility, or file permissions.'
                ]
            ];
            
            $this->_output_json($error_spec);
        }
    }
    
    /**
     * Debug endpoint to check OpenAPI setup
     */
    public function debug()
    {
        $config = \Rest_api\Config\OpenAPI::getConfig();
        
        $checks = [
            'composer_installed' => file_exists(PLUGINPATH . 'Rest_api/vendor/autoload.php'),
            'vendor_path' => PLUGINPATH . 'Rest_api/vendor/',
            'cache_dir_exists' => is_dir(PLUGINPATH . 'Rest_api/writable/cache/'),
            'cache_dir_writable' => is_writable(PLUGINPATH . 'Rest_api/writable/') || is_writable(PLUGINPATH . 'Rest_api/'),
            'schemas_dir_exists' => is_dir(PLUGINPATH . 'Rest_api/Schemas/'),
            'php_version' => PHP_VERSION,
            'openapi_config_exists' => file_exists(PLUGINPATH . 'Rest_api/Config/OpenAPI.php'),
            'swagger_generator_exists' => file_exists(PLUGINPATH . 'Rest_api/Libraries/Swagger_generator.php'),
            'scan_directories' => $config['scan_directories'],
            'scan_directories_exist' => []
        ];
        
        // Check each scan directory
        foreach ($config['scan_directories'] as $dir) {
            $checks['scan_directories_exist'][$dir] = [
                'exists' => is_dir($dir),
                'readable' => is_readable($dir),
                'files' => is_dir($dir) ? count(glob($dir . '*.php')) : 0
            ];
        }
        
        // Try to load classes
        try {
            if ($checks['composer_installed']) {
                require_once PLUGINPATH . 'Rest_api/vendor/autoload.php';
                $checks['swagger_php_loaded'] = class_exists('OpenApi\Generator');
                $checks['json_schema_loaded'] = class_exists('JsonSchema\Validator');
                
                // Try a test scan
                try {
                    $openapi = \OpenApi\Generator::scan($config['scan_directories']);
                    $spec = json_decode($openapi->toJson(), true);
                    $checks['test_scan'] = [
                        'success' => true,
                        'paths_found' => count($spec['paths'] ?? []),
                        'schemas_found' => count($spec['components']['schemas'] ?? [])
                    ];
                } catch (\Throwable $e) {
                    $checks['test_scan'] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            } else {
                $checks['swagger_php_loaded'] = false;
                $checks['json_schema_loaded'] = false;
            }
        } catch (\Throwable $e) {
            $checks['autoload_error'] = $e->getMessage();
        }
        
        // Check for annotation syntax
        try {
            $checks['php_attributes_supported'] = version_compare(PHP_VERSION, '8.0.0', '>=');
        } catch (\Throwable $e) {
            $checks['php_attributes_supported'] = false;
        }
        
        $this->response->setHeader('Content-Type', 'application/json');
        echo json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Serve Swagger UI interface
     */
    public function ui()
    {
        $data = [
            'spec_url' => get_uri() . 'swagger/spec'
        ];
        
        return view('Rest_api\Views\swagger\ui', $data);
    }

    /**
     * Clear cache (force regeneration)
     */
    public function clear_cache()
    {
        try {
            $this->Swagger_generator->clear_cache();
            
            // Also clear old cache file if exists
            if (file_exists($this->cache_file)) {
                unlink($this->cache_file);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'OpenAPI cache cleared successfully. Spec will be regenerated on next request.'
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Output JSON response
     */
    private function _output_json($data)
    {
        $this->response->setHeader('Content-Type', 'application/json');
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        
        // Ensure empty arrays become objects for OpenAPI compliance
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        // Fix empty paths array to be an object
        $json = preg_replace('/"paths":\s*\[\s*\]/', '"paths": {}', $json);
        
        echo $json;
        exit;
    }
}

