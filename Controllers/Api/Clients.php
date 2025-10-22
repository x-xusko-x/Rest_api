<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;
use OpenApi\Attributes as OA;

/**
 * Clients API Controller
 * 
 * @OA\Tag(
 *     name="Clients",
 *     description="Client and lead management operations"
 * )
 */
class Clients extends Api_controller
{
    public $Clients_model;
    public $Users_model;

    function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->Clients_model = model('App\Models\Clients_model', false);
        $this->Users_model = model('App\Models\Users_model', false);
    }

    /**
     * GET /api/v1/clients
     * List all clients with pagination and filtering
     * 
     * @OA\Get(
     *     path="/api/v1/clients",
     *     tags={"Clients"},
     *     summary="List all clients",
     *     description="Retrieve a paginated list of clients and leads with optional filtering",
     *     operationId="listClients",
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=100, default=50)),
     *     @OA\Parameter(name="offset", in="query", required=false, @OA\Schema(type="integer", minimum=0, default=0)),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="group_id", in="query", description="Filter by client group ID", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="owner_id", in="query", description="Filter by owner user ID", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="created_by", in="query", description="Filter by creator user ID", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="leads_only", in="query", description="Show only leads (is_lead=1)", required=false, @OA\Schema(type="string", enum={"1", "true"})),
     *     @OA\Parameter(name="status", in="query", description="Filter by lead status", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="source", in="query", description="Filter by lead source", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Successful response", @OA\JsonContent(ref="#/components/schemas/ClientListResponse")),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={{"ApiKeyAuth": {}, "ApiSecretAuth": {}}, {"BearerAuth": {}}}
     * )
     */
    public function index()
    {
        try {
            $pagination = $this->_get_pagination_params(50, 100);
            $options = array(
                'limit' => $pagination['limit'],
                'skip' => $pagination['offset']
            );

            $search = $this->request->getGet('search');
            if (!empty($search)) {
                $options['search_by'] = $search;
            }

            $group_id = $this->request->getGet('group_id');
            if ($group_id) {
                $options['group_id'] = (int)$group_id;
            }

            $owner_id = $this->request->getGet('owner_id');
            if ($owner_id) {
                $options['owner_id'] = (int)$owner_id;
            }

            $created_by = $this->request->getGet('created_by');
            if ($created_by) {
                $options['created_by'] = (int)$created_by;
            }

            $leads_only = $this->request->getGet('leads_only');
            if ($leads_only == '1' || $leads_only === 'true') {
                $options['leads_only'] = true;
            }

            $status = $this->request->getGet('status');
            if ($status) {
                $options['status'] = $status;
            }

            $source = $this->request->getGet('source');
            if ($source) {
                $options['source'] = $source;
            }

            $result = $this->Clients_model->get_details($options);
            $clients = $result['data'] ?? [];
            $total = $result['recordsTotal'] ?? 0;

            $formatted_clients = [];
            foreach ($clients as $client) {
                $formatted_clients[] = $this->_format_client($client);
            }

            $this->_api_list_response($formatted_clients, $total, 'clients', $pagination, true);
        } catch (\Exception $e) {
            log_message('error', 'API Clients::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve clients', 500);
        }
    }

    /**
     * GET /api/v1/clients/{id}
     * Get a specific client by ID
     * 
     * @OA\Get(
     *     path="/api/v1/clients/{id}",
     *     tags={"Clients"},
     *     summary="Get a client by ID",
     *     description="Retrieve detailed information about a specific client or lead",
     *     operationId="getClientById",
     *     @OA\Parameter(name="id", in="path", description="Client ID", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Response(response=200, description="Successful response", @OA\JsonContent(ref="#/components/schemas/ClientResponse")),
     *     @OA\Response(response=400, description="Bad Request - Invalid ID", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Client not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={{"ApiKeyAuth": {}, "ApiSecretAuth": {}}, {"BearerAuth": {}}}
     * )
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Client ID is required'), 400);
                return;
            }

            $client = $this->Clients_model->get_one($id);

            if (!$client || !$client->id) {
                $this->_api_response(array('message' => 'Client not found'), 404);
                return;
            }

            // Get client details with related data
            $options = array('id' => $id);
            $result = $this->Clients_model->get_details($options);
            $client_details = $result->getRow();

            if (!$client_details) {
                $this->_api_response(array('message' => 'Client not found'), 404);
                return;
            }

            $this->_api_response(array(
                'client' => $this->_format_client($client_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Clients::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve client',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/clients
     * Create a new client
     * 
     * @OA\Post(
     *     path="/api/v1/clients",
     *     tags={"Clients"},
     *     summary="Create a new client",
     *     description="Create a new client or lead with the provided data",
     *     operationId="createClient",
     *     @OA\RequestBody(required=true, description="Client data", @OA\JsonContent(ref="#/components/schemas/CreateClientRequest")),
     *     @OA\Response(response=201, description="Client created successfully", @OA\JsonContent(ref="#/components/schemas/ClientResponse")),
     *     @OA\Response(response=400, description="Validation failed", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={{"ApiKeyAuth": {}, "ApiSecretAuth": {}}, {"BearerAuth": {}}}
     * )
     */
    public function create()
    {
        try {
            // Validate request against OpenAPI schema
            $this->_validate_request_schema('Client', 'Create');
            $data = $this->request->getJSON(true);

            // Validate required fields
            $required_fields = array('company_name');
            $validation_errors = [];

            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    $validation_errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }

            if (!empty($validation_errors)) {
                $this->_api_response(array(
                    'message' => 'Validation failed',
                    'errors' => $validation_errors
                ), 400);
                return;
            }

            // Check for duplicate company name (for clients only, not leads)
            $is_lead = get_array_value($data, 'is_lead', 0);
            if (!$is_lead) {
                $duplicate = $this->Clients_model->is_duplicate_company_name($data['company_name']);
                if ($duplicate) {
                    $this->_api_response(array(
                        'message' => 'Company name already exists',
                        'existing_client_id' => $duplicate->id
                    ), 409);
                    return;
                }
            }

            // Validate owner_id if provided
            if (!empty($data['owner_id'])) {
                $owner = $this->Users_model->get_one($data['owner_id']);
                if (!$owner || !$owner->id || $owner->user_type != 'staff') {
                    $this->_api_response(array('message' => 'Invalid owner ID. Must be a staff member.'), 404);
                    return;
                }
            }

            // Prepare client data
            $client_data = array(
                'company_name' => get_array_value($data, 'company_name'),
                'address' => get_array_value($data, 'address', ''),
                'city' => get_array_value($data, 'city', ''),
                'state' => get_array_value($data, 'state', ''),
                'zip' => get_array_value($data, 'zip', ''),
                'country' => get_array_value($data, 'country', ''),
                'phone' => get_array_value($data, 'phone', ''),
                'website' => get_array_value($data, 'website', ''),
                'vat_number' => get_array_value($data, 'vat_number', ''),
                'gst_number' => get_array_value($data, 'gst_number', ''),
                'currency' => get_array_value($data, 'currency', ''),
                'currency_symbol' => get_array_value($data, 'currency_symbol', ''),
                'is_lead' => get_array_value($data, 'is_lead', 0),
                'owner_id' => get_array_value($data, 'owner_id', 0),
                'group_ids' => get_array_value($data, 'group_ids', ''),
                'labels' => get_array_value($data, 'labels', ''),
                'created_date' => get_current_utc_time()
            );

            // Add lead-specific fields if it's a lead
            if ($is_lead) {
                $client_data['lead_status_id'] = get_array_value($data, 'lead_status_id', 1);
                $client_data['lead_source_id'] = get_array_value($data, 'lead_source_id', 0);
            }

            // Clean data before saving
            $client_data = clean_data($client_data);

            // Save client
            $client_id = $this->Clients_model->ci_save($client_data);

            if (!$client_id) {
                $db_error = $this->Clients_model->db->error();
                log_message('error', 'API Clients::create database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to create client';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'cannot be null') !== false || 
                        strpos($db_error['message'], "doesn't have a default value") !== false) {
                        preg_match("/Column '([^']+)'/", $db_error['message'], $matches);
                        if (isset($matches[1])) {
                            $error_details['missing_field'] = $matches[1];
                            $error_message = ucfirst(str_replace('_', ' ', $matches[1])) . ' is required';
                        }
                    } else if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'A client with this information already exists';
                    } else if (strpos($db_error['message'], "Unknown column") !== false) {
                        preg_match("/Unknown column '([^']+)'/", $db_error['message'], $matches);
                        if (isset($matches[1])) {
                            $error_details['invalid_field'] = $matches[1];
                            $error_message = 'Invalid field: ' . $matches[1];
                        }
                    }
                }
                
                $response = array('message' => $error_message);
                if (!empty($error_details)) {
                    $response['error_details'] = $error_details;
                }
                
                $this->_api_response($response, 500);
                return;
            }

            // Get the created client
            $options = array('id' => $client_id);
            $result = $this->Clients_model->get_details($options);
            $client_details = $result->getRow();

            log_message('info', 'API: Client created successfully - ID: ' . $client_id);

            $this->_api_response(array(
                'message' => 'Client created successfully',
                'client' => $this->_format_client($client_details)
            ), 201);
        } catch (\Exception $e) {
            log_message('error', 'API Clients::create error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to create client',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * PUT /api/v1/clients/{id}
     * Update an existing client
     * 
     * @OA\Put(
     *     path="/api/v1/clients/{id}",
     *     tags={"Clients"},
     *     summary="Update a client",
     *     description="Update an existing client or lead with the provided data",
     *     operationId="updateClient",
     *     @OA\Parameter(name="id", in="path", description="Client ID", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\RequestBody(required=true, description="Client data to update", @OA\JsonContent(ref="#/components/schemas/UpdateClientRequest")),
     *     @OA\Response(response=200, description="Client updated successfully", @OA\JsonContent(ref="#/components/schemas/ClientResponse")),
     *     @OA\Response(response=400, description="Invalid request data", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Client not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={{"ApiKeyAuth": {}, "ApiSecretAuth": {}}, {"BearerAuth": {}}}
     * )
     */
    public function update($id = null)
    {
        try {
            // Validate request against OpenAPI schema
            $this->_validate_request_schema('Client', 'Update');
            if (!$id) {
                $this->_api_response(array('message' => 'Client ID is required'), 400);
                return;
            }

            // Check if client exists
            $client = $this->Clients_model->get_one($id);
            if (!$client || !$client->id) {
                $this->_api_response(array('message' => 'Client not found'), 404);
                return;
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $client_data = array();

            // Only update provided fields
            if (isset($data['company_name'])) {
                // Check for duplicate (only for clients, not leads)
                if ($client->is_lead == 0) {
                    $duplicate = $this->Clients_model->is_duplicate_company_name($data['company_name'], $id);
                    if ($duplicate) {
                        $this->_api_response(array(
                            'message' => 'Company name already exists',
                            'existing_client_id' => $duplicate->id
                        ), 409);
                        return;
                    }
                }
                $client_data['company_name'] = $data['company_name'];
            }
            
            if (isset($data['address'])) {
                $client_data['address'] = $data['address'];
            }
            if (isset($data['city'])) {
                $client_data['city'] = $data['city'];
            }
            if (isset($data['state'])) {
                $client_data['state'] = $data['state'];
            }
            if (isset($data['zip'])) {
                $client_data['zip'] = $data['zip'];
            }
            if (isset($data['country'])) {
                $client_data['country'] = $data['country'];
            }
            if (isset($data['phone'])) {
                $client_data['phone'] = $data['phone'];
            }
            if (isset($data['website'])) {
                $client_data['website'] = $data['website'];
            }
            if (isset($data['vat_number'])) {
                $client_data['vat_number'] = $data['vat_number'];
            }
            if (isset($data['gst_number'])) {
                $client_data['gst_number'] = $data['gst_number'];
            }
            if (isset($data['owner_id'])) {
                // Validate owner exists
                if ($data['owner_id'] > 0) {
                    $owner = $this->Users_model->get_one($data['owner_id']);
                    if (!$owner || !$owner->id || $owner->user_type != 'staff') {
                        $this->_api_response(array('message' => 'Invalid owner ID. Must be a staff member.'), 404);
                        return;
                    }
                }
                $client_data['owner_id'] = $data['owner_id'];
            }
            if (isset($data['group_ids'])) {
                $client_data['group_ids'] = $data['group_ids'];
            }
            if (isset($data['labels'])) {
                $client_data['labels'] = $data['labels'];
            }
            
            // Lead-specific fields
            if ($client->is_lead == 1) {
                if (isset($data['lead_status_id'])) {
                    $client_data['lead_status_id'] = $data['lead_status_id'];
                }
                if (isset($data['lead_source_id'])) {
                    $client_data['lead_source_id'] = $data['lead_source_id'];
                }
            }
            
            // Currency (check if editable)
            if (isset($data['currency']) || isset($data['currency_symbol'])) {
                if ($this->Clients_model->is_currency_editable($id)) {
                    if (isset($data['currency'])) {
                        $client_data['currency'] = $data['currency'];
                    }
                    if (isset($data['currency_symbol'])) {
                        $client_data['currency_symbol'] = $data['currency_symbol'];
                    }
                } else {
                    $this->_api_response(array(
                        'message' => 'Currency cannot be changed as this client has existing invoices, estimates, or other financial documents'
                    ), 400);
                    return;
                }
            }

            if (empty($client_data)) {
                $this->_api_response(array('message' => 'No valid fields to update'), 400);
                return;
            }

            // Clean data before saving
            $client_data = clean_data($client_data);

            // Update client
            $success = $this->Clients_model->ci_save($client_data, $id);

            if (!$success) {
                $db_error = $this->Clients_model->db->error();
                log_message('error', 'API Clients::update database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to update client';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'A client with this information already exists';
                    } else if (strpos($db_error['message'], "Unknown column") !== false) {
                        preg_match("/Unknown column '([^']+)'/", $db_error['message'], $matches);
                        if (isset($matches[1])) {
                            $error_details['invalid_field'] = $matches[1];
                            $error_message = 'Invalid field: ' . $matches[1];
                        }
                    }
                }
                
                $response = array('message' => $error_message);
                if (!empty($error_details)) {
                    $response['error_details'] = $error_details;
                }
                
                $this->_api_response($response, 500);
                return;
            }

            // Get updated client
            $options = array('id' => $id);
            $result = $this->Clients_model->get_details($options);
            $client_details = $result->getRow();

            log_message('info', 'API: Client updated successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Client updated successfully',
                'client' => $this->_format_client($client_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Clients::update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to update client',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * DELETE /api/v1/clients/{id}
     * Delete a client
     * 
     * @OA\Delete(
     *     path="/api/v1/clients/{id}",
     *     tags={"Clients"},
     *     summary="Delete a client",
     *     description="Delete a client or lead permanently",
     *     operationId="deleteClient",
     *     @OA\Parameter(name="id", in="path", description="Client ID", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Response(response=200, description="Client deleted successfully", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="data", type="object", @OA\Property(property="message", type="string", example="Client deleted successfully")), @OA\Property(property="meta", ref="#/components/schemas/ResponseMeta"))),
     *     @OA\Response(response=400, description="Invalid client ID", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Client not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={{"ApiKeyAuth": {}, "ApiSecretAuth": {}}, {"BearerAuth": {}}}
     * )
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Client ID is required'), 400);
                return;
            }

            // Check if client exists
            $client = $this->Clients_model->get_one($id);
            if (!$client || !$client->id) {
                $this->_api_response(array('message' => 'Client not found'), 404);
                return;
            }

            // Delete client (soft delete with contacts and files)
            $success = $this->Clients_model->delete_client_and_sub_items($id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to delete client'), 500);
                return;
            }

            log_message('info', 'API: Client deleted successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Client deleted successfully'
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Clients::delete error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to delete client',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/clients/{id}/convert
     * Convert a lead to a client
     * 
     * @OA\Post(
     *     path="/api/v1/clients/{id}/convert",
     *     tags={"Clients"},
     *     summary="Convert lead to client",
     *     description="Convert a lead (is_lead=1) to a regular client (is_lead=0)",
     *     operationId="convertLeadToClient",
     *     @OA\Parameter(name="id", in="path", description="Lead ID", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Lead converted to client successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string", example="Lead converted to client successfully"),
     *                 @OA\Property(property="client", ref="#/components/schemas/Client")
     *             ),
     *             @OA\Property(property="meta", ref="#/components/schemas/ResponseMeta")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad Request - ID required or already a client", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Lead not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={{"ApiKeyAuth": {}, "ApiSecretAuth": {}}, {"BearerAuth": {}}}
     * )
     */
    public function convert($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Client ID is required'), 400);
                return;
            }

            // Check if client exists
            $client = $this->Clients_model->get_one($id);
            if (!$client || !$client->id) {
                $this->_api_response(array('message' => 'Client not found'), 404);
                return;
            }

            // Verify it's actually a lead
            if ($client->is_lead != 1) {
                $this->_api_response(array('message' => 'This is already a client, not a lead.'), 400);
                return;
            }

            // Convert lead to client
            $conversion_data = array(
                'is_lead' => 0,
                'client_migration_date' => get_current_utc_time()
            );

            // Clean data before saving
            $conversion_data = clean_data($conversion_data);

            $success = $this->Clients_model->ci_save($conversion_data, $id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to convert lead to client'), 500);
                return;
            }

            // Get the converted client
            $options = array('id' => $id);
            $result = $this->Clients_model->get_details($options);
            $client_details = $result->getRow();

            log_message('info', 'API: Lead converted to client successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Lead converted to client successfully',
                'client' => $this->_format_client($client_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Clients::convert error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to convert lead to client',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format client data for API response
     */
    private function _format_client($client)
    {
        return array(
            'id' => (int)$client->id,
            'company_name' => $client->company_name ?? '',
            'address' => $client->address ?? '',
            'city' => $client->city ?? '',
            'state' => $client->state ?? '',
            'zip' => $client->zip ?? '',
            'country' => $client->country ?? '',
            'phone' => $client->phone ?? '',
            'website' => $client->website ?? '',
            'vat_number' => $client->vat_number ?? '',
            'gst_number' => $client->gst_number ?? '',
            'currency' => $client->currency ?? '',
            'currency_symbol' => $client->currency_symbol ?? '',
            'is_lead' => (int)($client->is_lead ?? 0),
            'owner_id' => (int)($client->owner_id ?? 0),
            'owner_name' => $client->owner_name ?? '',
            'owner_avatar' => $client->owner_avatar ?? '',
            'created_by' => (int)($client->created_by ?? 0),
            'group_ids' => $client->group_ids ?? '',
            'client_groups' => $client->client_groups ?? '',
            'labels' => $client->labels ?? '',
            'primary_contact_id' => (int)($client->primary_contact_id ?? 0),
            'primary_contact' => $client->primary_contact ?? '',
            'primary_contact_phone' => $client->primary_contact_phone ?? '',
            'contact_avatar' => $client->contact_avatar ?? '',
            'total_projects' => (int)($client->total_projects ?? 0),
            'invoice_value' => (float)($client->invoice_value ?? 0),
            'payment_received' => (float)($client->payment_received ?? 0),
            'created_date' => $client->created_date ?? null,
            'client_migration_date' => $client->client_migration_date ?? null,
            // Lead-specific fields
            'lead_status_id' => (int)($client->lead_status_id ?? 0),
            'lead_status_title' => $client->lead_status_title ?? '',
            'lead_status_color' => $client->lead_status_color ?? '',
            'lead_source_id' => (int)($client->lead_source_id ?? 0),
            'lead_source_title' => $client->lead_source_title ?? '',
            'manager_list' => $client->manager_list ?? ''
        );
    }
}

