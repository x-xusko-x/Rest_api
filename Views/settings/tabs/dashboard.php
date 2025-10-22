<div class="card no-border clearfix mb0">
    <div class="card-body">
        <!-- Current Statistics (Log-based) -->
        <h5 class="mb-3"><?php echo app_lang("current_statistics"); ?></h5>
        <div class="row">
            <div class="col-sm-6 col-lg-3">
                <div class="card dashboard-icon-widget">
                    <div class="card-body">
                        <div class="widget-icon bg-primary">
                            <i data-feather="key" class="icon"></i>
                        </div>
                        <div class="widget-details">
                            <h1><?php echo $active_api_keys; ?></h1>
                            <span class="bg-transparent-white"><?php echo app_lang("active_api_keys"); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card dashboard-icon-widget">
                    <div class="card-body">
                        <div class="widget-icon bg-success">
                            <i data-feather="activity" class="icon"></i>
                        </div>
                        <div class="widget-details">
                            <h1><?php echo $requests_today; ?></h1>
                            <span class="bg-transparent-white"><?php echo app_lang("requests_today"); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card dashboard-icon-widget">
                    <div class="card-body">
                        <div class="widget-icon bg-info">
                            <i data-feather="bar-chart-2" class="icon"></i>
                        </div>
                        <div class="widget-details">
                            <h1><?php echo $requests_this_month; ?></h1>
                            <span class="bg-transparent-white"><?php echo app_lang("requests_this_month"); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card dashboard-icon-widget">
                    <div class="card-body">
                        <div class="widget-icon bg-warning">
                            <i data-feather="clock" class="icon"></i>
                        </div>
                        <div class="widget-details">
                            <h1><?php echo $avg_response_time; ?>s</h1>
                            <span class="bg-transparent-white"><?php echo app_lang("avg_response_time"); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lifetime Statistics (Persistent, survives log cleaning) -->
        <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
            <h5 class="mb-0"><?php echo app_lang("lifetime_statistics"); ?></h5>
            <small class="text-muted">
                <i data-feather="info" class="icon-16"></i>
                <?php echo app_lang("lifetime_stats_note"); ?>
            </small>
        </div>
        <div class="row">
            <div class="col-sm-6 col-lg-3">
                <div class="card dashboard-icon-widget">
                    <div class="card-body">
                        <div class="widget-icon" style="background-color: #9b59b6;">
                            <i data-feather="database" class="icon"></i>
                        </div>
                        <div class="widget-details">
                            <h1><?php echo number_format($lifetime_total_calls); ?></h1>
                            <span
                                class="bg-transparent-white"><?php echo app_lang("total_api_calls_lifetime"); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card dashboard-icon-widget">
                    <div class="card-body">
                        <div class="widget-icon" style="background-color: #e67e22;">
                            <i data-feather="calendar" class="icon"></i>
                        </div>
                        <div class="widget-details">
                            <h1><?php echo number_format($lifetime_avg_per_day, 1); ?></h1>
                            <span class="bg-transparent-white"><?php echo app_lang("avg_calls_per_day"); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card dashboard-icon-widget">
                    <div class="card-body">
                        <div class="widget-icon" style="background-color: #3498db;">
                            <i data-feather="trending-up" class="icon"></i>
                        </div>
                        <div class="widget-details">
                            <h1><?php echo number_format($lifetime_avg_per_month, 1); ?></h1>
                            <span class="bg-transparent-white"><?php echo app_lang("avg_calls_per_month"); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card dashboard-icon-widget">
                    <div class="card-body">
                        <div class="widget-icon" style="background-color: #27ae60;">
                            <i data-feather="check-circle" class="icon"></i>
                        </div>
                        <div class="widget-details">
                            <h1><?php echo number_format($lifetime_success_rate, 1); ?>%</h1>
                            <span class="bg-transparent-white"><?php echo app_lang("success_rate_lifetime"); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <h5><?php echo app_lang("api_health"); ?></h5>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo app_lang("total_requests"); ?>
                        <span class="badge bg-primary rounded-pill"><?php echo $total_requests; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo app_lang("successful_requests"); ?>
                        <span class="badge bg-success rounded-pill"><?php echo $successful_requests; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo app_lang("failed_requests"); ?>
                        <span class="badge bg-danger rounded-pill"><?php echo $failed_requests; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo app_lang("success_rate"); ?>
                        <span class="badge bg-info rounded-pill">
                            <?php echo $total_requests > 0 ? round(($successful_requests / $total_requests) * 100, 2) . "%" : "N/A"; ?>
                        </span>
                    </li>
                </ul>
            </div>
            <div class="col-md-6">
                <h5><?php echo app_lang("maintenance"); ?></h5>
                <div class="card bg-light">
                    <div class="card-body">
                        <div class="alert alert-warning mb-3" role="alert">
                            <i data-feather="alert-triangle" class="icon-16"></i>
                            <?php echo app_lang("retention_policy_note"); ?>
                        </div>
                        <?php echo js_anchor("<i data-feather='trash-2' class='icon-16'></i> " . app_lang('clean_old_logs'), array('title' => app_lang('clean_old_logs'), "class" => "btn btn-danger btn-lg w-100", "id" => "clean-logs-button", "data-action-url" => get_uri("rest_api_settings/clean_logs"), "data-action" => "delete-confirmation")); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Pass PHP variables to JavaScript
    window.rest_api_base_url = "<?php echo get_uri(); ?>";
    window.app_lang = window.app_lang || {};
    window.app_lang.clean_logs_confirmation = "<?php echo app_lang('clean_logs_confirmation'); ?>";
    window.app_lang.error_occurred = "<?php echo app_lang('error_occurred'); ?>";
</script>
<script src="<?php echo get_file_uri('plugins/Rest_api/assets/js/dashboard.js'); ?>"></script>