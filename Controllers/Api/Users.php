<?php

namespace Rest_api\Controllers\Api;

use App\Models\Users_model;
use OpenApi\Attributes as OA;

/**
 * Users API Controller
 * Handles CRUD operations for users via REST API
 * 
 * @OA\Tag(
 *     name="Users",
 *     description="User management operations"
 * )
 */
class Users extends Api_controller {

    public $Users_model;

    function __construct() {
        parent::__construct();
        $this->Users_model = new Users_model();
    }

    /**
     * GET /api/v1/users
     * List all users with pagination and filtering
     */
    #[OA\Get(
        path: "/users",
        tags: ["Users"],
        summary: "List all users",
        description: "Retrieve a paginated list of users with optional filtering",
        operationId: "listUsers",
        parameters: [
            new OA\Parameter(name: "limit", in: "query", description: "Number of items to return (max: 100)", required: false, schema: new OA\Schema(type: "integer", minimum: 1, maximum: 100, default: 50)),
            new OA\Parameter(name: "offset", in: "query", description: "Number of items to skip", required: false, schema: new OA\Schema(type: "integer", minimum: 0, default: 0)),
            new OA\Parameter(name: "page", in: "query", description: "Page number (alternative to offset)", required: false, schema: new OA\Schema(type: "integer", minimum: 1)),
            new OA\Parameter(name: "search", in: "query", description: "Search term", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "user_type", in: "query", description: "Filter by user type", required: false, schema: new OA\Schema(type: "string", enum: ["staff", "client"])),
            new OA\Parameter(name: "status", in: "query", description: "Filter by status", required: false, schema: new OA\Schema(type: "string", enum: ["active", "inactive"])),
            new OA\Parameter(name: "client_id", in: "query", description: "Filter by client ID (for client users)", required: false, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful response", content: new OA\JsonContent(ref: "#/components/schemas/UserListResponse")),
            new OA\Response(response: 401, ref: "#/components/responses/Unauthorized"),
            new OA\Response(response: 403, ref: "#/components/responses/Forbidden"),
            new OA\Response(response: 429, ref: "#/components/responses/TooManyRequests"),
            new OA\Response(response: 500, ref: "#/components/responses/InternalServerError")
        ],
        security: [["ApiKeyAuth" => [], "ApiSecretAuth" => []], ["BearerAuth" => []]]
    )]
    public function index() {
        try {
            $pagination = $this->_get_pagination_params(50, 100);
            $options = [
                'limit' => $pagination['limit'],
                'skip' => $pagination['offset']
            ];

            $user_type = $this->request->getGet('user_type');
            if ($user_type) {
                $options['user_type'] = $user_type;
            }

            $status = $this->request->getGet('status');
            if ($status) {
                $options['status'] = $status;
            }

            $client_id = $this->request->getGet('client_id');
            if ($client_id) {
                $options['client_id'] = $client_id;
            }

            $search = $this->request->getGet('search');
            if ($search) {
                $options['search_by'] = $search;
            }
            
            $result = $this->Users_model->get_details($options);
            $users = [];
            foreach ($result['data'] as $user) {
                $users[] = $this->_sanitize_user_data($user);
            }
            
            $this->_api_list_response($users, $result['recordsTotal'], 'users', $pagination, true);
            
        } catch (\Exception $e) {
            log_message('error', 'API Users::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve users', 500);
        }
    }

    /**
     * GET /api/v1/users/{id}
     * Get a single user by ID
     * 
     * @OA\Get(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Get a user by ID",
     *     description="Retrieve detailed information about a specific user",
     *     operationId="getUserById",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/UserResponse")
     *     ),
     *     @OA\Response(response=400, description="Bad Request - Invalid ID", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="User not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={
     *         {"ApiKeyAuth": {}, "ApiSecretAuth": {}},
     *         {"BearerAuth": {}}
     *     }
     * )
     */
    public function show($id = null) {
        if (!$id) {
            $this->_api_response([
                'message' => 'User ID is required'
            ], 400);
        }
        
        try {
            $result = $this->Users_model->get_details(['id' => $id]);
            $users = $result->getResult();
            
            if (empty($users)) {
                $this->_api_response([
                    'message' => 'User not found'
                ], 404);
            }
            
            $user = $this->_sanitize_user_data($users[0]);
            
            $this->_api_response(['user' => $user], 200);
            
        } catch (\Exception $e) {
            $this->_api_response([
                'message' => 'Failed to retrieve user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/v1/users
     * Create a new user
     * 
     * @OA\Post(
     *     path="/api/v1/users",
     *     tags={"Users"},
     *     summary="Create a new user",
     *     description="Create a new user with the provided data",
     *     operationId="createUser",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User data",
     *         @OA\JsonContent(ref="#/components/schemas/CreateUserRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/UserResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="User created successfully")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation failed", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=409, description="Email already exists", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={
     *         {"ApiKeyAuth": {}, "ApiSecretAuth": {}},
     *         {"BearerAuth": {}}
     *     }
     * )
     */
    public function create() {
        try {
            // Validate request against OpenAPI schema
            $this->_validate_request_schema('User', 'Create');
            
            // Validate required fields (fallback/additional validation)
            $this->_validate_required_fields(['first_name', 'last_name', 'email', 'user_type']);
            
            $user_type = get_array_value($this->request_data, 'user_type') ?: 'staff';
            
            $data = array(
                'first_name' => get_array_value($this->request_data, 'first_name'),
                'last_name' => get_array_value($this->request_data, 'last_name'),
                'email' => get_array_value($this->request_data, 'email'),
                'user_type' => $user_type,
                'job_title' => get_array_value($this->request_data, 'job_title'),
                'phone' => get_array_value($this->request_data, 'phone'),
                'skype' => get_array_value($this->request_data, 'skype'),
                'gender' => get_array_value($this->request_data, 'gender'),
                'address' => get_array_value($this->request_data, 'address'),
                'alternative_address' => get_array_value($this->request_data, 'alternative_address'),
                'created_at' => get_current_utc_time()
            );
            
            // Add role_id or is_admin based on provided values
            $role_id = get_array_value($this->request_data, 'role_id');
            $is_admin = get_array_value($this->request_data, 'is_admin');
            
            if ($is_admin) {
                $data['is_admin'] = 1;
                $data['role_id'] = 0;
            } else if ($role_id) {
                $data['is_admin'] = 0;
                $data['role_id'] = $role_id;
            } else {
                $data['is_admin'] = 0;
                $data['role_id'] = 0;
            }
            
            // Add optional fields if provided
            $note = get_array_value($this->request_data, 'note');
            if ($note) {
                $data['note'] = $note;
            }
            
            // Handle password
            $password = get_array_value($this->request_data, 'password');
            if ($password) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            // Clean data (removes null/empty values) - IMPORTANT: Rise CRM pattern
            $data = clean_data($data);
            
            // Handle client_id for client users
            if ($data['user_type'] === 'client') {
                $data['client_id'] = get_array_value($this->request_data, 'client_id');
                if (!$data['client_id']) {
                    $this->_api_response([
                        'message' => 'client_id is required for client users'
                    ], 400);
                }
            }
            
            // Check if email already exists
            if ($this->Users_model->is_email_exists($data['email'])) {
                $this->_api_response([
                    'message' => 'Email already exists'
                ], 409);
            }
            
            // Log the data being saved for debugging
            log_message('info', 'API: Creating user with data: ' . json_encode($data));
            
            $user_id = $this->Users_model->ci_save($data);
            
            if ($user_id) {
                log_message('info', 'API: User created successfully with ID: ' . $user_id);
                
                $result = $this->Users_model->get_details(['id' => $user_id]);
                $users = $result->getResult();
                
                if (!empty($users)) {
                    $user = $this->_sanitize_user_data($users[0]);
                    
                    $this->_api_response([
                        'message' => 'User created successfully',
                        'user' => $user
                    ], 201);
                } else {
                    log_message('error', 'API: User created but could not be retrieved. ID: ' . $user_id);
                    $this->_api_response([
                        'message' => 'User created but could not be retrieved'
                    ], 500);
                }
            } else {
                $db_error = $this->Users_model->db->error();
                log_message('error', 'API Users::create database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to create user';
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
                        $error_message = 'A user with this information already exists';
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
            }
            
        } catch (\Exception $e) {
            log_message('error', 'API: Exception creating user: ' . $e->getMessage());
            log_message('error', 'API: Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response([
                'message' => 'Failed to create user',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ], 500);
        }
    }

    /**
     * PUT /api/v1/users/{id}
     * Update an existing user
     * 
     * @OA\Put(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Update a user",
     *     description="Update an existing user with the provided data (all fields optional)",
     *     operationId="updateUser",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="User data to update",
     *         @OA\JsonContent(ref="#/components/schemas/UpdateUserRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/UserResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="User updated successfully")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid request data", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="User not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=409, description="Email already exists", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={
     *         {"ApiKeyAuth": {}, "ApiSecretAuth": {}},
     *         {"BearerAuth": {}}
     *     }
     * )
     */
    public function update($id = null) {
        if (!$id) {
            $this->_api_response([
                'message' => 'User ID is required'
            ], 400);
        }
        
        try {
            // Validate request against OpenAPI schema
            $this->_validate_request_schema('User', 'Update');
            // Check if user exists
            $existing_user = $this->Users_model->get_one($id);
            if (!$existing_user) {
                $this->_api_response([
                    'message' => 'User not found'
                ], 404);
            }
            
            // Build update data (only include provided fields)
            $data = [];
            
            $updatable_fields = [
                'first_name', 'last_name', 'email', 'job_title', 'phone', 
                'skype', 'gender', 'address', 'alternative_address', 
                'role_id', 'status', 'is_admin', 'note', 'disable_login'
            ];
            
            foreach ($updatable_fields as $field) {
                if (isset($this->request_data[$field])) {
                    $data[$field] = $this->request_data[$field];
                }
            }
            
            // Handle password update
            if (isset($this->request_data['password']) && $this->request_data['password']) {
                $data['password'] = password_hash($this->request_data['password'], PASSWORD_DEFAULT);
            }
            
            // Check email uniqueness if email is being updated
            if (isset($data['email']) && $data['email'] !== $existing_user->email) {
                if ($this->Users_model->is_email_exists($data['email'])) {
                    $this->_api_response([
                        'message' => 'Email already exists'
                    ], 409);
                }
            }
            
            if (empty($data)) {
                $this->_api_response([
                    'message' => 'No fields to update'
                ], 400);
            }
            
            $success = $this->Users_model->ci_save($data, $id);
            
            if ($success) {
                $result = $this->Users_model->get_details(['id' => $id]);
                $user = $this->_sanitize_user_data($result->getResult()[0]);
                
                $this->_api_response([
                    'message' => 'User updated successfully',
                    'user' => $user
                ], 200);
            } else {
                $db_error = $this->Users_model->db->error();
                log_message('error', 'API Users::update database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to update user';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'A user with this information already exists';
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
            }
            
        } catch (\Exception $e) {
            log_message('error', 'API Users::update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response([
                'message' => 'Failed to update user',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/users/{id}
     * Delete a user (soft delete)
     * 
     * @OA\Delete(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Delete a user",
     *     description="Delete a user permanently",
     *     operationId="deleteUser",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string", example="User deleted successfully")
     *             ),
     *             @OA\Property(property="meta", ref="#/components/schemas/ResponseMeta")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid user ID", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="User not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, ref="#/components/responses/InternalServerError"),
     *     security={
     *         {"ApiKeyAuth": {}, "ApiSecretAuth": {}},
     *         {"BearerAuth": {}}
     *     }
     * )
     */
    public function delete($id = null) {
        if (!$id) {
            $this->_api_response([
                'message' => 'User ID is required'
            ], 400);
        }
        
        try {
            // Check if user exists
            $existing_user = $this->Users_model->get_one($id);
            if (!$existing_user) {
                $this->_api_response([
                    'message' => 'User not found'
                ], 404);
            }
            
            // Soft delete
            $success = $this->Users_model->delete_permanently($id);
            
            if ($success) {
                $this->_api_response([
                    'message' => 'User deleted successfully'
                ], 200);
            } else {
                $this->_api_response([
                    'message' => 'Failed to delete user'
                ], 500);
            }
            
        } catch (\Exception $e) {
            $this->_api_response([
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

