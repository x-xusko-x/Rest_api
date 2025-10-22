<?php

namespace Rest_api\Config;

use CodeIgniter\Config\BaseConfig;

class Rest_api extends BaseConfig {

    public $app_settings_array = array(
        "api_version" => "1.0.0",
        "api_enabled" => true,
        "default_rate_limit_per_minute" => 60,
        "default_rate_limit_per_hour" => 1000,
        "log_retention_days" => 90,
        "require_https" => false,
        "cors_enabled" => true,
        "cors_allowed_origins" => "*"
    );

    // Don't load from database in constructor - causes issues during installation
    // Settings will be loaded when actually needed

}
