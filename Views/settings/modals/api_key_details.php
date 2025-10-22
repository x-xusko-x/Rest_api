<div class="modal-body clearfix">
    <div class="container-fluid">

        <div class="row">
            <div class="card">
                <!-- General Info Section -->
                <div class="card-header">
                    <b><?php echo app_lang('general_info'); ?></b>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td width="25%"><strong><?php echo app_lang('name'); ?></strong></td>
                                <td><?php echo $api_key_info->name; ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('description'); ?></strong></td>
                                <td><?php echo $api_key_info->description ? $api_key_info->description : '-'; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('status'); ?></strong></td>
                                <td>
                                    <?php
                                    $status_class = $api_key_info->status === 'active' ? 'bg-success' : ($api_key_info->status === 'revoked' ? 'bg-danger' : 'bg-secondary');
                                    ?>
                                    <span
                                        class="badge <?php echo $status_class; ?>"><?php echo app_lang($api_key_info->status); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('type'); ?></strong></td>
                                <td>
                                    <?php
                                    $assignment_class = isset($api_key_info->assignment_type) && $api_key_info->assignment_type === 'external' ? 'bg-info' : 'bg-primary';
                                    $assignment_text = isset($api_key_info->assignment_type) && $api_key_info->assignment_type === 'external' ? app_lang('external') : app_lang('internal');
                                    ?>
                                    <span
                                        class="badge <?php echo $assignment_class; ?>"><?php echo $assignment_text; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('api_key'); ?></strong></td>
                                <td>
                                    <div class="input-group">
                                        <input type="text" class="form-control api-key-display"
                                            value="<?php echo $api_key_info->key; ?>" readonly>
                                        <button class="btn btn-default copy-api-key" type="button"
                                            data-key="<?php echo $api_key_info->key; ?>">
                                            <i data-feather="copy" class="icon-16"></i>
                                            <?php echo app_lang('copy'); ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('api_secret'); ?></strong></td>
                                <td>
                                    <div class="alert alert-warning mb-0">
                                        <i data-feather="alert-triangle" class="icon-16"></i>
                                        <?php echo app_lang('secret_is_hashed'); ?>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <b><?php echo app_lang('usage_statistics'); ?></b>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td width="25%"><strong><?php echo app_lang('total_calls'); ?></strong></td>
                                <td><?php echo number_format($api_key_info->total_calls ?? 0); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <b><?php echo app_lang('rate_limits'); ?></b>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td width="25%"><strong><?php echo app_lang('rate_limit_per_minute'); ?></strong>
                                </td>
                                <td><?php echo $api_key_info->rate_limit_per_minute; ?> requests</td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('rate_limit_per_hour'); ?></strong></td>
                                <td><?php echo $api_key_info->rate_limit_per_hour; ?> requests</td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('rate_limit_per_day'); ?></strong></td>
                                <td><?php echo isset($api_key_info->rate_limit_per_day) ? $api_key_info->rate_limit_per_day : 10000; ?>
                                    requests</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <b><?php echo app_lang('assigned_permission_group'); ?></b>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td width="25%"><strong><?php echo app_lang('permission_group'); ?></strong></td>
                                <td>
                                    <?php 
                                    if (isset($permission_group) && $permission_group) {
                                        echo $permission_group->name;
                                        if ($permission_group->is_system == 1) {
                                            echo ' <span class="badge bg-warning ms-2">' . app_lang('system_group_protected') . '</span>';
                                        }
                                    } else {
                                        echo '<span class="badge bg-info">' . app_lang('no_permission_group') . ' - ' . app_lang('full_access') . '</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php if (isset($permission_group) && $permission_group && $permission_group->description) { ?>
                            <tr>
                                <td><strong><?php echo app_lang('description'); ?></strong></td>
                                <td><?php echo $permission_group->description; ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <b><?php echo app_lang('security_settings'); ?></b>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td width="25%"><strong><?php echo app_lang('require_https'); ?></strong></td>
                                <td>
                                    <?php 
                                    if ($api_key_info->require_https === null) {
                                        echo '<span class="badge bg-secondary">' . app_lang('use_global_setting') . '</span>';
                                    } else if ($api_key_info->require_https == 1) {
                                        echo '<span class="badge bg-success">' . app_lang('force_enabled') . '</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">' . app_lang('force_disabled') . '</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('cors_enabled'); ?></strong></td>
                                <td>
                                    <?php 
                                    if ($api_key_info->cors_enabled === null) {
                                        echo '<span class="badge bg-secondary">' . app_lang('use_global_setting') . '</span>';
                                    } else if ($api_key_info->cors_enabled == 1) {
                                        echo '<span class="badge bg-success">' . app_lang('force_enabled') . '</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">' . app_lang('force_disabled') . '</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php if ($api_key_info->cors_allowed_origins): ?>
                            <tr>
                                <td><strong><?php echo app_lang('cors_allowed_origins'); ?></strong></td>
                                <td><pre><?php echo htmlspecialchars($api_key_info->cors_allowed_origins); ?></pre></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <b><?php echo app_lang('timestamps'); ?></b>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td width="25%"><strong><?php echo app_lang('created_at'); ?></strong></td>
                                <td><?php echo format_to_datetime($api_key_info->created_at); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('last_used'); ?></strong></td>
                                <td><?php echo $api_key_info->last_used_at ? format_to_datetime($api_key_info->last_used_at) : app_lang('never_used'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('expires'); ?></strong></td>
                                <td><?php echo $api_key_info->expires_at ? format_to_datetime($api_key_info->expires_at) : app_lang('never'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($api_key_info->ip_whitelist): ?>

                <div class="card">
                    <div class="card-header">
                        <b><?php echo app_lang('ip_whitelist'); ?></b>
                    </div>
                    <div class="card-body">
                        <pre><?php echo htmlspecialchars($api_key_info->ip_whitelist); ?><br></pre>
                    </div>
                </div>

            <?php endif; ?>
        </div>


    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span>
        <?php echo app_lang('close'); ?></button>
    <?php echo modal_anchor(get_uri("api_keys/modal_form"), "<i data-feather='edit' class='icon-16'></i> " . app_lang('edit'), array("class" => "btn btn-primary", "title" => app_lang('edit_api_key'), "data-post-id" => $api_key_info->id)); ?>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        // Copy button functionality
        $('.copy-api-key').on('click', function () {
            var key = $(this).data('key');
            navigator.clipboard.writeText(key);
            appAlert.success("<?php echo app_lang('api_key_copied'); ?>", { duration: 2000 });
        });

        feather.replace();
    });
</script>