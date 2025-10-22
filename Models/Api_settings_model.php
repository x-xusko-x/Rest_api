<?php

namespace Rest_api\Models;

use App\Models\Crud_model;

class Api_settings_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'api_settings';
        parent::__construct($this->table);
    }

    function get_setting($setting_name) {
        $result = $this->db_builder->getWhere(array('setting_name' => $setting_name), 1);
        if (count($result->getResult()) == 1) {
            return $result->getRow()->setting_value;
        }
    }

    function save_setting($setting_name, $setting_value, $type = "app") {
        $fields = array(
            'setting_name' => $setting_name,
            'setting_value' => $setting_value
        );

        $exists = $this->get_setting($setting_name);
        if ($exists === NULL) {
            $fields["type"] = $type;
            return $this->db_builder->insert($fields);
        } else {
            $this->db_builder->where('setting_name', $setting_name);
            $this->db_builder->update($fields);
        }
    }

    function get_all_settings() {
        $this->db_builder->select('setting_name, setting_value');
        $this->db_builder->where('deleted', 0);
        return $this->db_builder->get();
    }
}

