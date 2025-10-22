<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;

class Invoices extends Api_controller
{
    public $Invoices_model;
    public $Clients_model;
    public $Projects_model;
    public $Users_model;

    function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->Invoices_model = model('App\Models\Invoices_model', false);
        $this->Clients_model = model('App\Models\Clients_model', false);
        $this->Projects_model = model('App\Models\Projects_model', false);
        $this->Users_model = model('App\Models\Users_model', false);
    }

    /**
     * GET /api/v1/invoices
     * List all invoices with pagination and filtering
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

            $client_id = $this->request->getGet('client_id');
            if ($client_id) {
                $options['client_id'] = (int)$client_id;
            }

            $project_id = $this->request->getGet('project_id');
            if ($project_id) {
                $options['project_id'] = (int)$project_id;
            }

            $type = $this->request->getGet('type');
            if ($type) {
                $options['type'] = $type;
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

            $recurring = $this->request->getGet('recurring');
            if ($recurring !== null) {
                $options['recurring'] = $recurring;
            }

            $all_result = $this->Invoices_model->get_details($options);
            $total_count = $all_result ? $all_result->getNumRows() : 0;
            $all_invoices = $all_result ? $all_result->getResult() : [];
            $paginated_invoices = array_slice($all_invoices, $pagination['offset'], $pagination['limit']);

            $formatted_invoices = [];
            foreach ($paginated_invoices as $invoice) {
                $formatted_invoices[] = $this->_format_invoice($invoice);
            }

            $this->_api_list_response($formatted_invoices, $total_count, 'invoices', $pagination, true);
        } catch (\Exception $e) {
            log_message('error', 'API Invoices::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve invoices', 500);
        }
    }

    /**
     * GET /api/v1/invoices/{id}
     * Get a specific invoice by ID
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Invoice ID is required'), 400);
                return;
            }

            $invoice = $this->Invoices_model->get_one($id);

            if (!$invoice || !$invoice->id) {
                $this->_api_response(array('message' => 'Invoice not found'), 404);
                return;
            }

            // Get invoice details with related data
            $options = array('id' => $id);
            $result = $this->Invoices_model->get_details($options);
            $invoice_details = $result->getRow();

            if (!$invoice_details) {
                $this->_api_response(array('message' => 'Invoice not found'), 404);
                return;
            }

            $this->_api_response(array(
                'invoice' => $this->_format_invoice($invoice_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Invoices::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve invoice',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/invoices
     * Create a new invoice
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            $required_fields = array('client_id', 'bill_date', 'due_date');
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

            // Validate client_id
            $client = $this->Clients_model->get_one($data['client_id']);
            if (!$client || !$client->id) {
                $this->_api_response(array('message' => 'Invalid client ID'), 404);
                return;
            }

            // Get dates for display ID generation
            $bill_date = get_array_value($data, 'bill_date');
            $due_date = get_array_value($data, 'due_date');
            
            // Prepare invoice data - following Rise CRM's Invoices controller pattern exactly
            // Note: type, status, and created_date have database defaults
            $invoice_data = array(
                'client_id' => get_array_value($data, 'client_id'),
                'project_id' => get_array_value($data, 'project_id', 0),
                'bill_date' => $bill_date,
                'due_date' => $due_date,
                'tax_id' => get_array_value($data, 'tax_id', 0),
                'tax_id2' => get_array_value($data, 'tax_id2', 0),
                'tax_id3' => get_array_value($data, 'tax_id3', 0),
                'company_id' => get_array_value($data, 'company_id') ? get_array_value($data, 'company_id') : get_default_company_id(),
                'note' => get_array_value($data, 'note', ''),
                'labels' => get_array_value($data, 'labels', ''),
                'discount_amount' => get_array_value($data, 'discount_amount', 0),
                'discount_amount_type' => get_array_value($data, 'discount_amount_type', 'percentage'),
                'discount_type' => get_array_value($data, 'discount_type', 'before_tax'),
                'estimate_id' => get_array_value($data, 'estimate_id', 0),
                'order_id' => get_array_value($data, 'order_id', 0),
                'created_by' => 1 // System/API user
            );
            
            // Generate invoice display ID using Rise CRM's helper function
            // This is REQUIRED for invoice creation
            try {
                $display_id_data = prepare_invoice_display_id_data($due_date, $bill_date);
                $invoice_data = array_merge($invoice_data, $display_id_data);
                log_message('info', 'API: Generated display ID data: ' . json_encode($display_id_data));
            } catch (\Exception $e) {
                log_message('error', 'API: Failed to generate display ID: ' . $e->getMessage());
                $this->_api_response(array(
                    'message' => 'Failed to generate invoice display ID',
                    'error' => $e->getMessage()
                ), 500);
                return;
            }

            // Validate project_id only if it's greater than 0
            if ((int)$invoice_data['project_id'] > 0) {
                $project = $this->Projects_model->get_one($invoice_data['project_id']);
                if (!$project || !$project->id) {
                    $this->_api_response(array('message' => 'Invalid project ID'), 404);
                    return;
                }
            }

            // Clean data before saving
            $invoice_data = clean_data($invoice_data);

            // Log the data being saved for debugging
            log_message('info', 'API: Attempting to create invoice with data: ' . json_encode($invoice_data));

            // Save invoice using model's business logic method (handles total calculation)
            $invoice_id = $this->Invoices_model->save_invoice_and_update_total($invoice_data);

            if (!$invoice_id) {
                log_message('error', 'API: Invoice save failed. Data: ' . json_encode($invoice_data));
                
                // Get database error if available
                $db_error = $this->Invoices_model->db->error();
                log_message('error', 'API: Database error: ' . json_encode($db_error));
                
                $this->_api_response(array(
                    'message' => 'Failed to create invoice',
                    'debug' => get_setting('app_environment') === 'development' ? $db_error : null
                ), 500);
                return;
            }

            // Get the created invoice
            $options = array('id' => $invoice_id);
            $result = $this->Invoices_model->get_details($options);
            $invoice_details = $result->getRow();

            log_message('info', 'API: Invoice created successfully - ID: ' . $invoice_id);

            $this->_api_response(array(
                'message' => 'Invoice created successfully',
                'invoice' => $this->_format_invoice($invoice_details)
            ), 201);
        } catch (\Exception $e) {
            log_message('error', 'API Invoices::create error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to create invoice',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * PUT /api/v1/invoices/{id}
     * Update an existing invoice
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Invoice ID is required'), 400);
                return;
            }

            // Check if invoice exists
            $invoice = $this->Invoices_model->get_one($id);
            if (!$invoice || !$invoice->id) {
                $this->_api_response(array('message' => 'Invoice not found'), 404);
                return;
            }

            $data = $this->request->getJSON(true);

            // Prepare update data
            $invoice_data = array();

            // Only update provided fields
            if (isset($data['client_id'])) {
                $client = $this->Clients_model->get_one($data['client_id']);
                if (!$client || !$client->id) {
                    $this->_api_response(array('message' => 'Invalid client ID'), 404);
                    return;
                }
                $invoice_data['client_id'] = $data['client_id'];
            }

            if (isset($data['project_id'])) {
                // Only validate if project_id is provided and greater than 0
                if (!empty($data['project_id']) && $data['project_id'] > 0) {
                    $project = $this->Projects_model->get_one($data['project_id']);
                    if (!$project || !$project->id) {
                        $this->_api_response(array('message' => 'Invalid project ID'), 404);
                        return;
                    }
                }
                $invoice_data['project_id'] = (int)$data['project_id'];
            }

            if (isset($data['bill_date'])) {
                $invoice_data['bill_date'] = $data['bill_date'];
            }

            if (isset($data['due_date'])) {
                $invoice_data['due_date'] = $data['due_date'];
            }

            if (isset($data['status'])) {
                $invoice_data['status'] = $data['status'];
            }

            if (isset($data['type'])) {
                $invoice_data['type'] = $data['type'];
            }

            if (isset($data['tax_id'])) {
                $invoice_data['tax_id'] = $data['tax_id'];
            }

            if (isset($data['tax_id2'])) {
                $invoice_data['tax_id2'] = $data['tax_id2'];
            }

            if (isset($data['tax_id3'])) {
                $invoice_data['tax_id3'] = $data['tax_id3'];
            }

            if (isset($data['note'])) {
                $invoice_data['note'] = $data['note'];
            }

            if (isset($data['labels'])) {
                $invoice_data['labels'] = $data['labels'];
            }

            if (isset($data['discount_amount'])) {
                $invoice_data['discount_amount'] = $data['discount_amount'];
            }

            if (isset($data['discount_amount_type'])) {
                $invoice_data['discount_amount_type'] = $data['discount_amount_type'];
            }

            if (isset($data['discount_type'])) {
                $invoice_data['discount_type'] = $data['discount_type'];
            }

            if (empty($invoice_data)) {
                $this->_api_response(array('message' => 'No valid fields to update'), 400);
                return;
            }

            // Clean data before saving
            $invoice_data = clean_data($invoice_data);

            // Update invoice using model's business logic method (handles total calculation)
            $success = $this->Invoices_model->save_invoice_and_update_total($invoice_data, $id);

            if (!$success) {
                $db_error = $this->Invoices_model->db->error();
                log_message('error', 'API Invoices::update database error: ' . json_encode($db_error));
                
                $error_message = 'Failed to update invoice';
                $error_details = [];
                
                if (isset($db_error['code']) && $db_error['code'] !== 0) {
                    $error_details['database_error'] = $db_error['message'];
                    
                    if (strpos($db_error['message'], 'Duplicate entry') !== false) {
                        $error_message = 'An invoice with this information already exists';
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

            // Get updated invoice
            $options = array('id' => $id);
            $result = $this->Invoices_model->get_details($options);
            $invoice_details = $result->getRow();

            log_message('info', 'API: Invoice updated successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Invoice updated successfully',
                'invoice' => $this->_format_invoice($invoice_details)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Invoices::update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            $this->_api_response(array(
                'message' => 'Failed to update invoice',
                'error_details' => array(
                    'exception' => $e->getMessage(),
                    'type' => get_class($e)
                )
            ), 500);
        }
    }

    /**
     * DELETE /api/v1/invoices/{id}
     * Delete an invoice
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Invoice ID is required'), 400);
                return;
            }

            // Check if invoice exists
            $invoice = $this->Invoices_model->get_one($id);
            if (!$invoice || !$invoice->id) {
                $this->_api_response(array('message' => 'Invoice not found'), 404);
                return;
            }

            // Delete invoice permanently (including related items)
            $success = $this->Invoices_model->delete_permanently($id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to delete invoice'), 500);
                return;
            }

            log_message('info', 'API: Invoice deleted successfully - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Invoice deleted successfully'
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Invoices::delete error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to delete invoice',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format invoice data for API response
     */
    private function _format_invoice($invoice)
    {
        return array(
            'id' => (int)$invoice->id,
            'display_id' => $invoice->display_id ?? '',
            'client_id' => (int)($invoice->client_id ?? 0),
            'company_name' => $invoice->company_name ?? '',
            'project_id' => (int)($invoice->project_id ?? 0),
            'project_title' => $invoice->project_title ?? '',
            'type' => $invoice->type ?? 'invoice',
            'bill_date' => $invoice->bill_date ?? null,
            'due_date' => $invoice->due_date ?? null,
            'status' => $invoice->status ?? '',
            'invoice_value' => (float)($invoice->invoice_value ?? 0),
            'payment_received' => (float)($invoice->payment_received ?? 0),
            'tax_id' => (int)($invoice->tax_id ?? 0),
            'tax_id2' => (int)($invoice->tax_id2 ?? 0),
            'tax_id3' => (int)($invoice->tax_id3 ?? 0),
            'tax_percentage' => (float)($invoice->tax_percentage ?? 0),
            'tax_percentage2' => (float)($invoice->tax_percentage2 ?? 0),
            'tax_percentage3' => (float)($invoice->tax_percentage3 ?? 0),
            'discount_amount' => (float)($invoice->discount_amount ?? 0),
            'discount_amount_type' => $invoice->discount_amount_type ?? 'percentage',
            'discount_type' => $invoice->discount_type ?? 'before_tax',
            'currency' => $invoice->currency ?? '',
            'currency_symbol' => $invoice->currency_symbol ?? '',
            'note' => $invoice->note ?? '',
            'labels' => $invoice->labels ?? '',
            'recurring' => (int)($invoice->recurring ?? 0),
            'recurring_invoice_id' => (int)($invoice->recurring_invoice_id ?? 0),
            'recurring_invoice_display_id' => $invoice->recurring_invoice_display_id ?? '',
            'main_invoice_id' => (int)($invoice->main_invoice_id ?? 0),
            'main_invoice_display_id' => $invoice->main_invoice_display_id ?? '',
            'created_by' => (int)($invoice->created_by ?? 0),
            'created_date' => $invoice->created_date ?? null,
            'cancelled_by' => (int)($invoice->cancelled_by ?? 0),
            'cancelled_by_user' => $invoice->cancelled_by_user ?? '',
            'cancelled_at' => $invoice->cancelled_at ?? null
        );
    }
}

