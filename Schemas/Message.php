<?php

namespace Rest_api\Schemas;

use OpenApi\Attributes as OA;

/**
 * Message Resource OpenAPI Schemas
 */
class Message
{
    /**
     * Create Message Request Schema
     */
    #[OA\Schema(
        schema: "CreateMessageRequest",
        title: "Create Message Request",
        description: "Request body for creating a new message",
        required: ["subject", "message", "to_user_id"],
        properties: [
            new OA\Property(property: "subject", type: "string", description: "Message subject", example: "Project Update"),
            new OA\Property(property: "message", type: "string", description: "Message content", example: "The project is progressing well."),
            new OA\Property(property: "to_user_id", type: "integer", description: "Recipient user ID", example: 5)
        ],
        type: "object"
    )]
    public static function getCreateSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['subject', 'message', 'to_user_id'],
            'properties' => [
                'subject' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 500
                ],
                'message' => [
                    'type' => 'string',
                    'minLength' => 1
                ],
                'to_user_id' => [
                    'type' => 'integer',
                    'minimum' => 1
                ]
            ]
        ];
    }
}

