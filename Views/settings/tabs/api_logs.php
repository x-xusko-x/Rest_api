<div class="card no-border clearfix mb0">
    <div class="card-body">
        <div class="table-responsive">
            <table id="api-logs-table" class="display" cellspacing="0" width="100%">            
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#api-logs-table").appTable({
            source: '<?php echo_uri("api_logs/list_data") ?>',
            order: [[0, "desc"]],
            filterDropdown: [
                {name: "api_key_id", class: "w200", options: <?php echo $api_keys_dropdown; ?>},
                {name: "method", class: "w100", options: [
                    {id: "", text: "<?php echo app_lang('all_methods'); ?>"},
                    {id: "GET", text: "GET"},
                    {id: "POST", text: "POST"},
                    {id: "PUT", text: "PUT"},
                    {id: "PATCH", text: "PATCH"},
                    {id: "DELETE", text: "DELETE"}
                ]},
                {name: "response_code", class: "w100", options: [
                    {id: "", text: "<?php echo app_lang('all_status'); ?>"},
                    {id: "200", text: "200 OK"},
                    {id: "201", text: "201 Created"},
                    {id: "400", text: "400 Bad Request"},
                    {id: "401", text: "401 Unauthorized"},
                    {id: "403", text: "403 Forbidden"},
                    {id: "404", text: "404 Not Found"},
                    {id: "429", text: "429 Too Many Requests"},
                    {id: "500", text: "500 Internal Error"}
                ]}
            ],
            columns: [
                {title: "<?php echo app_lang("timestamp"); ?>", "class": "w15p"},
                {title: "<?php echo app_lang("method"); ?>", "class": "w8p"},
                {title: "<?php echo app_lang("endpoint"); ?>", "class": "w25p"},
                {title: "<?php echo app_lang("api_key"); ?>", "class": "w15p"},
                {title: "<?php echo app_lang("response_code"); ?>", "class": "w10p"},
                {title: "<?php echo app_lang("response_time"); ?>", "class": "w10p"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            xlsColumns: [0, 1, 2, 3, 4, 5]
        });
    });
</script>

<script src="<?php echo get_file_uri('plugins/Rest_api/assets/js/api_logs.js'); ?>"></script>
