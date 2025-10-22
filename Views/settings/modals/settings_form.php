<?php echo form_open(get_uri("rest_api_settings/save_settings"), array("id" => "rest_api-settings-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <div class="form-group">
            <label for="api_enabled" class="col-md-3"><?php echo app_lang('api_enabled'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_checkbox("api_enabled", "1", $api_enabled == "1" ? true : false, "id='api_enabled' class='form-check-input'");
                ?>
            </div>
        </div>

        <div class="form-group">
            <label for="default_rate_limit_per_minute" class="col-md-3"><?php echo app_lang('default_rate_limit_per_minute'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "id" => "default_rate_limit_per_minute",
                    "name" => "default_rate_limit_per_minute",
                    "value" => $default_rate_limit_per_minute ? $default_rate_limit_per_minute : 60,
                    "class" => "form-control",
                    "placeholder" => app_lang('default_rate_limit_per_minute'),
                    "type" => "number",
                    "data-rule-required" => true,
                    "data-msg-required" => app_lang("field_required"),
                ));
                ?>
            </div>
        </div>

        <div class="form-group">
            <label for="default_rate_limit_per_hour" class="col-md-3"><?php echo app_lang('default_rate_limit_per_hour'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "id" => "default_rate_limit_per_hour",
                    "name" => "default_rate_limit_per_hour",
                    "value" => $default_rate_limit_per_hour ? $default_rate_limit_per_hour : 1000,
                    "class" => "form-control",
                    "placeholder" => app_lang('default_rate_limit_per_hour'),
                    "type" => "number",
                    "data-rule-required" => true,
                    "data-msg-required" => app_lang("field_required"),
                ));
                ?>
            </div>
        </div>

        <div class="form-group">
            <label for="log_retention_days" class="col-md-3"><?php echo app_lang('log_retention_days'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_input(array(
                    "id" => "log_retention_days",
                    "name" => "log_retention_days",
                    "value" => $log_retention_days ? $log_retention_days : 90,
                    "class" => "form-control",
                    "placeholder" => app_lang('log_retention_days'),
                    "type" => "number",
                    "data-rule-required" => true,
                    "data-msg-required" => app_lang("field_required"),
                ));
                ?>
            </div>
        </div>

        <div class="form-group">
            <label for="require_https" class="col-md-3"><?php echo app_lang('require_https'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_checkbox("require_https", "1", $require_https == "1" ? true : false, "id='require_https' class='form-check-input'");
                ?>
            </div>
        </div>

        <div class="form-group">
            <label for="cors_enabled" class="col-md-3"><?php echo app_lang('cors_enabled'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_checkbox("cors_enabled", "1", $cors_enabled == "1" ? true : false, "id='cors_enabled' class='form-check-input'");
                ?>
            </div>
        </div>

        <div class="form-group">
            <label for="cors_allowed_origins" class="col-md-3"><?php echo app_lang('cors_allowed_origins'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_textarea(array(
                    "id" => "cors_allowed_origins",
                    "name" => "cors_allowed_origins",
                    "value" => $cors_allowed_origins,
                    "class" => "form-control",
                    "placeholder" => app_lang('cors_allowed_origins_placeholder'),
                    "rows" => 3
                ));
                ?>
                <small class="text-muted"><?php echo app_lang('one_per_line'); ?></small>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function() {
        $("#rest_api-settings-form").appForm({
            onSuccess: function(result) {
                if (result.success) {
                    appAlert.success(result.message, {duration: 10000});
                    location.reload();
                } else {
                    appAlert.error(result.message);
                }
            }
        });

        feather.replace();
    });
</script>

