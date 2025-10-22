<?php

namespace Rest_api\Models;

use App\Models\Crud_model;

class Api_rate_limits_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'api_rate_limits';
        parent::__construct($this->table);
        
        // Disable activity logging for performance
        $this->disable_log_activity();
    }
    
    /**
     * Override get_all to NOT filter by deleted column (table doesn't have it)
     */
    function get_all($include_deleted = false) {
        // Ignore $include_deleted parameter - table has no deleted column
        return $this->db_builder->get();
    }
    
    /**
     * Override get_all_where to NOT add deleted filter
     */
    function get_all_where($where = array(), $limit = 1000000, $offset = 0, $sort_by_field = null, $select_field_names = null) {
        $where = $this->_get_clean_value($where);

        if ($select_field_names) {
            $this->db_builder->select($select_field_names);
        }

        if ($sort_by_field) {
            $this->db_builder->orderBy($sort_by_field);
        }

        return $this->db_builder->getWhere($where, $limit, $offset);
    }
    
    /**
     * Override get_one_where to NOT add deleted filter
     */
    function get_one_where($where = array()) {
        $where = $this->_get_clean_value($where, "", false);

        $result = $this->db_builder->getWhere($where, 1);

        if ($result->getRow()) {
            return $result->getRow();
        }
        
        return false;
    }

    /**
     * Check rate limit for an API key
     * 
     * @param int $api_key_id
     * @param int $per_minute_limit
     * @param int $per_hour_limit
     * @return array ['allowed' => bool, 'minute_remaining' => int, 'hour_remaining' => int]
     */
    function check_limit($api_key_id, $per_minute_limit, $per_hour_limit, $per_day_limit = 10000) {
        $api_key_id = (int) $this->_get_clean_value($api_key_id);
        $per_minute_limit = (int) $this->_get_clean_value($per_minute_limit);
        $per_hour_limit = (int) $this->_get_clean_value($per_hour_limit);
        $per_day_limit = (int) $this->_get_clean_value($per_day_limit);

        $minute_window = date('Y-m-d H:i:00');
        $hour_window = date('Y-m-d H:00:00');
        $day_window = date('Y-m-d 00:00:00');

        // Get minute count from current minute window
        $minute_record = $this->get_one_where(array(
            'api_key_id' => $api_key_id,
            'minute_window' => $minute_window
        ));
        $minute_count = isset($minute_record->minute_count) ? (int) $minute_record->minute_count : 0;

        // Get hour count - sum ALL minute_counts from current hour
        $this->db_builder->selectSum('minute_count', 'total');
        $this->db_builder->select('COALESCE(SUM(minute_count), 0) as total', false);
        $this->db_builder->where('api_key_id', $api_key_id);
        $this->db_builder->where('hour_window', $hour_window);
        $hour_result = $this->db_builder->get()->getRow();
        $hour_count = (int) ($hour_result->total ?? 0);

        // Get day count - sum ALL minute_counts from current day
        $this->db_builder->select('COALESCE(SUM(minute_count), 0) as total', false);
        $this->db_builder->where('api_key_id', $api_key_id);
        $this->db_builder->where('day_window', $day_window);
        $day_result = $this->db_builder->get()->getRow();
        $day_count = (int) ($day_result->total ?? 0);

        $minute_remaining = max(0, $per_minute_limit - $minute_count);
        $hour_remaining = max(0, $per_hour_limit - $hour_count);
        $day_remaining = max(0, $per_day_limit - $day_count);

        $allowed = ($minute_count < $per_minute_limit) && ($hour_count < $per_hour_limit) && ($day_count < $per_day_limit);

        return array(
            'allowed' => $allowed,
            'minute_remaining' => $minute_remaining,
            'hour_remaining' => $hour_remaining,
            'day_remaining' => $day_remaining,
            'minute_limit' => $per_minute_limit,
            'hour_limit' => $per_hour_limit,
            'day_limit' => $per_day_limit,
            'minute_reset' => strtotime($minute_window) + 60,
            'hour_reset' => strtotime($hour_window) + 3600,
            'day_reset' => strtotime($day_window) + 86400
        );
    }

    /**
     * Increment rate limit counters
     * Uses INSERT ... ON DUPLICATE KEY UPDATE to avoid race conditions
     * 
     * Note: This uses raw SQL because CodeIgniter's Query Builder doesn't support 
     * "ON DUPLICATE KEY UPDATE" syntax, which is essential for handling race conditions
     * in high-concurrency scenarios.
     * 
     * @param int $api_key_id
     * @return bool
     */
    function increment_counter($api_key_id) {
        $api_key_id = (int) $this->_get_clean_value($api_key_id);

        $minute_window = date('Y-m-d H:i:00');
        $hour_window = date('Y-m-d H:00:00');
        $day_window = date('Y-m-d 00:00:00');
        $created_at = get_current_utc_time();

        $api_rate_limits_table = $this->db->prefixTable('api_rate_limits');

        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle race conditions
        // If record exists, increment minute_count; if not, insert new with minute_count = 1
        // Raw SQL is required as Query Builder doesn't support ON DUPLICATE KEY UPDATE
        $sql = "INSERT INTO $api_rate_limits_table 
                (api_key_id, minute_window, hour_window, day_window, minute_count, hour_count, day_count, created_at) 
                VALUES (?, ?, ?, ?, 1, 0, 0, ?)
                ON DUPLICATE KEY UPDATE 
                minute_count = minute_count + 1";

        try {
            $this->db->query($sql, [
                $api_key_id,
                $minute_window,
                $hour_window,
                $day_window,
                $created_at
            ]);
            return true;
        } catch (\Exception $e) {
            // Log error but don't fail the request
            log_message('error', 'Rate limit increment error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean old rate limit records
     * 
     * @return int Number of deleted records
     */
    function clean_old_records() {
        // Delete records older than 2 hours
        $cutoff_time = date('Y-m-d H:i:s', strtotime('-2 hours'));
        
        $this->db_builder->where('hour_window <', $cutoff_time);
        $this->db_builder->delete();
        
        return $this->db->affectedRows();
    }
}

