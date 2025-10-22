<div class="card no-border clearfix mb0">
    <div class="card-header">
        <div class="row mx-auto">
            <div class="col-md-9">
                <p class="text-muted"><?php echo app_lang('api_keys_tab_description'); ?></p>
            </div>
            <div class="col-md-3 text-end">
                <?php echo modal_anchor(get_uri("api_keys/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('generate_new_key'), array("class" => "btn btn-primary", "title" => app_lang('generate_new_key'))); ?>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="api-keys-table" class="display" cellspacing="0" width="100%">
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#api-keys-table").appTable({
            source: '<?php echo_uri("api_keys/list_data") ?>',
            order: [[7, "desc"]],
            columns: [
                { title: "<?php echo app_lang("name"); ?>", "class": "w12p" },
                { title: "<?php echo app_lang("status"); ?>", "class": "w8p" },
                { title: "<?php echo app_lang("type"); ?>", "class": "w8p" },
                { title: "<?php echo app_lang("total_calls"); ?>", "class": "w10p" },
                { title: "<?php echo app_lang("limits"); ?>", "class": "w12p" },
                { title: "<?php echo app_lang("last_used"); ?>", "class": "w10p" },
                { title: "<?php echo app_lang("expires"); ?>", "class": "w10p" },
                { title: "<?php echo app_lang("created"); ?>", "class": "w10p" },
                { title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100" }
            ],
            onInitComplete: function () {
                // Initialize tooltips for total calls
                $('[data-bs-toggle="tooltip"]').tooltip();
            },
            onRelaodCallback: function () {
                // Re-initialize tooltips after table reload
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });

        // Initialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>