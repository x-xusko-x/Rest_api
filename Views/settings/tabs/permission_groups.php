<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-9">
                <p class="text-muted"><?php echo app_lang('manage_api_permission_groups'); ?></p>
            </div>
            <div class="col-md-3 text-end">
                <?php echo modal_anchor(get_uri("api_permission_groups/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_permission_group'), array("class" => "btn btn-primary", "title" => app_lang('add_permission_group'), "data-modal-lg" => "1")); ?>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="permission-groups-table" class="display" cellspacing="0" width="100%">            
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#permission-groups-table").appTable({
            source: '<?php echo_uri("api_permission_groups/list_data") ?>',
            columns: [
                {title: '<?php echo app_lang("permission_group_name") ?>'},
                {title: '<?php echo app_lang("description") ?>'},
                {title: '<?php echo app_lang("endpoints_enabled") ?>', class: "text-center w150"},
                {title: '<?php echo app_lang("created") ?>', class: "text-center w150"},
                {title: '<i data-feather="menu" class="icon-16"></i>', class: "text-center option w100"}
            ]
        });
    });
</script>

