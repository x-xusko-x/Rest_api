<div class="modal-body clearfix">
    <div class="container-fluid">

        <div class="row">
            <div class="card">
                <div class="card-header">
                    <?php echo app_lang('request_details'); ?>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td width="25%"><strong><?php echo app_lang('api_log'); ?></strong></td>
                                <td>#<?php echo $log_info->id; ?></td>
                            </tr>
                            <tr>
                                <td width="25%"><strong><?php echo app_lang('timestamp'); ?></strong></td>
                                <td><?php echo format_to_datetime($log_info->created_at); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('api_key'); ?></strong></td>
                                <td><?php echo $api_key_name ?? '-'; ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('method'); ?></strong></td>
                                <td>
                                    <?php
                                    $method_class = 'bg-secondary';
                                    switch ($log_info->method) {
                                        case 'GET':
                                            $method_class = 'bg-success';
                                            break;
                                        case 'POST':
                                            $method_class = 'bg-primary';
                                            break;
                                        case 'PUT':
                                            $method_class = 'bg-warning';
                                            break;
                                        case 'DELETE':
                                            $method_class = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span
                                        class="badge <?php echo $method_class; ?>"><?php echo $log_info->method; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('endpoint'); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($log_info->endpoint); ?></code></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('ip_address'); ?></strong></td>
                                <td><?php echo $log_info->ip_address; ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('user_agent'); ?></strong></td>
                                <td><small><?php echo htmlspecialchars($log_info->user_agent); ?></small></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <?php echo app_lang('response_details'); ?>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td width="25%"><strong><?php echo app_lang('response_code'); ?></strong></td>
                                <td>
                                    <?php
                                    $status_class = 'bg-secondary';
                                    if ($log_info->response_code >= 200 && $log_info->response_code < 300) {
                                        $status_class = 'bg-success';
                                    } else if ($log_info->response_code >= 300 && $log_info->response_code < 400) {
                                        $status_class = 'bg-info';
                                    } else if ($log_info->response_code >= 400 && $log_info->response_code < 500) {
                                        $status_class = 'bg-warning';
                                    } else if ($log_info->response_code >= 500) {
                                        $status_class = 'bg-danger';
                                    }
                                    ?>
                                    <span
                                        class="badge <?php echo $status_class; ?>"><?php echo $log_info->response_code; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php echo app_lang('response_time'); ?></strong></td>
                                <td><?php echo round($log_info->response_time, 2); ?> ms</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($log_info->request_body): ?>

                <div class="card">
                    <div class="card-header">
                        <?php echo app_lang('request_body'); ?>
                    </div>
                    <div class="card-body">
                        <pre class="json-view"
                            style="background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;"><code class="language-json"><?php
                            $request_json = json_decode($log_info->request_body);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                echo htmlspecialchars(json_encode($request_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                            } else {
                                echo htmlspecialchars($log_info->request_body);
                            }
                            ?></code></pre>
                    </div>
                </div>

            <?php endif; ?>

            <?php if ($log_info->response_body): ?>

                <div class="card">
                    <div class="card-header">
                        <?php echo app_lang('response_body'); ?>
                    </div>
                    <div class="card-body">
                        <pre class="json-view"
                            style="background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;"><code class="language-json"><?php
                            $response_json = json_decode($log_info->response_body);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                echo htmlspecialchars(json_encode($response_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                            } else {
                                echo htmlspecialchars($log_info->response_body);
                            }
                            ?></code></pre>
                    </div>
                </div>

            <?php endif; ?>
        </div>

    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span>
        <?php echo app_lang('close'); ?></button>
</div>

<style>
    /* JSON Syntax Highlighting */
    .json-view {
        font-family: 'Courier New', Courier, monospace;
        font-size: 13px;
        line-height: 1.6;
        color: #333;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    .json-view code {
        display: block;
        white-space: pre-wrap;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        feather.replace();

        // Simple JSON syntax highlighting
        $('.json-view code').each(function () {
            var text = $(this).text();

            // Only highlight if it looks like valid JSON
            if (text.trim().startsWith('{') || text.trim().startsWith('[')) {
                var highlighted = text
                    // String values (but not keys)
                    .replace(/(:\s*)"([^"]*)"/g, function (match, colon, value) {
                        return colon + '<span style="color: #0d6832;">"' + value + '"</span>';
                    })
                    // Keys
                    .replace(/"([^"]+)"(\s*:)/g, '<span style="color: #0451a5;">"$1"</span>$2')
                    // Numbers
                    .replace(/:\s*(-?\d+\.?\d*)/g, ': <span style="color: #098658;">$1</span>')
                    // Booleans
                    .replace(/:\s*(true|false)/g, ': <span style="color: #0000ff;">$1</span>')
                    // Null
                    .replace(/:\s*(null)/g, ': <span style="color: #808080;">$1</span>')
                    // Brackets
                    .replace(/([{}\[\]])/g, '<span style="color: #000000; font-weight: bold;">$1</span>');

                $(this).html(highlighted);
            }
        });
    });
</script>