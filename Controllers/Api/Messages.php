<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;

class Messages extends Api_controller
{
    public $Messages_model;
    public $Users_model;

    function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->Messages_model = model('App\Models\Messages_model', false);
        $this->Users_model = model('App\Models\Users_model', false);
    }

    /**
     * GET /api/v1/messages
     * List all messages with pagination and filtering
     */
    public function index()
    {
        try {
            $pagination = $this->_get_pagination_params(50, 100);
            $options = array(
                'limit' => $pagination['limit'],
                'skip' => $pagination['offset'],
                'user_id' => $this->authenticated_user_id
            );

            $search = $this->request->getGet('search');
            if (!empty($search)) {
                $options['search_by'] = $search;
            }

            $message_id = $this->request->getGet('message_id');
            if ($message_id) {
                $options['message_id'] = (int)$message_id;
            }

            $is_inbox = $this->request->getGet('is_inbox');
            if ($is_inbox == '1' || $is_inbox === 'true') {
                $options['is_inbox'] = true;
            }

            $result = $this->Messages_model->get_details($options);
            $messages = is_object($result) && isset($result->result) ? $result->result : [];
            $total = is_object($result) && isset($result->found_rows) ? $result->found_rows : count($messages);

            $formatted_messages = [];
            foreach ($messages as $message) {
                $formatted_messages[] = $this->_format_message($message);
            }

            $this->_api_list_response($formatted_messages, $total, 'messages', $pagination, true);
        } catch (\Exception $e) {
            log_message('error', 'API Messages::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve messages', 500);
        }
    }

    /**
     * GET /api/v1/messages/{id}
     * Get a specific message by ID
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Message ID is required'), 400);
                return;
            }

            $message = $this->Messages_model->get_one($id);

            if (!$message || !$message->id) {
                $this->_api_response(array('message' => 'Message not found'), 404);
                return;
            }

            // Get message details with related data
            $options = array('id' => $id, 'user_id' => $this->authenticated_user_id);
            $result = $this->Messages_model->get_details($options);
            $message_details = $result->getRow();

            if (!$message_details) {
                $this->_api_response(array('message' => 'Message not found'), 404);
                return;
            }

            $this->_api_response(array(
                'message' => $this->_format_message($message_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Messages::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve message',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/messages
     * Create a new message
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate using schema (standardized approach)
            $this->_validate_request_schema('Message', 'Create');
            
            // Fallback validation for required fields
            $this->_validate_required_fields(['subject', 'message', 'to_user_id']);

            // Validate to_user exists
            $to_user = $this->Users_model->get_one($data['to_user_id']);
            if (!$to_user || !$to_user->id) {
                $this->_api_response(array('message' => 'Invalid to_user_id'), 404);
                return;
            }

            // Prepare message data
            $message_data = array(
                'subject' => get_array_value($data, 'subject'),
                'message' => get_array_value($data, 'message'),
                'to_user_id' => (int)$data['to_user_id'],
                'from_user_id' => $this->authenticated_user_id,
                'status' => 'unread',
                'created_at' => get_current_utc_time()
            );

            // Clean data before saving
            $message_data = clean_data($message_data);

            // Save message
            $message_id = $this->Messages_model->ci_save($message_data);

            if (!$message_id) {
                $db_error = $this->Messages_model->db->error();
                log_message('error', 'API Messages::create database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to create message';
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
                        $error_message = 'A message with this information already exists';
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

            // Get the created message
            $message = $this->Messages_model->get_one($message_id);

            log_message('info', 'API: Message created successfully - ID: ' . $message_id);

            $this->_api_response(array(
                'message' => 'Message created successfully',
                'data' => $this->_format_message($message)
            ), 201);
        } catch (\Exception $e) {
            log_message('error', 'API Messages::create error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to create message',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * DELETE /api/v1/messages/{id}
     * Delete a message
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Message ID is required'), 400);
                return;
            }

            // Check if message exists
            $message = $this->Messages_model->get_one($id);
            if (!$message || !$message->id) {
                $this->_api_response(array('message' => 'Message not found'), 404);
                return;
            }

            // Delete message (soft delete)
            $success = $this->Messages_model->delete($id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to delete message'), 500);
                return;
            }

            log_message('info', 'API: Message deleted successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Message deleted successfully'
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Messages::delete error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to delete message',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format message data for API response
     */
    private function _format_message($message)
    {
        return array(
            'id' => (int)$message->id,
            'subject' => $message->subject ?? '',
            'message' => $message->message ?? '',
            'to_user_id' => (int)($message->to_user_id ?? 0),
            'from_user_id' => (int)($message->from_user_id ?? 0),
            'from_user' => $message->from_user ?? '',
            'to_user' => $message->to_user ?? '',
            'status' => $message->status ?? 'unread',
            'created_at' => $message->created_at ?? null
        );
    }
}

