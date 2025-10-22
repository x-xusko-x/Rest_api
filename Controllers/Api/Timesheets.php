<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;

class Timesheets extends Api_controller
{
    public $Timesheets_model;
    public $Projects_model;
    public $Tasks_model;
    public $Users_model;

    function __construct()
    {
        parent::__construct();

        // Initialize models
        $this->Timesheets_model = model('App\Models\Timesheets_model', false);
        $this->Projects_model = model('App\Models\Projects_model', false);
        $this->Tasks_model = model('App\Models\Tasks_model', false);
        $this->Users_model = model('App\Models\Users_model', false);
    }

    /**
     * GET /api/v1/timesheets
     * List all timesheets with pagination and filtering
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

            $project_id = $this->request->getGet('project_id');
            if ($project_id) {
                $options['project_id'] = (int) $project_id;
            }

            $task_id = $this->request->getGet('task_id');
            if ($task_id) {
                $options['task_id'] = (int) $task_id;
            }

            $user_id = $this->request->getGet('user_id');
            if ($user_id) {
                $options['user_id'] = (int) $user_id;
            }

            $client_id = $this->request->getGet('client_id');
            if ($client_id) {
                $options['client_id'] = (int) $client_id;
            }

            $status = $this->request->getGet('status');
            if ($status) {
                $options['status'] = $status;
            }

            $start_date = $this->request->getGet('start_date');
            if ($start_date) {
                $options['start_date'] = $start_date;
            }

            $end_date = $this->request->getGet('end_date');
            if ($end_date) {
                $options['end_date'] = $end_date;
            }

            $result = $this->Timesheets_model->get_details($options);
            $timesheets = is_array($result) && isset($result['data']) ? $result['data'] : [];
            $total = is_array($result) && isset($result['recordsTotal']) ? $result['recordsTotal'] : 0;

            $formatted_timesheets = [];
            foreach ($timesheets as $timesheet) {
                $formatted_timesheets[] = $this->_format_timesheet($timesheet);
            }

            $this->_api_list_response($formatted_timesheets, $total, 'timesheets', $pagination, true);
        } catch (\Exception $e) {
            log_message('error', 'API Timesheets::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve timesheets', 500);
        }
    }

    /**
     * GET /api/v1/timesheets/{id}
     * Get a specific timesheet by ID
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Timesheet ID is required'), 400);
                return;
            }

            $timesheet = $this->Timesheets_model->get_one($id);

            if (!$timesheet || !$timesheet->id) {
                $this->_api_response(array('message' => 'Timesheet not found'), 404);
                return;
            }

            // Get timesheet details with related data
            $options = array('id' => $id);
            $result = $this->Timesheets_model->get_details($options);
            $timesheet_details = $result->getRow();

            if (!$timesheet_details) {
                $this->_api_response(array('message' => 'Timesheet not found'), 404);
                return;
            }

            $this->_api_response(array(
                'timesheet' => $this->_format_timesheet($timesheet_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Timesheets::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve timesheet',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/timesheets
     * Create a new timesheet
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            $required_fields = array('project_id', 'user_id', 'start_time');
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

            // Validate project exists
            $project = $this->Projects_model->get_one($data['project_id']);
            if (!$project || !$project->id) {
                $this->_api_response(array('message' => 'Invalid project ID'), 404);
                return;
            }

            // Validate user exists
            $user = $this->Users_model->get_one($data['user_id']);
            if (!$user || !$user->id) {
                $this->_api_response(array('message' => 'Invalid user ID'), 404);
                return;
            }

            // Validate task if provided
            if (!empty($data['task_id'])) {
                $task = $this->Tasks_model->get_one($data['task_id']);
                if (!$task || !$task->id) {
                    $this->_api_response(array('message' => 'Invalid task ID'), 404);
                    return;
                }
            }

            // Prepare timesheet data
            $start_time = get_array_value($data, 'start_time');
            $end_time = get_array_value($data, 'end_time', '');
            
            // Calculate hours from start_time and end_time
            $hours = 0;
            if ($start_time && $end_time) {
                $start_timestamp = strtotime($start_time);
                $end_timestamp = strtotime($end_time);
                if ($start_timestamp && $end_timestamp && $end_timestamp > $start_timestamp) {
                    $hours = ($end_timestamp - $start_timestamp) / 3600; // Convert seconds to hours
                }
            }
            
            $timesheet_data = array(
                'project_id' => (int) $data['project_id'],
                'user_id' => (int) $data['user_id'],
                'task_id' => get_array_value($data, 'task_id', 0),
                'start_time' => $start_time,
                'end_time' => $end_time,
                'note' => get_array_value($data, 'note', ''),
                'hours' => $hours
            );

            // Clean data before saving
            $timesheet_data = clean_data($timesheet_data);

            // Save timesheet
            $timesheet_id = $this->Timesheets_model->ci_save($timesheet_data);

            if (!$timesheet_id) {
                $db_error = $this->Timesheets_model->db->error();
                log_message('error', 'API Timesheets::create database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to create timesheet';
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
                        $error_message = 'A timesheet with this information already exists';
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

            // Get the created timesheet
            $options = array('id' => $timesheet_id);
            $result = $this->Timesheets_model->get_details($options);
            $timesheet_details = $result->getRow();

            log_message('info', 'API: Timesheet created successfully - ID: ' . $timesheet_id);

            $this->_api_response(array(
                'message' => 'Timesheet created successfully',
                'timesheet' => $this->_format_timesheet($timesheet_details)
            ), 201);
        } catch (\Exception $e) {
            log_message('error', 'API Timesheets::create error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to create timesheet',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * PUT /api/v1/timesheets/{id}
     * Update an existing timesheet
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Timesheet ID is required'), 400);
                return;
            }

            // Check if timesheet exists
            $timesheet = $this->Timesheets_model->get_one($id);
            if (!$timesheet || !$timesheet->id) {
                $this->_api_response(array('message' => 'Timesheet not found'), 404);
                return;
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $timesheet_data = array();

            // Only update provided fields
            if (isset($data['project_id'])) {
                $project = $this->Projects_model->get_one($data['project_id']);
                if (!$project || !$project->id) {
                    $this->_api_response(array('message' => 'Invalid project ID'), 404);
                    return;
                }
                $timesheet_data['project_id'] = (int) $data['project_id'];
            }
            if (isset($data['user_id'])) {
                $user = $this->Users_model->get_one($data['user_id']);
                if (!$user || !$user->id) {
                    $this->_api_response(array('message' => 'Invalid user ID'), 404);
                    return;
                }
                $timesheet_data['user_id'] = (int) $data['user_id'];
            }
            if (isset($data['task_id'])) {
                if ($data['task_id'] > 0) {
                    $task = $this->Tasks_model->get_one($data['task_id']);
                    if (!$task || !$task->id) {
                        $this->_api_response(array('message' => 'Invalid task ID'), 404);
                        return;
                    }
                }
                $timesheet_data['task_id'] = (int) $data['task_id'];
            }
            if (isset($data['start_time'])) {
                $timesheet_data['start_time'] = $data['start_time'];
            }
            if (isset($data['end_time'])) {
                $timesheet_data['end_time'] = $data['end_time'];
            }
            if (isset($data['note'])) {
                $timesheet_data['note'] = $data['note'];
            }
            if (isset($data['status'])) {
                $timesheet_data['status'] = $data['status'];
            }

            if (empty($timesheet_data)) {
                $this->_api_response(array('message' => 'No valid fields to update'), 400);
                return;
            }

            // Clean data before saving
            $timesheet_data = clean_data($timesheet_data);

            // Update timesheet
            $success = $this->Timesheets_model->ci_save($timesheet_data, $id);

            if (!$success) {
                $db_error = $this->Timesheets_model->db->error();
                log_message('error', 'API Timesheets::update database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to update timesheet';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'A timesheet with this information already exists';
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

            // Get updated timesheet
            $options = array('id' => $id);
            $result = $this->Timesheets_model->get_details($options);
            $timesheet_details = $result->getRow();

            log_message('info', 'API: Timesheet updated successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Timesheet updated successfully',
                'timesheet' => $this->_format_timesheet($timesheet_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Timesheets::update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to update timesheet',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * DELETE /api/v1/timesheets/{id}
     * Delete a timesheet
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Timesheet ID is required'), 400);
                return;
            }

            // Check if timesheet exists
            $timesheet = $this->Timesheets_model->get_one($id);
            if (!$timesheet || !$timesheet->id) {
                $this->_api_response(array('message' => 'Timesheet not found'), 404);
                return;
            }

            // Delete timesheet (soft delete)
            $success = $this->Timesheets_model->delete($id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to delete timesheet'), 500);
                return;
            }

            log_message('info', 'API: Timesheet deleted successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Timesheet deleted successfully'
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Timesheets::delete error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to delete timesheet',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format timesheet data for API response
     */
    private function _format_timesheet($timesheet)
    {
        return array(
            'id' => (int) $timesheet->id,
            'project_id' => (int) ($timesheet->project_id ?? 0),
            'project_title' => $timesheet->project_title ?? '',
            'task_id' => (int) ($timesheet->task_id ?? 0),
            'task_title' => $timesheet->task_title ?? '',
            'user_id' => (int) ($timesheet->user_id ?? 0),
            'user_name' => $timesheet->member_name ?? '',
            'user_avatar' => $timesheet->user_avatar ?? '',
            'start_time' => $timesheet->start_time ?? null,
            'end_time' => $timesheet->end_time ?? null,
            'note' => $timesheet->note ?? '',
            'status' => $timesheet->status ?? 'open',
            'client_id' => (int) ($timesheet->client_id ?? 0),
            'client_name' => $timesheet->company_name ?? '',
            'created_by' => (int) ($timesheet->created_by ?? 0),
            'created_date' => $timesheet->created_date ?? null
        );
    }
}

