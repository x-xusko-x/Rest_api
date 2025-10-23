<?php

namespace Rest_api\Schemas;

use OpenApi\Attributes as OA;

/**
 * Project Resource OpenAPI Schemas
 */
class Project
{
    /**
     * Create Project Request Schema
     */
    #[OA\Schema(
        schema: "CreateProjectRequest",
        title: "Create Project Request",
        description: "Request body for creating a new project",
        required: ["title", "client_id"],
        properties: [
            new OA\Property(property: "title", type: "string", description: "Project title", example: "Website Redesign"),
            new OA\Property(property: "client_id", type: "integer", description: "Client ID", example: 5),
            new OA\Property(property: "description", type: "string", description: "Project description", example: "Complete website redesign project"),
            new OA\Property(property: "start_date", type: "string", format: "date", description: "Project start date", example: "2025-01-01"),
            new OA\Property(property: "deadline", type: "string", format: "date", description: "Project deadline", example: "2025-12-31"),
            new OA\Property(property: "price", type: "number", description: "Project price", example: 5000.00),
            new OA\Property(property: "project_type", type: "string", description: "Project type", example: "client_project"),
            new OA\Property(property: "status_id", type: "integer", description: "Status ID", example: 1),
            new OA\Property(property: "labels", type: "string", description: "Comma-separated labels", example: "urgent,web")
        ],
        type: "object"
    )]
    public static function getCreateSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['title', 'client_id'],
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 500
                ],
                'client_id' => [
                    'type' => 'integer',
                    'minimum' => 1
                ],
                'description' => [
                    'type' => 'string'
                ],
                'start_date' => [
                    'type' => 'string',
                    'format' => 'date'
                ],
                'deadline' => [
                    'type' => 'string',
                    'format' => 'date'
                ],
                'price' => [
                    'type' => 'number',
                    'minimum' => 0
                ],
                'project_type' => [
                    'type' => 'string'
                ],
                'status_id' => [
                    'type' => 'integer',
                    'minimum' => 1
                ],
                'labels' => [
                    'type' => 'string'
                ]
            ]
        ];
    }

    /**
     * Update Project Request Schema
     */
    #[OA\Schema(
        schema: "UpdateProjectRequest",
        title: "Update Project Request",
        description: "Request body for updating a project (all fields optional)",
        properties: [
            new OA\Property(property: "title", type: "string", example: "Website Redesign Updated"),
            new OA\Property(property: "client_id", type: "integer", example: 5),
            new OA\Property(property: "description", type: "string", example: "Updated description"),
            new OA\Property(property: "start_date", type: "string", format: "date", example: "2025-01-01"),
            new OA\Property(property: "deadline", type: "string", format: "date", example: "2025-12-31"),
            new OA\Property(property: "price", type: "number", example: 6000.00),
            new OA\Property(property: "status_id", type: "integer", example: 2),
            new OA\Property(property: "labels", type: "string", example: "urgent,web,design")
        ],
        type: "object"
    )]
    public static function getUpdateSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 500
                ],
                'client_id' => [
                    'type' => 'integer',
                    'minimum' => 1
                ],
                'description' => [
                    'type' => 'string'
                ],
                'start_date' => [
                    'type' => 'string',
                    'format' => 'date'
                ],
                'deadline' => [
                    'type' => 'string',
                    'format' => 'date'
                ],
                'price' => [
                    'type' => 'number',
                    'minimum' => 0
                ],
                'status_id' => [
                    'type' => 'integer',
                    'minimum' => 1
                ],
                'labels' => [
                    'type' => 'string'
                ]
            ]
        ];
    }
}

