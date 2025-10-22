<?php

namespace Rest_api\Models;

use App\Models\Crud_model;

class Api_logs_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'api_logs';
        parent::__construct($this->table);
        
        // Disable activity logging for performance
        $this->disable_log_activity();
    }

    /**
     * Log an API request
     * 
     * @param array $log_data
     * @return int Insert ID
     */
    function log_request($log_data) {
        $data = array(
            'api_key_id' => get_array_value($log_data, 'api_key_id'),
            'method' => get_array_value($log_data, 'method'),
            'endpoint' => get_array_value($log_data, 'endpoint'),
            'request_body' => get_array_value($log_data, 'request_body'),
            'response_code' => get_array_value($log_data, 'response_code'),
            'response_body' => get_array_value($log_data, 'response_body'),
            'response_time' => get_array_value($log_data, 'response_time'),
            'ip_address' => get_array_value($log_data, 'ip_address'),
            'user_agent' => get_array_value($log_data, 'user_agent'),
            'created_at' => date('Y-m-d H:i:s')
        );

        return $this->ci_save($data);
    }

    /**
     * Get API logs with filters - OPTIMIZED for large datasets
     * 
     * @param array $options
     * @return object|array Query result object, or array with data/recordsTotal when paginated
     */
    function get_details($options = array()) {
        $api_logs_table = $this->db->prefixTable('api_logs');
        $api_keys_table = $this->db->prefixTable('api_keys');

        // OPTIMIZATION: Exclude heavy text columns by default to prevent memory issues
        // Use 'include_body' option to load request/response bodies when needed
        $include_body = $this->_get_clean_value($options, "include_body");
        if ($include_body) {
            $this->db_builder->select("$api_logs_table.*, $api_keys_table.name AS api_key_name");
        } else {
            // Select only lightweight columns for list view
            $this->db_builder->select("
                $api_logs_table.id,
                $api_logs_table.api_key_id,
                $api_logs_table.method,
                $api_logs_table.endpoint,
                $api_logs_table.response_code,
                $api_logs_table.response_time,
                $api_logs_table.ip_address,
                $api_logs_table.created_at,
                $api_keys_table.name AS api_key_name
            ");
        }
        
        $this->db_builder->join($api_keys_table, "$api_keys_table.id = $api_logs_table.api_key_id", 'left');

        // Apply filters
        $this->_apply_log_filters($options, $api_logs_table);

        $this->db_builder->orderBy("$api_logs_table.created_at", 'DESC');

        $limit = $this->_get_clean_value($options, "limit");
        if ($limit) {
            $skip = $this->_get_clean_value($options, "skip");
            $offset = $skip ? $skip : 0;
            
            // OPTIMIZATION: Use separate lightweight query for counting
            $total_count = $this->_get_filtered_count($options);
            
            $this->db_builder->limit($limit, $offset);
            $result = $this->db_builder->get();
            
            return array(
                "data" => $result->getResult(),
                "recordsTotal" => $total_count,
                "recordsFiltered" => $total_count,
            );
        }

        return $this->db_builder->get();
    }

    /**
     * Apply common filters to log queries
     * Extracted to reuse for both data retrieval and counting
     * 
     * @param array $options
     * @param string $api_logs_table
     */
    private function _apply_log_filters($options, $api_logs_table) {
        $api_key_id = $this->_get_clean_value($options, "api_key_id");
        if ($api_key_id) {
            $this->db_builder->where("$api_logs_table.api_key_id", $api_key_id);
        }

        $method = $this->_get_clean_value($options, "method");
        if ($method) {
            $this->db_builder->where("$api_logs_table.method", $method);
        }

        $endpoint = $this->_get_clean_value($options, "endpoint");
        if ($endpoint) {
            $this->db_builder->like("$api_logs_table.endpoint", $endpoint);
        }

        $response_code = $this->_get_clean_value($options, "response_code");
        if ($response_code) {
            $this->db_builder->where("$api_logs_table.response_code", $response_code);
        }

        $start_date = $this->_get_clean_value($options, "start_date");
        if ($start_date) {
            $this->db_builder->where("DATE($api_logs_table.created_at) >=", $start_date);
        }

        $end_date = $this->_get_clean_value($options, "end_date");
        if ($end_date) {
            $this->db_builder->where("DATE($api_logs_table.created_at) <=", $end_date);
        }
    }

    /**
     * Get count of filtered logs using optimized query
     * Separate method to avoid loading heavy columns just for counting
     * 
     * @param array $options
     * @return int Total count
     */
    private function _get_filtered_count($options) {
        $api_logs_table = $this->db->prefixTable('api_logs');
        
        // Use separate builder instance for counting
        $count_builder = $this->db->table($api_logs_table);
        $count_builder->select("COUNT($api_logs_table.id) as total");
        
        // Apply same filters using a temporary instance
        $temp_builder = $this->db_builder;
        $this->db_builder = $count_builder;
        $this->_apply_log_filters($options, $api_logs_table);
        
        $result = $count_builder->get()->getRow();
        
        // Restore original builder
        $this->db_builder = $temp_builder;
        
        return $result ? (int) $result->total : 0;
    }

    /**
     * Get single log entry with full details including request/response bodies
     * Use this for detail view to avoid loading heavy data for all records
     * 
     * @param int $log_id
     * @return object|null Log entry
     */
    function get_log_details($log_id) {
        $log_id = (int) $this->_get_clean_value($log_id);
        $api_logs_table = $this->db->prefixTable('api_logs');
        $api_keys_table = $this->db->prefixTable('api_keys');
        
        $this->db_builder->select("$api_logs_table.*, $api_keys_table.name AS api_key_name");
        $this->db_builder->join($api_keys_table, "$api_keys_table.id = $api_logs_table.api_key_id", 'left');
        $this->db_builder->where("$api_logs_table.id", $log_id);
        $this->db_builder->limit(1);
        
        $result = $this->db_builder->get();
        return $result ? $result->getRow() : null;
    }

    /**
     * Clean old logs based on retention policy
     * 
     * @param int $retention_days Number of days to keep logs (0 = delete all)
     * @return int Number of deleted records
     */
    function clean_old_logs($retention_days = 90) {
        $retention_days = (int) $this->_get_clean_value($retention_days);
        
        if ($retention_days === 0) {
            // Delete ALL logs (manual override)
            $this->db_builder->truncate();
        } else {
            // Delete only old logs (automatic cleanup)
            $cutoff_date = date('Y-m-d', strtotime("-$retention_days days"));
            $this->db_builder->where("DATE(created_at) <", $cutoff_date);
            $this->db_builder->delete();
        }
        
        return $this->db->affectedRows();
    }

    /**
     * Get API usage statistics
     * 
     * @param array $options
     * @return object Statistics
     */
    function get_statistics($options = array()) {
        $this->db_builder->select('COUNT(*) as total_requests', false);
        $this->db_builder->select('COUNT(DISTINCT DATE(created_at)) as active_days', false);
        $this->db_builder->select('AVG(response_time) as avg_response_time', false);
        $this->db_builder->select('MAX(response_time) as max_response_time', false);
        $this->db_builder->select('SUM(CASE WHEN response_code >= 200 AND response_code < 300 THEN 1 ELSE 0 END) as success_count', false);
        $this->db_builder->select('SUM(CASE WHEN response_code >= 400 THEN 1 ELSE 0 END) as error_count', false);
        
        $api_key_id = $this->_get_clean_value($options, "api_key_id");
        if ($api_key_id) {
            $this->db_builder->where('api_key_id', $api_key_id);
        }
        
        $start_date = $this->_get_clean_value($options, "start_date");
        if ($start_date) {
            $this->db_builder->where("DATE(created_at) >=", $start_date);
        }
        
        $end_date = $this->_get_clean_value($options, "end_date");
        if ($end_date) {
            $this->db_builder->where("DATE(created_at) <=", $end_date);
        }

        return $this->db_builder->get()->getRow();
    }
}

