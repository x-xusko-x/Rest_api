<?php

namespace Rest_api\Config;

use CodeIgniter\Events\Events;

/**
 * Plugin Event Hooks
 * 
 * Register event listeners for the REST API plugin
 */

// Simple event registration - don't instantiate models here
// as this file is loaded during plugin installation/registration

Events::on('pre_system', function () {
    // Plugin initialization code can go here if needed
});

// Periodic statistics update - runs on post_controller_constructor
// This ensures models are available and runs once per request (minimal overhead)
Events::on('post_controller_constructor', function () {
    try {
        // Only run periodically (every 5-10 minutes) to avoid overhead
        $settings_model = new \Rest_api\Models\Api_settings_model();
        $last_stats_update = $settings_model->get_setting('last_stats_update');
        
        $current_time = time();
        $last_update_time = $last_stats_update ? strtotime($last_stats_update) : 0;
        
        // Update every 10 minutes (600 seconds)
        if (($current_time - $last_update_time) >= 600) {
            $statistics_model = new \Rest_api\Models\Api_statistics_model();
            $result = $statistics_model->update_from_logs();
            
            // Update last update timestamp
            if ($result['success']) {
                $settings_model->save_setting('last_stats_update', date('Y-m-d H:i:s'));
                
                // Log if new calls were processed
                if (isset($result['new_calls']) && $result['new_calls'] > 0) {
                    log_message('info', "REST API: Persistent statistics updated - {$result['new_calls']} new calls processed");
                }
            }
        }
    } catch (\Exception $e) {
        // Don't break the application if stats update fails
        log_message('error', 'REST API: Failed to update persistent statistics: ' . $e->getMessage());
    }
});