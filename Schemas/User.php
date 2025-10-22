<?php

namespace Rest_api\Schemas;

use OpenApi\Attributes as OA;

/**
 * User Resource OpenAPI Schemas
 */
class User
{
    /**
     * User Model Schema
     */
    #[OA\Schema(
        schema: "User",
        title: "User",
        description: "User resource model",
        required: ["id", "first_name", "last_name", "email", "user_type"],
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "first_name", type: "string", example: "John"),
            new OA\Property(property: "last_name", type: "string", example: "Doe"),
            new OA\Property(property: "email", type: "string", format: "email", example: "john.doe@example.com"),
            new OA\Property(property: "user_type", type: "string", enum: ["staff", "client"], example: "staff"),
            new OA\Property(property: "job_title", type: "string", nullable: true, example: "Developer"),
            new OA\Property(property: "phone", type: "string", nullable: true, example: "+1234567890"),
            new OA\Property(property: "skype", type: "string", nullable: true, example: "john.doe"),
            new OA\Property(property: "gender", type: "string", enum: ["male", "female", "other"], nullable: true, example: "male"),
            new OA\Property(property: "address", type: "string", nullable: true, example: "123 Main St"),
            new OA\Property(property: "alternative_address", type: "string", nullable: true, example: "456 Oak Ave"),
            new OA\Property(property: "status", type: "string", enum: ["active", "inactive"], example: "active"),
            new OA\Property(property: "role_id", type: "integer", nullable: true, example: 1),
            new OA\Property(property: "is_admin", type: "integer", enum: [0, 1], example: 0),
            new OA\Property(property: "client_id", type: "integer", nullable: true, description: "Client ID for client type users", example: null),
            new OA\Property(property: "note", type: "string", nullable: true, example: "Important user note"),
            new OA\Property(property: "disable_login", type: "integer", enum: [0, 1], nullable: true, example: 0),
            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2025-10-15T10:30:00+00:00")
        ],
        type: "object"
    )]
    public static function getUserSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['id', 'first_name', 'last_name', 'email', 'user_type'],
            'properties' => [
                'id' => ['type' => 'integer'],
                'first_name' => ['type' => 'string'],
                'last_name' => ['type' => 'string'],
                'email' => ['type' => 'string', 'format' => 'email'],
                'user_type' => ['type' => 'string', 'enum' => ['staff', 'client']],
                'job_title' => ['type' => ['string', 'null']],
                'phone' => ['type' => ['string', 'null']],
                'skype' => ['type' => ['string', 'null']],
                'gender' => ['type' => ['string', 'null'], 'enum' => ['male', 'female', 'other', null]],
                'address' => ['type' => ['string', 'null']],
                'alternative_address' => ['type' => ['string', 'null']],
                'status' => ['type' => 'string', 'enum' => ['active', 'inactive']],
                'role_id' => ['type' => ['integer', 'null']],
                'is_admin' => ['type' => 'integer', 'enum' => [0, 1]],
                'client_id' => ['type' => ['integer', 'null']],
                'note' => ['type' => ['string', 'null']],
                'disable_login' => ['type' => ['integer', 'null'], 'enum' => [0, 1, null]],
                'created_at' => ['type' => 'string', 'format' => 'date-time']
            ]
        ];
    }

    /**
     * Create User Request Schema
     */
    #[OA\Schema(
        schema: "CreateUserRequest",
        title: "Create User Request",
        description: "Request body for creating a new user",
        required: ["first_name", "last_name", "email", "user_type"],
        properties: [
            new OA\Property(property: "first_name", type: "string", description: "User's first name", example: "John"),
            new OA\Property(property: "last_name", type: "string", description: "User's last name", example: "Doe"),
            new OA\Property(property: "email", type: "string", format: "email", description: "User's email address", example: "john.doe@example.com"),
            new OA\Property(property: "user_type", type: "string", enum: ["staff", "client"], description: "User type", example: "staff"),
            new OA\Property(property: "password", type: "string", format: "password", description: "User's password (optional)", example: "SecurePass123!"),
            new OA\Property(property: "job_title", type: "string", description: "User's job title", example: "Developer"),
            new OA\Property(property: "phone", type: "string", description: "Phone number", example: "+1234567890"),
            new OA\Property(property: "skype", type: "string", description: "Skype username", example: "john.doe"),
            new OA\Property(property: "gender", type: "string", enum: ["male", "female", "other"], description: "Gender", example: "male"),
            new OA\Property(property: "address", type: "string", description: "Primary address", example: "123 Main St"),
            new OA\Property(property: "alternative_address", type: "string", description: "Alternative address", example: "456 Oak Ave"),
            new OA\Property(property: "role_id", type: "integer", description: "Role ID (for staff users)", example: 1),
            new OA\Property(property: "is_admin", type: "boolean", description: "Whether user is admin", example: false),
            new OA\Property(property: "client_id", type: "integer", description: "Client ID (required for client type users)", example: 5),
            new OA\Property(property: "note", type: "string", description: "Additional notes", example: "Important user")
        ],
        type: "object"
    )]
    public static function getCreateSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['first_name', 'last_name', 'email', 'user_type'],
            'properties' => [
                'first_name' => ['type' => 'string', 'minLength' => 1],
                'last_name' => ['type' => 'string', 'minLength' => 1],
                'email' => ['type' => 'string', 'format' => 'email'],
                'user_type' => ['type' => 'string', 'enum' => ['staff', 'client']],
                'password' => ['type' => 'string', 'minLength' => 6],
                'job_title' => ['type' => 'string'],
                'phone' => ['type' => 'string'],
                'skype' => ['type' => 'string'],
                'gender' => ['type' => 'string', 'enum' => ['male', 'female', 'other']],
                'address' => ['type' => 'string'],
                'alternative_address' => ['type' => 'string'],
                'role_id' => ['type' => 'integer', 'minimum' => 0],
                'is_admin' => ['type' => ['boolean', 'integer'], 'enum' => [0, 1, true, false]],
                'client_id' => ['type' => 'integer', 'minimum' => 1],
                'note' => ['type' => 'string']
            ]
        ];
    }

    /**
     * Update User Request Schema
     */
    #[OA\Schema(
        schema: "UpdateUserRequest",
        title: "Update User Request",
        description: "Request body for updating a user (all fields optional)",
        properties: [
            new OA\Property(property: "first_name", type: "string", example: "John"),
            new OA\Property(property: "last_name", type: "string", example: "Doe"),
            new OA\Property(property: "email", type: "string", format: "email", example: "john.doe@example.com"),
            new OA\Property(property: "password", type: "string", format: "password", description: "New password (optional)", example: "NewSecurePass123!"),
            new OA\Property(property: "job_title", type: "string", example: "Senior Developer"),
            new OA\Property(property: "phone", type: "string", example: "+1234567890"),
            new OA\Property(property: "skype", type: "string", example: "john.doe"),
            new OA\Property(property: "gender", type: "string", enum: ["male", "female", "other"], example: "male"),
            new OA\Property(property: "address", type: "string", example: "123 Main St"),
            new OA\Property(property: "alternative_address", type: "string", example: "456 Oak Ave"),
            new OA\Property(property: "status", type: "string", enum: ["active", "inactive"], example: "active"),
            new OA\Property(property: "role_id", type: "integer", example: 2),
            new OA\Property(property: "is_admin", type: "boolean", example: false),
            new OA\Property(property: "note", type: "string", example: "Updated note"),
            new OA\Property(property: "disable_login", type: "boolean", example: false)
        ],
        type: "object"
    )]
    public static function getUpdateSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'first_name' => ['type' => 'string', 'minLength' => 1],
                'last_name' => ['type' => 'string', 'minLength' => 1],
                'email' => ['type' => 'string', 'format' => 'email'],
                'password' => ['type' => 'string', 'minLength' => 6],
                'job_title' => ['type' => 'string'],
                'phone' => ['type' => 'string'],
                'skype' => ['type' => 'string'],
                'gender' => ['type' => 'string', 'enum' => ['male', 'female', 'other']],
                'address' => ['type' => 'string'],
                'alternative_address' => ['type' => 'string'],
                'status' => ['type' => 'string', 'enum' => ['active', 'inactive']],
                'role_id' => ['type' => 'integer', 'minimum' => 0],
                'is_admin' => ['type' => ['boolean', 'integer'], 'enum' => [0, 1, true, false]],
                'note' => ['type' => 'string'],
                'disable_login' => ['type' => ['boolean', 'integer'], 'enum' => [0, 1, true, false]]
            ]
        ];
    }

    /**
     * User Response Schema
     */
    #[OA\Schema(
        schema: "UserResponse",
        title: "User Response",
        description: "Single user response",
        required: ["success", "data", "meta"],
        properties: [
            new OA\Property(property: "success", type: "boolean", example: true),
            new OA\Property(
                property: "data",
                properties: [
                    new OA\Property(property: "user", ref: "#/components/schemas/User")
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
                        'user' => self::getUserSchema()
                    ]
                ],
                'meta' => Common::getResponseMetaSchema()
            ]
        ];
    }

    /**
     * User List Response Schema
     */
    #[OA\Schema(
        schema: "UserListResponse",
        title: "User List Response",
        description: "List of users with pagination",
        required: ["success", "data", "meta"],
        properties: [
            new OA\Property(property: "success", type: "boolean", example: true),
            new OA\Property(
                property: "data",
                properties: [
                    new OA\Property(
                        property: "users",
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/User")
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
                        'users' => [
                            'type' => 'array',
                            'items' => self::getUserSchema()
                        ],
                        'pagination' => Common::getPaginationSchema()
                    ]
                ],
                'meta' => Common::getResponseMetaSchema()
            ]
        ];
    }
}

