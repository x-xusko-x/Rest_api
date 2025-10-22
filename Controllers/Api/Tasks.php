<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;

class Tasks extends Api_controller
{
    public $Tasks_model;
    public $Projects_model;
    public $Users_model;

    function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->Tasks_model = model('App\Models\Tasks_model', false);
        $this->Projects_model = model('App\Models\Projects_model', false);
        $this->Users_model = model('App\Models\Users_model', false);
    }

    /**
     * GET /api/v1/tasks
     * List all tasks with pagination and filtering
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
                $options['project_id'] = (int)$project_id;
            }

            $assigned_to = $this->request->getGet('assigned_to');
            if ($assigned_to) {
                $options['assigned_to'] = (int)$assigned_to;
            }

            $status_id = $this->request->getGet('status_id');
            if ($status_id) {
                $options['task_status_id'] = (int)$status_id;
            }

            $priority_id = $this->request->getGet('priority_id');
            if ($priority_id) {
                $options['priority_id'] = (int)$priority_id;
            }

            $result = $this->Tasks_model->get_details($options);
            $tasks = $result['data'] ?? [];
            $total = $result['recordsTotal'] ?? 0;

            $formatted_tasks = [];
            foreach ($tasks as $task) {
                $formatted_tasks[] = $this->_format_task($task);
            }

            $this->_api_list_response($formatted_tasks, $total, 'tasks', $pagination, true);
        } catch (\Exception $e) {
            log_message('error', 'API Tasks::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve tasks', 500);
        }
    }

    /**
     * GET /api/v1/tasks/{id}
     * Get a specific task by ID
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Task ID is required'), 400);
                return;
            }

            $task = $this->Tasks_model->get_one($id);

            if (!$task || !$task->id) {
                $this->_api_response(array('message' => 'Task not found'), 404);
                return;
            }

            // Get task details with related data
            $options = array('id' => $id);
            $result = $this->Tasks_model->get_details($options);
            $task_details = $result->getRow();

            if (!$task_details) {
                $this->_api_response(array('message' => 'Task not found'), 404);
                return;
            }

            $this->_api_response(array(
                'task' => $this->_format_task($task_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Tasks::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve task',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/tasks
     * Create a new task
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            $required_fields = array('title', 'project_id');
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
                $this->_api_response(array('message' => 'Project not found'), 404);
                return;
            }

            // Validate assigned_to user if provided
            if (!empty($data['assigned_to'])) {
                $user = $this->Users_model->get_one($data['assigned_to']);
                if (!$user || !$user->id) {
                    $this->_api_response(array('message' => 'Assigned user not found'), 404);
                    return;
                }
            }

            // Get next sort value
            $sort_value = $this->Tasks_model->get_next_sort_value(
                $data['project_id'],
                get_array_value($data, 'status_id', 1)
            );

            // Prepare task data
            $task_data = array(
                'title' => get_array_value($data, 'title'),
                'description' => get_array_value($data, 'description', ''),
                'project_id' => get_array_value($data, 'project_id'),
                'assigned_to' => get_array_value($data, 'assigned_to', 0),
                'milestone_id' => get_array_value($data, 'milestone_id', 0),
                'status_id' => get_array_value($data, 'status_id', 1), // 1 = To Do
                'priority_id' => get_array_value($data, 'priority_id', 0),
                'labels' => get_array_value($data, 'labels', ''),
                'points' => get_array_value($data, 'points', 1),
                'start_date' => get_array_value($data, 'start_date', null),
                'deadline' => get_array_value($data, 'deadline', null),
                'context' => get_array_value($data, 'context', 'project'),
                'collaborators' => get_array_value($data, 'collaborators', ''),
                'sort' => $sort_value
            );

            // Clean data before saving
            $task_data = clean_data($task_data);

            // Save task
            $task_id = $this->Tasks_model->ci_save($task_data);

            if (!$task_id) {
                $db_error = $this->Tasks_model->db->error();
                log_message('error', 'API Tasks::create database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to create task';
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
                        $error_message = 'A task with this information already exists';
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

            // Get the created task
            $options = array('id' => $task_id);
            $result = $this->Tasks_model->get_details($options);
            $task_details = $result->getRow();

            log_message('info', 'API: Task created successfully - ID: ' . $task_id);

            $this->_api_response(array(
                'message' => 'Task created successfully',
                'task' => $this->_format_task($task_details)
            ), 201);
        } catch (\Exception $e) {
            log_message('error', 'API Tasks::create error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to create task',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * PUT /api/v1/tasks/{id}
     * Update an existing task
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Task ID is required'), 400);
                return;
            }

            // Check if task exists
            $task = $this->Tasks_model->get_one($id);
            if (!$task || !$task->id) {
                $this->_api_response(array('message' => 'Task not found'), 404);
                return;
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $task_data = array();

            // Only update provided fields
            if (isset($data['title'])) {
                $task_data['title'] = $data['title'];
            }
            if (isset($data['description'])) {
                $task_data['description'] = $data['description'];
            }
            if (isset($data['project_id'])) {
                // Validate project exists
                $project = $this->Projects_model->get_one($data['project_id']);
                if (!$project || !$project->id) {
                    $this->_api_response(array('message' => 'Project not found'), 404);
                    return;
                }
                $task_data['project_id'] = $data['project_id'];
            }
            if (isset($data['assigned_to'])) {
                // Validate user exists
                if ($data['assigned_to'] > 0) {
                    $user = $this->Users_model->get_one($data['assigned_to']);
                    if (!$user || !$user->id) {
                        $this->_api_response(array('message' => 'Assigned user not found'), 404);
                        return;
                    }
                }
                $task_data['assigned_to'] = $data['assigned_to'];
            }
            if (isset($data['milestone_id'])) {
                $task_data['milestone_id'] = $data['milestone_id'];
            }
            if (isset($data['status_id'])) {
                $task_data['status_id'] = $data['status_id'];
                $task_data['status_changed_at'] = get_current_utc_time();
            }
            if (isset($data['priority_id'])) {
                $task_data['priority_id'] = $data['priority_id'];
            }
            if (isset($data['labels'])) {
                $task_data['labels'] = $data['labels'];
            }
            if (isset($data['points'])) {
                $task_data['points'] = $data['points'];
            }
            if (isset($data['start_date'])) {
                $task_data['start_date'] = $data['start_date'];
            }
            if (isset($data['deadline'])) {
                $task_data['deadline'] = $data['deadline'];
            }
            if (isset($data['collaborators'])) {
                $task_data['collaborators'] = $data['collaborators'];
            }

            if (empty($task_data)) {
                $this->_api_response(array('message' => 'No valid fields to update'), 400);
                return;
            }

            // Clean data before saving
            $task_data = clean_data($task_data);

            // Update task
            $success = $this->Tasks_model->ci_save($task_data, $id);

            if (!$success) {
                $db_error = $this->Tasks_model->db->error();
                log_message('error', 'API Tasks::update database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to update task';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'A task with this information already exists';
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

            // Get updated task
            $options = array('id' => $id);
            $result = $this->Tasks_model->get_details($options);
            $task_details = $result->getRow();

            log_message('info', 'API: Task updated successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Task updated successfully',
                'task' => $this->_format_task($task_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Tasks::update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to update task',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * DELETE /api/v1/tasks/{id}
     * Delete a task
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Task ID is required'), 400);
                return;
            }

            // Check if task exists
            $task = $this->Tasks_model->get_one($id);
            if (!$task || !$task->id) {
                $this->_api_response(array('message' => 'Task not found'), 404);
                return;
            }

            // Delete task (soft delete with sub-items)
            $success = $this->Tasks_model->delete_task_and_sub_items($id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to delete task'), 500);
                return;
            }

            log_message('info', 'API: Task deleted successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Task deleted successfully'
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Tasks::delete error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to delete task',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format task data for API response
     */
    private function _format_task($task)
    {
        return array(
            'id' => (int)$task->id,
            'title' => $task->title ?? '',
            'description' => $task->description ?? '',
            'project_id' => (int)($task->project_id ?? 0),
            'project_title' => $task->project_title ?? '',
            'assigned_to' => (int)($task->assigned_to ?? 0),
            'assigned_to_user' => $task->assigned_to_user ?? '',
            'assigned_to_avatar' => $task->assigned_to_avatar ?? '',
            'milestone_id' => (int)($task->milestone_id ?? 0),
            'milestone_title' => $task->milestone_title ?? '',
            'status_id' => (int)($task->status_id ?? 0),
            'status_title' => $task->status_title ?? '',
            'status_color' => $task->status_color ?? '',
            'priority_id' => (int)($task->priority_id ?? 0),
            'priority_title' => $task->priority_title ?? '',
            'priority_icon' => $task->priority_icon ?? '',
            'priority_color' => $task->priority_color ?? '',
            'labels' => $task->labels ?? '',
            'points' => (int)($task->points ?? 0),
            'start_date' => $task->start_date ?? null,
            'deadline' => $task->deadline ?? null,
            'context' => $task->context ?? 'project',
            'collaborators' => $task->collaborators ?? '',
            'collaborator_list' => $task->collaborator_list ?? '',
            'created_date' => $task->created_date ?? null,
            'created_by' => (int)($task->created_by ?? 0),
            'sort' => (int)($task->sort ?? 0)
        );
    }
}
