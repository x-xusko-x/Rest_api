<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;
use OpenApi\Attributes as OA;

/**
 * Announcements API Controller
 * 
 * @OA\Tag(
 *     name="Announcements",
 *     description="Announcement management operations"
 * )
 */
class Announcements extends Api_controller
{
    public $Announcements_model;
    public $Users_model;

    function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->Announcements_model = model('App\Models\Announcements_model', false);
        $this->Users_model = model('App\Models\Users_model', false);
    }

    /**
     * GET /api/v1/announcements
     * List all announcements with pagination and filtering
     * 
     * @OA\Get(
     *     path="/api/v1/announcements",
     *     tags={"Announcements"},
     *     summary="List all announcements",
     *     description="Retrieve a paginated list of announcements with optional filtering",
     *     operationId="listAnnouncements",
     *     @OA\Parameter(name="limit", in="query", description="Number of items to return (max: 100)", required=false, @OA\Schema(type="integer", minimum=1, maximum=100, default=50)),
     *     @OA\Parameter(name="offset", in="query", description="Number of items to skip", required=false, @OA\Schema(type="integer", minimum=0, default=0)),
     *     @OA\Parameter(name="search", in="query", description="Search term", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="start_date", in="query", description="Filter by start date (YYYY-MM-DD)", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", description="Filter by end date (YYYY-MM-DD)", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Successful response", @OA\JsonContent(ref="#/components/schemas/AnnouncementListResponse")),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={{"ApiKeyAuth": {}, "ApiSecretAuth": {}}, {"BearerAuth": {}}}
     * )
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

            $start_date = $this->request->getGet('start_date');
            $end_date = $this->request->getGet('end_date');
            if ($start_date && $end_date) {
                $options['start_date'] = $start_date;
                $options['end_date'] = $end_date;
            }

            $all_result = $this->Announcements_model->get_details($options);
            $total_count = $all_result ? $all_result->getNumRows() : 0;
            $all_announcements = $all_result ? $all_result->getResult() : [];
            $paginated_announcements = array_slice($all_announcements, $pagination['offset'], $pagination['limit']);

            $formatted_announcements = [];
            foreach ($paginated_announcements as $announcement) {
                $formatted_announcements[] = $this->_format_announcement($announcement);
            }

            $this->_api_list_response($formatted_announcements, $total_count, 'announcements', $pagination, true);
        } catch (\Exception $e) {
            log_message('error', 'API Announcements::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve announcements', 500);
        }
    }

    /**
     * GET /api/v1/announcements/{id}
     * Get a specific announcement by ID
     * 
     * @OA\Get(
     *     path="/api/v1/announcements/{id}",
     *     tags={"Announcements"},
     *     summary="Get an announcement by ID",
     *     description="Retrieve detailed information about a specific announcement",
     *     operationId="getAnnouncementById",
     *     @OA\Parameter(name="id", in="path", description="Announcement ID", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Response(response=200, description="Successful response", @OA\JsonContent(ref="#/components/schemas/AnnouncementResponse")),
     *     @OA\Response(response=400, description="Bad Request - Invalid ID", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Announcement not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={{"ApiKeyAuth": {}, "ApiSecretAuth": {}}, {"BearerAuth": {}}}
     * )
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Announcement ID is required'), 400);
                return;
            }

            $announcement = $this->Announcements_model->get_one($id);

            if (!$announcement || !$announcement->id) {
                $this->_api_response(array('message' => 'Announcement not found'), 404);
                return;
            }

            // Get announcement details with related data
            $options = array('id' => $id);
            $result = $this->Announcements_model->get_details($options);
            $announcement_details = $result->getRow();

            if (!$announcement_details) {
                $this->_api_response(array('message' => 'Announcement not found'), 404);
                return;
            }

            $this->_api_response(array(
                'announcement' => $this->_format_announcement($announcement_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Announcements::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve announcement',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/announcements
     * Create a new announcement
     * 
     * @OA\Post(
     *     path="/api/v1/announcements",
     *     tags={"Announcements"},
     *     summary="Create a new announcement",
     *     description="Create a new announcement with the provided data",
     *     operationId="createAnnouncement",
     *     @OA\RequestBody(required=true, description="Announcement data", @OA\JsonContent(ref="#/components/schemas/CreateAnnouncementRequest")),
     *     @OA\Response(response=201, description="Announcement created successfully", @OA\JsonContent(ref="#/components/schemas/AnnouncementResponse")),
     *     @OA\Response(response=400, description="Validation failed", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={{"ApiKeyAuth": {}, "ApiSecretAuth": {}}, {"BearerAuth": {}}}
     * )
     */
    public function create()
    {
        try {
            // Validate request against OpenAPI schema
            $this->_validate_request_schema('Announcement', 'Create');
            
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

            // Prepare announcement data
            $announcement_data = array(
                'title' => get_array_value($data, 'title'),
                'description' => get_array_value($data, 'description', ''),
                'start_date' => get_array_value($data, 'start_date', ''),
                'end_date' => get_array_value($data, 'end_date', ''),
                'share_with' => get_array_value($data, 'share_with', ''),
                'files' => '',
                'read_by' => 0,
                'created_by' => $this->authenticated_user_id,
                'created_at' => get_current_utc_time()
            );

            // Clean data before saving
            $announcement_data = clean_data($announcement_data);

            // Save announcement
            $announcement_id = $this->Announcements_model->ci_save($announcement_data);

            if (!$announcement_id) {
                $db_error = $this->Announcements_model->db->error();
                log_message('error', 'API Announcements::create database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to create announcement';
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
                        $error_message = 'An announcement with this information already exists';
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

            // Get the created announcement
            $options = array('id' => $announcement_id);
            $result = $this->Announcements_model->get_details($options);
            $announcement_details = $result->getRow();

            log_message('info', 'API: Announcement created successfully - ID: ' . $announcement_id);

            $this->_api_response(array(
                'message' => 'Announcement created successfully',
                'announcement' => $this->_format_announcement($announcement_details)
            ), 201);
        } catch (\Exception $e) {
            log_message('error', 'API Announcements::create error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to create announcement',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * PUT /api/v1/announcements/{id}
     * Update an existing announcement
     * 
     * @OA\Put(
     *     path="/api/v1/announcements/{id}",
     *     tags={"Announcements"},
     *     summary="Update an announcement",
     *     description="Update an existing announcement with the provided data",
     *     operationId="updateAnnouncement",
     *     @OA\Parameter(name="id", in="path", description="Announcement ID", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\RequestBody(required=true, description="Announcement data to update", @OA\JsonContent(ref="#/components/schemas/UpdateAnnouncementRequest")),
     *     @OA\Response(response=200, description="Announcement updated successfully", @OA\JsonContent(ref="#/components/schemas/AnnouncementResponse")),
     *     @OA\Response(response=400, description="Invalid request data", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Announcement not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={{"ApiKeyAuth": {}, "ApiSecretAuth": {}}, {"BearerAuth": {}}}
     * )
     */
    public function update($id = null)
    {
        try {
            // Validate request against OpenAPI schema
            $this->_validate_request_schema('Announcement', 'Update');
            if (!$id) {
                $this->_api_response(array('message' => 'Announcement ID is required'), 400);
                return;
            }

            // Check if announcement exists
            $announcement = $this->Announcements_model->get_one($id);
            if (!$announcement || !$announcement->id) {
                $this->_api_response(array('message' => 'Announcement not found'), 404);
                return;
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $announcement_data = array();

            // Only update provided fields
            if (isset($data['title'])) {
                $announcement_data['title'] = $data['title'];
            }
            if (isset($data['description'])) {
                $announcement_data['description'] = $data['description'];
            }
            if (isset($data['start_date'])) {
                $announcement_data['start_date'] = $data['start_date'];
            }
            if (isset($data['end_date'])) {
                $announcement_data['end_date'] = $data['end_date'];
            }
            if (isset($data['share_with'])) {
                $announcement_data['share_with'] = $data['share_with'];
            }

            if (empty($announcement_data)) {
                $this->_api_response(array('message' => 'No valid fields to update'), 400);
                return;
            }

            // Clean data before saving
            $announcement_data = clean_data($announcement_data);

            // Update announcement
            $success = $this->Announcements_model->ci_save($announcement_data, $id);

            if (!$success) {
                $db_error = $this->Announcements_model->db->error();
                log_message('error', 'API Announcements::update database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to update announcement';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'An announcement with this information already exists';
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

            // Get updated announcement
            $options = array('id' => $id);
            $result = $this->Announcements_model->get_details($options);
            $announcement_details = $result->getRow();

            log_message('info', 'API: Announcement updated successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Announcement updated successfully',
                'announcement' => $this->_format_announcement($announcement_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Announcements::update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to update announcement',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * DELETE /api/v1/announcements/{id}
     * Delete an announcement
     * 
     * @OA\Delete(
     *     path="/api/v1/announcements/{id}",
     *     tags={"Announcements"},
     *     summary="Delete an announcement",
     *     description="Delete an announcement permanently",
     *     operationId="deleteAnnouncement",
     *     @OA\Parameter(name="id", in="path", description="Announcement ID", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Response(response=200, description="Announcement deleted successfully", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="data", type="object", @OA\Property(property="message", type="string", example="Announcement deleted successfully")), @OA\Property(property="meta", ref="#/components/schemas/ResponseMeta"))),
     *     @OA\Response(response=400, description="Invalid announcement ID", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Announcement not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={{"ApiKeyAuth": {}, "ApiSecretAuth": {}}, {"BearerAuth": {}}}
     * )
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Announcement ID is required'), 400);
                return;
            }

            // Check if announcement exists
            $announcement = $this->Announcements_model->get_one($id);
            if (!$announcement || !$announcement->id) {
                $this->_api_response(array('message' => 'Announcement not found'), 404);
                return;
            }

            // Delete announcement (soft delete)
            $success = $this->Announcements_model->delete($id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to delete announcement'), 500);
                return;
            }

            log_message('info', 'API: Announcement deleted successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Announcement deleted successfully'
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Announcements::delete error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to delete announcement',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format announcement data for API response
     */
    private function _format_announcement($announcement)
    {
        return array(
            'id' => (int)$announcement->id,
            'title' => $announcement->title ?? '',
            'description' => $announcement->description ?? '',
            'start_date' => $announcement->start_date ?? null,
            'end_date' => $announcement->end_date ?? null,
            'share_with' => $announcement->share_with ?? '',
            'created_by' => (int)($announcement->created_by ?? 0),
            'created_by_user' => $announcement->created_by_user ?? '',
            'created_by_avatar' => $announcement->created_by_avatar ?? '',
            'created_date' => $announcement->created_date ?? null
        );
    }
}

