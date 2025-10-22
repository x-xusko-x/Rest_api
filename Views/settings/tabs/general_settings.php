<div class="card no-border clearfix mb0">
    <?php echo form_open(get_uri("rest_api_settings/save_general_settings"), array("id" => "rest-api-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
    
    <div class="card-body">
        <div class="form-group">
            <div class="row">
                <label for="api_enabled" class="col-md-3"><?php echo app_lang('api_enabled'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_checkbox("api_enabled", "1", get_array_value($settings, "api_enabled") == "1" ? true : false, "id='api_enabled' class='form-check-input'");
                    ?>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <div class="row">
                <label for="default_rate_limit_per_minute" class="col-md-3"><?php echo app_lang('default_rate_limit_per_minute'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "default_rate_limit_per_minute",
                        "name" => "default_rate_limit_per_minute",
                        "value" => get_array_value($settings, "default_rate_limit_per_minute", "60"),
                        "class" => "form-control",
                        "placeholder" => app_lang('default_rate_limit_per_minute'),
                        "type" => "number",
                        "min" => 0,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <div class="row">
                <label for="default_rate_limit_per_hour" class="col-md-3"><?php echo app_lang('default_rate_limit_per_hour'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "default_rate_limit_per_hour",
                        "name" => "default_rate_limit_per_hour",
                        "value" => get_array_value($settings, "default_rate_limit_per_hour", "1000"),
                        "class" => "form-control",
                        "placeholder" => app_lang('default_rate_limit_per_hour'),
                        "type" => "number",
                        "min" => 0,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <div class="row">
                <label for="default_rate_limit_per_day" class="col-md-3"><?php echo app_lang('default_rate_limit_per_day'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "default_rate_limit_per_day",
                        "name" => "default_rate_limit_per_day",
                        "value" => get_array_value($settings, "default_rate_limit_per_day", "10000"),
                        "class" => "form-control",
                        "placeholder" => app_lang('default_rate_limit_per_day'),
                        "type" => "number",
                        "min" => 0,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <div class="row">
                <label for="log_retention_days" class="col-md-3"><?php echo app_lang('log_retention_days'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "log_retention_days",
                        "name" => "log_retention_days",
                        "value" => get_array_value($settings, "log_retention_days", "90"),
                        "class" => "form-control",
                        "placeholder" => app_lang('log_retention_days'),
                        "type" => "number",
                        "min" => 0,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <div class="row">
                <label for="require_https" class="col-md-3"><?php echo app_lang('require_https'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_checkbox("require_https", "1", get_array_value($settings, "require_https") == "1" ? true : false, "id='require_https' class='form-check-input'");
                    ?>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <div class="row">
                <label for="cors_enabled" class="col-md-3"><?php echo app_lang('cors_enabled'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_checkbox("cors_enabled", "1", get_array_value($settings, "cors_enabled") == "1" ? true : false, "id='cors_enabled' class='form-check-input'");
                    ?>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <div class="row">
                <label for="cors_allowed_origins" class="col-md-3"><?php echo app_lang('cors_allowed_origins'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "cors_allowed_origins",
                        "name" => "cors_allowed_origins",
                        "value" => get_array_value($settings, "cors_allowed_origins"),
                        "class" => "form-control",
                        "placeholder" => app_lang('cors_allowed_origins_placeholder'),
                        "data-rich-text-editor" => false
                    ));
                    ?>
                    <span class="text-muted"><small><?php echo app_lang('one_per_line'); ?></small></span>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <div class="row">
                <label for="default_ip_whitelist" class="col-md-3"><?php echo app_lang('default_ip_whitelist'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "default_ip_whitelist",
                        "name" => "default_ip_whitelist",
                        "value" => get_array_value($settings, "default_ip_whitelist"),
                        "class" => "form-control",
                        "placeholder" => app_lang('default_ip_whitelist_placeholder'),
                        "data-rich-text-editor" => false,
                        "rows" => 3
                    ));
                    ?>
                    <span class="text-muted"><small><?php echo app_lang('default_ip_whitelist_help'); ?></small></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card-footer">
        <button type="submit" class="btn btn-primary"><span data-feather='check-circle' class="icon-16"></span> <?php echo app_lang('save'); ?></button>
    </div>
    
    <?php echo form_close(); ?>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#rest-api-settings-form").appForm({
            isModal: false,
            beforeAjaxSubmit: function(data, $form, options) {
                // Explicitly prevent modal behavior
                options.dataType = 'json';
                return true;
            },
            onSuccess: function (result) {
                if (result.success) {
                    appAlert.success(result.message, {duration: 3000});
                } else {
                    appAlert.error(result.message);
                }
                // Prevent any modal from showing
                $('.modal').modal('hide');
                return false;
            }
        });
        
        // Initialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>

