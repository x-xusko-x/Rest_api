<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;

class Estimates extends Api_controller
{
    public $Estimates_model;
    public $Clients_model;
    public $Projects_model;
    public $Estimate_items_model;

    function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->Estimates_model = model('App\Models\Estimates_model', false);
        $this->Clients_model = model('App\Models\Clients_model', false);
        $this->Projects_model = model('App\Models\Projects_model', false);
        $this->Estimate_items_model = model('App\Models\Estimate_items_model', false);
    }

    /**
     * GET /api/v1/estimates
     * List all estimates with pagination and filtering
     */
    public function index()
    {
        try {
            // Use standardized pagination params with global limit enforcement
            $pagination = $this->_get_pagination_params(50, 100);
            
            // Build query options
            $options = array();
            
            // Add search if provided
            $search = $this->request->getGet('search');
            if (!empty($search)) {
                $options['search_by'] = $search;
            }

            // Add filters
            $client_id = $this->request->getGet('client_id');
            if ($client_id) {
                $options['client_id'] = (int)$client_id;
            }

            $status = $this->request->getGet('status');
            if ($status) {
                $options['status'] = $status;
            }

            $start_date = $this->request->getGet('start_date');
            $end_date = $this->request->getGet('end_date');
            if ($start_date && $end_date) {
                $options['start_date'] = $start_date;
                $options['end_date'] = $end_date;
            }

            $exclude_draft = $this->request->getGet('exclude_draft');
            if ($exclude_draft == '1' || $exclude_draft === 'true') {
                $options['exclude_draft'] = true;
            }

            // Get ALL estimates matching filters (for total count)
            $all_result = $this->Estimates_model->get_details($options);
            $total_count = $all_result ? $all_result->getNumRows() : 0;
            
            // Get only the paginated subset
            $all_estimates = $all_result ? $all_result->getResult() : [];
            
            // Apply pagination manually (since model doesn't support it)
            $paginated_estimates = array_slice($all_estimates, $pagination['offset'], $pagination['limit']);

            // Format estimates data
            $formatted_estimates = [];
            foreach ($paginated_estimates as $estimate) {
                $formatted_estimates[] = $this->_format_estimate($estimate);
            }

            // Use standardized response with automatic single-object unwrapping
            $this->_api_list_response(
                $formatted_estimates,
                $total_count,
                'estimates',
                $pagination,
                true  // Enable single-object unwrapping for limit=1
            );
            
        } catch (\Exception $e) {
            log_message('error', 'API Estimates::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve estimates', 500);
        }
    }

    /**
     * GET /api/v1/estimates/{id}
     * Get a specific estimate by ID
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Estimate ID is required'), 400);
                return;
            }

            $estimate = $this->Estimates_model->get_one($id);

            if (!$estimate || !$estimate->id) {
                $this->_api_response(array('message' => 'Estimate not found'), 404);
                return;
            }

            // Get estimate details with related data
            $options = array('id' => $id);
            $result = $this->Estimates_model->get_details($options);
            $estimate_details = $result->getRow();

            if (!$estimate_details) {
                $this->_api_response(array('message' => 'Estimate not found'), 404);
                return;
            }

            // Get estimate items
            $items = $this->Estimate_items_model->get_details(array('estimate_id' => $id))->getResult();
            $formatted_items = [];
            foreach ($items as $item) {
                $formatted_items[] = array(
                    'id' => (int)$item->id,
                    'title' => $item->title ?? '',
                    'description' => $item->description ?? '',
                    'quantity' => (float)($item->quantity ?? 0),
                    'rate' => (float)($item->rate ?? 0),
                    'total' => (float)($item->total ?? 0),
                    'sort' => (int)($item->sort ?? 0)
                );
            }

            $response = $this->_format_estimate($estimate_details);
            $response['items'] = $formatted_items;

            $this->_api_single_response($response, 'estimate');
        } catch (\Exception $e) {
            log_message('error', 'API Estimates::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve estimate',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/estimates
     * Create a new estimate
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            $required_fields = array('client_id', 'estimate_date');
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

            // Validate client exists
            $client = $this->Clients_model->get_one($data['client_id']);
            if (!$client || !$client->id) {
                $this->_api_response(array('message' => 'Invalid client ID'), 404);
                return;
            }

            // Validate project if provided
            if (!empty($data['project_id'])) {
                $project = $this->Projects_model->get_one($data['project_id']);
                if (!$project || !$project->id) {
                    $this->_api_response(array('message' => 'Invalid project ID'), 404);
                    return;
                }
            }

            // Prepare estimate data
            // Note: Estimates don't have a 'title' field, they use 'note' for content
            // If 'title' is provided, map it to 'note' if note is empty
            $note = get_array_value($data, 'note', '');
            if (!$note && isset($data['title'])) {
                $note = $data['title'];
            }
            if (!$note && isset($data['content'])) {
                $note = $data['content'];
            }
            
            $estimate_data = array(
                'client_id' => (int)$data['client_id'],
                'estimate_date' => get_array_value($data, 'estimate_date'),
                'valid_until' => get_array_value($data, 'valid_until', ''),
                'project_id' => get_array_value($data, 'project_id', 0),
                'note' => $note,
                'status' => get_array_value($data, 'status', 'draft'),
                'tax_id' => get_array_value($data, 'tax_id', 0),
                'tax_id2' => get_array_value($data, 'tax_id2', 0),
                'discount_amount' => get_array_value($data, 'discount_amount', 0),
                'discount_amount_type' => get_array_value($data, 'discount_amount_type', 'percentage'),
                'discount_type' => get_array_value($data, 'discount_type', ''),
                'company_id' => get_array_value($data, 'company_id') ? get_array_value($data, 'company_id') : get_default_company_id(),
                'public_key' => make_random_string(),
                'created_by' => $this->authenticated_user_id
            );

            // Clean data before saving
            $estimate_data = clean_data($estimate_data);

            // Save estimate
            $estimate_id = $this->Estimates_model->ci_save($estimate_data);

            if (!$estimate_id) {
                // Get the actual database error
                $db_error = $this->Estimates_model->db->error();
                log_message('error', 'API Estimates::create database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to create estimate';
                $error_details = [];
                
                // Check for common database errors
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    // Parse specific errors for better UX
                    if (strpos($db_error['message'], 'cannot be null') !== false || strpos($db_error['message'], "doesn't have a default value") !== false) {
                        preg_match("/Column '([^']+)'/", $db_error['message'], $matches);
                        if (isset($matches[1])) {
                            $error_details['missing_field'] = $matches[1];
                            $error_message = ucfirst(str_replace('_', ' ', $matches[1])) . ' is required';
                        }
                    } else if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'An estimate with this information already exists';
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

            // Get the created estimate
            $options = array('id' => $estimate_id);
            $result = $this->Estimates_model->get_details($options);
            $estimate_details = $result->getRow();

            log_message('info', 'API: Estimate created successfully - ID: ' . $estimate_id);

            $this->_api_created_response(
                $this->_format_estimate($estimate_details),
                'estimate'
            );
        } catch (\Exception $e) {
            log_message('error', 'API Estimates::create error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to create estimate',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * PUT /api/v1/estimates/{id}
     * Update an existing estimate
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Estimate ID is required'), 400);
                return;
            }

            // Check if estimate exists
            $estimate = $this->Estimates_model->get_one($id);
            if (!$estimate || !$estimate->id) {
                $this->_api_response(array('message' => 'Estimate not found'), 404);
                return;
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $estimate_data = array();

            // Only update provided fields
            if (isset($data['client_id'])) {
                $client = $this->Clients_model->get_one($data['client_id']);
                if (!$client || !$client->id) {
                    $this->_api_response(array('message' => 'Invalid client ID'), 404);
                    return;
                }
                $estimate_data['client_id'] = (int)$data['client_id'];
            }
            
            if (isset($data['project_id'])) {
                if ($data['project_id'] > 0) {
                    $project = $this->Projects_model->get_one($data['project_id']);
                    if (!$project || !$project->id) {
                        $this->_api_response(array('message' => 'Invalid project ID'), 404);
                        return;
                    }
                }
                $estimate_data['project_id'] = (int)$data['project_id'];
            }

            if (isset($data['estimate_date'])) {
                $estimate_data['estimate_date'] = $data['estimate_date'];
            }
            if (isset($data['valid_until'])) {
                $estimate_data['valid_until'] = $data['valid_until'];
            }
            if (isset($data['note'])) {
                $estimate_data['note'] = $data['note'];
            }
            if (isset($data['status'])) {
                $estimate_data['status'] = $data['status'];
            }
            if (isset($data['tax_id'])) {
                $estimate_data['tax_id'] = (int)$data['tax_id'];
            }
            if (isset($data['tax_id2'])) {
                $estimate_data['tax_id2'] = (int)$data['tax_id2'];
            }
            if (isset($data['discount_amount'])) {
                $estimate_data['discount_amount'] = $data['discount_amount'];
            }
            if (isset($data['discount_amount_type'])) {
                $estimate_data['discount_amount_type'] = $data['discount_amount_type'];
            }
            if (isset($data['discount_type'])) {
                $estimate_data['discount_type'] = $data['discount_type'];
            }

            if (empty($estimate_data)) {
                $this->_api_response(array('message' => 'No valid fields to update'), 400);
                return;
            }

            // Clean data before saving
            $estimate_data = clean_data($estimate_data);

            // Update estimate
            $success = $this->Estimates_model->ci_save($estimate_data, $id);

            if (!$success) {
                $db_error = $this->Estimates_model->db->error();
                log_message('error', 'API Estimates::update database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to update estimate';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'An estimate with this information already exists';
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

            // Get updated estimate
            $options = array('id' => $id);
            $result = $this->Estimates_model->get_details($options);
            $estimate_details = $result->getRow();

            log_message('info', 'API: Estimate updated successfully - ID: ' . $id);

            $this->_api_updated_response(
                $this->_format_estimate($estimate_details),
                'estimate'
            );
        } catch (\Exception $e) {
            log_message('error', 'API Estimates::update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to update estimate',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * DELETE /api/v1/estimates/{id}
     * Delete an estimate
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Estimate ID is required'), 400);
                return;
            }

            // Check if estimate exists
            $estimate = $this->Estimates_model->get_one($id);
            if (!$estimate || !$estimate->id) {
                $this->_api_response(array('message' => 'Estimate not found'), 404);
                return;
            }

            // Delete estimate (soft delete)
            $success = $this->Estimates_model->delete($id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to delete estimate'), 500);
                return;
            }

            log_message('info', 'API: Estimate deleted successfully - ID: ' . $id);

            $this->_api_deleted_response('estimate');
        } catch (\Exception $e) {
            log_message('error', 'API Estimates::delete error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to delete estimate',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format estimate data for API response
     */
    private function _format_estimate($estimate)
    {
        return array(
            'id' => (int)$estimate->id,
            'estimate_number' => $estimate->estimate_number ?? '',
            'client_id' => (int)($estimate->client_id ?? 0),
            'company_name' => $estimate->company_name ?? '',
            'project_id' => (int)($estimate->project_id ?? 0),
            'project_title' => $estimate->project_title ?? '',
            'estimate_date' => $estimate->estimate_date ?? null,
            'valid_until' => $estimate->valid_until ?? null,
            'note' => $estimate->note ?? '',
            'status' => $estimate->status ?? 'draft',
            'tax_id' => (int)($estimate->tax_id ?? 0),
            'tax_id2' => (int)($estimate->tax_id2 ?? 0),
            'tax_percentage' => (float)($estimate->tax_percentage ?? 0),
            'tax_percentage2' => (float)($estimate->tax_percentage2 ?? 0),
            'discount_amount' => (float)($estimate->discount_amount ?? 0),
            'discount_amount_type' => $estimate->discount_amount_type ?? 'percentage',
            'discount_type' => $estimate->discount_type ?? '',
            'estimate_value' => (float)($estimate->estimate_value ?? 0),
            'currency' => $estimate->currency ?? '',
            'currency_symbol' => $estimate->currency_symbol ?? '',
            'is_lead' => (int)($estimate->is_lead ?? 0),
            'accepted_by' => (int)($estimate->accepted_by ?? 0),
            'signer_name' => $estimate->signer_name ?? '',
            'signer_email' => $estimate->signer_email ?? '',
            'created_by' => (int)($estimate->created_by ?? 0),
            'created_date' => $estimate->created_date ?? null
        );
    }
}

