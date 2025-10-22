<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;

class Contracts extends Api_controller
{
    public $Contracts_model;
    public $Clients_model;
    public $Projects_model;
    public $Contract_items_model;

    function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->Contracts_model = model('App\Models\Contracts_model', false);
        $this->Clients_model = model('App\Models\Clients_model', false);
        $this->Projects_model = model('App\Models\Projects_model', false);
        $this->Contract_items_model = model('App\Models\Contract_items_model', false);
    }

    /**
     * GET /api/v1/contracts
     * List all contracts with pagination and filtering
     */
    public function index()
    {
        try {
            $pagination = $this->_get_pagination_params(50, 100);
            $options = array();

            $search = $this->request->getGet('search');
            if (!empty($search)) {
                $options['search_by'] = $search;
            }

            $client_id = $this->request->getGet('client_id');
            if ($client_id) {
                $options['client_id'] = (int)$client_id;
            }

            $project_id = $this->request->getGet('project_id');
            if ($project_id) {
                $options['project_id'] = (int)$project_id;
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

            $all_result = $this->Contracts_model->get_details($options);
            $total_count = $all_result ? $all_result->getNumRows() : 0;
            $all_contracts = $all_result ? $all_result->getResult() : [];
            $paginated_contracts = array_slice($all_contracts, $pagination['offset'], $pagination['limit']);

            $formatted_contracts = [];
            foreach ($paginated_contracts as $contract) {
                $formatted_contracts[] = $this->_format_contract($contract);
            }

            $this->_api_list_response($formatted_contracts, $total_count, 'contracts', $pagination, true);
        } catch (\Exception $e) {
            log_message('error', 'API Contracts::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve contracts', 500);
        }
    }

    /**
     * GET /api/v1/contracts/{id}
     * Get a specific contract by ID
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Contract ID is required'), 400);
                return;
            }

            $contract = $this->Contracts_model->get_one($id);

            if (!$contract || !$contract->id) {
                $this->_api_response(array('message' => 'Contract not found'), 404);
                return;
            }

            // Get contract details with related data
            $options = array('id' => $id);
            $result = $this->Contracts_model->get_details($options);
            $contract_details = $result->getRow();

            if (!$contract_details) {
                $this->_api_response(array('message' => 'Contract not found'), 404);
                return;
            }

            // Get contract items
            $items = $this->Contract_items_model->get_details(array('contract_id' => $id))->getResult();
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

            $response = $this->_format_contract($contract_details);
            $response['items'] = $formatted_items;

            $this->_api_response(array(
                'contract' => $response
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Contracts::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve contract',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/contracts
     * Create a new contract
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            $required_fields = array('client_id', 'contract_date');
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

            // Prepare contract data
            // Support both 'title' and 'subject' for backwards compatibility
            $title = get_array_value($data, 'title', '');
            if (!$title) {
                $title = get_array_value($data, 'subject', '');
            }
            
            $contract_data = array(
                'client_id' => (int)$data['client_id'],
                'contract_date' => get_array_value($data, 'contract_date'),
                'valid_until' => get_array_value($data, 'valid_until', ''),
                'project_id' => get_array_value($data, 'project_id', 0),
                'title' => $title,
                'note' => get_array_value($data, 'note', ''),
                'content' => get_array_value($data, 'content', ''),
                'status' => get_array_value($data, 'status', 'draft'),
                'tax_id' => get_array_value($data, 'tax_id', 0),
                'tax_id2' => get_array_value($data, 'tax_id2', 0),
                'discount_amount' => get_array_value($data, 'discount_amount', 0),
                'discount_amount_type' => get_array_value($data, 'discount_amount_type', 'percentage'),
                'discount_type' => get_array_value($data, 'discount_type', ''),
                'company_id' => get_array_value($data, 'company_id') ? get_array_value($data, 'company_id') : get_default_company_id(),
                'public_key' => make_random_string()
            );

            // Clean data before saving
            $contract_data = clean_data($contract_data);

            // Save contract
            $contract_id = $this->Contracts_model->ci_save($contract_data);

            if (!$contract_id) {
                // Get the actual database error
                $db_error = $this->Contracts_model->db->error();
                log_message('error', 'API Contracts::create database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to create contract';
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
                        $error_message = 'A contract with this information already exists';
                    }
                }
                
                $response = array('message' => $error_message);
                if (!empty($error_details)) {
                    $response['error_details'] = $error_details;
                }
                
                $this->_api_response($response, 500);
                return;
            }

            // Get the created contract
            $options = array('id' => $contract_id);
            $result = $this->Contracts_model->get_details($options);
            $contract_details = $result->getRow();

            log_message('info', 'API: Contract created successfully - ID: ' . $contract_id);

            $this->_api_response(array(
                'message' => 'Contract created successfully',
                'contract' => $this->_format_contract($contract_details)
            ), 201);
        } catch (\Exception $e) {
            log_message('error', 'API Contracts::create error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to create contract',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * PUT /api/v1/contracts/{id}
     * Update an existing contract
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Contract ID is required'), 400);
                return;
            }

            // Check if contract exists
            $contract = $this->Contracts_model->get_one($id);
            if (!$contract || !$contract->id) {
                $this->_api_response(array('message' => 'Contract not found'), 404);
                return;
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $contract_data = array();

            // Only update provided fields
            if (isset($data['client_id'])) {
                $client = $this->Clients_model->get_one($data['client_id']);
                if (!$client || !$client->id) {
                    $this->_api_response(array('message' => 'Invalid client ID'), 404);
                    return;
                }
                $contract_data['client_id'] = (int)$data['client_id'];
            }
            
            if (isset($data['project_id'])) {
                if ($data['project_id'] > 0) {
                    $project = $this->Projects_model->get_one($data['project_id']);
                    if (!$project || !$project->id) {
                        $this->_api_response(array('message' => 'Invalid project ID'), 404);
                        return;
                    }
                }
                $contract_data['project_id'] = (int)$data['project_id'];
            }

            if (isset($data['contract_date'])) {
                $contract_data['contract_date'] = $data['contract_date'];
            }
            if (isset($data['valid_until'])) {
                $contract_data['valid_until'] = $data['valid_until'];
            }
            if (isset($data['title'])) {
                $contract_data['title'] = $data['title'];
            }
            if (isset($data['note'])) {
                $contract_data['note'] = $data['note'];
            }
            if (isset($data['content'])) {
                $contract_data['content'] = $data['content'];
            }
            if (isset($data['status'])) {
                $contract_data['status'] = $data['status'];
            }
            if (isset($data['tax_id'])) {
                $contract_data['tax_id'] = (int)$data['tax_id'];
            }
            if (isset($data['tax_id2'])) {
                $contract_data['tax_id2'] = (int)$data['tax_id2'];
            }
            if (isset($data['discount_amount'])) {
                $contract_data['discount_amount'] = $data['discount_amount'];
            }
            if (isset($data['discount_amount_type'])) {
                $contract_data['discount_amount_type'] = $data['discount_amount_type'];
            }
            if (isset($data['discount_type'])) {
                $contract_data['discount_type'] = $data['discount_type'];
            }

            if (empty($contract_data)) {
                $this->_api_response(array('message' => 'No valid fields to update'), 400);
                return;
            }

            // Clean data before saving
            $contract_data = clean_data($contract_data);

            // Update contract
            $success = $this->Contracts_model->ci_save($contract_data, $id);

            if (!$success) {
                $db_error = $this->Contracts_model->db->error();
                log_message('error', 'API Contracts::update database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to update contract';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'A contract with this information already exists';
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

            // Get updated contract
            $options = array('id' => $id);
            $result = $this->Contracts_model->get_details($options);
            $contract_details = $result->getRow();

            log_message('info', 'API: Contract updated successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Contract updated successfully',
                'contract' => $this->_format_contract($contract_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Contracts::update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to update contract',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * DELETE /api/v1/contracts/{id}
     * Delete a contract
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Contract ID is required'), 400);
                return;
            }

            // Check if contract exists
            $contract = $this->Contracts_model->get_one($id);
            if (!$contract || !$contract->id) {
                $this->_api_response(array('message' => 'Contract not found'), 404);
                return;
            }

            // Delete contract (soft delete)
            $success = $this->Contracts_model->delete($id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to delete contract'), 500);
                return;
            }

            log_message('info', 'API: Contract deleted successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Contract deleted successfully'
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Contracts::delete error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to delete contract',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format contract data for API response
     */
    private function _format_contract($contract)
    {
        return array(
            'id' => (int)$contract->id,
            'contract_number' => $contract->contract_number ?? '',
            'title' => $contract->title ?? '',
            'client_id' => (int)($contract->client_id ?? 0),
            'company_name' => $contract->company_name ?? '',
            'project_id' => (int)($contract->project_id ?? 0),
            'project_title' => $contract->project_title ?? '',
            'contract_date' => $contract->contract_date ?? null,
            'valid_until' => $contract->valid_until ?? null,
            'note' => $contract->note ?? '',
            'content' => $contract->content ?? '',
            'status' => $contract->status ?? 'draft',
            'tax_id' => (int)($contract->tax_id ?? 0),
            'tax_id2' => (int)($contract->tax_id2 ?? 0),
            'tax_percentage' => (float)($contract->tax_percentage ?? 0),
            'tax_percentage2' => (float)($contract->tax_percentage2 ?? 0),
            'discount_amount' => (float)($contract->discount_amount ?? 0),
            'discount_amount_type' => $contract->discount_amount_type ?? 'percentage',
            'discount_type' => $contract->discount_type ?? '',
            'contract_value' => (float)($contract->contract_value ?? 0),
            'currency' => $contract->currency ?? '',
            'currency_symbol' => $contract->currency_symbol ?? '',
            'is_lead' => (int)($contract->is_lead ?? 0),
            'accepted_by' => (int)($contract->accepted_by ?? 0),
            'signer_name' => $contract->signer_name ?? '',
            'signer_email' => $contract->signer_email ?? '',
            'created_by' => (int)($contract->created_by ?? 0),
            'created_date' => $contract->created_date ?? null
        );
    }
}

