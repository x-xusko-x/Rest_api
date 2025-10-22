<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "rest_api";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="no-border clearfix">

                <ul id="rest-api-tabs" data-bs-toggle="ajax-tab" class="nav nav-tabs bg-white title" role="tablist">
                    <li><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("rest_api_settings/dashboard/"); ?>" data-bs-target="#rest-api-dashboard-tab"><?php echo app_lang('dashboard'); ?></a></li>
                    <li><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("rest_api_settings/api_keys_tab/"); ?>" data-bs-target="#rest-api-keys-tab"><?php echo app_lang('api_keys'); ?></a></li>
                    <li><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("rest_api_settings/logs_tab/"); ?>" data-bs-target="#rest-api-logs-tab"><?php echo app_lang('api_logs'); ?></a></li>
                    <li><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("rest_api_settings/general_settings/"); ?>" data-bs-target="#rest-api-general-settings-tab"><?php echo app_lang('general_settings'); ?></a></li>
                    <li><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("rest_api_settings/permission_groups_tab/"); ?>" data-bs-target="#rest-api-permission-groups-tab"><?php echo app_lang('permission_groups'); ?></a></li>
                    <li><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("rest_api_settings/documentation_tab/"); ?>" data-bs-target="#rest-api-documentation-tab"><?php echo app_lang('api_documentation'); ?></a></li>
                </ul>

                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane fade" id="rest-api-dashboard-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="rest-api-keys-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="rest-api-logs-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="rest-api-general-settings-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="rest-api-permission-groups-tab"></div>
                    <div role="tabpanel" class="tab-pane fade" id="rest-api-documentation-tab"></div>
                </div>

            </div>
        </div>
    </div>
</div>
