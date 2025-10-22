/**
 * REST API Logs JavaScript
 */

(function() {
    'use strict';

    $(document).ready(function() {
        initApiLogs();
    });

    /**
     * Initialize API Logs Table
     */
    function initApiLogs() {
        // Check if logs were cleared and reload table if needed
        checkAndReloadIfCleared();
        
        // Initialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }

    /**
     * Check if logs were cleared from Dashboard and reload if needed
     */
    function checkAndReloadIfCleared() {
        if (sessionStorage.getItem('api_logs_cleared') === 'true') {
            // Clear the flag
            sessionStorage.removeItem('api_logs_cleared');
            
            // Reload the table to show cleared logs
            setTimeout(function() {
                if ($.fn.DataTable.isDataTable('#api-logs-table')) {
                    $("#api-logs-table").DataTable().ajax.reload(null, false);
                }
            }, 100);
        }
    }

})();

