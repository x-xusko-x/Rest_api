<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;

class Expenses extends Api_controller
{
    public $Expenses_model;
    public $Expense_categories_model;
    public $Projects_model;
    public $Users_model;
    public $Clients_model;

    function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->Expenses_model = model('App\Models\Expenses_model', false);
        $this->Expense_categories_model = model('App\Models\Expense_categories_model', false);
        $this->Projects_model = model('App\Models\Projects_model', false);
        $this->Users_model = model('App\Models\Users_model', false);
        $this->Clients_model = model('App\Models\Clients_model', false);
    }

    /**
     * GET /api/v1/expenses
     * List all expenses with pagination and filtering
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

            $category_id = $this->request->getGet('category_id');
            if ($category_id) {
                $options['category_id'] = (int)$category_id;
            }

            $project_id = $this->request->getGet('project_id');
            if ($project_id) {
                $options['project_id'] = (int)$project_id;
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
            $end_date = $this->request->getGet('end_date');
            if ($start_date && $end_date) {
                $options['start_date'] = $start_date;
                $options['end_date'] = $end_date;
            }

            $recurring = $this->request->getGet('recurring');
            if ($recurring == '1' || $recurring === 'true') {
                $options['recurring'] = true;
            }

            $all_result = $this->Expenses_model->get_details($options);
            $total_count = $all_result ? $all_result->getNumRows() : 0;
            $all_expenses = $all_result ? $all_result->getResult() : [];
            $paginated_expenses = array_slice($all_expenses, $pagination['offset'], $pagination['limit']);

            $formatted_expenses = [];
            foreach ($paginated_expenses as $expense) {
                $formatted_expenses[] = $this->_format_expense($expense);
            }

            $this->_api_list_response($formatted_expenses, $total_count, 'expenses', $pagination, true);
        } catch (\Exception $e) {
            log_message('error', 'API Expenses::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve expenses', 500);
        }
    }

    /**
     * GET /api/v1/expenses/{id}
     * Get a specific expense by ID
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Expense ID is required'), 400);
                return;
            }

            $expense = $this->Expenses_model->get_one($id);

            if (!$expense || !$expense->id) {
                $this->_api_response(array('message' => 'Expense not found'), 404);
                return;
            }

            // Get expense details with related data
            $options = array('id' => $id);
            $result = $this->Expenses_model->get_details($options);
            $expense_details = $result->getRow();

            if (!$expense_details) {
                $this->_api_response(array('message' => 'Expense not found'), 404);
                return;
            }

            $this->_api_response(array(
                'expense' => $this->_format_expense($expense_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Expenses::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve expense',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/expenses
     * Create a new expense
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            $required_fields = array('expense_date', 'category_id', 'amount');
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

            // Validate category exists
            $category = $this->Expense_categories_model->get_one($data['category_id']);
            if (!$category || !$category->id) {
                $this->_api_response(array('message' => 'Invalid category ID'), 404);
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

            // Validate user if provided
            if (!empty($data['user_id'])) {
                $user = $this->Users_model->get_one($data['user_id']);
                if (!$user || !$user->id) {
                    $this->_api_response(array('message' => 'Invalid user ID'), 404);
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

            // Prepare expense data
            $expense_data = array(
                'expense_date' => get_array_value($data, 'expense_date'),
                'category_id' => (int)$data['category_id'],
                'amount' => (float)$data['amount'],
                'title' => get_array_value($data, 'title', ''),
                'description' => get_array_value($data, 'description', ''),
                'project_id' => get_array_value($data, 'project_id', 0),
                'user_id' => get_array_value($data, 'user_id', 0),
                'client_id' => get_array_value($data, 'client_id', 0),
                'tax_id' => get_array_value($data, 'tax_id', 0),
                'tax_id2' => get_array_value($data, 'tax_id2', 0),
                'recurring' => get_array_value($data, 'recurring', 0),
                'repeat_every' => get_array_value($data, 'repeat_every', 0),
                'repeat_type' => get_array_value($data, 'repeat_type', null),
                'no_of_cycles' => get_array_value($data, 'no_of_cycles', 0),
                'created_by' => $this->authenticated_user_id
            );

            // Clean data before saving
            $expense_data = clean_data($expense_data);

            // Save expense
            $expense_id = $this->Expenses_model->ci_save($expense_data);

            if (!$expense_id) {
                $db_error = $this->Expenses_model->db->error();
                log_message('error', 'API Expenses::create database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to create expense';
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
                        $error_message = 'An expense with this information already exists';
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

            // Get the created expense
            $options = array('id' => $expense_id);
            $result = $this->Expenses_model->get_details($options);
            $expense_details = $result->getRow();

            log_message('info', 'API: Expense created successfully - ID: ' . $expense_id);

            $this->_api_response(array(
                'message' => 'Expense created successfully',
                'expense' => $this->_format_expense($expense_details)
            ), 201);
        } catch (\Exception $e) {
            log_message('error', 'API Expenses::create error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to create expense',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * PUT /api/v1/expenses/{id}
     * Update an existing expense
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Expense ID is required'), 400);
                return;
            }

            // Check if expense exists
            $expense = $this->Expenses_model->get_one($id);
            if (!$expense || !$expense->id) {
                $this->_api_response(array('message' => 'Expense not found'), 404);
                return;
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $expense_data = array();

            // Only update provided fields
            if (isset($data['expense_date'])) {
                $expense_data['expense_date'] = $data['expense_date'];
            }
            if (isset($data['category_id'])) {
                $category = $this->Expense_categories_model->get_one($data['category_id']);
                if (!$category || !$category->id) {
                    $this->_api_response(array('message' => 'Invalid category ID'), 404);
                    return;
                }
                $expense_data['category_id'] = (int)$data['category_id'];
            }
            if (isset($data['amount'])) {
                $expense_data['amount'] = (float)$data['amount'];
            }
            if (isset($data['title'])) {
                $expense_data['title'] = $data['title'];
            }
            if (isset($data['description'])) {
                $expense_data['description'] = $data['description'];
            }
            if (isset($data['project_id'])) {
                if ($data['project_id'] > 0) {
                    $project = $this->Projects_model->get_one($data['project_id']);
                    if (!$project || !$project->id) {
                        $this->_api_response(array('message' => 'Invalid project ID'), 404);
                        return;
                    }
                }
                $expense_data['project_id'] = (int)$data['project_id'];
            }
            if (isset($data['user_id'])) {
                if ($data['user_id'] > 0) {
                    $user = $this->Users_model->get_one($data['user_id']);
                    if (!$user || !$user->id) {
                        $this->_api_response(array('message' => 'Invalid user ID'), 404);
                        return;
                    }
                }
                $expense_data['user_id'] = (int)$data['user_id'];
            }
            if (isset($data['client_id'])) {
                if ($data['client_id'] > 0) {
                    $client = $this->Clients_model->get_one($data['client_id']);
                    if (!$client || !$client->id) {
                        $this->_api_response(array('message' => 'Invalid client ID'), 404);
                        return;
                    }
                }
                $expense_data['client_id'] = (int)$data['client_id'];
            }
            if (isset($data['tax_id'])) {
                $expense_data['tax_id'] = (int)$data['tax_id'];
            }
            if (isset($data['tax_id2'])) {
                $expense_data['tax_id2'] = (int)$data['tax_id2'];
            }
            if (isset($data['recurring'])) {
                $expense_data['recurring'] = (int)$data['recurring'];
            }

            if (empty($expense_data)) {
                $this->_api_response(array('message' => 'No valid fields to update'), 400);
                return;
            }

            // Clean data before saving
            $expense_data = clean_data($expense_data);

            // Update expense
            $success = $this->Expenses_model->ci_save($expense_data, $id);

            if (!$success) {
                $db_error = $this->Expenses_model->db->error();
                log_message('error', 'API Expenses::update database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to update expense';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'An expense with this information already exists';
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

            // Get updated expense
            $options = array('id' => $id);
            $result = $this->Expenses_model->get_details($options);
            $expense_details = $result->getRow();

            log_message('info', 'API: Expense updated successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Expense updated successfully',
                'expense' => $this->_format_expense($expense_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Expenses::update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to update expense',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * DELETE /api/v1/expenses/{id}
     * Delete an expense
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Expense ID is required'), 400);
                return;
            }

            // Check if expense exists
            $expense = $this->Expenses_model->get_one($id);
            if (!$expense || !$expense->id) {
                $this->_api_response(array('message' => 'Expense not found'), 404);
                return;
            }

            // Delete expense (soft delete)
            $success = $this->Expenses_model->delete($id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to delete expense'), 500);
                return;
            }

            log_message('info', 'API: Expense deleted successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Expense deleted successfully'
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Expenses::delete error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to delete expense',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format expense data for API response
     */
    private function _format_expense($expense)
    {
        return array(
            'id' => (int)$expense->id,
            'expense_date' => $expense->expense_date ?? null,
            'category_id' => (int)($expense->category_id ?? 0),
            'category_title' => $expense->category_title ?? '',
            'amount' => (float)($expense->amount ?? 0),
            'title' => $expense->title ?? '',
            'description' => $expense->description ?? '',
            'project_id' => (int)($expense->project_id ?? 0),
            'project_title' => $expense->project_title ?? '',
            'user_id' => (int)($expense->user_id ?? 0),
            'linked_user_name' => $expense->linked_user_name ?? '',
            'client_id' => (int)($expense->client_id ?? 0),
            'linked_client_name' => $expense->linked_client_name ?? '',
            'tax_id' => (int)($expense->tax_id ?? 0),
            'tax_id2' => (int)($expense->tax_id2 ?? 0),
            'tax_percentage' => (float)($expense->tax_percentage ?? 0),
            'tax_percentage2' => (float)($expense->tax_percentage2 ?? 0),
            'recurring' => (int)($expense->recurring ?? 0),
            'created_by' => (int)($expense->created_by ?? 0),
            'created_date' => $expense->created_date ?? null
        );
    }
}

