<?php

namespace Rest_api\Models;

use App\Models\Crud_model;

class Api_statistics_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'api_statistics';
        parent::__construct($this->table);
        
        // Disable activity logging for performance
        $this->disable_log_activity();
    }

    /**
     * Get persistent statistics (single row table, id=1)
     * 
     * @return object|null Statistics record
     */
    function get_statistics() {
        $this->db_builder->where('id', 1);
        $this->db_builder->limit(1);
        $result = $this->db_builder->get();
        
        if ($result) {
            $row = $result->getRow();
            if ($row) {
                return $row;
            }
        }
        
        // If no record exists, initialize it
        $this->initialize_statistics();
        
        // Try again after initialization
        $this->db_builder->where('id', 1);
        $this->db_builder->limit(1);
        $result = $this->db_builder->get();
        return $result ? $result->getRow() : null;
    }

    /**
     * Initialize statistics record if it doesn't exist
     * 
     * @return bool Success
     */
    function initialize_statistics() {
        // Check if record exists
        $this->db_builder->select('id');
        $this->db_builder->where('id', 1);
        $check = $this->db_builder->get();
        if ($check && $check->getRow()) {
            return true; // Already initialized
        }
        
        // Initialize with zeros
        $data = array(
            'id' => 1,
            'total_calls' => 0,
            'successful_calls' => 0,
            'failed_calls' => 0,
            'total_response_time' => 0.0000,
            'first_call_date' => null,
            'last_updated_at' => null
        );
        
        return $this->db_builder->insert($data);
    }

    /**
     * Aggregate new logs and update persistent statistics
     * Called periodically by cron/event system
     * 
     * @return array Result with counts
     */
    function update_from_logs() {
        $api_logs_table = $this->db->prefixTable('api_logs');
        $api_statistics_table = $this->db->prefixTable('api_statistics');
        
        // Get current statistics
        $stats = $this->get_statistics();
        if (!$stats) {
            return array('success' => false, 'message' => 'Failed to get statistics');
        }
        
        $last_updated = $stats->last_updated_at;
        
        // Build where clause for new logs only
        $logs_builder = $this->db->table($this->db->prefixTable('api_logs'));
        $logs_builder->select('COUNT(*) as new_total_calls', false);
        $logs_builder->select('SUM(CASE WHEN response_code >= 200 AND response_code < 300 THEN 1 ELSE 0 END) as new_successful_calls', false);
        $logs_builder->select('SUM(CASE WHEN response_code >= 400 THEN 1 ELSE 0 END) as new_failed_calls', false);
        $logs_builder->select('SUM(response_time) as new_total_response_time', false);
        $logs_builder->select('MIN(created_at) as earliest_new_call', false);
        
        if ($last_updated) {
            $logs_builder->where('created_at >', $last_updated);
        }
        
        $result = $logs_builder->get();
        $new_data = $result ? $result->getRow() : null;
        
        if (!$new_data || $new_data->new_total_calls == 0) {
            return array('success' => true, 'message' => 'No new logs to process', 'new_calls' => 0);
        }
        
        // Update statistics with new data
        $this->db_builder->set('total_calls', 'total_calls + ' . intval($new_data->new_total_calls), false);
        $this->db_builder->set('successful_calls', 'successful_calls + ' . intval($new_data->new_successful_calls), false);
        $this->db_builder->set('failed_calls', 'failed_calls + ' . intval($new_data->new_failed_calls), false);
        $this->db_builder->set('total_response_time', 'total_response_time + ' . floatval($new_data->new_total_response_time), false);
        $this->db_builder->set('first_call_date', "COALESCE(first_call_date, '" . $new_data->earliest_new_call . "')", false);
        $this->db_builder->set('last_updated_at', 'NOW()', false);
        $this->db_builder->where('id', 1);
        $this->db_builder->update();
        
        return array(
            'success' => true, 
            'message' => 'Statistics updated successfully',
            'new_calls' => intval($new_data->new_total_calls),
            'new_successful' => intval($new_data->new_successful_calls),
            'new_failed' => intval($new_data->new_failed_calls)
        );
    }

    /**
     * Calculate lifetime averages based on persistent statistics
     * 
     * @return object Calculated averages
     */
    function get_calculated_averages() {
        $stats = $this->get_statistics();
        
        $result = (object) array(
            'avg_per_day' => 0,
            'avg_per_month' => 0,
            'success_rate' => 0,
            'avg_response_time' => 0,
            'days_active' => 0,
            'months_active' => 0
        );
        
        if (!$stats || !$stats->first_call_date) {
            return $result;
        }
        
        // Calculate days since first call
        $first_call_timestamp = strtotime($stats->first_call_date);
        $now_timestamp = time();
        $days_diff = max(1, floor(($now_timestamp - $first_call_timestamp) / 86400)); // At least 1 day
        $months_diff = max(1, floor($days_diff / 30)); // Approximate months
        
        $result->days_active = $days_diff;
        $result->months_active = $months_diff;
        
        // Calculate averages
        if ($stats->total_calls > 0) {
            $result->avg_per_day = round($stats->total_calls / $days_diff, 2);
            $result->avg_per_month = round($stats->total_calls / $months_diff, 2);
            $result->success_rate = round(($stats->successful_calls / $stats->total_calls) * 100, 2);
            $result->avg_response_time = round($stats->total_response_time / $stats->total_calls, 4);
        }
        
        return $result;
    }

    /**
     * Manually recalculate statistics from all logs
     * Useful for fixing data inconsistencies
     * 
     * Note: This uses raw SQL for "ON DUPLICATE KEY UPDATE" because CodeIgniter's 
     * Query Builder doesn't support this MySQL-specific syntax, which is needed for
     * atomic upsert operations.
     * 
     * @return bool Success
     */
    function recalculate_from_all_logs() {
        $api_logs_table = $this->db->prefixTable('api_logs');
        $api_statistics_table = $this->db->prefixTable('api_statistics');
        
        // Aggregate ALL logs using Query Builder
        $logs_builder = $this->db->table($api_logs_table);
        $logs_builder->select('COUNT(*) as total_calls', false);
        $logs_builder->select('SUM(CASE WHEN response_code >= 200 AND response_code < 300 THEN 1 ELSE 0 END) as successful_calls', false);
        $logs_builder->select('SUM(CASE WHEN response_code >= 400 THEN 1 ELSE 0 END) as failed_calls', false);
        $logs_builder->select('SUM(response_time) as total_response_time', false);
        $logs_builder->select('MIN(created_at) as first_call_date', false);
        $logs_builder->where('created_at IS NOT NULL', null, false);
        
        $result = $logs_builder->get();
        $data = $result ? $result->getRow() : null;
        
        if (!$data) {
            return false;
        }
        
        // Update or insert statistics
        // Raw SQL required for ON DUPLICATE KEY UPDATE (MySQL-specific)
        $update_sql = "INSERT INTO $api_statistics_table 
                        (id, total_calls, successful_calls, failed_calls, total_response_time, first_call_date, last_updated_at)
                        VALUES (
                            1,
                            " . intval($data->total_calls) . ",
                            " . intval($data->successful_calls) . ",
                            " . intval($data->failed_calls) . ",
                            " . floatval($data->total_response_time) . ",
                            " . ($data->first_call_date ? "'" . $data->first_call_date . "'" : "NULL") . ",
                            NOW()
                        )
                        ON DUPLICATE KEY UPDATE
                            total_calls = " . intval($data->total_calls) . ",
                            successful_calls = " . intval($data->successful_calls) . ",
                            failed_calls = " . intval($data->failed_calls) . ",
                            total_response_time = " . floatval($data->total_response_time) . ",
                            first_call_date = " . ($data->first_call_date ? "'" . $data->first_call_date . "'" : "NULL") . ",
                            last_updated_at = NOW()";
        
        return $this->db->query($update_sql);
    }
}

