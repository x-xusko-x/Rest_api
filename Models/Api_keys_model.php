<?php

namespace Rest_api\Models;

use App\Models\Crud_model;

class Api_keys_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'api_keys';
        parent::__construct($this->table);
    }

    /**
     * Get API key details with filters - SIMPLIFIED
     * 
     * @param array $options
     * @return object Query result
     */
    function get_details($options = array()) {
        $api_keys_table = $this->db->prefixTable('api_keys');
        $permission_groups_table = $this->db->prefixTable('api_permission_groups');
        
        $this->db_builder->select("$api_keys_table.*, $permission_groups_table.name as permission_group_name");
        $this->db_builder->join($permission_groups_table, "$api_keys_table.permission_group_id = $permission_groups_table.id AND $permission_groups_table.deleted = 0", 'left');
        $this->db_builder->where("$api_keys_table.deleted", 0);

        $id = get_array_value($options, "id");
        if ($id) {
            $this->db_builder->where("$api_keys_table.id", $id);
        }

        $status = get_array_value($options, "status");
        if ($status) {
            $this->db_builder->where("$api_keys_table.status", $status);
        }

        $this->db_builder->orderBy("$api_keys_table.created_at", 'DESC');

        return $this->db_builder->get();
    }

    /**
     * Generate a new API key
     * 
     * @return string 64-character API key
     */
    function generate_key() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate a new API secret
     * 
     * @return string Random secret
     */
    function generate_secret() {
        return bin2hex(random_bytes(64));
    }

    /**
     * Hash the API secret
     * 
     * @param string $secret
     * @return string Hashed secret
     */
    function hash_secret($secret) {
        return password_hash($secret, PASSWORD_BCRYPT);
    }
}
