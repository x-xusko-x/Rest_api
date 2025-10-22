<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;

class Projects extends Api_controller
{
    public $Projects_model;
    public $Clients_model;
    public $Users_model;

    function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->Projects_model = model('App\Models\Projects_model', false);
        $this->Clients_model = model('App\Models\Clients_model', false);
        $this->Users_model = model('App\Models\Users_model', false);
    }

    /**
     * GET /api/v1/projects
     * List all projects with pagination and filtering
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

            $status_id = $this->request->getGet('status_id');
            if ($status_id) {
                $options['status_id'] = (int)$status_id;
            }

            $result = $this->Projects_model->get_details($options);
            $projects = $result['data'] ?? [];
            $total = $result['recordsTotal'] ?? 0;

            $formatted_projects = [];
            foreach ($projects as $project) {
                $formatted_projects[] = $this->_format_project($project);
            }

            $this->_api_list_response($formatted_projects, $total, 'projects', $pagination, true);
        } catch (\Exception $e) {
            log_message('error', 'API Projects::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve projects', 500);
        }
    }

    /**
     * GET /api/v1/projects/{id}
     * Get a specific project by ID
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Project ID is required'), 400);
                return;
            }

            $project = $this->Projects_model->get_one($id);

            if (!$project || !$project->id) {
                $this->_api_response(array('message' => 'Project not found'), 404);
                return;
            }

            // Get project details with related data
            $options = array('id' => $id);
            $result = $this->Projects_model->get_details($options);
            $project_details = $result->getRow();

            if (!$project_details) {
                $this->_api_response(array('message' => 'Project not found'), 404);
                return;
            }

            $this->_api_response(array(
                'project' => $this->_format_project($project_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Projects::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve project',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/projects
     * Create a new project
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            $required_fields = array('title', 'client_id');
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
                $this->_api_response(array('message' => 'Client not found'), 404);
                return;
            }

            // Prepare project data
            $project_data = array(
                'title' => get_array_value($data, 'title'),
                'description' => get_array_value($data, 'description', ''),
                'client_id' => get_array_value($data, 'client_id'),
                'start_date' => get_array_value($data, 'start_date', null),
                'deadline' => get_array_value($data, 'deadline', null),
                'price' => get_array_value($data, 'price', 0),
                'project_type' => get_array_value($data, 'project_type', 'client_project'),
                'status_id' => get_array_value($data, 'status_id', 1), // 1 = Open
                'labels' => get_array_value($data, 'labels', ''),
                'created_date' => get_current_utc_time()
            );

            // Clean data before saving
            $project_data = clean_data($project_data);

            // Save project
            $project_id = $this->Projects_model->ci_save($project_data);

            if (!$project_id) {
                $db_error = $this->Projects_model->db->error();
                log_message('error', 'API Projects::create database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to create project';
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
                        $error_message = 'A project with this information already exists';
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

            // Get the created project
            $created_project = $this->Projects_model->get_one($project_id);
            $options = array('id' => $project_id);
            $result = $this->Projects_model->get_details($options);
            $project_details = $result->getRow();

            log_message('info', 'API: Project created successfully - ID: ' . $project_id);

            $this->_api_response(array(
                'message' => 'Project created successfully',
                'project' => $this->_format_project($project_details)
            ), 201);
        } catch (\Exception $e) {
            log_message('error', 'API Projects::create error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to create project',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * PUT /api/v1/projects/{id}
     * Update an existing project
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Project ID is required'), 400);
                return;
            }

            // Check if project exists
            $project = $this->Projects_model->get_one($id);
            if (!$project || !$project->id) {
                $this->_api_response(array('message' => 'Project not found'), 404);
                return;
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $project_data = array();

            // Only update provided fields
            if (isset($data['title'])) {
                $project_data['title'] = $data['title'];
            }
            if (isset($data['description'])) {
                $project_data['description'] = $data['description'];
            }
            if (isset($data['client_id'])) {
                // Validate client exists
                $client = $this->Clients_model->get_one($data['client_id']);
                if (!$client || !$client->id) {
                    $this->_api_response(array('message' => 'Client not found'), 404);
                    return;
                }
                $project_data['client_id'] = $data['client_id'];
            }
            if (isset($data['start_date'])) {
                $project_data['start_date'] = $data['start_date'];
            }
            if (isset($data['deadline'])) {
                $project_data['deadline'] = $data['deadline'];
            }
            if (isset($data['price'])) {
                $project_data['price'] = $data['price'];
            }
            if (isset($data['status_id'])) {
                $project_data['status_id'] = $data['status_id'];
            }
            if (isset($data['labels'])) {
                $project_data['labels'] = $data['labels'];
            }

            if (empty($project_data)) {
                $this->_api_response(array('message' => 'No valid fields to update'), 400);
                return;
            }

            // Clean data before saving
            $project_data = clean_data($project_data);

            // Update project
            $success = $this->Projects_model->ci_save($project_data, $id);

            if (!$success) {
                $db_error = $this->Projects_model->db->error();
                log_message('error', 'API Projects::update database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to update project';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'A project with this information already exists';
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

            // Get updated project
            $options = array('id' => $id);
            $result = $this->Projects_model->get_details($options);
            $project_details = $result->getRow();

            log_message('info', 'API: Project updated successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Project updated successfully',
                'project' => $this->_format_project($project_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Projects::update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to update project',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * DELETE /api/v1/projects/{id}
     * Delete a project
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Project ID is required'), 400);
                return;
            }

            // Check if project exists
            $project = $this->Projects_model->get_one($id);
            if (!$project || !$project->id) {
                $this->_api_response(array('message' => 'Project not found'), 404);
                return;
            }

            // Delete project (soft delete)
            $success = $this->Projects_model->delete_project_and_sub_items($id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to delete project'), 500);
                return;
            }

            log_message('info', 'API: Project deleted successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Project deleted successfully'
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Projects::delete error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to delete project',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format project data for API response
     */
    private function _format_project($project)
    {
        return array(
            'id' => (int)$project->id,
            'title' => $project->title ?? '',
            'description' => $project->description ?? '',
            'client_id' => (int)($project->client_id ?? 0),
            'company_name' => $project->company_name ?? '',
            'start_date' => $project->start_date ?? null,
            'deadline' => $project->deadline ?? null,
            'price' => (float)($project->price ?? 0),
            'project_type' => $project->project_type ?? '',
            'status_id' => (int)($project->status_id ?? 0),
            'status_title' => $project->status_title ?? '',
            'status_color' => $project->status_icon ?? '',
            'labels' => $project->labels ?? '',
            'total_points' => (int)($project->total_points ?? 0),
            'completed_points' => (int)($project->completed_points ?? 0),
            'created_date' => $project->created_date ?? null,
            'created_by' => (int)($project->created_by ?? 0)
        );
    }
}

