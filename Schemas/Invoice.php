<?php

namespace Rest_api\Schemas;

use OpenApi\Attributes as OA;

/**
 * Invoice Resource OpenAPI Schemas
 */
class Invoice
{
    /**
     * Create Invoice Request Schema
     */
    #[OA\Schema(
        schema: "CreateInvoiceRequest",
        title: "Create Invoice Request",
        description: "Request body for creating a new invoice",
        required: ["client_id", "bill_date", "due_date"],
        properties: [
            new OA\Property(property: "client_id", type: "integer", description: "Client ID", example: 5),
            new OA\Property(property: "bill_date", type: "string", format: "date", description: "Bill date", example: "2025-01-01"),
            new OA\Property(property: "due_date", type: "string", format: "date", description: "Due date", example: "2025-01-31"),
            new OA\Property(property: "project_id", type: "integer", description: "Project ID (optional)", example: 10),
            new OA\Property(property: "tax_id", type: "integer", description: "Tax ID 1", example: 1),
            new OA\Property(property: "tax_id2", type: "integer", description: "Tax ID 2", example: 0),
            new OA\Property(property: "tax_id3", type: "integer", description: "Tax ID 3", example: 0),
            new OA\Property(property: "note", type: "string", description: "Invoice note", example: "Payment terms: Net 30"),
            new OA\Property(property: "labels", type: "string", description: "Comma-separated labels", example: "urgent,recurring"),
            new OA\Property(property: "discount_amount", type: "number", description: "Discount amount", example: 10),
            new OA\Property(property: "discount_amount_type", type: "string", enum: ["percentage", "fixed"], description: "Discount type", example: "percentage"),
            new OA\Property(property: "discount_type", type: "string", enum: ["before_tax", "after_tax"], description: "When discount is applied", example: "before_tax")
        ],
        type: "object"
    )]
    public static function getCreateSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['client_id', 'bill_date', 'due_date'],
            'properties' => [
                'client_id' => [
                    'type' => 'integer',
                    'minimum' => 1
                ],
                'bill_date' => [
                    'type' => 'string',
                    'format' => 'date'
                ],
                'due_date' => [
                    'type' => 'string',
                    'format' => 'date'
                ],
                'project_id' => [
                    'type' => 'integer',
                    'minimum' => 0
                ],
                'tax_id' => [
                    'type' => 'integer',
                    'minimum' => 0
                ],
                'tax_id2' => [
                    'type' => 'integer',
                    'minimum' => 0
                ],
                'tax_id3' => [
                    'type' => 'integer',
                    'minimum' => 0
                ],
                'note' => [
                    'type' => 'string'
                ],
                'labels' => [
                    'type' => 'string'
                ],
                'discount_amount' => [
                    'type' => 'number',
                    'minimum' => 0
                ],
                'discount_amount_type' => [
                    'type' => 'string',
                    'enum' => ['percentage', 'fixed']
                ],
                'discount_type' => [
                    'type' => 'string',
                    'enum' => ['before_tax', 'after_tax']
                ],
                'estimate_id' => [
                    'type' => 'integer',
                    'minimum' => 0
                ],
                'order_id' => [
                    'type' => 'integer',
                    'minimum' => 0
                ]
            ]
        ];
    }

    /**
     * Update Invoice Request Schema
     */
    #[OA\Schema(
        schema: "UpdateInvoiceRequest",
        title: "Update Invoice Request",
        description: "Request body for updating an invoice (all fields optional)",
        properties: [
            new OA\Property(property: "client_id", type: "integer", example: 5),
            new OA\Property(property: "project_id", type: "integer", example: 10),
            new OA\Property(property: "bill_date", type: "string", format: "date", example: "2025-01-01"),
            new OA\Property(property: "due_date", type: "string", format: "date", example: "2025-01-31"),
            new OA\Property(property: "status", type: "string", example: "sent"),
            new OA\Property(property: "type", type: "string", example: "invoice"),
            new OA\Property(property: "tax_id", type: "integer", example: 1),
            new OA\Property(property: "tax_id2", type: "integer", example: 0),
            new OA\Property(property: "tax_id3", type: "integer", example: 0),
            new OA\Property(property: "note", type: "string", example: "Updated payment terms"),
            new OA\Property(property: "labels", type: "string", example: "urgent"),
            new OA\Property(property: "discount_amount", type: "number", example: 15),
            new OA\Property(property: "discount_amount_type", type: "string", enum: ["percentage", "fixed"], example: "percentage"),
            new OA\Property(property: "discount_type", type: "string", enum: ["before_tax", "after_tax"], example: "before_tax")
        ],
        type: "object"
    )]
    public static function getUpdateSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'client_id' => [
                    'type' => 'integer',
                    'minimum' => 1
                ],
                'project_id' => [
                    'type' => 'integer',
                    'minimum' => 0
                ],
                'bill_date' => [
                    'type' => 'string',
                    'format' => 'date'
                ],
                'due_date' => [
                    'type' => 'string',
                    'format' => 'date'
                ],
                'status' => [
                    'type' => 'string'
                ],
                'type' => [
                    'type' => 'string'
                ],
                'tax_id' => [
                    'type' => 'integer',
                    'minimum' => 0
                ],
                'tax_id2' => [
                    'type' => 'integer',
                    'minimum' => 0
                ],
                'tax_id3' => [
                    'type' => 'integer',
                    'minimum' => 0
                ],
                'note' => [
                    'type' => 'string'
                ],
                'labels' => [
                    'type' => 'string'
                ],
                'discount_amount' => [
                    'type' => 'number',
                    'minimum' => 0
                ],
                'discount_amount_type' => [
                    'type' => 'string',
                    'enum' => ['percentage', 'fixed']
                ],
                'discount_type' => [
                    'type' => 'string',
                    'enum' => ['before_tax', 'after_tax']
                ]
            ]
        ];
    }
}

