/**
 * REST API Plugin Admin JavaScript
 */

(function() {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initRestApiAdmin();
    });

    /**
     * Initialize REST API Admin functionality
     */
    function initRestApiAdmin() {
        // Initialize copy to clipboard
        initCopyToClipboard();

        // Initialize tooltips
        initTooltips();

        // Initialize API key visibility toggle
        initApiKeyVisibility();

        // Initialize rate limit warnings
        initRateLimitWarnings();

        // Initialize endpoint toggles
        initEndpointToggles();
    }

    /**
     * Initialize copy to clipboard functionality
     */
    function initCopyToClipboard() {
        $(document).on('click', '.copy-api-key', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var textToCopy = $(this).data('key');
            
            if (!textToCopy) {
                var $input = $(this).closest('.input-group').find('input');
                textToCopy = $input.val();
            }

            // Create temporary input
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(textToCopy).select();
            
            try {
                document.execCommand('copy');
                
                // Visual feedback
                var originalHtml = $button.html();
                $button.html('<i data-feather="check" class="icon-16"></i> ' + (window.app_lang && window.app_lang.copied ? window.app_lang.copied : 'Copied!'));
                $button.addClass('copy-btn-success btn-success');
                
                setTimeout(function() {
                    $button.html(originalHtml);
                    $button.removeClass('copy-btn-success btn-success');
                    feather.replace();
                }, 2000);
                
                // Show alert
                if (window.appAlert) {
                    appAlert.success(window.app_lang && window.app_lang.api_key_copied ? window.app_lang.api_key_copied : 'API key copied to clipboard', {duration: 3000});
                }
            } catch (err) {
                console.error('Copy failed:', err);
                if (window.appAlert) {
                    appAlert.error('Failed to copy API key');
                }
            }
            
            $temp.remove();
        });
    }

    /**
     * Initialize Bootstrap tooltips
     */
    function initTooltips() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }

    /**
     * Initialize API key visibility toggle
     */
    function initApiKeyVisibility() {
        $(document).on('click', '.toggle-api-key-visibility', function(e) {
            e.preventDefault();
            
            var $input = $(this).closest('.input-group').find('input');
            var $icon = $(this).find('i');
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.attr('data-feather', 'eye-off');
            } else {
                $input.attr('type', 'password');
                $icon.attr('data-feather', 'eye');
            }
            
            feather.replace();
        });
    }

    /**
     * Initialize rate limit warnings
     */
    function initRateLimitWarnings() {
        $('.rate-limit-input').on('input', function() {
            var value = parseInt($(this).val());
            var $indicator = $(this).closest('.form-group').find('.rate-limit-indicator');
            
            if (!$indicator.length) {
                $indicator = $('<span class="rate-limit-indicator ml-2"></span>');
                $(this).after($indicator);
            }
            
            if (value < 10) {
                $indicator.text('Very Low').removeClass('warning').addClass('danger');
            } else if (value < 50) {
                $indicator.text('Low').removeClass('danger').addClass('warning');
            } else {
                $indicator.text('Normal').removeClass('warning danger');
            }
        });
    }

    /**
     * Initialize endpoint toggles
     */
    function initEndpointToggles() {
        $('#toggle-all-endpoints').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.endpoint-checkbox').prop('checked', isChecked);
        });

        $('.endpoint-checkbox').on('change', function() {
            var totalCheckboxes = $('.endpoint-checkbox').length;
            var checkedCheckboxes = $('.endpoint-checkbox:checked').length;
            
            $('#toggle-all-endpoints').prop('checked', totalCheckboxes === checkedCheckboxes);
        });
    }

    /**
     * Format response time
     */
    function formatResponseTime(milliseconds) {
        if (milliseconds < 1000) {
            return milliseconds.toFixed(2) + ' ms';
        } else {
            return (milliseconds / 1000).toFixed(2) + ' s';
        }
    }

    /**
     * Get status badge HTML
     */
    function getStatusBadge(statusCode) {
        var badgeClass = 'bg-secondary';
        
        if (statusCode >= 200 && statusCode < 300) {
            badgeClass = 'bg-success';
        } else if (statusCode >= 300 && statusCode < 400) {
            badgeClass = 'bg-info';
        } else if (statusCode >= 400 && statusCode < 500) {
            badgeClass = 'bg-warning';
        } else if (statusCode >= 500) {
            badgeClass = 'bg-danger';
        }
        
        return '<span class="badge ' + badgeClass + '">' + statusCode + '</span>';
    }

    /**
     * Get method badge HTML
     */
    function getMethodBadge(method) {
        var badgeClass = 'bg-secondary';
        
        switch(method.toUpperCase()) {
            case 'GET':
                badgeClass = 'bg-success';
                break;
            case 'POST':
                badgeClass = 'bg-primary';
                break;
            case 'PUT':
                badgeClass = 'bg-warning';
                break;
            case 'DELETE':
                badgeClass = 'bg-danger';
                break;
            case 'PATCH':
                badgeClass = 'bg-info';
                break;
        }
        
        return '<span class="badge method-badge ' + badgeClass + '">' + method.toUpperCase() + '</span>';
    }

    /**
     * Refresh statistics
     */
    function refreshStatistics() {
        $.ajax({
            url: window.baseUrl + 'rest_api_settings/get_statistics',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateStatistics(response.data);
                }
            },
            error: function() {
                if (window.appAlert) {
                    appAlert.error('Failed to refresh statistics');
                }
            }
        });
    }

    /**
     * Update statistics display
     */
    function updateStatistics(data) {
        if (data.total_api_keys !== undefined) {
            $('.stat-total-keys').text(data.total_api_keys);
        }
        if (data.requests_today !== undefined) {
            $('.stat-requests-today').text(data.requests_today);
        }
        if (data.requests_this_month !== undefined) {
            $('.stat-requests-month').text(data.requests_this_month);
        }
        if (data.avg_response_time !== undefined) {
            $('.stat-avg-response').text(formatResponseTime(data.avg_response_time));
        }
    }

    /**
     * Export logs
     */
    window.exportApiLogs = function(format) {
        var filters = getLogFilters();
        var url = window.baseUrl + 'api_logs/export?format=' + format + '&' + $.param(filters);
        window.open(url, '_blank');
    };

    /**
     * Get current log filters
     */
    function getLogFilters() {
        return {
            api_key_id: $('#api-key-filter').val(),
            method: $('#method-filter').val(),
            response_code: $('#status-filter').val(),
            start_date: $('#start-date').val(),
            end_date: $('#end-date').val()
        };
    }

    /**
     * Generate new API key
     */
    window.generateApiKey = function() {
        // This will open the modal form
        // The actual generation happens server-side when the form is saved
        return true;
    };

    /**
     * Revoke API key
     */
    window.revokeApiKey = function(keyId) {
        if (!confirm(window.app_lang && window.app_lang.revoke_api_key_confirmation ? window.app_lang.revoke_api_key_confirmation : 'Are you sure you want to revoke this API key?')) {
            return false;
        }

        $.ajax({
            url: window.baseUrl + 'api_keys/revoke',
            type: 'POST',
            data: {id: keyId},
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (window.appAlert) {
                        appAlert.success(response.message);
                    }
                    if (window.appTable) {
                        $('#api-keys-table').appTable({reload: true});
                    }
                } else {
                    if (window.appAlert) {
                        appAlert.error(response.message);
                    }
                }
            },
            error: function() {
                if (window.appAlert) {
                    appAlert.error('An error occurred');
                }
            }
        });
    };

    /**
     * Test API endpoint
     */
    window.testApiEndpoint = function(endpoint, method) {
        var apiKey = prompt('Enter your API key to test:');
        if (!apiKey) return;

        $.ajax({
            url: window.baseUrl + 'api/v1/' + endpoint,
            type: method || 'GET',
            headers: {
                'X-API-KEY': apiKey
            },
            dataType: 'json',
            success: function(response) {
                alert('Success! Response:\n' + JSON.stringify(response, null, 2));
            },
            error: function(xhr) {
                alert('Error! Status: ' + xhr.status + '\nResponse:\n' + xhr.responseText);
            }
        });
    };

    // Expose utility functions globally
    window.RestApiAdmin = {
        formatResponseTime: formatResponseTime,
        getStatusBadge: getStatusBadge,
        getMethodBadge: getMethodBadge,
        refreshStatistics: refreshStatistics,
        exportApiLogs: exportApiLogs,
        generateApiKey: generateApiKey,
        revokeApiKey: revokeApiKey,
        testApiEndpoint: testApiEndpoint
    };

})();

