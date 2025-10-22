<?php

namespace Rest_api\Controllers;

use App\Controllers\Security_Controller;
use Rest_api\Models\Api_permission_groups_model;

class Api_permission_groups extends Security_Controller {

    protected $Api_permission_groups_model;

    function __construct() {
        parent::__construct();
        $this->access_only_admin();
        
        $this->Api_permission_groups_model = new Api_permission_groups_model();
    }

    /**
     * Permission Groups List Page
     */
    function index() {
        return $this->template->rander("Rest_api\Views\settings\\tabs\permission_groups");
    }

    /**
     * Get permission groups list data for DataTable
     */
    function list_data() {
        $list_data = $this->Api_permission_groups_model->get_details()->getResult();
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
        $name = $data->name;
        
        // Add system badge if it's a system group
        if ($data->is_system == 1) {
            $name .= ' <span class="badge bg-warning ms-2">' . app_lang('system_group_protected') . '</span>';
        }

        $description = $data->description ? $data->description : '-';
        
        // Count enabled endpoints
        $endpoints_count = $this->Api_permission_groups_model->count_enabled_endpoints($data->permissions);
        $available_endpoints = count($this->Api_permission_groups_model->get_available_endpoints());
        $endpoints_display = $endpoints_count . ' / ' . $available_endpoints;

        $created_at = format_to_datetime($data->created_at);

        // Actions - prevent deletion of system groups
        $actions = modal_anchor(get_uri("api_permission_groups/view/" . $data->id), "<i data-feather='info' class='icon-16'></i>", array(
            "class" => "view-group-details",
            "title" => app_lang('view_details'),
            "data-modal-lg" => "1"
        ));

        $actions .= modal_anchor(get_uri("api_permission_groups/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array(
            "class" => "edit",
            "title" => app_lang('edit_permission_group'),
            "data-post-id" => $data->id,
            "data-modal-lg" => "1"
        ));

        // Only allow deletion of non-system groups
        if ($data->is_system != 1) {
            $actions .= js_anchor("<i data-feather='x' class='icon-16'></i>", array(
                'title' => app_lang('delete_permission_group'),
                "class" => "delete",
                "data-id" => $data->id,
                "data-action-url" => get_uri("api_permission_groups/delete"),
                "data-action" => "delete-confirmation"
            ));
        }

        return array(
            $name,
            $description,
            $endpoints_display,
            $created_at,
            $actions
        );
    }

    /**
     * View permission group details
     */
    function view($id = 0) {
        if ($id) {
            $group_info = $this->Api_permission_groups_model->get_one($id);
            $view_data['group_info'] = $group_info;
            
            // Decode permissions for display
            $view_data['permissions'] = json_decode($group_info->permissions, true);
            $view_data['available_endpoints'] = $this->Api_permission_groups_model->get_available_endpoints();
            
            return $this->template->view("Rest_api\Views\settings\modals\permission_group_details", $view_data);
        }
        show_404();
    }

    /**
     * Permission Group modal form
     */
    function modal_form() {
        $model_info = $this->Api_permission_groups_model->get_one($this->request->getPost('id'));
        $view_data['model_info'] = $model_info;
        
        // Get available endpoints
        $view_data['available_endpoints'] = $this->Api_permission_groups_model->get_available_endpoints();
        
        // Decode existing permissions if editing
        if ($model_info && $model_info->permissions) {
            $view_data['existing_permissions'] = json_decode($model_info->permissions, true);
        } else {
            $view_data['existing_permissions'] = array();
        }

        return $this->template->view("Rest_api\Views\settings\modals\permission_group_form", $view_data);
    }

    /**
     * Save permission group
     */
    function save() {
        $id = $this->request->getPost('id');
        $is_editing = !empty($id);

        $this->validate_submitted_data(array(
            "name" => "required"
        ));

        // Get group info to check if it's a system group
        $group_info = null;
        if ($id) {
            $group_info = $this->Api_permission_groups_model->get_one($id);
        }

        $data = array(
            "description" => $this->request->getPost('description')
        );

        // Only allow name changes for non-system groups
        if (!$group_info || $group_info->is_system != 1) {
            $data["name"] = $this->request->getPost('name');
        }

        // Build permissions JSON from submitted data
        $permissions = array();
        $available_endpoints = $this->Api_permission_groups_model->get_available_endpoints();
        
        foreach ($available_endpoints as $endpoint_key => $endpoint_info) {
            $permissions[$endpoint_key] = array(
                'create' => $this->request->getPost($endpoint_key . '_create') == '1',
                'read' => $this->request->getPost($endpoint_key . '_read') == '1',
                'update' => $this->request->getPost($endpoint_key . '_update') == '1',
                'delete' => $this->request->getPost($endpoint_key . '_delete') == '1'
            );
        }

        $data['permissions'] = json_encode($permissions);

        // Validate permissions
        if (!$this->Api_permission_groups_model->validate_permissions($data['permissions'])) {
            echo json_encode(array("success" => false, 'message' => app_lang('invalid_permissions_data')));
            return;
        }

        if (!$id) {
            // New group
            $data['created_at'] = get_current_utc_time();
            $data['is_system'] = 0; // User-created groups are never system groups
        } else {
            $data['updated_at'] = get_current_utc_time();
        }

        $save_id = $this->Api_permission_groups_model->ci_save($data, $id);
        
        if ($save_id) {
            echo json_encode(array(
                "success" => true,
                "data" => $this->_row_data($save_id),
                "id" => $save_id,
                "message" => $is_editing ? app_lang('permission_group_updated') : app_lang('permission_group_created')
            ));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    /**
     * Delete permission group
     */
    function delete() {
        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));
        
        $id = $this->request->getPost('id');
        
        // Check if it's a system group
        $group_info = $this->Api_permission_groups_model->get_one($id);
        if ($group_info && $group_info->is_system == 1) {
            echo json_encode(array("success" => false, 'message' => app_lang('cannot_delete_system_group')));
            return;
        }

        if ($this->Api_permission_groups_model->delete($id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('permission_group_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    /**
     * Get row data for DataTable
     */
    function _row_data($id) {
        $options = array("id" => $id);
        $data = $this->Api_permission_groups_model->get_details($options)->getRow();
        return $this->_make_row($data);
    }

    /**
     * Get available endpoints (AJAX endpoint for dynamic loading)
     */
    function get_endpoints() {
        $endpoints = $this->Api_permission_groups_model->get_available_endpoints();
        echo json_encode(array("success" => true, "endpoints" => $endpoints));
    }
}

