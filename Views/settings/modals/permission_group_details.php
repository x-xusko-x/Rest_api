<div class="modal-body clearfix">
    <div class="container-fluid">
        <div class="row">
            <div class="card">
                <div class="card-header">
                    <b><?php echo app_lang('general_info'); ?></b>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td width="25%"><strong><?php echo app_lang('permission_group_name'); ?></strong></td>
                                <td>
                                    <?php echo $group_info->name; ?>
                                    <?php if ($group_info->is_system == 1) { ?>
                                        <span class="badge bg-warning ms-2"><?php echo app_lang('system_group_protected'); ?></span>
                                    <?php } ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('description'); ?></strong></td>
                                <td><?php echo $group_info->description ? $group_info->description : '-'; ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('created_at'); ?></strong></td>
                                <td><?php echo format_to_datetime($group_info->created_at); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <b><?php echo app_lang('endpoint_permissions'); ?></b>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><?php echo app_lang('endpoint'); ?></th>
                                    <th class="text-center"><?php echo app_lang('create_permission'); ?></th>
                                    <th class="text-center"><?php echo app_lang('read_permission'); ?></th>
                                    <th class="text-center"><?php echo app_lang('update_permission'); ?></th>
                                    <th class="text-center"><?php echo app_lang('delete_permission'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($available_endpoints as $endpoint_key => $endpoint_data) {
                                    $endpoint_name = $endpoint_data['name'];
                                    $perms = isset($permissions[$endpoint_key]) ? $permissions[$endpoint_key] : array();
                                    
                                    $create_enabled = isset($perms['create']) && $perms['create'] === true;
                                    $read_enabled = isset($perms['read']) && $perms['read'] === true;
                                    $update_enabled = isset($perms['update']) && $perms['update'] === true;
                                    $delete_enabled = isset($perms['delete']) && $perms['delete'] === true;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo $endpoint_name; ?></strong></td>
                                        <td class="text-center">
                                            <?php if ($create_enabled) { ?>
                                                <span class="badge bg-success"><i data-feather="check" class="icon-16"></i></span>
                                            <?php } else { ?>
                                                <span class="badge bg-secondary"><i data-feather="x" class="icon-16"></i></span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($read_enabled) { ?>
                                                <span class="badge bg-success"><i data-feather="check" class="icon-16"></i></span>
                                            <?php } else { ?>
                                                <span class="badge bg-secondary"><i data-feather="x" class="icon-16"></i></span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($update_enabled) { ?>
                                                <span class="badge bg-success"><i data-feather="check" class="icon-16"></i></span>
                                            <?php } else { ?>
                                                <span class="badge bg-secondary"><i data-feather="x" class="icon-16"></i></span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($delete_enabled) { ?>
                                                <span class="badge bg-success"><i data-feather="check" class="icon-16"></i></span>
                                            <?php } else { ?>
                                                <span class="badge bg-secondary"><i data-feather="x" class="icon-16"></i></span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <?php echo modal_anchor(get_uri("api_permission_groups/modal_form"), "<i data-feather='edit' class='icon-16'></i> " . app_lang('edit'), array("class" => "btn btn-primary", "title" => app_lang('edit_permission_group'), "data-post-id" => $group_info->id, "data-modal-lg" => "1")); ?>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        feather.replace();
    });
</script>

