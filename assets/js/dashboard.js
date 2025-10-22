/**
 * REST API Dashboard JavaScript
 */

(function() {
    'use strict';

    $(document).ready(function() {
        // Wait for Rise CRM's app.all.js to load
        if (typeof appAlert !== 'undefined') {
            initDashboard();
        } else {
            // Retry after a short delay
            setTimeout(function() {
                initDashboard();
            }, 100);
        }
    });

    /**
     * Initialize Dashboard
     */
    function initDashboard() {
        initCleanLogsButton();
        initCharts();
        initStatistics();
    }

    /**
     * Initialize Clean Logs Button
     */
    function initCleanLogsButton() {
        // Use event delegation to ensure it works even if loaded late
        $(document).off('click', '#clean-logs-button').on('click', '#clean-logs-button', function (e) {
            e.preventDefault();

            if (typeof appAlert === 'undefined') {
                console.error('appAlert is not defined');
                return;
            }

            appAlert.confirm(window.app_lang.clean_logs_confirmation || "Are you sure you want to delete all API logs?", function () {
                // Show loading state
                if (typeof appLoader !== 'undefined') {
                    appLoader.show();
                }
                
                $.ajax({
                    url: window.rest_api_base_url + 'rest_api_settings/clean_logs',
                    type: 'POST',
                    dataType: 'json',
                    success: function (result) {
                        if (typeof appLoader !== 'undefined') {
                            appLoader.hide();
                        }
                        
                        if (result.success) {
                            appAlert.success(result.message, { duration: 3000 });
                            
                            // Set flag in sessionStorage so Logs tab knows to reload
                            sessionStorage.setItem('api_logs_cleared', 'true');
                            
                            // Reload the logs table if it exists (user is currently on Logs tab)
                            if (typeof $("#api-logs-table").DataTable === 'function' && $.fn.DataTable.isDataTable('#api-logs-table')) {
                                $("#api-logs-table").DataTable().ajax.reload(null, false);
                            }
                            
                            // Update dashboard stats without full page reload
                            // Reload charts/stats via AJAX
                            if (typeof loadDashboardStats === 'function') {
                                loadDashboardStats();
                            } else {
                                // Fallback: just reload the current tab content
                                $("a[data-bs-target='#rest_api_settings-dashboard-tab']").trigger('click');
                            }
                        } else {
                            appAlert.error(result.message);
                        }
                    },
                    error: function () {
                        if (typeof appLoader !== 'undefined') {
                            appLoader.hide();
                        }
                        if (typeof appAlert !== 'undefined') {
                            appAlert.error(window.app_lang.error_occurred || "An error occurred");
                        }
                    }
                });
            });
        });
    }

    /**
     * Initialize Charts
     */
    function initCharts() {
        // Chart initialization can go here if needed
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }

    /**
     * Initialize Statistics
     */
    function initStatistics() {
        // Statistics initialization
    }

    /**
     * Load Dashboard Stats (public function)
     */
    window.loadDashboardStats = function() {
        // Reload stats via AJAX if needed
        return true;
    };

})();

