<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;

class Notes extends Api_controller
{
    public $Notes_model;
    public $Projects_model;
    public $Clients_model;
    public $Users_model;
    public $Note_category_model;

    function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->Notes_model = model('App\Models\Notes_model', false);
        $this->Projects_model = model('App\Models\Projects_model', false);
        $this->Clients_model = model('App\Models\Clients_model', false);
        $this->Users_model = model('App\Models\Users_model', false);
        $this->Note_category_model = model('App\Models\Note_category_model', false);
    }

    /**
     * GET /api/v1/notes
     * List all notes with pagination and filtering
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

            $project_id = $this->request->getGet('project_id');
            if ($project_id) {
                $options['project_id'] = (int)$project_id;
            }

            $client_id = $this->request->getGet('client_id');
            if ($client_id) {
                $options['client_id'] = (int)$client_id;
            }

            $user_id = $this->request->getGet('user_id');
            if ($user_id) {
                $options['user_id'] = (int)$user_id;
            }

            $category_id = $this->request->getGet('category_id');
            if ($category_id) {
                $options['category_id'] = (int)$category_id;
            }

            $label_id = $this->request->getGet('label_id');
            if ($label_id) {
                $options['label_id'] = $label_id;
            }

            $my_notes = $this->request->getGet('my_notes');
            if ($my_notes == '1' || $my_notes === 'true') {
                $options['created_by'] = $this->authenticated_user_id;
            }

            $all_result = $this->Notes_model->get_details($options);
            $total_count = $all_result ? $all_result->getNumRows() : 0;
            $all_notes = $all_result ? $all_result->getResult() : [];
            $paginated_notes = array_slice($all_notes, $pagination['offset'], $pagination['limit']);

            $formatted_notes = [];
            foreach ($paginated_notes as $note) {
                $formatted_notes[] = $this->_format_note($note);
            }

            $this->_api_list_response($formatted_notes, $total_count, 'notes', $pagination, true);
        } catch (\Exception $e) {
            log_message('error', 'API Notes::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve notes', 500);
        }
    }

    /**
     * GET /api/v1/notes/{id}
     * Get a specific note by ID
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Note ID is required'), 400);
                return;
            }

            $note = $this->Notes_model->get_one($id);

            if (!$note || !$note->id) {
                $this->_api_response(array('message' => 'Note not found'), 404);
                return;
            }

            // Get note details with related data
            $options = array('id' => $id);
            $result = $this->Notes_model->get_details($options);
            $note_details = $result->getRow();

            if (!$note_details) {
                $this->_api_response(array('message' => 'Note not found'), 404);
                return;
            }

            $this->_api_response(array(
                'note' => $this->_format_note($note_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Notes::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve note',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/notes
     * Create a new note
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            $required_fields = array('title');
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

            // Validate project if provided
            if (!empty($data['project_id'])) {
                $project = $this->Projects_model->get_one($data['project_id']);
                if (!$project || !$project->id) {
                    $this->_api_response(array('message' => 'Invalid project ID'), 404);
                    return;
                }
            }

            // Validate client if provided
            if (!empty($data['client_id'])) {
                $client = $this->Clients_model->get_one($data['client_id']);
                if (!$client || !$client->id) {
                    $this->_api_response(array('message' => 'Invalid client ID'), 404);
                    return;
                }
            }

            // Validate user if provided
            if (!empty($data['user_id'])) {
                $user = $this->Users_model->get_one($data['user_id']);
                if (!$user || !$user->id) {
                    $this->_api_response(array('message' => 'Invalid user ID'), 404);
                    return;
                }
            }

            // Validate category if provided
            if (!empty($data['category_id'])) {
                $category = $this->Note_category_model->get_one($data['category_id']);
                if (!$category || !$category->id) {
                    $this->_api_response(array('message' => 'Invalid category ID'), 404);
                    return;
                }
            }

            // Prepare note data
            $note_data = array(
                'title' => get_array_value($data, 'title'),
                'description' => get_array_value($data, 'description', ''),
                'project_id' => get_array_value($data, 'project_id', 0),
                'client_id' => get_array_value($data, 'client_id', 0),
                'user_id' => get_array_value($data, 'user_id', 0),
                'category_id' => get_array_value($data, 'category_id', 0),
                'labels' => get_array_value($data, 'labels', ''),
                'color' => get_array_value($data, 'color', ''),
                'is_public' => get_array_value($data, 'is_public', 0),
                'files' => '',
                'created_by' => $this->authenticated_user_id,
                'created_at' => get_current_utc_time()
            );

            // Clean data before saving
            $note_data = clean_data($note_data);

            // Save note
            $note_id = $this->Notes_model->ci_save($note_data);

            if (!$note_id) {
                $db_error = $this->Notes_model->db->error();
                log_message('error', 'API Notes::create database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to create note';
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
                        $error_message = 'A note with this information already exists';
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

            // Get the created note
            $options = array('id' => $note_id);
            $result = $this->Notes_model->get_details($options);
            $note_details = $result->getRow();

            log_message('info', 'API: Note created successfully - ID: ' . $note_id);

            $this->_api_response(array(
                'message' => 'Note created successfully',
                'note' => $this->_format_note($note_details)
            ), 201);
        } catch (\Exception $e) {
            log_message('error', 'API Notes::create error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to create note',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * PUT /api/v1/notes/{id}
     * Update an existing note
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Note ID is required'), 400);
                return;
            }

            // Check if note exists
            $note = $this->Notes_model->get_one($id);
            if (!$note || !$note->id) {
                $this->_api_response(array('message' => 'Note not found'), 404);
                return;
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $note_data = array();

            // Only update provided fields
            if (isset($data['title'])) {
                $note_data['title'] = $data['title'];
            }
            if (isset($data['description'])) {
                $note_data['description'] = $data['description'];
            }
            if (isset($data['project_id'])) {
                if ($data['project_id'] > 0) {
                    $project = $this->Projects_model->get_one($data['project_id']);
                    if (!$project || !$project->id) {
                        $this->_api_response(array('message' => 'Invalid project ID'), 404);
                        return;
                    }
                }
                $note_data['project_id'] = (int)$data['project_id'];
            }
            if (isset($data['client_id'])) {
                if ($data['client_id'] > 0) {
                    $client = $this->Clients_model->get_one($data['client_id']);
                    if (!$client || !$client->id) {
                        $this->_api_response(array('message' => 'Invalid client ID'), 404);
                        return;
                    }
                }
                $note_data['client_id'] = (int)$data['client_id'];
            }
            if (isset($data['user_id'])) {
                if ($data['user_id'] > 0) {
                    $user = $this->Users_model->get_one($data['user_id']);
                    if (!$user || !$user->id) {
                        $this->_api_response(array('message' => 'Invalid user ID'), 404);
                        return;
                    }
                }
                $note_data['user_id'] = (int)$data['user_id'];
            }
            if (isset($data['category_id'])) {
                if ($data['category_id'] > 0) {
                    $category = $this->Note_category_model->get_one($data['category_id']);
                    if (!$category || !$category->id) {
                        $this->_api_response(array('message' => 'Invalid category ID'), 404);
                        return;
                    }
                }
                $note_data['category_id'] = (int)$data['category_id'];
            }
            if (isset($data['labels'])) {
                $note_data['labels'] = $data['labels'];
            }
            if (isset($data['is_public'])) {
                $note_data['is_public'] = (int)$data['is_public'];
            }

            if (empty($note_data)) {
                $this->_api_response(array('message' => 'No valid fields to update'), 400);
                return;
            }

            // Clean data before saving
            $note_data = clean_data($note_data);

            // Update note
            $success = $this->Notes_model->ci_save($note_data, $id);

            if (!$success) {
                $db_error = $this->Notes_model->db->error();
                log_message('error', 'API Notes::update database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to update note';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'A note with this information already exists';
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

            // Get updated note
            $options = array('id' => $id);
            $result = $this->Notes_model->get_details($options);
            $note_details = $result->getRow();

            log_message('info', 'API: Note updated successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Note updated successfully',
                'note' => $this->_format_note($note_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Notes::update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to update note',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * DELETE /api/v1/notes/{id}
     * Delete a note
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Note ID is required'), 400);
                return;
            }

            // Check if note exists
            $note = $this->Notes_model->get_one($id);
            if (!$note || !$note->id) {
                $this->_api_response(array('message' => 'Note not found'), 404);
                return;
            }

            // Delete note (soft delete)
            $success = $this->Notes_model->delete($id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to delete note'), 500);
                return;
            }

            log_message('info', 'API: Note deleted successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Note deleted successfully'
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Notes::delete error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to delete note',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format note data for API response
     */
    private function _format_note($note)
    {
        return array(
            'id' => (int)$note->id,
            'title' => $note->title ?? '',
            'description' => $note->description ?? '',
            'project_id' => (int)($note->project_id ?? 0),
            'project_title' => $note->project_title ?? '',
            'client_id' => (int)($note->client_id ?? 0),
            'client_name' => $note->client_name ?? '',
            'user_id' => (int)($note->user_id ?? 0),
            'user_name' => $note->user_name ?? '',
            'category_id' => (int)($note->category_id ?? 0),
            'category_title' => $note->category_title ?? '',
            'labels' => $note->labels ?? '',
            'is_public' => (int)($note->is_public ?? 0),
            'created_by' => (int)($note->created_by ?? 0),
            'created_by_user' => $note->created_by_user ?? '',
            'created_by_avatar' => $note->created_by_avatar ?? '',
            'created_date' => $note->created_date ?? null
        );
    }
}

