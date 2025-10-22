<?php

namespace Rest_api\Models;

use App\Models\Crud_model;

class Api_permission_groups_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'api_permission_groups';
        parent::__construct($this->table);
    }

    /**
     * Get permission groups with filters
     * 
     * @param array $options
     * @return object Query result
     */
    function get_details($options = array()) {
        $this->db_builder->where('deleted', 0);

        $id = get_array_value($options, "id");
        if ($id) {
            $this->db_builder->where('id', $id);
        }

        $is_system = get_array_value($options, "is_system");
        if ($is_system !== null) {
            $this->db_builder->where('is_system', $is_system ? 1 : 0);
        }

        $this->db_builder->orderBy('is_system', 'DESC');
        $this->db_builder->orderBy('name', 'ASC');

        return $this->db_builder->get();
    }

    /**
     * Get available API endpoints
     * Returns array of endpoint names and their available CRUD operations
     * 
     * @return array
     */
    function get_available_endpoints() {
        return array(
            'users' => array(
                'name' => 'Users',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'projects' => array(
                'name' => 'Projects',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'tasks' => array(
                'name' => 'Tasks',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'clients' => array(
                'name' => 'Clients',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'invoices' => array(
                'name' => 'Invoices',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'estimates' => array(
                'name' => 'Estimates',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'proposals' => array(
                'name' => 'Proposals',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'contracts' => array(
                'name' => 'Contracts',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'expenses' => array(
                'name' => 'Expenses',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'tickets' => array(
                'name' => 'Tickets',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'timesheets' => array(
                'name' => 'Timesheets',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'events' => array(
                'name' => 'Events',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'notes' => array(
                'name' => 'Notes',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'messages' => array(
                'name' => 'Messages',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'notifications' => array(
                'name' => 'Notifications',
                'operations' => array('create', 'read', 'update', 'delete')
            ),
            'announcements' => array(
                'name' => 'Announcements',
                'operations' => array('create', 'read', 'update', 'delete')
            )
        );
    }

    /**
     * Validate permissions JSON structure
     * 
     * @param string $permissions_json
     * @return bool
     */
    function validate_permissions($permissions_json) {
        if (empty($permissions_json)) {
            return false;
        }

        $permissions = json_decode($permissions_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        $available_endpoints = $this->get_available_endpoints();
        
        // Validate structure
        foreach ($permissions as $endpoint => $operations) {
            if (!isset($available_endpoints[$endpoint])) {
                return false;
            }
            
            if (!is_array($operations)) {
                return false;
            }

            foreach ($operations as $operation => $enabled) {
                if (!in_array($operation, array('create', 'read', 'update', 'delete'))) {
                    return false;
                }
                
                if (!is_bool($enabled)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Override delete to prevent deletion of system groups
     */
    function delete($id = 0, $undo = false) {
        $group_info = $this->get_one($id);
        
        if ($group_info && $group_info->is_system == 1) {
            return false; // Cannot delete system groups
        }

        return parent::delete($id, $undo);
    }

    /**
     * Count endpoints that are enabled in permissions
     * 
     * @param string $permissions_json
     * @return int
     */
    function count_enabled_endpoints($permissions_json) {
        if (empty($permissions_json)) {
            return 0;
        }

        $permissions = json_decode($permissions_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return 0;
        }

        $count = 0;
        foreach ($permissions as $endpoint => $operations) {
            // Count endpoint as enabled if at least one operation is true
            if (is_array($operations)) {
                foreach ($operations as $enabled) {
                    if ($enabled === true) {
                        $count++;
                        break;
                    }
                }
            }
        }

        return $count;
    }
}

