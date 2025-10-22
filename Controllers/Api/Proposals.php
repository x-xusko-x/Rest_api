<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;

class Proposals extends Api_controller
{
    public $Proposals_model;
    public $Clients_model;
    public $Projects_model;
    public $Proposal_items_model;

    function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->Proposals_model = model('App\Models\Proposals_model', false);
        $this->Clients_model = model('App\Models\Clients_model', false);
        $this->Projects_model = model('App\Models\Projects_model', false);
        $this->Proposal_items_model = model('App\Models\Proposal_items_model', false);
    }

    /**
     * GET /api/v1/proposals
     * List all proposals with pagination and filtering
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

            $all_result = $this->Proposals_model->get_details($options);
            $total_count = $all_result ? $all_result->getNumRows() : 0;
            $all_proposals = $all_result ? $all_result->getResult() : [];
            $paginated_proposals = array_slice($all_proposals, $pagination['offset'], $pagination['limit']);

            $formatted_proposals = [];
            foreach ($paginated_proposals as $proposal) {
                $formatted_proposals[] = $this->_format_proposal($proposal);
            }

            $this->_api_list_response($formatted_proposals, $total_count, 'proposals', $pagination, true);
        } catch (\Exception $e) {
            log_message('error', 'API Proposals::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve proposals', 500);
        }
    }

    /**
     * GET /api/v1/proposals/{id}
     * Get a specific proposal by ID
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Proposal ID is required'), 400);
                return;
            }

            $proposal = $this->Proposals_model->get_one($id);

            if (!$proposal || !$proposal->id) {
                $this->_api_response(array('message' => 'Proposal not found'), 404);
                return;
            }

            // Get proposal details with related data
            $options = array('id' => $id);
            $result = $this->Proposals_model->get_details($options);
            $proposal_details = $result->getRow();

            if (!$proposal_details) {
                $this->_api_response(array('message' => 'Proposal not found'), 404);
                return;
            }

            // Get proposal items
            $items = $this->Proposal_items_model->get_details(array('proposal_id' => $id))->getResult();
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

            $response = $this->_format_proposal($proposal_details);
            $response['items'] = $formatted_items;

            $this->_api_response(array(
                'proposal' => $response
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Proposals::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve proposal',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/proposals
     * Create a new proposal
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            $required_fields = array('client_id', 'proposal_date');
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

            // Prepare proposal data
            // Note: Proposals don't have a 'title' field, they use 'note' for content
            // If 'title' is provided, map it to 'note' if note is empty
            $note = get_array_value($data, 'note', '');
            if (!$note && isset($data['title'])) {
                $note = $data['title'];
            }
            if (!$note && isset($data['content'])) {
                $note = $data['content'];
            }
            
            $proposal_data = array(
                'client_id' => (int)$data['client_id'],
                'proposal_date' => get_array_value($data, 'proposal_date'),
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
            $proposal_data = clean_data($proposal_data);

            // Save proposal
            $proposal_id = $this->Proposals_model->ci_save($proposal_data);

            if (!$proposal_id) {
                // Get the actual database error
                $db_error = $this->Proposals_model->db->error();
                log_message('error', 'API Proposals::create database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to create proposal';
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
                        $error_message = 'A proposal with this information already exists';
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

            // Get the created proposal
            $options = array('id' => $proposal_id);
            $result = $this->Proposals_model->get_details($options);
            $proposal_details = $result->getRow();

            log_message('info', 'API: Proposal created successfully - ID: ' . $proposal_id);

            $this->_api_response(array(
                'message' => 'Proposal created successfully',
                'proposal' => $this->_format_proposal($proposal_details)
            ), 201);
        } catch (\Exception $e) {
            log_message('error', 'API Proposals::create error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to create proposal',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * PUT /api/v1/proposals/{id}
     * Update an existing proposal
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Proposal ID is required'), 400);
                return;
            }

            // Check if proposal exists
            $proposal = $this->Proposals_model->get_one($id);
            if (!$proposal || !$proposal->id) {
                $this->_api_response(array('message' => 'Proposal not found'), 404);
                return;
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $proposal_data = array();

            // Only update provided fields
            if (isset($data['client_id'])) {
                $client = $this->Clients_model->get_one($data['client_id']);
                if (!$client || !$client->id) {
                    $this->_api_response(array('message' => 'Invalid client ID'), 404);
                    return;
                }
                $proposal_data['client_id'] = (int)$data['client_id'];
            }
            
            if (isset($data['project_id'])) {
                if ($data['project_id'] > 0) {
                    $project = $this->Projects_model->get_one($data['project_id']);
                    if (!$project || !$project->id) {
                        $this->_api_response(array('message' => 'Invalid project ID'), 404);
                        return;
                    }
                }
                $proposal_data['project_id'] = (int)$data['project_id'];
            }

            if (isset($data['proposal_date'])) {
                $proposal_data['proposal_date'] = $data['proposal_date'];
            }
            if (isset($data['valid_until'])) {
                $proposal_data['valid_until'] = $data['valid_until'];
            }
            if (isset($data['note'])) {
                $proposal_data['note'] = $data['note'];
            }
            if (isset($data['status'])) {
                $proposal_data['status'] = $data['status'];
            }
            if (isset($data['tax_id'])) {
                $proposal_data['tax_id'] = (int)$data['tax_id'];
            }
            if (isset($data['tax_id2'])) {
                $proposal_data['tax_id2'] = (int)$data['tax_id2'];
            }
            if (isset($data['discount_amount'])) {
                $proposal_data['discount_amount'] = $data['discount_amount'];
            }
            if (isset($data['discount_amount_type'])) {
                $proposal_data['discount_amount_type'] = $data['discount_amount_type'];
            }
            if (isset($data['discount_type'])) {
                $proposal_data['discount_type'] = $data['discount_type'];
            }

            if (empty($proposal_data)) {
                $this->_api_response(array('message' => 'No valid fields to update'), 400);
                return;
            }

            // Clean data before saving
            $proposal_data = clean_data($proposal_data);

            // Update proposal
            $success = $this->Proposals_model->ci_save($proposal_data, $id);

            if (!$success) {
                $db_error = $this->Proposals_model->db->error();
                log_message('error', 'API Proposals::update database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to update proposal';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'A proposal with this information already exists';
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

            // Get updated proposal
            $options = array('id' => $id);
            $result = $this->Proposals_model->get_details($options);
            $proposal_details = $result->getRow();

            log_message('info', 'API: Proposal updated successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Proposal updated successfully',
                'proposal' => $this->_format_proposal($proposal_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Proposals::update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to update proposal',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * DELETE /api/v1/proposals/{id}
     * Delete a proposal
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Proposal ID is required'), 400);
                return;
            }

            // Check if proposal exists
            $proposal = $this->Proposals_model->get_one($id);
            if (!$proposal || !$proposal->id) {
                $this->_api_response(array('message' => 'Proposal not found'), 404);
                return;
            }

            // Delete proposal (soft delete)
            $success = $this->Proposals_model->delete($id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to delete proposal'), 500);
                return;
            }

            log_message('info', 'API: Proposal deleted successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Proposal deleted successfully'
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Proposals::delete error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to delete proposal',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format proposal data for API response
     */
    private function _format_proposal($proposal)
    {
        return array(
            'id' => (int)$proposal->id,
            'proposal_number' => $proposal->proposal_number ?? '',
            'client_id' => (int)($proposal->client_id ?? 0),
            'company_name' => $proposal->company_name ?? '',
            'project_id' => (int)($proposal->project_id ?? 0),
            'project_title' => $proposal->project_title ?? '',
            'proposal_date' => $proposal->proposal_date ?? null,
            'valid_until' => $proposal->valid_until ?? null,
            'note' => $proposal->note ?? '',
            'status' => $proposal->status ?? 'draft',
            'tax_id' => (int)($proposal->tax_id ?? 0),
            'tax_id2' => (int)($proposal->tax_id2 ?? 0),
            'tax_percentage' => (float)($proposal->tax_percentage ?? 0),
            'tax_percentage2' => (float)($proposal->tax_percentage2 ?? 0),
            'discount_amount' => (float)($proposal->discount_amount ?? 0),
            'discount_amount_type' => $proposal->discount_amount_type ?? 'percentage',
            'discount_type' => $proposal->discount_type ?? '',
            'proposal_value' => (float)($proposal->proposal_value ?? 0),
            'currency' => $proposal->currency ?? '',
            'currency_symbol' => $proposal->currency_symbol ?? '',
            'is_lead' => (int)($proposal->is_lead ?? 0),
            'accepted_by' => (int)($proposal->accepted_by ?? 0),
            'signer_name' => $proposal->signer_name ?? '',
            'signer_email' => $proposal->signer_email ?? '',
            'last_preview_seen' => $proposal->last_preview_seen ?? null,
            'created_by' => (int)($proposal->created_by ?? 0),
            'created_date' => $proposal->created_date ?? null
        );
    }
}

