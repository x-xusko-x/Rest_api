<?php

namespace Rest_api\Schemas;

use OpenApi\Attributes as OA;

/**
 * Common OpenAPI Schema Definitions
 * 
 * Reusable schemas for responses, pagination, errors, etc.
 */
class Common
{
    /**
     * Get Response Meta schema
     */
    #[OA\Schema(
        schema: "ResponseMeta",
        title: "Response Metadata",
        description: "Standard metadata included in all API responses",
        required: ["timestamp", "response_time", "version"],
        properties: [
            new OA\Property(
                property: "timestamp",
                type: "string",
                format: "date-time",
                description: "ISO 8601 timestamp of response",
                example: "2025-10-15T12:34:56+00:00"
            ),
            new OA\Property(
                property: "response_time",
                type: "string",
                description: "Response time in milliseconds",
                example: "45.23ms"
            ),
            new OA\Property(
                property: "version",
                type: "string",
                description: "API version",
                example: "1.0"
            )
        ],
        type: "object"
    )]
    public static function getResponseMetaSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['timestamp', 'response_time', 'version'],
            'properties' => [
                'timestamp' => [
                    'type' => 'string',
                    'format' => 'date-time',
                    'description' => 'ISO 8601 timestamp of response'
                ],
                'response_time' => [
                    'type' => 'string',
                    'description' => 'Response time in milliseconds'
                ],
                'version' => [
                    'type' => 'string',
                    'description' => 'API version'
                ]
            ]
        ];
    }

    /**
     * Get Pagination Meta schema
     */
    #[OA\Schema(
        schema: "PaginationMeta",
        title: "Pagination Metadata",
        description: "Pagination information for list responses",
        required: ["total", "count", "per_page", "current_page", "total_pages", "offset", "has_more"],
        properties: [
            new OA\Property(property: "total", type: "integer", description: "Total number of items available", example: 150),
            new OA\Property(property: "count", type: "integer", description: "Number of items in current response", example: 50),
            new OA\Property(property: "per_page", type: "integer", description: "Items per page", example: 50),
            new OA\Property(property: "current_page", type: "integer", description: "Current page number", example: 1),
            new OA\Property(property: "total_pages", type: "integer", description: "Total number of pages", example: 3),
            new OA\Property(property: "offset", type: "integer", description: "Current offset", example: 0),
            new OA\Property(property: "has_more", type: "boolean", description: "Whether more pages are available", example: true)
        ],
        type: "object"
    )]
    public static function getPaginationSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['total', 'count', 'per_page', 'current_page', 'total_pages', 'offset', 'has_more'],
            'properties' => [
                'total' => ['type' => 'integer', 'description' => 'Total number of items'],
                'count' => ['type' => 'integer', 'description' => 'Number of items in current response'],
                'per_page' => ['type' => 'integer', 'description' => 'Items per page'],
                'current_page' => ['type' => 'integer', 'description' => 'Current page number'],
                'total_pages' => ['type' => 'integer', 'description' => 'Total number of pages'],
                'offset' => ['type' => 'integer', 'description' => 'Current offset'],
                'has_more' => ['type' => 'boolean', 'description' => 'Whether more pages are available']
            ]
        ];
    }

    /**
     * Get Error Response schema
     */
    #[OA\Schema(
        schema: "ErrorResponse",
        title: "Error Response",
        description: "Standard error response structure",
        required: ["success", "error", "meta"],
        properties: [
            new OA\Property(property: "success", type: "boolean", example: false),
            new OA\Property(
                property: "error",
                properties: [
                    new OA\Property(property: "code", type: "integer", example: 400),
                    new OA\Property(property: "message", type: "string", example: "Validation failed"),
                    new OA\Property(property: "details", type: "object", example: ["field1" => "Field1 is required"])
                ],
                type: "object"
            ),
            new OA\Property(property: "meta", ref: "#/components/schemas/ResponseMeta")
        ],
        type: "object"
    )]
    public static function getErrorResponseSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['success', 'error', 'meta'],
            'properties' => [
                'success' => ['type' => 'boolean', 'enum' => [false]],
                'error' => [
                    'type' => 'object',
                    'properties' => [
                        'code' => ['type' => 'integer'],
                        'message' => ['type' => 'string'],
                        'details' => ['type' => 'object']
                    ]
                ],
                'meta' => self::getResponseMetaSchema()
            ]
        ];
    }

    /**
     * Get Success Response schema (generic)
     */
    #[OA\Schema(
        schema: "SuccessResponse",
        title: "Success Response",
        description: "Standard success response structure",
        required: ["success", "data", "meta"],
        properties: [
            new OA\Property(property: "success", type: "boolean", example: true),
            new OA\Property(property: "data", type: "object", description: "Response data"),
            new OA\Property(property: "meta", ref: "#/components/schemas/ResponseMeta")
        ],
        type: "object"
    )]
    public static function getSuccessResponseSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['success', 'data', 'meta'],
            'properties' => [
                'success' => ['type' => 'boolean', 'enum' => [true]],
                'data' => ['type' => 'object'],
                'meta' => self::getResponseMetaSchema()
            ]
        ];
    }

    /**
     * Get Validation Error schema
     */
    #[OA\Schema(
        schema: "ValidationError",
        title: "Validation Error",
        description: "Validation error details",
        required: ["property", "message"],
        properties: [
            new OA\Property(property: "property", type: "string", description: "Field name that failed validation", example: "email"),
            new OA\Property(property: "message", type: "string", description: "Validation error message", example: "Email is required"),
            new OA\Property(property: "constraint", type: "string", description: "Validation constraint that failed", example: "required")
        ],
        type: "object"
    )]
    public static function getValidationErrorSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['property', 'message'],
            'properties' => [
                'property' => ['type' => 'string'],
                'message' => ['type' => 'string'],
                'constraint' => ['type' => 'string']
            ]
        ];
    }

    /**
     * Common query parameters for list endpoints
     */
    public static function getListParameters(): array
    {
        return [
            [
                'name' => 'limit',
                'in' => 'query',
                'description' => 'Number of items to return (max: 100)',
                'required' => false,
                'schema' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                    'default' => 50
                ]
            ],
            [
                'name' => 'offset',
                'in' => 'query',
                'description' => 'Number of items to skip (for pagination)',
                'required' => false,
                'schema' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'default' => 0
                ]
            ],
            [
                'name' => 'page',
                'in' => 'query',
                'description' => 'Page number (alternative to offset)',
                'required' => false,
                'schema' => [
                    'type' => 'integer',
                    'minimum' => 1
                ]
            ],
            [
                'name' => 'search',
                'in' => 'query',
                'description' => 'Search term for filtering results',
                'required' => false,
                'schema' => [
                    'type' => 'string'
                ]
            ]
        ];
    }

    /**
     * Common responses for all endpoints
     */
    public static function getCommonResponses(): array
    {
        return [
            '401' => [
                'description' => 'Unauthorized - Invalid or missing API credentials',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                    ]
                ]
            ],
            '403' => [
                'description' => 'Forbidden - Insufficient permissions',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                    ]
                ]
            ],
            '429' => [
                'description' => 'Too Many Requests - Rate limit exceeded',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                    ]
                ]
            ],
            '500' => [
                'description' => 'Internal Server Error',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                    ]
                ]
            ]
        ];
    }
}

