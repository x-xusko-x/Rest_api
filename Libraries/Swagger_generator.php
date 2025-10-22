<?php

namespace Rest_api\Libraries;

use OpenApi\Generator;
use OpenApi\Util;

/**
 * Swagger OpenAPI 3.0 Generator
 * 
 * Scans controllers and schemas with OpenAPI annotations and generates OpenAPI spec
 */
class Swagger_generator
{
    private $config;
    private $cache_path;

    public function __construct()
    {
        // Load composer autoloader for swagger-php
        require_once PLUGINPATH . 'Rest_api/vendor/autoload.php';

        $this->config = \Rest_api\Config\OpenAPI::getConfig();
        $this->cache_path = \Rest_api\Config\OpenAPI::getCachePath();
    }

    /**
     * Generate complete OpenAPI 3.0 specification
     * Uses caching to improve performance
     * 
     * @param bool $force_refresh Force regeneration (bypass cache)
     * @return array OpenAPI specification as array
     */
    public function generate($force_refresh = false)
    {
        // Check cache
        if (!$force_refresh && $this->config['cache_enabled'] && \Rest_api\Config\OpenAPI::isCacheValid()) {
            $cached = $this->_load_from_cache();
            if ($cached) {
                log_message('info', 'OpenAPI: Loaded spec from cache');
                return $cached;
            }
        }

        log_message('info', 'OpenAPI: Generating spec from annotations...');

        try {
            // Scan directories with OpenAPI annotations
            $openapi = Generator::scan($this->config['scan_directories'], [
                'logger' => new \Rest_api\Libraries\OpenAPI_logger()
            ]);

            // Convert to array
            $spec = json_decode($openapi->toJson(), true);

            // Add server URL dynamically
            if (!isset($spec['servers']) || empty($spec['servers'])) {
                $spec['servers'] = [
                    [
                        'url' => get_uri() . 'api/v1',
                        'description' => 'API V1 Server'
                    ]
                ];
            }

            // Save to cache
            if ($this->config['cache_enabled']) {
                $this->_save_to_cache($spec);
            }

            log_message('info', 'OpenAPI: Spec generated successfully');
            return $spec;

        } catch (\Exception $e) {
            log_message('error', 'OpenAPI: Failed to generate spec - ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());

            // Return minimal fallback spec
            return $this->_get_fallback_spec();
        }
    }

    /**
     * Load spec from cache
     */
    private function _load_from_cache()
    {
        if (!file_exists($this->cache_path)) {
            return null;
        }

        $content = file_get_contents($this->cache_path);
        if (!$content) {
            return null;
        }

        return json_decode($content, true);
    }

    /**
     * Save spec to cache
     */
    private function _save_to_cache($spec)
    {
        try {
            // Ensure cache directory exists
            $cache_dir = dirname($this->cache_path);
            if (!is_dir($cache_dir)) {
                mkdir($cache_dir, 0755, true);
            }

            file_put_contents($this->cache_path, json_encode($spec, JSON_PRETTY_PRINT));
            log_message('info', 'OpenAPI: Spec saved to cache');
        } catch (\Exception $e) {
            log_message('error', 'OpenAPI: Failed to save cache - ' . $e->getMessage());
        }
    }


    /**
     * Get fallback spec when generation fails
     */
    private function _get_fallback_spec()
    {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Rise CRM REST API',
                'description' => 'REST API for Rise CRM (Fallback Spec)',
                'version' => '1.0.0'
            ],
            'servers' => [
                [
                    'url' => get_uri() . 'api/v1',
                    'description' => 'API V1 Server'
                ]
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
                        'scheme' => 'bearer',
                        'bearerFormat' => 'base64(api_key:api_secret)'
                    ]
                ]
            ]
        ];
    }

    /**
     * Clear OpenAPI cache
     */
    public function clear_cache()
    {
        return \Rest_api\Config\OpenAPI::clearCache();
        }
    }

    /**
 * PSR-3 compatible logger for OpenAPI generator
 */
class OpenAPI_logger implements \Psr\Log\LoggerInterface
{
    public function emergency($message, array $context = []): void
    {
        log_message('emergency', 'OpenAPI: ' . $message);
    }

    public function alert($message, array $context = []): void
    {
        log_message('alert', 'OpenAPI: ' . $message);
    }

    public function critical($message, array $context = []): void
    {
        log_message('critical', 'OpenAPI: ' . $message);
    }

    public function error($message, array $context = []): void
    {
        log_message('error', 'OpenAPI: ' . $message);
    }

    public function warning($message, array $context = []): void
    {
        log_message('warning', 'OpenAPI: ' . $message);
    }

    public function notice($message, array $context = []): void
    {
        log_message('notice', 'OpenAPI: ' . $message);
    }

    public function info($message, array $context = []): void
    {
        log_message('info', 'OpenAPI: ' . $message);
    }

    public function debug($message, array $context = []): void
    {
        log_message('debug', 'OpenAPI: ' . $message);
    }

    public function log($level, $message, array $context = []): void
    {
        log_message($level, 'OpenAPI: ' . $message);
    }
}
