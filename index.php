<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: REST API
  Description: REST API interface for Rise CRM
  Version: 0.1.6 - Beta 2
  Requires at least: 3.6
  Author: x-xusko-x
  Author URL: https://github.com/x-xusko-x
 */

// Add admin setting menu item
app_hooks()->add_filter('app_filter_admin_settings_menu', function ($settings_menu) {
    $settings_menu["plugins"][] = array("name" => "rest_api", "url" => "rest_api_settings");
    return $settings_menu;
});

/* Add setting links to the plugin setting page
app_hooks()->add_filter('app_filter_action_links_of_Rest_api', function () {
    $action_links_array = array(
        anchor(get_uri("rest_api_settings"), app_lang("rest_api_settings")),
        anchor(get_uri("api_keys"), app_lang("api_keys")),
        anchor(get_uri("api_logs"), app_lang("api_logs")),
        anchor(get_uri("api_docs"), app_lang("api_documentation")),
    );
    
    return $action_links_array;
});
 */

// Installation: install dependencies
register_installation_hook("Rest_api", function ($item_purchase_code) {
    include PLUGINPATH . "Rest_api/install/do_install.php";
});

// Uninstallation: remove data from database (optional - we preserve data)
register_uninstallation_hook("Rest_api", function () {
    // Note: We don't drop tables on uninstall to preserve data
    // Admins can manually drop tables if needed:
    // DROP TABLE IF EXISTS `api_keys`;
    // DROP TABLE IF EXISTS `api_logs`;
    // DROP TABLE IF EXISTS `api_rate_limits`;
    // DROP TABLE IF EXISTS `api_settings`;
});
