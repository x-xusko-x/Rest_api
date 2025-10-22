<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;

class Events extends Api_controller
{
    public $Events_model;
    public $Users_model;
    public $Clients_model;

    function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->Events_model = model('App\Models\Events_model', false);
        $this->Users_model = model('App\Models\Users_model', false);
        $this->Clients_model = model('App\Models\Clients_model', false);
    }

    /**
     * GET /api/v1/events
     * List all events with pagination and filtering
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

            $user_id = $this->request->getGet('user_id');
            if ($user_id) {
                $options['user_id'] = (int)$user_id;
            }

            $client_id = $this->request->getGet('client_id');
            if ($client_id) {
                $options['client_id'] = (int)$client_id;
            }

            $start_date = $this->request->getGet('start_date');
            if ($start_date) {
                $options['start_date'] = $start_date;
            }

            $end_date = $this->request->getGet('end_date');
            if ($end_date) {
                $options['end_date'] = $end_date;
            }

            $all_result = $this->Events_model->get_details($options);
            $total_count = $all_result ? $all_result->getNumRows() : 0;
            $all_events = $all_result ? $all_result->getResult() : [];
            $paginated_events = array_slice($all_events, $pagination['offset'], $pagination['limit']);

            $formatted_events = [];
            foreach ($paginated_events as $event) {
                $formatted_events[] = $this->_format_event($event);
            }

            $this->_api_list_response($formatted_events, $total_count, 'events', $pagination, true);
        } catch (\Exception $e) {
            log_message('error', 'API Events::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve events', 500);
        }
    }

    /**
     * GET /api/v1/events/{id}
     * Get a specific event by ID
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Event ID is required'), 400);
                return;
            }

            $event = $this->Events_model->get_one($id);

            if (!$event || !$event->id) {
                $this->_api_response(array('message' => 'Event not found'), 404);
                return;
            }

            // Get event details with related data
            $options = array('id' => $id);
            $result = $this->Events_model->get_details($options);
            $event_details = $result->getRow();

            if (!$event_details) {
                $this->_api_response(array('message' => 'Event not found'), 404);
                return;
            }

            $this->_api_response(array(
                'event' => $this->_format_event($event_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Events::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve event',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/events
     * Create a new event
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            $required_fields = array('title', 'start_date', 'end_date');
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

            // Prepare event data
            $event_data = array(
                'title' => get_array_value($data, 'title'),
                'description' => get_array_value($data, 'description', ''),
                'start_date' => get_array_value($data, 'start_date'),
                'end_date' => get_array_value($data, 'end_date'),
                'start_time' => get_array_value($data, 'start_time', ''),
                'end_time' => get_array_value($data, 'end_time', ''),
                'location' => get_array_value($data, 'location', ''),
                'color' => get_array_value($data, 'color', ''),
                'share_with' => get_array_value($data, 'share_with', ''),
                'labels' => get_array_value($data, 'labels', ''),
                'recurring' => get_array_value($data, 'recurring', 0),
                'repeat_every' => get_array_value($data, 'repeat_every', 0),
                'repeat_type' => get_array_value($data, 'repeat_type', null),
                'no_of_cycles' => get_array_value($data, 'no_of_cycles', 0),
                'recurring_dates' => '',
                'last_start_date' => null,
                'client_id' => get_array_value($data, 'client_id', 0),
                'type' => get_array_value($data, 'type', 'event'),
                'confirmed_by' => 0,
                'rejected_by' => 0,
                'created_by' => $this->authenticated_user_id
            );

            // Clean data before saving
            $event_data = clean_data($event_data);

            // Save event
            $event_id = $this->Events_model->ci_save($event_data);

            if (!$event_id) {
                // Get the actual database error
                $db_error = $this->Events_model->db->error();
                log_message('error', 'API Events::create database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to create event';
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
                        $error_message = 'An event with this information already exists';
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

            // Get the created event using simple get_one for immediate retrieval
            $event_details = $this->Events_model->get_one($event_id);

            if (!$event_details || !$event_details->id) {
                log_message('error', 'API: Event created but could not retrieve - ID: ' . $event_id);
                // Event was created but couldn't retrieve - still return success with basic data
                $this->_api_response(array(
                    'message' => 'Event created successfully',
                    'event' => array('id' => $event_id)
                ), 201);
                return;
            }

            log_message('info', 'API: Event created successfully - ID: ' . $event_id);

            $this->_api_response(array(
                'message' => 'Event created successfully',
                'event' => $this->_format_event($event_details)
            ), 201);
        } catch (\Exception $e) {
            log_message('error', 'API Events::create error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to create event',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * PUT /api/v1/events/{id}
     * Update an existing event
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Event ID is required'), 400);
                return;
            }

            // Check if event exists
            $event = $this->Events_model->get_one($id);
            if (!$event || !$event->id) {
                $this->_api_response(array('message' => 'Event not found'), 404);
                return;
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $event_data = array();

            // Only update provided fields
            if (isset($data['title'])) {
                $event_data['title'] = $data['title'];
            }
            if (isset($data['description'])) {
                $event_data['description'] = $data['description'];
            }
            if (isset($data['start_date'])) {
                $event_data['start_date'] = $data['start_date'];
            }
            if (isset($data['end_date'])) {
                $event_data['end_date'] = $data['end_date'];
            }
            if (isset($data['start_time'])) {
                $event_data['start_time'] = $data['start_time'];
            }
            if (isset($data['end_time'])) {
                $event_data['end_time'] = $data['end_time'];
            }
            if (isset($data['location'])) {
                $event_data['location'] = $data['location'];
            }
            if (isset($data['color'])) {
                $event_data['color'] = $data['color'];
            }
            if (isset($data['share_with'])) {
                $event_data['share_with'] = $data['share_with'];
            }

            if (empty($event_data)) {
                $this->_api_response(array('message' => 'No valid fields to update'), 400);
                return;
            }

            // Clean data before saving
            $event_data = clean_data($event_data);

            // Update event
            $success = $this->Events_model->ci_save($event_data, $id);

            if (!$success) {
                $db_error = $this->Events_model->db->error();
                log_message('error', 'API Events::update database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to update event';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'An event with this information already exists';
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

            // Get updated event
            $options = array('id' => $id);
            $result = $this->Events_model->get_details($options);
            $event_details = $result->getRow();

            log_message('info', 'API: Event updated successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Event updated successfully',
                'event' => $this->_format_event($event_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Events::update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to update event',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * DELETE /api/v1/events/{id}
     * Delete an event
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Event ID is required'), 400);
                return;
            }

            // Check if event exists
            $event = $this->Events_model->get_one($id);
            if (!$event || !$event->id) {
                $this->_api_response(array('message' => 'Event not found'), 404);
                return;
            }

            // Delete event (soft delete)
            $success = $this->Events_model->delete($id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to delete event'), 500);
                return;
            }

            log_message('info', 'API: Event deleted successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Event deleted successfully'
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Events::delete error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to delete event',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format event data for API response
     */
    private function _format_event($event)
    {
        return array(
            'id' => (int)$event->id,
            'title' => $event->title ?? '',
            'description' => $event->description ?? '',
            'start_date' => $event->start_date ?? null,
            'end_date' => $event->end_date ?? null,
            'start_time' => $event->start_time ?? '',
            'end_time' => $event->end_time ?? '',
            'location' => $event->location ?? '',
            'color' => $event->color ?? '',
            'share_with' => $event->share_with ?? '',
            'created_by' => (int)($event->created_by ?? 0),
            'created_by_user' => $event->created_by_user ?? '',
            'created_by_avatar' => $event->created_by_avatar ?? '',
            'created_date' => $event->created_date ?? null
        );
    }
}

