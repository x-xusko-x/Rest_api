<?php

namespace Rest_api\Schemas;

use OpenApi\Attributes as OA;

/**
 * Announcement Resource OpenAPI Schemas
 */
class Announcement
{
    /**
     * Announcement Model Schema
     */
    #[OA\Schema(
        schema: "Announcement",
        title: "Announcement",
        description: "Announcement resource model",
        required: ["id", "title"],
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "title", type: "string", example: "Important Announcement"),
            new OA\Property(property: "description", type: "string", nullable: true, example: "This is an important announcement for all staff."),
            new OA\Property(property: "start_date", type: "string", format: "date", nullable: true, example: "2025-10-15"),
            new OA\Property(property: "end_date", type: "string", format: "date", nullable: true, example: "2025-10-20"),
            new OA\Property(property: "share_with", type: "string", nullable: true, description: "Comma-separated list of user IDs or 'all'", example: "all"),
            new OA\Property(property: "created_by", type: "integer", example: 1),
            new OA\Property(property: "created_by_user", type: "string", nullable: true, example: "John Doe"),
            new OA\Property(property: "created_by_avatar", type: "string", nullable: true, example: "avatar.jpg"),
            new OA\Property(property: "created_date", type: "string", format: "date-time", nullable: true, example: "2025-10-15T10:30:00+00:00")
        ],
        type: "object"
    )]
    public static function getAnnouncementSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['id', 'title'],
            'properties' => [
                'id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'description' => ['type' => ['string', 'null']],
                'start_date' => ['type' => ['string', 'null'], 'format' => 'date'],
                'end_date' => ['type' => ['string', 'null'], 'format' => 'date'],
                'share_with' => ['type' => ['string', 'null']],
                'created_by' => ['type' => 'integer'],
                'created_by_user' => ['type' => ['string', 'null']],
                'created_by_avatar' => ['type' => ['string', 'null']],
                'created_date' => ['type' => ['string', 'null'], 'format' => 'date-time']
            ]
        ];
    }

    /**
     * Create Announcement Request Schema
     */
    #[OA\Schema(
        schema: "CreateAnnouncementRequest",
        title: "Create Announcement Request",
        description: "Request body for creating a new announcement",
        required: ["title"],
        properties: [
            new OA\Property(property: "title", type: "string", description: "Announcement title", example: "Important Announcement"),
            new OA\Property(property: "description", type: "string", description: "Announcement description/content", example: "This is an important announcement for all staff."),
            new OA\Property(property: "start_date", type: "string", format: "date", description: "Start date (YYYY-MM-DD)", example: "2025-10-15"),
            new OA\Property(property: "end_date", type: "string", format: "date", description: "End date (YYYY-MM-DD)", example: "2025-10-20"),
            new OA\Property(property: "share_with", type: "string", description: "Comma-separated user IDs or 'all'", example: "all")
        ],
        type: "object"
    )]
    public static function getCreateSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['title'],
            'properties' => [
                'title' => ['type' => 'string', 'minLength' => 1],
                'description' => ['type' => 'string'],
                'start_date' => ['type' => 'string', 'format' => 'date'],
                'end_date' => ['type' => 'string', 'format' => 'date'],
                'share_with' => ['type' => 'string']
            ]
        ];
    }

    /**
     * Update Announcement Request Schema
     */
    #[OA\Schema(
        schema: "UpdateAnnouncementRequest",
        title: "Update Announcement Request",
        description: "Request body for updating an announcement (all fields optional)",
        properties: [
            new OA\Property(property: "title", type: "string", example: "Updated Announcement Title"),
            new OA\Property(property: "description", type: "string", example: "Updated announcement content"),
            new OA\Property(property: "start_date", type: "string", format: "date", example: "2025-10-16"),
            new OA\Property(property: "end_date", type: "string", format: "date", example: "2025-10-21"),
            new OA\Property(property: "share_with", type: "string", example: "1,2,3")
        ],
        type: "object"
    )]
    public static function getUpdateSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string', 'minLength' => 1],
                'description' => ['type' => 'string'],
                'start_date' => ['type' => 'string', 'format' => 'date'],
                'end_date' => ['type' => 'string', 'format' => 'date'],
                'share_with' => ['type' => 'string']
            ]
        ];
    }

    /**
     * Announcement Response Schema
     */
    #[OA\Schema(
        schema: "AnnouncementResponse",
        title: "Announcement Response",
        description: "Single announcement response",
        required: ["success", "data", "meta"],
        properties: [
            new OA\Property(property: "success", type: "boolean", example: true),
            new OA\Property(
                property: "data",
                properties: [
                    new OA\Property(property: "announcement", ref: "#/components/schemas/Announcement")
                ],
                type: "object"
            ),
            new OA\Property(property: "meta", ref: "#/components/schemas/ResponseMeta")
        ],
        type: "object"
    )]
    public static function getResponseSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['success', 'data', 'meta'],
            'properties' => [
                'success' => ['type' => 'boolean', 'enum' => [true]],
                'data' => [
                    'type' => 'object',
                    'properties' => [
                        'announcement' => self::getAnnouncementSchema()
                    ]
                ],
                'meta' => Common::getResponseMetaSchema()
            ]
        ];
    }

    /**
     * Announcement List Response Schema
     */
    #[OA\Schema(
        schema: "AnnouncementListResponse",
        title: "Announcement List Response",
        description: "List of announcements with pagination",
        required: ["success", "data", "meta"],
        properties: [
            new OA\Property(property: "success", type: "boolean", example: true),
            new OA\Property(
                property: "data",
                properties: [
                    new OA\Property(
                        property: "announcements",
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Announcement")
                    ),
                    new OA\Property(property: "pagination", ref: "#/components/schemas/PaginationMeta")
                ],
                type: "object"
            ),
            new OA\Property(property: "meta", ref: "#/components/schemas/ResponseMeta")
        ],
        type: "object"
    )]
    public static function getListResponseSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['success', 'data', 'meta'],
            'properties' => [
                'success' => ['type' => 'boolean', 'enum' => [true]],
                'data' => [
                    'type' => 'object',
                    'properties' => [
                        'announcements' => [
                            'type' => 'array',
                            'items' => self::getAnnouncementSchema()
                        ],
                        'pagination' => Common::getPaginationSchema()
                    ]
                ],
                'meta' => Common::getResponseMetaSchema()
            ]
        ];
    }
}

