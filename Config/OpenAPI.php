<?php

namespace Rest_api\Config;

use OpenApi\Attributes as OA;

/**
 * OpenAPI 3.0 Base Configuration for Rise CRM REST API
 * 
 * This class defines the root OpenAPI specification metadata
 */
#[OA\Info(
    version: "1.0.0",
    title: "Rise CRM REST API",
    description: "Comprehensive REST API for Rise CRM with full CRUD operations on all major resources",
    contact: new OA\Contact(
        name: "API Support",
        email: "api@risecrm.com"
    ),
    license: new OA\License(
        name: "Rise CRM License",
        url: "https://codecanyon.net/licenses/standard"
    )
)]
#[OA\Server(
    url: "/api/v1",
    description: "API V1 Server"
)]
#[OA\SecurityScheme(
    securityScheme: "ApiKeyAuth",
    type: "apiKey",
    name: "X-API-Key",
    in: "header",
    description: "API Key for authentication"
)]
#[OA\SecurityScheme(
    securityScheme: "ApiSecretAuth",
    type: "apiKey",
    name: "X-API-Secret",
    in: "header",
    description: "API Secret for authentication"
)]
#[OA\SecurityScheme(
    securityScheme: "BearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "base64(api_key:api_secret)",
    description: "Bearer token authentication (base64 encoded api_key:api_secret)"
)]
#[OA\Tag(
    name: "Users",
    description: "User management operations"
)]
#[OA\Tag(
    name: "Clients",
    description: "Client and lead management operations"
)]
#[OA\Tag(
    name: "Projects",
    description: "Project management operations"
)]
#[OA\Tag(
    name: "Tasks",
    description: "Task management operations"
)]
#[OA\Tag(
    name: "Invoices",
    description: "Invoice management operations"
)]
#[OA\Tag(
    name: "Estimates",
    description: "Estimate management operations"
)]
#[OA\Tag(
    name: "Proposals",
    description: "Proposal management operations"
)]
#[OA\Tag(
    name: "Contracts",
    description: "Contract management operations"
)]
#[OA\Tag(
    name: "Expenses",
    description: "Expense management operations"
)]
#[OA\Tag(
    name: "Tickets",
    description: "Support ticket management operations"
)]
#[OA\Tag(
    name: "Timesheets",
    description: "Timesheet management operations"
)]
#[OA\Tag(
    name: "Events",
    description: "Event management operations"
)]
#[OA\Tag(
    name: "Notes",
    description: "Note management operations"
)]
#[OA\Tag(
    name: "Messages",
    description: "Message management operations"
)]
#[OA\Tag(
    name: "Notifications",
    description: "Notification management operations"
)]
#[OA\Tag(
    name: "Announcements",
    description: "Announcement management operations"
)]
#[OA\Response(
    response: "Unauthorized",
    description: "Unauthorized - Invalid or missing API credentials",
    content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
)]
#[OA\Response(
    response: "Forbidden",
    description: "Forbidden - Insufficient permissions",
    content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
)]
#[OA\Response(
    response: "TooManyRequests",
    description: "Too Many Requests - Rate limit exceeded",
    content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
)]
#[OA\Response(
    response: "InternalServerError",
    description: "Internal Server Error",
    content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
)]
class OpenAPI
{
    /**
     * Get OpenAPI configuration settings
     */
    public static function getConfig(): array
    {
        return [
            'cache_enabled' => true,
            'cache_path' => PLUGINPATH . 'Rest_api/writable/cache/',
            'cache_file' => 'openapi.json',
            'scan_directories' => [
                PLUGINPATH . 'Rest_api/Controllers/Api/',
                PLUGINPATH . 'Rest_api/Schemas/',
                PLUGINPATH . 'Rest_api/Config/',
            ],
            'validation_enabled' => true, // Enable request validation
            'validate_responses' => ENVIRONMENT === 'development', // Only validate responses in dev mode
        ];
    }

    /**
     * Get cache file path
     */
    public static function getCachePath(): string
    {
        $config = self::getConfig();
        return $config['cache_path'] . $config['cache_file'];
    }

    /**
     * Check if cache is valid
     */
    public static function isCacheValid(): bool
    {
        $cache_path = self::getCachePath();
        
        if (!file_exists($cache_path)) {
            return false;
        }

        // Check if any source files have been modified since cache was generated
        $cache_time = filemtime($cache_path);
        $config = self::getConfig();
        
        foreach ($config['scan_directories'] as $dir) {
            if (self::directoryModifiedAfter($dir, $cache_time)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if directory has been modified after a given timestamp
     */
    private static function directoryModifiedAfter(string $dir, int $timestamp): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                if ($file->getMTime() > $timestamp) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Clear OpenAPI cache
     */
    public static function clearCache(): bool
    {
        $cache_path = self::getCachePath();
        
        if (file_exists($cache_path)) {
            return unlink($cache_path);
        }

        return true;
    }
}

