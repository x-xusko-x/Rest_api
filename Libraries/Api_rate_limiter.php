<?php

namespace Rest_api\Libraries;

use Rest_api\Models\Api_rate_limits_model;

/**
 * API Rate Limiter
 * Tracks and enforces rate limits for API keys
 */
class Api_rate_limiter {

    protected $Api_rate_limits_model;
    
    public function __construct() {
        $this->Api_rate_limits_model = new Api_rate_limits_model();
    }
    
    /**
     * Check if rate limit is exceeded
     * 
     * @param int $api_key_id
     * @param array $limits ['per_minute' => 60, 'per_hour' => 1000, 'per_day' => 10000]
     * @return bool True if rate limit exceeded, false otherwise
     */
    public function check_rate_limit($api_key_id, $limits) {
        // Check current rate limit status using existing model
        $status = $this->Api_rate_limits_model->check_limit(
            $api_key_id, 
            $limits['per_minute'], 
            $limits['per_hour'],
            $limits['per_day']
        );
        
        // If not allowed, limit is exceeded
        if (!$status['allowed']) {
            return true; // Exceeded
        }
        
        // Increment the counter for this request
        $this->Api_rate_limits_model->increment_counter($api_key_id);
        
        return false; // Not exceeded
    }
    
    /**
     * Get current rate limit status for an API key
     * 
     * @param int $api_key_id
     * @param array $limits
     * @return array Status information
     */
    public function get_rate_limit_status($api_key_id, $limits) {
        $status = $this->Api_rate_limits_model->check_limit(
            $api_key_id,
            $limits['per_minute'],
            $limits['per_hour'],
            $limits['per_day']
        );
        
        return array(
            'per_minute' => array(
                'limit' => $limits['per_minute'],
                'remaining' => $status['minute_remaining']
            ),
            'per_hour' => array(
                'limit' => $limits['per_hour'],
                'remaining' => $status['hour_remaining']
            ),
            'per_day' => array(
                'limit' => $limits['per_day'],
                'remaining' => $status['day_remaining']
            )
        );
    }
    
    /**
     * Clean old rate limit records (run periodically via cron)
     */
    public function cleanup_old_records() {
        return $this->Api_rate_limits_model->clean_old_records();
    }
}

