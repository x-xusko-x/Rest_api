<?php

namespace Rest_api\Controllers;

use App\Controllers\Security_Controller;
use Rest_api\Models\Api_logs_model;
use Rest_api\Models\Api_keys_model;

class Api_logs extends Security_Controller {

    protected $Api_logs_model;
    protected $Api_keys_model;

    function __construct() {
        parent::__construct();
        $this->access_only_admin();
        
        $this->Api_logs_model = new Api_logs_model();
        $this->Api_keys_model = new Api_keys_model();
    }

    /**
     * API Logs Page
     */
    function index() {
        $view_data['api_keys_dropdown'] = $this->_get_api_keys_dropdown();
        return $this->template->rander("Rest_api\Views\settings\api_logs", $view_data);
    }

    /**
     * Get API logs list data for DataTable
     */
    function list_data() {
        try {
            $options = array(
                'limit' => $this->request->getPost('length'),
                'skip' => $this->request->getPost('start')
            );
            
            // Add filters
            $api_key_id = $this->request->getPost('api_key_id');
            if ($api_key_id) {
                $options['api_key_id'] = $api_key_id;
            }
            
            $method = $this->request->getPost('method');
            if ($method) {
                $options['method'] = $method;
            }
            
            $response_code = $this->request->getPost('response_code');
            if ($response_code) {
                $options['response_code'] = $response_code;
            }

            $list_data = $this->Api_logs_model->get_details($options);
            
            $result = array();
            
            if (is_array($list_data) && isset($list_data['data'])) {
                // With pagination
                foreach ($list_data['data'] as $data) {
                    $result[] = $this->_make_row($data);
                }
                
                echo json_encode(array(
                    "draw" => $this->request->getPost('draw'),
                    "recordsTotal" => $list_data['recordsTotal'],
                    "recordsFiltered" => $list_data['recordsFiltered'],
                    "data" => $result
                ));
            } else {
                // Without pagination (shouldn't happen with limit set)
                $logs = $list_data->getResult();
                foreach ($logs as $data) {
                    $result[] = $this->_make_row($data);
                }
                
                echo json_encode(array(
                    "draw" => $this->request->getPost('draw'),
                    "recordsTotal" => count($result),
                    "recordsFiltered" => count($result),
                    "data" => $result
                ));
            }
        } catch (\Exception $e) {
            log_message('error', 'API Logs list_data error: ' . $e->getMessage());
            echo json_encode(array(
                "draw" => $this->request->getPost('draw'),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => array()
            ));
        }
    }
    
    /**
     * Make a row for DataTable
     */
    private function _make_row($data) {
        // Timestamp
        $timestamp = format_to_datetime($data->created_at);
        
        // Method with badge
        $method_class = 'bg-secondary';
        switch($data->method) {
            case 'GET': $method_class = 'bg-info'; break;
            case 'POST': $method_class = 'bg-success'; break;
            case 'PUT': $method_class = 'bg-warning'; break;
            case 'DELETE': $method_class = 'bg-danger'; break;
        }
        $method = '<span class="badge ' . $method_class . '">' . $data->method . '</span>';
        
        // Endpoint
        $endpoint = $data->endpoint;
        
        // API Key
        $api_key_name = $data->api_key_name ?: ($data->api_key_id == 0 ? app_lang('unknown') : app_lang('deleted'));
        
        // Response code with badge
        $code_class = 'bg-secondary';
        if ($data->response_code >= 200 && $data->response_code < 300) {
            $code_class = 'bg-success';
        } else if ($data->response_code >= 400 && $data->response_code < 500) {
            $code_class = 'bg-warning';
        } else if ($data->response_code >= 500) {
            $code_class = 'bg-danger';
        }
        $response_code = '<span class="badge ' . $code_class . '">' . $data->response_code . '</span>';
        
        // Response time
        $response_time = round($data->response_time, 2) . 'ms';
        
        // Actions
        $view = modal_anchor(get_uri("api_logs/view/" . $data->id), "<i data-feather='eye' class='icon-16'></i>", array(
            "class" => "view-log-details",
            "title" => app_lang('view_details'),
            "data-post-id" => $data->id,
            "data-modal-lg" => "1"
        ));
        
        return array(
            $timestamp,
            $method,
            $endpoint,
            $api_key_name,
            $response_code,
            $response_time,
            $view
        );
    }

    /**
     * View API log details
     */
    function view($id = 0) {
        if ($id) {
            // Use optimized method that loads full details + API key name in single query
            $log = $this->Api_logs_model->get_log_details($id);
            
            if ($log && isset($log->id)) {
                $view_data['log_info'] = $log;
                
                // API key name is already loaded from the optimized query
                $view_data['api_key_name'] = $log->api_key_name ?? app_lang('unknown');
                
                return $this->template->view("Rest_api\Views\settings\modals\api_log_details", $view_data);
            }
        }
        
        show_404();
    }
    
    /**
     * Get API keys dropdown
     */
    private function _get_api_keys_dropdown() {
        $dropdown = array(array("id" => "", "text" => "- All -"));
        
        $api_keys_result = $this->Api_keys_model->get_details();
        if ($api_keys_result) {
            $api_keys = $api_keys_result->getResult();
            foreach ($api_keys as $key) {
                $dropdown[] = array(
                    "id" => $key->id,
                    "text" => $key->name
                );
            }
        }
        
        return json_encode($dropdown);
    }
}
