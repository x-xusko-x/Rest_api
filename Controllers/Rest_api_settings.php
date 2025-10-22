<?php

namespace Rest_api\Controllers;

use App\Controllers\Security_Controller;
use Rest_api\Models\Api_keys_model;
use Rest_api\Models\Api_logs_model;
use Rest_api\Models\Api_settings_model;
use Rest_api\Models\Api_statistics_model;

class Rest_api_settings extends Security_Controller {

    protected $Api_keys_model;
    protected $Api_logs_model;
    protected $Api_settings_model;
    protected $Api_statistics_model;

    function __construct() {
        parent::__construct();
        $this->access_only_admin();
        
        $this->Api_keys_model = new Api_keys_model();
        $this->Api_logs_model = new Api_logs_model();
        $this->Api_settings_model = new Api_settings_model();
        $this->Api_statistics_model = new Api_statistics_model();
    }

    /**
     * REST API Settings - Main view with tabs
     */
    function index() {
        return $this->template->rander("Rest_api\Views\settings\index");
    }

    /**
     * Dashboard tab
     */
    function dashboard() {
        // Get statistics with error handling
        $view_data['total_api_keys'] = 0;
        $view_data['active_api_keys'] = 0;
        $view_data['requests_today'] = 0;
        $view_data['requests_this_month'] = 0;
        $view_data['total_requests'] = 0;
        $view_data['successful_requests'] = 0;
        $view_data['failed_requests'] = 0;
        $view_data['avg_response_time'] = 0;
        
        // Initialize lifetime statistics
        $view_data['lifetime_total_calls'] = 0;
        $view_data['lifetime_avg_per_day'] = 0;
        $view_data['lifetime_avg_per_month'] = 0;
        $view_data['lifetime_success_rate'] = 0;

        try {
            $all_keys_result = $this->Api_keys_model->get_details(array('deleted' => 0));
            if ($all_keys_result) {
                $all_keys = $all_keys_result->getResult();
                $view_data['total_api_keys'] = count($all_keys);
                
                $active_count = 0;
                foreach ($all_keys as $key) {
                    if ($key->status === 'active') {
                        $active_count++;
                    }
                }
                $view_data['active_api_keys'] = $active_count;
            }
        } catch (\Exception $e) {
            log_message('error', 'Dashboard API keys count error: ' . $e->getMessage());
        }
        
        try {
            $today = date('Y-m-d');
            $this_month_start = date('Y-m-01');

            $today_stats = $this->Api_logs_model->get_statistics(['start_date' => $today]);
            if ($today_stats) {
                $view_data['requests_today'] = isset($today_stats->total_requests) ? $today_stats->total_requests : 0;
            }
            
            $month_stats = $this->Api_logs_model->get_statistics(['start_date' => $this_month_start]);
            if ($month_stats) {
                $view_data['requests_this_month'] = isset($month_stats->total_requests) ? $month_stats->total_requests : 0;
            }
            
            $total_stats = $this->Api_logs_model->get_statistics();
            if ($total_stats) {
                $view_data['total_requests'] = isset($total_stats->total_requests) ? $total_stats->total_requests : 0;
                $view_data['successful_requests'] = isset($total_stats->success_count) ? $total_stats->success_count : 0;
                $view_data['failed_requests'] = isset($total_stats->error_count) ? $total_stats->error_count : 0;
                $view_data['avg_response_time'] = isset($total_stats->avg_response_time) && $total_stats->avg_response_time ? round($total_stats->avg_response_time, 4) : 0;
            }
        } catch (\Exception $e) {
            log_message('error', 'Dashboard statistics error: ' . $e->getMessage());
        }
        
        // Get persistent lifetime statistics
        try {
            $persistent_stats = $this->Api_statistics_model->get_statistics();
            if ($persistent_stats) {
                $view_data['lifetime_total_calls'] = $persistent_stats->total_calls;
                
                // Get calculated averages
                $averages = $this->Api_statistics_model->get_calculated_averages();
                if ($averages) {
                    $view_data['lifetime_avg_per_day'] = $averages->avg_per_day;
                    $view_data['lifetime_avg_per_month'] = $averages->avg_per_month;
                    $view_data['lifetime_success_rate'] = $averages->success_rate;
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Dashboard lifetime statistics error: ' . $e->getMessage());
        }

        return $this->template->view("Rest_api\Views\settings\\tabs\dashboard", $view_data);
    }

    /**
     * General Settings tab
     */
    function general_settings() {
        $view_data['settings'] = array();
        
        try {
            $settings_result = $this->Api_settings_model->get_all_settings();
            if ($settings_result) {
                $settings = $settings_result->getResult();
                foreach ($settings as $setting) {
                    $view_data['settings'][$setting->setting_name] = $setting->setting_value;
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'General settings load error: ' . $e->getMessage());
        }
        
        return $this->template->view("Rest_api\Views\settings\\tabs\general_settings", $view_data);
    }

    /**
     * Save general settings
     */
    function save_general_settings() {
        $this->access_only_admin();

        $settings = array(
            "api_enabled",
            "default_rate_limit_per_minute",
            "default_rate_limit_per_hour",
            "default_rate_limit_per_day",
            "log_retention_days",
            "require_https",
            "cors_enabled",
            "cors_allowed_origins",
            "default_ip_whitelist"
        );

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            if (is_null($value)) {
                $value = "";
            }
            $this->Api_settings_model->save_setting($setting, $value);
        }

        echo json_encode(array("success" => true, "message" => app_lang('settings_updated')));
    }

    /**
     * API Keys tab
     */
    function api_keys_tab() {
        return $this->template->view("Rest_api\Views\settings\\tabs\api_keys");
    }

    /**
     * Permission Groups tab
     */
    function permission_groups_tab() {
        return $this->template->view("Rest_api\Views\settings\\tabs\permission_groups");
    }

    /**
     * Logs tab - FIXED JSON encoding
     */
    function logs_tab() {
        // Build dropdown in correct format for filterDropdown
        $dropdown = array(array("id" => "", "text" => "- All -"));
        
        try {
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
        } catch (\Exception $e) {
            log_message('error', 'Logs tab dropdown error: ' . $e->getMessage());
        }
        
        // JSON encode for JavaScript
        $view_data['api_keys_dropdown'] = json_encode($dropdown);
        
        return $this->template->view("Rest_api\Views\settings\\tabs\api_logs", $view_data);
    }

    /**
     * Documentation tab
     */
    function documentation_tab() {
        return $this->template->view("Rest_api\Views\settings\\tabs\documentation");
    }

    /**
     * Clean logs - Manual override to delete ALL logs
     */
    function clean_logs() {
        $this->access_only_admin();
        
        try {
            // Manual button deletes ALL logs (retention_days = 0)
            $deleted_count = $this->Api_logs_model->clean_old_logs(0);
            
            if ($deleted_count > 0) {
                $message = app_lang('all_logs_cleared') . ": $deleted_count " . ($deleted_count == 1 ? "record" : "records") . " deleted";
                echo json_encode(array("success" => true, "message" => $message));
            } else {
                $message = app_lang('no_logs_to_delete');
                echo json_encode(array("success" => true, "message" => $message));
            }
        } catch (\Exception $e) {
            log_message('error', 'Clean logs error: ' . $e->getMessage());
            echo json_encode(array("success" => false, "message" => app_lang('error_occurred') . ": " . $e->getMessage()));
        }
    }
    
    /**
     * Update persistent statistics from logs
     * Can be called manually or by cron
     */
    function update_persistent_statistics() {
        $this->access_only_admin();
        
        try {
            $result = $this->Api_statistics_model->update_from_logs();
            
            if ($result['success']) {
                $new_calls = isset($result['new_calls']) ? $result['new_calls'] : 0;
                $message = "Statistics updated successfully. Processed $new_calls new call(s).";
                echo json_encode(array("success" => true, "message" => $message, "data" => $result));
            } else {
                echo json_encode(array("success" => false, "message" => $result['message']));
            }
        } catch (\Exception $e) {
            log_message('error', 'Update persistent statistics error: ' . $e->getMessage());
            echo json_encode(array("success" => false, "message" => app_lang('error_occurred') . ": " . $e->getMessage()));
        }
    }
    
    /**
     * Recalculate statistics from all logs
     * Useful for fixing data inconsistencies
     */
    function recalculate_statistics() {
        $this->access_only_admin();
        
        try {
            $success = $this->Api_statistics_model->recalculate_from_all_logs();
            
            if ($success) {
                echo json_encode(array("success" => true, "message" => "Statistics recalculated successfully from all logs."));
            } else {
                echo json_encode(array("success" => false, "message" => "Failed to recalculate statistics."));
            }
        } catch (\Exception $e) {
            log_message('error', 'Recalculate statistics error: ' . $e->getMessage());
            echo json_encode(array("success" => false, "message" => app_lang('error_occurred') . ": " . $e->getMessage()));
        }
    }
}
