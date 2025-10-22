<?php echo form_open(get_uri("api_keys/save"), array("id" => "api-key-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info ? $model_info->id : ''; ?>" />

        <div class="form-group">
            <div class="row">
                <label for="name" class="col-md-3"><?php echo app_lang('name'); ?><span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "name",
                        "name" => "name",
                        "value" => $model_info ? $model_info->name : '',
                        "class" => "form-control",
                        "placeholder" => app_lang('name'),
                        "autofocus" => true,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="description" class="col-md-3"><?php echo app_lang('api_key_description'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "description",
                        "name" => "description",
                        "value" => $model_info ? $model_info->description : '',
                        "class" => "form-control",
                        "placeholder" => app_lang('api_key_description'),
                        "rows" => 3
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="status" class="col-md-3"><?php echo app_lang('api_key_status'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_dropdown("status", array(
                        "active" => app_lang("active"),
                        "inactive" => app_lang("inactive"),
                        "revoked" => app_lang("revoked")
                    ), $model_info ? $model_info->status : 'active', "class='select2 w-100' id='status'");
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('type'); ?></label>
                <div class="col-md-9">
                    <div>
                        <label class="me-3">
                            <input type="radio" name="assignment_type" value="internal" <?php echo (!$model_info || !$model_info->assignment_type || $model_info->assignment_type === 'internal') ? 'checked' : ''; ?> /> 
                            <?php echo app_lang('internal'); ?> (<?php echo app_lang('our_team'); ?>)
                        </label>
                        <label>
                            <input type="radio" name="assignment_type" value="external" <?php echo ($model_info && $model_info->assignment_type === 'external') ? 'checked' : ''; ?> /> 
                            <?php echo app_lang('external'); ?> (<?php echo app_lang('clients'); ?>)
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="permission_group_id" class="col-md-3"><?php echo app_lang('assigned_permission_group'); ?></label>
                <div class="col-md-9">
                    <?php
                    $selected_group = $model_info ? $model_info->permission_group_id : '';
                    echo form_dropdown("permission_group_id", $permission_groups_dropdown, $selected_group, "class='select2 w-100' id='permission_group_id'");
                    ?>
                    <br>
                    <small class="text-muted"><?php echo app_lang('permission_group_help_text'); ?></small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="rate_limit_per_minute" class="col-md-3"><?php echo app_lang('rate_limit_per_minute'); ?><span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <?php
                    $default_per_minute = $model_info && $model_info->rate_limit_per_minute ? $model_info->rate_limit_per_minute : $default_rate_limit_per_minute;
                    echo form_input(array(
                        "id" => "rate_limit_per_minute",
                        "name" => "rate_limit_per_minute",
                        "value" => $default_per_minute,
                        "class" => "form-control",
                        "placeholder" => $default_rate_limit_per_minute,
                        "type" => "number",
                        "min" => 1,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="rate_limit_per_hour" class="col-md-3"><?php echo app_lang('rate_limit_per_hour'); ?><span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <?php
                    $default_per_hour = $model_info && $model_info->rate_limit_per_hour ? $model_info->rate_limit_per_hour : $default_rate_limit_per_hour;
                    echo form_input(array(
                        "id" => "rate_limit_per_hour",
                        "name" => "rate_limit_per_hour",
                        "value" => $default_per_hour,
                        "class" => "form-control",
                        "placeholder" => $default_rate_limit_per_hour,
                        "type" => "number",
                        "min" => 1,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="rate_limit_per_day" class="col-md-3"><?php echo app_lang('rate_limit_per_day'); ?><span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <?php
                    $default_per_day = $model_info && $model_info->rate_limit_per_day ? $model_info->rate_limit_per_day : $default_rate_limit_per_day;
                    echo form_input(array(
                        "id" => "rate_limit_per_day",
                        "name" => "rate_limit_per_day",
                        "value" => $default_per_day,
                        "class" => "form-control",
                        "placeholder" => $default_rate_limit_per_day,
                        "type" => "number",
                        "min" => 1,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="ip_whitelist" class="col-md-3"><?php echo app_lang('ip_whitelist'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "ip_whitelist",
                        "name" => "ip_whitelist",
                        "value" => $model_info ? $model_info->ip_whitelist : '',
                        "class" => "form-control",
                        "placeholder" => app_lang('ip_whitelist_placeholder'),
                        "rows" => 3
                    ));
                    ?>
                    <small class="text-muted"><?php echo app_lang('one_per_line'); ?></small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="require_https" class="col-md-3"><?php echo app_lang('per_key_https'); ?></label>
                <div class="col-md-9">
                    <?php
                    $https_value = '';
                    if ($model_info && property_exists($model_info, 'require_https') && $model_info->require_https !== null) {
                        $https_value = $model_info->require_https ? '1' : '0';
                    }
                    echo form_dropdown("require_https", array(
                        "" => app_lang("use_global_setting") . ($global_require_https ? ' (' . app_lang('enabled') . ')' : ' (' . app_lang('disabled') . ')'),
                        "1" => app_lang("force_enabled"),
                        "0" => app_lang("force_disabled")
                    ), $https_value, "class='select2 w-100' id='require_https'");
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="cors_enabled" class="col-md-3"><?php echo app_lang('per_key_cors'); ?></label>
                <div class="col-md-9">
                    <?php
                    $cors_value = '';
                    if ($model_info && property_exists($model_info, 'cors_enabled') && $model_info->cors_enabled !== null) {
                        $cors_value = $model_info->cors_enabled ? '1' : '0';
                    }
                    echo form_dropdown("cors_enabled", array(
                        "" => app_lang("use_global_setting") . ($global_cors_enabled ? ' (' . app_lang('enabled') . ')' : ' (' . app_lang('disabled') . ')'),
                        "1" => app_lang("force_enabled"),
                        "0" => app_lang("force_disabled")
                    ), $cors_value, "class='select2 w-100' id='cors_enabled'");
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="cors_allowed_origins" class="col-md-3"><?php echo app_lang('per_key_cors_origins'); ?></label>
                <div class="col-md-9">
                    <?php
                    $cors_origins = '';
                    if ($model_info && property_exists($model_info, 'cors_allowed_origins') && $model_info->cors_allowed_origins !== null) {
                        $cors_origins = $model_info->cors_allowed_origins;
                    }
                    echo form_textarea(array(
                        "id" => "cors_allowed_origins",
                        "name" => "cors_allowed_origins",
                        "value" => $cors_origins,
                        "class" => "form-control",
                        "placeholder" => app_lang('cors_allowed_origins_placeholder'),
                        "rows" => 3
                    ));
                    ?>
                    <small class="text-muted"><?php echo app_lang('leave_empty_for_global'); ?></small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="expires_at" class="col-md-3"><?php echo app_lang('expiration_date'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "expires_at",
                        "name" => "expires_at",
                        "value" => $model_info ? $model_info->expires_at : '',
                        "class" => "form-control",
                        "placeholder" => app_lang('expiration_date'),
                        "autocomplete" => "off"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <?php if ($model_info && $model_info->id && $model_info->key) { ?>
        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('api_key'); ?></label>
                <div class="col-md-9">
                    <div class="input-group">
                        <input type="text" class="form-control" id="api-key-value" value="<?php echo $model_info->key; ?>" readonly>
                        <button class="btn btn-default" type="button" id="copy-api-key">
                            <i data-feather="copy" class="icon-16"></i> <?php echo app_lang('copy'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>

        <!-- Secret will be shown here after creation -->
        <div id="new-secret-container" style="display: none;">
            <div class="alert alert-warning">
                <h5><i data-feather="alert-triangle" class="icon-16"></i> <?php echo app_lang('important'); ?>!</h5>
                
                <div class="form-group">
                    <label><strong><?php echo app_lang('api_key'); ?>:</strong></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="new-key-display" readonly>
                        <button class="btn btn-default" type="button" id="copy-new-key">
                            <i data-feather="copy" class="icon-16"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group mt-2">
                    <label><strong><?php echo app_lang('api_secret'); ?>:</strong></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="new-secret-display" readonly>
                        <button class="btn btn-default" type="button" id="copy-new-secret">
                            <i data-feather="copy" class="icon-16"></i>
                        </button>
                    </div>
                </div>

                <p class="text-danger mt-2 mb-0"><strong><?php echo app_lang('store_secret_securely'); ?></strong></p>
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
        $("#api-key-form").appForm({
            isModal: false,
            onSuccess: function(result) {
                if (result.success) {
                    $("#api-keys-table").appTable({newData: result.data, dataId: result.id});
                    
                    // Show secret for NEW keys IN THE MODAL
                    if (result.api_key && result.api_secret) {
                        // Hide the form and submit button
                        $("#api-key-form .container-fluid > div").not("#new-secret-container").hide();
                        $("#api-key-form button[type='submit']").hide();
                        
                        // Show the secret container
                        $("#new-key-display").val(result.api_key);
                        $("#new-secret-display").val(result.api_secret);
                        $("#new-secret-container").show();
                        
                        // Change close button text
                        $(".modal-footer button[data-bs-dismiss='modal']").html('<span data-feather="check" class="icon-16"></span> <?php echo app_lang('done'); ?>');
                        
                        // Re-initialize feather icons
                        feather.replace();
                        
                        // Copy buttons
                        $("#copy-new-key").on('click', function() {
                            navigator.clipboard.writeText($("#new-key-display").val());
                            appAlert.success("<?php echo app_lang('api_key_copied'); ?>", {duration: 2000});
                        });
                        $("#copy-new-secret").on('click', function() {
                            navigator.clipboard.writeText($("#new-secret-display").val());
                            appAlert.success("<?php echo app_lang('copied'); ?>", {duration: 2000});
                        });
                    } else {
                        appAlert.success(result.message, {duration: 3000});
                        // Close modal for edit
                        setTimeout(function() {
                            $(".modal").modal('hide');
                        }, 1000);
                    }
                } else {
                    appAlert.error(result.message);
                }
            }
        });

        // Status dropdown
        $("#status").select2({
            minimumResultsForSearch: -1
        });

        // Permission group dropdown
        $("#permission_group_id").select2();

        // Security settings dropdowns
        $("#require_https").select2({
            minimumResultsForSearch: -1
        });
        $("#cors_enabled").select2({
            minimumResultsForSearch: -1
        });

        // Date picker
        setDatePicker("#expires_at");

        // Copy to clipboard for existing key
        $("#copy-api-key").click(function() {
            var copyText = document.getElementById("api-key-value");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);
            appAlert.success("<?php echo app_lang('api_key_copied'); ?>", {duration: 3000});
        });

        feather.replace();
    });
</script>
