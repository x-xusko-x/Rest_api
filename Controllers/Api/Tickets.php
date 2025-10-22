<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;

class Tickets extends Api_controller
{
    public $Tickets_model;
    public $Clients_model;
    public $Ticket_types_model;
    public $Projects_model;
    public $Users_model;
    public $Ticket_comments_model;

    function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->Tickets_model = model('App\Models\Tickets_model', false);
        $this->Clients_model = model('App\Models\Clients_model', false);
        $this->Ticket_types_model = model('App\Models\Ticket_types_model', false);
        $this->Projects_model = model('App\Models\Projects_model', false);
        $this->Users_model = model('App\Models\Users_model', false);
        $this->Ticket_comments_model = model('App\Models\Ticket_comments_model', false);
    }

    /**
     * GET /api/v1/tickets
     * List all tickets with pagination and filtering
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

            $assigned_to = $this->request->getGet('assigned_to');
            if ($assigned_to) {
                $options['assigned_to'] = $assigned_to;
            }

            $ticket_types = $this->request->getGet('ticket_types');
            if ($ticket_types) {
                $options['ticket_types'] = explode(',', $ticket_types);
            }

            $ticket_label = $this->request->getGet('ticket_label');
            if ($ticket_label) {
                $options['ticket_label'] = $ticket_label;
            }

            $result = $this->Tickets_model->get_details($options);
            $tickets = is_array($result) && isset($result['data']) ? $result['data'] : [];
            $total = is_array($result) && isset($result['recordsTotal']) ? $result['recordsTotal'] : 0;

            $formatted_tickets = [];
            foreach ($tickets as $ticket) {
                $formatted_tickets[] = $this->_format_ticket($ticket);
            }

            $this->_api_list_response($formatted_tickets, $total, 'tickets', $pagination, true);
        } catch (\Exception $e) {
            log_message('error', 'API Tickets::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve tickets', 500);
        }
    }

    /**
     * GET /api/v1/tickets/{id}
     * Get a specific ticket by ID
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Ticket ID is required'), 400);
                return;
            }

            $ticket = $this->Tickets_model->get_one($id);

            if (!$ticket || !$ticket->id) {
                $this->_api_response(array('message' => 'Ticket not found'), 404);
                return;
            }

            // Get ticket details with related data
            $options = array('id' => $id);
            $result = $this->Tickets_model->get_details($options);
            $ticket_details = $result->getRow();

            if (!$ticket_details) {
                $this->_api_response(array('message' => 'Ticket not found'), 404);
                return;
            }

            $this->_api_response(array(
                'ticket' => $this->_format_ticket($ticket_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Tickets::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve ticket',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/tickets
     * Create a new ticket
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            $required_fields = array('title', 'client_id', 'ticket_type_id');
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

            // Validate ticket type exists
            $ticket_type = $this->Ticket_types_model->get_one($data['ticket_type_id']);
            if (!$ticket_type || !$ticket_type->id) {
                $this->_api_response(array('message' => 'Invalid ticket type ID'), 404);
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

            // Validate assigned_to if provided
            if (!empty($data['assigned_to'])) {
                $user = $this->Users_model->get_one($data['assigned_to']);
                if (!$user || !$user->id) {
                    $this->_api_response(array('message' => 'Invalid assigned_to user ID'), 404);
                    return;
                }
            }

            // Prepare ticket data
            $now = get_current_utc_time();
            $ticket_data = array(
                'title' => get_array_value($data, 'title'),
                'client_id' => (int)$data['client_id'],
                'ticket_type_id' => (int)$data['ticket_type_id'],
                'project_id' => get_array_value($data, 'project_id', 0),
                'assigned_to' => get_array_value($data, 'assigned_to', 0),
                'requested_by' => get_array_value($data, 'requested_by', 0),
                'labels' => get_array_value($data, 'labels', ''),
                'created_by' => $this->authenticated_user_id,
                'created_at' => $now,
                'last_activity_at' => $now,
                'creator_name' => '',
                'creator_email' => ''
            );

            // Clean data before saving
            $ticket_data = clean_data($ticket_data);

            // Save ticket
            $ticket_id = $this->Tickets_model->ci_save($ticket_data);

            if (!$ticket_id) {
                // Get the actual database error
                $db_error = $this->Tickets_model->db->error();
                log_message('error', 'API Tickets::create database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to create ticket';
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
                        $error_message = 'A ticket with this information already exists';
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

            // Add description as first comment if provided
            $description = get_array_value($data, 'description', '');
            if ($description) {
                $comment_data = array(
                    'description' => $description,
                    'ticket_id' => $ticket_id,
                    'created_by' => $this->authenticated_user_id,
                    'created_at' => $now
                );
                $this->Ticket_comments_model->ci_save($comment_data);
            }

            // Get the created ticket
            $options = array('id' => $ticket_id);
            $result = $this->Tickets_model->get_details($options);
            $ticket_details = $result->getRow();

            log_message('info', 'API: Ticket created successfully - ID: ' . $ticket_id);

            $this->_api_response(array(
                'message' => 'Ticket created successfully',
                'ticket' => $this->_format_ticket($ticket_details)
            ), 201);
        } catch (\Exception $e) {
            log_message('error', 'API Tickets::create error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to create ticket',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * PUT /api/v1/tickets/{id}
     * Update an existing ticket
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Ticket ID is required'), 400);
                return;
            }

            // Check if ticket exists
            $ticket = $this->Tickets_model->get_one($id);
            if (!$ticket || !$ticket->id) {
                $this->_api_response(array('message' => 'Ticket not found'), 404);
                return;
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $ticket_data = array();

            // Only update provided fields
            if (isset($data['title'])) {
                $ticket_data['title'] = $data['title'];
            }
            if (isset($data['description'])) {
                $ticket_data['description'] = $data['description'];
            }
            if (isset($data['client_id'])) {
                $client = $this->Clients_model->get_one($data['client_id']);
                if (!$client || !$client->id) {
                    $this->_api_response(array('message' => 'Invalid client ID'), 404);
                    return;
                }
                $ticket_data['client_id'] = (int)$data['client_id'];
            }
            if (isset($data['ticket_type_id'])) {
                $ticket_type = $this->Ticket_types_model->get_one($data['ticket_type_id']);
                if (!$ticket_type || !$ticket_type->id) {
                    $this->_api_response(array('message' => 'Invalid ticket type ID'), 404);
                    return;
                }
                $ticket_data['ticket_type_id'] = (int)$data['ticket_type_id'];
            }
            if (isset($data['project_id'])) {
                if ($data['project_id'] > 0) {
                    $project = $this->Projects_model->get_one($data['project_id']);
                    if (!$project || !$project->id) {
                        $this->_api_response(array('message' => 'Invalid project ID'), 404);
                        return;
                    }
                }
                $ticket_data['project_id'] = (int)$data['project_id'];
            }
            if (isset($data['assigned_to'])) {
                if ($data['assigned_to'] > 0) {
                    $user = $this->Users_model->get_one($data['assigned_to']);
                    if (!$user || !$user->id) {
                        $this->_api_response(array('message' => 'Invalid assigned_to user ID'), 404);
                        return;
                    }
                }
                $ticket_data['assigned_to'] = (int)$data['assigned_to'];
            }
            if (isset($data['labels'])) {
                $ticket_data['labels'] = $data['labels'];
            }
            if (isset($data['status'])) {
                $ticket_data['status'] = $data['status'];
            }

            if (empty($ticket_data)) {
                $this->_api_response(array('message' => 'No valid fields to update'), 400);
                return;
            }

            // Clean data before saving
            $ticket_data = clean_data($ticket_data);

            // Update ticket
            $success = $this->Tickets_model->ci_save($ticket_data, $id);

            if (!$success) {
                $db_error = $this->Tickets_model->db->error();
                log_message('error', 'API Tickets::update database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to update ticket';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'A ticket with this information already exists';
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

            // Get updated ticket
            $options = array('id' => $id);
            $result = $this->Tickets_model->get_details($options);
            $ticket_details = $result->getRow();

            log_message('info', 'API: Ticket updated successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Ticket updated successfully',
                'ticket' => $this->_format_ticket($ticket_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Tickets::update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to update ticket',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * DELETE /api/v1/tickets/{id}
     * Delete a ticket
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Ticket ID is required'), 400);
                return;
            }

            // Check if ticket exists
            $ticket = $this->Tickets_model->get_one($id);
            if (!$ticket || !$ticket->id) {
                $this->_api_response(array('message' => 'Ticket not found'), 404);
                return;
            }

            // Delete ticket (soft delete)
            $success = $this->Tickets_model->delete($id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to delete ticket'), 500);
                return;
            }

            log_message('info', 'API: Ticket deleted successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Ticket deleted successfully'
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Tickets::delete error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to delete ticket',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format ticket data for API response
     */
    private function _format_ticket($ticket)
    {
        return array(
            'id' => (int)$ticket->id,
            'ticket_number' => $ticket->ticket_number ?? '',
            'title' => $ticket->title ?? '',
            'description' => $ticket->description ?? '',
            'client_id' => (int)($ticket->client_id ?? 0),
            'company_name' => $ticket->company_name ?? '',
            'ticket_type_id' => (int)($ticket->ticket_type_id ?? 0),
            'ticket_type' => $ticket->ticket_type ?? '',
            'project_id' => (int)($ticket->project_id ?? 0),
            'project_title' => $ticket->project_title ?? '',
            'assigned_to' => (int)($ticket->assigned_to ?? 0),
            'assigned_to_user' => $ticket->assigned_to_user ?? '',
            'assigned_to_avatar' => $ticket->assigned_to_avatar ?? '',
            'creator_name' => $ticket->creator_name ?? '',
            'creator_avatar' => $ticket->creator_avatar ?? '',
            'labels' => $ticket->labels ?? '',
            'status' => $ticket->status ?? 'new',
            'last_activity' => $ticket->last_activity ?? null,
            'total_comments' => (int)($ticket->total_comments ?? 0),
            'created_by' => (int)($ticket->created_by ?? 0),
            'created_date' => $ticket->created_date ?? null
        );
    }
}

