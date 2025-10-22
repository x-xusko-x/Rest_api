<?php

namespace Rest_api\Controllers;

use App\Controllers\Security_Controller;
use Rest_api\Models\Api_keys_model;
use Rest_api\Models\Api_settings_model;
use Rest_api\Models\Api_permission_groups_model;

class Api_keys extends Security_Controller {

    protected $Api_keys_model;
    protected $Api_settings_model;
    protected $Api_permission_groups_model;

    function __construct() {
        parent::__construct();
        $this->access_only_admin();
        
        $this->Api_keys_model = new Api_keys_model();
        $this->Api_settings_model = new Api_settings_model();
        $this->Api_permission_groups_model = new Api_permission_groups_model();
    }

    /**
     * API Keys List Page
     */
    function index() {
        return $this->template->rander("Rest_api\Views\settings\api_keys_list");
    }

    /**
     * Get API keys list data for DataTable
     */
    function list_data() {
        $list_data = $this->Api_keys_model->get_details()->getResult();
        $result = array();
        
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }
        
        echo json_encode(array("data" => $result));
    }

    /**
     * Prepare a row for DataTable
     */
    private function _make_row($data) {
        $status_class = "bg-secondary";
        if ($data->status === 'active') {
            $status_class = "bg-success";
        } else if ($data->status === 'revoked') {
            $status_class = "bg-danger";
        }

        $status = "<span class='badge $status_class'>" . app_lang($data->status) . "</span>";

        $last_used = $data->last_used_at ? format_to_relative_time($data->last_used_at) : app_lang('never_used');

        // Format total calls with compact notation and tooltip
        $total_calls = $data->total_calls ?? 0;
        $calls_display = $this->_format_number_compact($total_calls);
        $calls_html = '<span data-bs-toggle="tooltip" title="' . number_format($total_calls) . ' ' . app_lang('total_calls') . '">' . $calls_display . '</span>';

        $rate_limits = $data->rate_limit_per_minute . "/" . app_lang('minute') . "<br>" . 
                      $data->rate_limit_per_hour . "/" . app_lang('hour') . "<br>" .
                      (isset($data->rate_limit_per_day) ? $data->rate_limit_per_day : 10000) . "/" . app_lang('day');

        $expires = $data->expires_at ? format_to_date($data->expires_at, false) : "-";
        $created_at = format_to_datetime($data->created_at);
        
        $assignment = isset($data->assignment_type) && $data->assignment_type === 'external' 
            ? '<span class="badge bg-info">' . app_lang('external') . '</span>' 
            : '<span class="badge bg-primary">' . app_lang('internal') . '</span>';

        return array(
            $data->name,
            $status,
            $assignment,
            $calls_html,
            $rate_limits,
            $last_used,
            $expires,
            $created_at,
            modal_anchor(get_uri("api_keys/view/" . $data->id), "<i data-feather='info' class='icon-16'></i>", array(
                "class" => "view-key-details",
                "title" => app_lang('view_details'),
                "data-modal-lg" => "1"
            ))
            . modal_anchor(get_uri("api_keys/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array(
                "class" => "edit",
                "title" => app_lang('edit_api_key'),
                "data-post-id" => $data->id
            ))
            . js_anchor("<i data-feather='x' class='icon-16'></i>", array(
                'title' => app_lang('delete_api_key'),
                "class" => "delete",
                "data-id" => $data->id,
                "data-action-url" => get_uri("api_keys/delete"),
                "data-action" => "delete-confirmation"
            ))
        );
    }

    /**
     * Format number in compact notation (100k, 1.2M, etc.)
     */
    private function _format_number_compact($number) {
        if ($number < 100000) {
            return number_format($number);
        } else if ($number < 1000000) {
            return round($number / 1000, 1) . 'k';
        } else if ($number < 1000000000) {
            return round($number / 1000000, 1) . 'M';
        } else {
            return round($number / 1000000000, 1) . 'B';
        }
    }

    /**
     * View API key details
     */
    function view($id = 0) {
        if ($id) {
            $options = array("id" => $id);
            $api_key_data = $this->Api_keys_model->get_details($options)->getRow();
            $view_data['api_key_info'] = $api_key_data;
            
            // Get permission group details if assigned
            if ($api_key_data && $api_key_data->permission_group_id) {
                $view_data['permission_group'] = $this->Api_permission_groups_model->get_one($api_key_data->permission_group_id);
            } else {
                $view_data['permission_group'] = null;
            }
            
            return $this->template->view("Rest_api\Views\settings\modals\api_key_details", $view_data);
        }
        show_404();
    }

    /**
     * API Key modal form
     */
    function modal_form() {
        // Get model_info from database
        $model_info = $this->Api_keys_model->get_one($this->request->getPost('id'));
        $view_data['model_info'] = $model_info;
        
        // Load default settings for new keys (use already initialized model)
        $view_data['default_rate_limit_per_minute'] = $this->Api_settings_model->get_setting('default_rate_limit_per_minute') ?: 60;
        $view_data['default_rate_limit_per_hour'] = $this->Api_settings_model->get_setting('default_rate_limit_per_hour') ?: 1000;
        $view_data['default_rate_limit_per_day'] = $this->Api_settings_model->get_setting('default_rate_limit_per_day') ?: 10000;
        
        // Load default security settings
        $view_data['global_require_https'] = $this->Api_settings_model->get_setting('require_https') == '1';
        $view_data['global_cors_enabled'] = $this->Api_settings_model->get_setting('cors_enabled') == '1';
        $view_data['global_cors_origins'] = $this->Api_settings_model->get_setting('cors_allowed_origins') ?: '';
        $view_data['global_ip_whitelist'] = $this->Api_settings_model->get_setting('default_ip_whitelist') ?: '';

        // Load permission groups for dropdown
        $permission_groups = $this->Api_permission_groups_model->get_details()->getResult();
        $view_data['permission_groups_dropdown'] = array('' => '- ' . app_lang('no_permission_group') . ' (' . app_lang('full_access') . ') -');
        foreach ($permission_groups as $group) {
            $view_data['permission_groups_dropdown'][$group->id] = $group->name;
        }

        return $this->template->view("Rest_api\Views\settings\modals\api_key_form", $view_data);
    }

    /**
     * Save API key
     */
    function save() {
        $id = $this->request->getPost('id');

        $this->validate_submitted_data(array(
            "name" => "required"
        ));

        // Handle per-key security settings (NULL = use global, 0/1 = override)
        $require_https = $this->request->getPost('require_https');
        $cors_enabled = $this->request->getPost('cors_enabled');
        
        $permission_group_id = $this->request->getPost('permission_group_id');
        
        $data = array(
            "name" => $this->request->getPost('name'),
            "description" => $this->request->getPost('description'),
            "assignment_type" => $this->request->getPost('assignment_type') ?: 'internal',
            "status" => $this->request->getPost('status') ?: 'active',
            "rate_limit_per_minute" => $this->request->getPost('rate_limit_per_minute') ?: 60,
            "rate_limit_per_hour" => $this->request->getPost('rate_limit_per_hour') ?: 1000,
            "rate_limit_per_day" => $this->request->getPost('rate_limit_per_day') ?: 10000,
            "ip_whitelist" => $this->request->getPost('ip_whitelist'),
            "require_https" => $require_https === '' ? null : ($require_https == '1' ? 1 : 0),
            "cors_enabled" => $cors_enabled === '' ? null : ($cors_enabled == '1' ? 1 : 0),
            "cors_allowed_origins" => $this->request->getPost('cors_allowed_origins') ?: null,
            "permission_group_id" => $permission_group_id ? $permission_group_id : null,
            "expires_at" => $this->request->getPost('expires_at') ? $this->request->getPost('expires_at') : null
        );

        $plain_secret = null;
        
        if (!$id) {
            // New API key - generate key and secret
            $data['key'] = $this->Api_keys_model->generate_key();
            $plain_secret = bin2hex(random_bytes(64)); // Store plain secret to return
            $data['secret'] = password_hash($plain_secret, PASSWORD_BCRYPT);
            $data['created_by'] = $this->login_user->id;
            $data['created_at'] = get_current_utc_time();
        }

        $save_id = $this->Api_keys_model->ci_save($data, $id);
        
        if ($save_id) {
            $response = array(
                "success" => true,
                "data" => $this->_row_data($save_id),
                "id" => $save_id,
                "message" => app_lang('api_key_created')
            );
            
            // Include API key and secret for NEW keys only (one-time view)
            if ($plain_secret) {
                $response['api_key'] = $data['key'];
                $response['api_secret'] = $plain_secret;
            }
            
            echo json_encode($response);
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    /**
     * Delete API key
     */
    function delete() {
        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));
        
        $id = $this->request->getPost('id');

        if ($this->Api_keys_model->delete($id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('api_key_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    /**
     * Get row data for DataTable
     */
    function _row_data($id) {
        $options = array("id" => $id);
        $data = $this->Api_keys_model->get_details($options)->getRow();
        return $this->_make_row($data);
    }
}
