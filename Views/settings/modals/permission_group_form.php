<?php echo form_open(get_uri("api_permission_groups/save"), array("id" => "permission-group-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info ? $model_info->id : ''; ?>" />

        <div class="form-group">
            <div class="row">
                <label for="name" class="col-md-3"><?php echo app_lang('permission_group_name'); ?><span
                        class="text-danger">*</span></label>
                <div class="col-md-9">
                    <?php
                    $is_system = $model_info && $model_info->is_system == 1;
                    $name_input_attributes = array(
                        "id" => "name",
                        "name" => "name",
                        "value" => $model_info ? $model_info->name : '',
                        "class" => "form-control",
                        "placeholder" => app_lang('permission_group_name'),
                        "autofocus" => true,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required")
                    );

                    // Only add readonly if it's a system group
                    if ($is_system) {
                        $name_input_attributes["readonly"] = true;
                    }

                    echo form_input($name_input_attributes);
                    ?>
                    <?php if ($is_system) { ?>
                        <small class="text-muted"><?php echo app_lang('system_group_protected'); ?> -
                            <?php echo app_lang('cannot_rename'); ?></small>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="description"
                    class="col-md-3"><?php echo app_lang('permission_group_description'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "description",
                        "name" => "description",
                        "value" => $model_info ? $model_info->description : '',
                        "class" => "form-control",
                        "placeholder" => app_lang('permission_group_description'),
                        "rows" => 3
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('endpoint_permissions'); ?></label>
                <div class="col-md-9">
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm p-2 btn-success" id="enable-all-btn">
                            <i data-feather="check-square" class="icon-16"></i>
                            <?php echo app_lang('enable_all_endpoints'); ?>
                        </button>
                        <button type="button" class="btn btn-sm p-2 btn-secondary" id="disable-all-btn">
                            <i data-feather="square" class="icon-16"></i>
                            <?php echo app_lang('disable_all_endpoints'); ?>
                        </button>
                    </div>

                    <div class="accordion" id="endpoints-accordion">
                        <?php
                        $index = 0;
                        foreach ($available_endpoints as $endpoint_key => $endpoint_data) {
                            $endpoint_name = $endpoint_data['name'];
                            $accordion_id = "accordion-" . $endpoint_key;
                            $collapse_id = "collapse-" . $endpoint_key;

                            // Get existing permissions for this endpoint
                            $current_perms = isset($existing_permissions[$endpoint_key]) ? $existing_permissions[$endpoint_key] : array();
                            ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header m-0" id="<?php echo $accordion_id; ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#<?php echo $collapse_id; ?>" aria-expanded="false"
                                        aria-controls="<?php echo $collapse_id; ?>">
                                        <strong><?php echo $endpoint_name; ?></strong>
                                    </button>
                                </h2>
                                <div id="<?php echo $collapse_id; ?>" class="accordion-collapse collapse"
                                    aria-labelledby="<?php echo $accordion_id; ?>" data-bs-parent="#endpoints-accordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <?php
                                            $operations = array('create', 'read', 'update', 'delete');
                                            foreach ($operations as $operation) {
                                                $field_name = $endpoint_key . '_' . $operation;
                                                $is_checked = isset($current_perms[$operation]) && $current_perms[$operation] === true;
                                                ?>
                                                <div class="col-md-6 mb-2">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input permission-toggle" type="checkbox"
                                                            name="<?php echo $field_name; ?>" id="<?php echo $field_name; ?>"
                                                            value="1" <?php echo $is_checked ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="<?php echo $field_name; ?>">
                                                            <?php echo ucfirst($operation); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $index++;
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span>
        <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span>
        <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#permission-group-form").appForm({
            onSuccess: function (result) {
                if (result.success) {
                    $("#permission-groups-table").appTable({ newData: result.data, dataId: result.id });
                    appAlert.success(result.message, { duration: 3000 });
                } else {
                    appAlert.error(result.message);
                }
            }
        });

        // Enable All button
        $("#enable-all-btn").click(function () {
            $(".permission-toggle").prop('checked', true);
        });

        // Disable All button
        $("#disable-all-btn").click(function () {
            $(".permission-toggle").prop('checked', false);
        });

        feather.replace();
    });
</script>