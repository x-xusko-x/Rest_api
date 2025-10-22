<?php

namespace Rest_api\Schemas;

use OpenApi\Attributes as OA;

/**
 * Client/Lead Resource OpenAPI Schemas
 */
class Client
{
    /**
     * Client Model Schema
     */
    #[OA\Schema(
        schema: "Client",
        title: "Client",
        description: "Client or Lead resource model",
        required: ["id", "company_name"],
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "company_name", type: "string", example: "Acme Corporation"),
            new OA\Property(property: "is_lead", type: "integer", enum: [0, 1], description: "1 if lead, 0 if client", example: 0),
            new OA\Property(property: "type", type: "string", nullable: true, description: "person or organization", example: "organization"),
            new OA\Property(property: "website", type: "string", nullable: true, example: "https://acme.com"),
            new OA\Property(property: "phone", type: "string", nullable: true, example: "+1234567890"),
            new OA\Property(property: "address", type: "string", nullable: true, example: "123 Business St"),
            new OA\Property(property: "city", type: "string", nullable: true, example: "New York"),
            new OA\Property(property: "state", type: "string", nullable: true, example: "NY"),
            new OA\Property(property: "zip", type: "string", nullable: true, example: "10001"),
            new OA\Property(property: "country", type: "string", nullable: true, example: "USA"),
            new OA\Property(property: "vat_number", type: "string", nullable: true, example: "VAT123456"),
            new OA\Property(property: "gst_number", type: "string", nullable: true, example: "GST789012"),
            new OA\Property(property: "currency", type: "string", nullable: true, example: "USD"),
            new OA\Property(property: "currency_symbol", type: "string", nullable: true, example: "$"),
            new OA\Property(property: "owner_id", type: "integer", nullable: true, description: "User ID who owns this client", example: 1),
            new OA\Property(property: "group_id", type: "integer", nullable: true, description: "Client group ID", example: 1),
            new OA\Property(property: "source", type: "string", nullable: true, description: "Lead source", example: "website"),
            new OA\Property(property: "status", type: "string", nullable: true, description: "Lead status", example: "open"),
            new OA\Property(property: "labels", type: "string", nullable: true, description: "Comma-separated label IDs", example: "1,2,3"),
            new OA\Property(property: "created_by", type: "integer", nullable: true, example: 1),
            new OA\Property(property: "created_at", type: "string", format: "date-time", nullable: true, example: "2025-10-15T10:30:00+00:00")
        ],
        type: "object"
    )]
    public static function getClientSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['id', 'company_name'],
            'properties' => [
                'id' => ['type' => 'integer'],
                'company_name' => ['type' => 'string'],
                'is_lead' => ['type' => 'integer', 'enum' => [0, 1]],
                'type' => ['type' => ['string', 'null']],
                'website' => ['type' => ['string', 'null']],
                'phone' => ['type' => ['string', 'null']],
                'address' => ['type' => ['string', 'null']],
                'city' => ['type' => ['string', 'null']],
                'state' => ['type' => ['string', 'null']],
                'zip' => ['type' => ['string', 'null']],
                'country' => ['type' => ['string', 'null']],
                'vat_number' => ['type' => ['string', 'null']],
                'gst_number' => ['type' => ['string', 'null']],
                'currency' => ['type' => ['string', 'null']],
                'currency_symbol' => ['type' => ['string', 'null']],
                'owner_id' => ['type' => ['integer', 'null']],
                'group_id' => ['type' => ['integer', 'null']],
                'source' => ['type' => ['string', 'null']],
                'status' => ['type' => ['string', 'null']],
                'labels' => ['type' => ['string', 'null']],
                'created_by' => ['type' => ['integer', 'null']],
                'created_at' => ['type' => ['string', 'null'], 'format' => 'date-time']
            ]
        ];
    }

    /**
     * Create Client Request Schema
     */
    #[OA\Schema(
        schema: "CreateClientRequest",
        title: "Create Client Request",
        description: "Request body for creating a new client or lead",
        required: ["company_name"],
        properties: [
            new OA\Property(property: "company_name", type: "string", description: "Company or person name", example: "Acme Corporation"),
            new OA\Property(property: "is_lead", type: "integer", enum: [0, 1], description: "1 to create as lead, 0 as client", example: 0),
            new OA\Property(property: "type", type: "string", enum: ["person", "organization"], description: "Client type", example: "organization"),
            new OA\Property(property: "website", type: "string", format: "uri", description: "Company website", example: "https://acme.com"),
            new OA\Property(property: "phone", type: "string", description: "Phone number", example: "+1234567890"),
            new OA\Property(property: "address", type: "string", description: "Street address", example: "123 Business St"),
            new OA\Property(property: "city", type: "string", description: "City", example: "New York"),
            new OA\Property(property: "state", type: "string", description: "State/Province", example: "NY"),
            new OA\Property(property: "zip", type: "string", description: "ZIP/Postal code", example: "10001"),
            new OA\Property(property: "country", type: "string", description: "Country", example: "USA"),
            new OA\Property(property: "vat_number", type: "string", description: "VAT number", example: "VAT123456"),
            new OA\Property(property: "gst_number", type: "string", description: "GST number", example: "GST789012"),
            new OA\Property(property: "currency", type: "string", description: "Currency code (USD, EUR, etc.)", example: "USD"),
            new OA\Property(property: "owner_id", type: "integer", description: "User ID who owns this client", example: 1),
            new OA\Property(property: "group_id", type: "integer", description: "Client group ID", example: 1),
            new OA\Property(property: "source", type: "string", description: "Lead source", example: "website"),
            new OA\Property(property: "status", type: "string", description: "Lead status", example: "open"),
            new OA\Property(property: "labels", type: "string", description: "Comma-separated label IDs", example: "1,2,3")
        ],
        type: "object"
    )]
    public static function getCreateSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['company_name'],
            'properties' => [
                'company_name' => ['type' => 'string', 'minLength' => 1],
                'is_lead' => ['type' => 'integer', 'enum' => [0, 1]],
                'type' => ['type' => 'string', 'enum' => ['person', 'organization']],
                'website' => ['type' => 'string', 'format' => 'uri'],
                'phone' => ['type' => 'string'],
                'address' => ['type' => 'string'],
                'city' => ['type' => 'string'],
                'state' => ['type' => 'string'],
                'zip' => ['type' => 'string'],
                'country' => ['type' => 'string'],
                'vat_number' => ['type' => 'string'],
                'gst_number' => ['type' => 'string'],
                'currency' => ['type' => 'string', 'pattern' => '^[A-Z]{3}$'],
                'owner_id' => ['type' => 'integer', 'minimum' => 1],
                'group_id' => ['type' => 'integer', 'minimum' => 1],
                'source' => ['type' => 'string'],
                'status' => ['type' => 'string'],
                'labels' => ['type' => 'string']
            ]
        ];
    }

    /**
     * Update Client Request Schema
     */
    #[OA\Schema(
        schema: "UpdateClientRequest",
        title: "Update Client Request",
        description: "Request body for updating a client or lead (all fields optional)",
        properties: [
            new OA\Property(property: "company_name", type: "string", example: "Updated Company Name"),
            new OA\Property(property: "type", type: "string", enum: ["person", "organization"], example: "organization"),
            new OA\Property(property: "website", type: "string", format: "uri", example: "https://updated-acme.com"),
            new OA\Property(property: "phone", type: "string", example: "+1234567890"),
            new OA\Property(property: "address", type: "string", example: "456 New Address St"),
            new OA\Property(property: "city", type: "string", example: "Los Angeles"),
            new OA\Property(property: "state", type: "string", example: "CA"),
            new OA\Property(property: "zip", type: "string", example: "90001"),
            new OA\Property(property: "country", type: "string", example: "USA"),
            new OA\Property(property: "vat_number", type: "string", example: "VAT999999"),
            new OA\Property(property: "gst_number", type: "string", example: "GST888888"),
            new OA\Property(property: "currency", type: "string", example: "EUR"),
            new OA\Property(property: "owner_id", type: "integer", example: 2),
            new OA\Property(property: "group_id", type: "integer", example: 2),
            new OA\Property(property: "source", type: "string", example: "referral"),
            new OA\Property(property: "status", type: "string", example: "closed"),
            new OA\Property(property: "labels", type: "string", example: "4,5,6")
        ],
        type: "object"
    )]
    public static function getUpdateSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'company_name' => ['type' => 'string', 'minLength' => 1],
                'type' => ['type' => 'string', 'enum' => ['person', 'organization']],
                'website' => ['type' => 'string', 'format' => 'uri'],
                'phone' => ['type' => 'string'],
                'address' => ['type' => 'string'],
                'city' => ['type' => 'string'],
                'state' => ['type' => 'string'],
                'zip' => ['type' => 'string'],
                'country' => ['type' => 'string'],
                'vat_number' => ['type' => 'string'],
                'gst_number' => ['type' => 'string'],
                'currency' => ['type' => 'string', 'pattern' => '^[A-Z]{3}$'],
                'owner_id' => ['type' => 'integer', 'minimum' => 1],
                'group_id' => ['type' => 'integer', 'minimum' => 1],
                'source' => ['type' => 'string'],
                'status' => ['type' => 'string'],
                'labels' => ['type' => 'string']
            ]
        ];
    }

    /**
     * Client Response Schema
     */
    #[OA\Schema(
        schema: "ClientResponse",
        title: "Client Response",
        description: "Single client response",
        required: ["success", "data", "meta"],
        properties: [
            new OA\Property(property: "success", type: "boolean", example: true),
            new OA\Property(
                property: "data",
                properties: [
                    new OA\Property(property: "client", ref: "#/components/schemas/Client")
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
                        'client' => self::getClientSchema()
                    ]
                ],
                'meta' => Common::getResponseMetaSchema()
            ]
        ];
    }

    /**
     * Client List Response Schema
     */
    #[OA\Schema(
        schema: "ClientListResponse",
        title: "Client List Response",
        description: "List of clients with pagination",
        required: ["success", "data", "meta"],
        properties: [
            new OA\Property(property: "success", type: "boolean", example: true),
            new OA\Property(
                property: "data",
                properties: [
                    new OA\Property(
                        property: "clients",
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Client")
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
                        'clients' => [
                            'type' => 'array',
                            'items' => self::getClientSchema()
                        ],
                        'pagination' => Common::getPaginationSchema()
                    ]
                ],
                'meta' => Common::getResponseMetaSchema()
            ]
        ];
    }
}

