<!-- API Documentation - Swagger UI -->
<div class="panel panel-default">
    <div class="page-title clearfix">
        <h1><?php echo app_lang('api_documentation'); ?></h1>
        <div class="title-button-group">
            <button type="button" id="swagger-authorize-btn" class="btn btn-success">
                <i class="fa fa-key"></i> <?php echo app_lang('authorize'); ?>
            </button>
        </div>
    </div>
    
    <div class="panel-body">
        <div id="swagger-ui"></div>
    </div>
</div>

<!-- Swagger UI Assets -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css" />

<script>
// Load Swagger UI dynamically
(function() {
    // Show loading state
    $('#swagger-ui').html('<div class="text-center" style="padding: 80px;"><i class="fa fa-circle-o-notch fa-spin fa-3x text-primary"></i><p class="mt15 text-muted">Loading API Documentation...</p></div>');
    
    // Load Swagger UI scripts
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    // Load scripts sequentially
    loadScript('https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js')
        .then(() => loadScript('https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-standalone-preset.js'))
        .then(() => {
            // Initialize Swagger UI
            const ui = SwaggerUIBundle({
                url: "<?php echo get_uri('swagger/spec'); ?>",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [SwaggerUIBundle.plugins.DownloadUrl],
                layout: "StandaloneLayout",
                defaultModelsExpandDepth: -1,
                docExpansion: 'list',
                filter: true,
                showExtensions: true,
                displayRequestDuration: true,
                persistAuthorization: true,
                
                // Pre-fill auth from session storage
                onComplete: function() {
                    const apiKey = sessionStorage.getItem('rise_api_key');
                    const apiSecret = sessionStorage.getItem('rise_api_secret');
                    
                    if (apiKey && apiSecret) {
                        ui.preauthorizeApiKey('ApiKeyAuth', apiKey);
                        ui.preauthorizeApiKey('ApiSecretAuth', apiSecret);
                    }
                },
                
                // Save auth to session storage
                requestInterceptor: function(request) {
                    if (request.headers['X-API-Key']) {
                        sessionStorage.setItem('rise_api_key', request.headers['X-API-Key']);
                    }
                    if (request.headers['X-API-Secret']) {
                        sessionStorage.setItem('rise_api_secret', request.headers['X-API-Secret']);
                    }
                    return request;
                }
            });
            
            window.swaggerUI = ui;
            
            // Connect custom authorize button to Swagger's auth system
            $('#swagger-authorize-btn').on('click', function() {
                // Use Swagger's API to show auth modal
                if (window.swaggerUI && window.swaggerUI.authActions) {
                    // Show authorization modal for both API Key and Secret
                    window.swaggerUI.authActions.showDefinitions(['ApiKeyAuth', 'ApiSecretAuth']);
                } else {
                    // Fallback: try to find and click Swagger's authorize button
                    setTimeout(() => {
                        const authBtn = document.querySelector('.swagger-ui .authorize');
                        if (authBtn) {
                            authBtn.click();
                        } else {
                            console.error('Could not find Swagger authorize button');
                        }
                    }, 100);
                }
            });
        })
        .catch(error => {
            console.error('Error loading Swagger UI:', error);
            $('#swagger-ui').html(
                '<div class="alert alert-danger m20">' +
                '<strong><i class="fa fa-exclamation-triangle"></i> Error</strong><br>' +
                'Failed to load Swagger UI. Please check your internet connection and refresh the page.' +
                '</div>'
            );
        });
})();
</script>

<style>
/* ========================================
   Swagger UI - Rise CRM Panel Integration
   ======================================== */

/* Hide Swagger's default top bar */
.swagger-ui .topbar {
    display: none !important;
}

/* Hide server selector and info section (we show title in page-title) */
.swagger-ui .servers,
.swagger-ui .information-container {
    display: none !important;
}

/* Show authorize wrapper in operations section */
.swagger-ui .wrapper .auth-wrapper {
    display: block !important;
    margin-bottom: 20px;
}

/* But hide it from scheme container */
.swagger-ui .scheme-container .auth-wrapper {
    display: none !important;
}

/* Container styling - properly enclosed */
.swagger-ui {
    font-family: inherit !important;
    background: white !important;
    border: 1px solid #e4e4e4 !important;
    border-radius: 4px !important;
    padding: 0 !important;
}

/* Wrapper for main content */
.swagger-ui .wrapper {
    padding: 20px !important;
}

/* Info section */
.swagger-ui .info {
    margin: 20px 0 !important;
}

.swagger-ui .info .title {
    color: #1e88e5 !important;
    font-family: inherit !important;
    font-size: 32px !important;
}

/* Operation blocks */
.swagger-ui .opblock-tag {
    border-bottom: 1px solid #e4e4e4 !important;
    font-family: inherit !important;
    font-size: 18px !important;
    color: #333 !important;
}

.swagger-ui .opblock {
    border: 1px solid #e4e4e4 !important;
    border-radius: 4px !important;
    margin: 0 0 15px !important;
    box-shadow: none !important;
}

/* HTTP Method badges - Rise CRM colors */
.swagger-ui .opblock.opblock-get .opblock-summary-method {
    background: #49cc90 !important;
}

.swagger-ui .opblock.opblock-post .opblock-summary-method {
    background: #1e88e5 !important;
}

.swagger-ui .opblock.opblock-put .opblock-summary-method {
    background: #ffa726 !important;
}

.swagger-ui .opblock.opblock-delete .opblock-summary-method {
    background: #ef5350 !important;
}

/* Buttons */
.swagger-ui .btn {
    font-family: inherit !important;
    border-radius: 4px !important;
    box-shadow: none !important;
    font-size: 13px !important;
}

.swagger-ui .btn.execute {
    background-color: #1e88e5 !important;
    border-color: #1e88e5 !important;
}

.swagger-ui .btn.authorize {
    background-color: #49cc90 !important;
    border-color: #49cc90 !important;
}

/* Form inputs */
.swagger-ui input[type=text],
.swagger-ui textarea,
.swagger-ui select {
    border: 1px solid #ddd !important;
    border-radius: 4px !important;
    font-family: inherit !important;
}

.swagger-ui input:focus,
.swagger-ui textarea:focus {
    border-color: #1e88e5 !important;
    outline: none !important;
    box-shadow: 0 0 0 0.2rem rgba(30, 136, 229, 0.15) !important;
}

/* Tables */
.swagger-ui table thead tr th {
    font-family: inherit !important;
    font-size: 12px !important;
    border-bottom: 1px solid #e4e4e4 !important;
}

/* Code blocks */
.swagger-ui .highlight-code {
    background: #f5f5f5 !important;
    border: 1px solid #e4e4e4 !important;
}

/* Authorization modal */
.swagger-ui .dialog-ux .modal-ux {
    border: 1px solid #e4e4e4 !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important;
}

.swagger-ui .dialog-ux .modal-ux-header {
    background: #f5f5f5 !important;
    border-bottom: 1px solid #e4e4e4 !important;
}

/* Response section */
.swagger-ui .responses-inner {
    padding: 15px !important;
}

/* Models section */
.swagger-ui .model-box {
    background: #f9f9f9 !important;
    border: 1px solid #e4e4e4 !important;
}

/* Filter section - match API Logs style */
.swagger-ui .filter-container {
    background: #f9f9f9 !important;
    border-bottom: 1px solid #e4e4e4 !important;
    padding: 15px 20px !important;
    margin: 0 !important;
}

.swagger-ui .filter input[type=text] {
    width: 100% !important;
    max-width: 400px !important;
}

/* Scheme container */
.swagger-ui .scheme-container {
    background: #f5f5f5 !important;
    border: 1px solid #e4e4e4 !important;
    box-shadow: none !important;
}

/* Scrollbars */
.swagger-ui ::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.swagger-ui ::-webkit-scrollbar-track {
    background: #f5f5f5;
}

.swagger-ui ::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 4px;
}

.swagger-ui ::-webkit-scrollbar-thumb:hover {
    background: #999;
}

/* Ensure authorization modal is always visible when triggered */
.swagger-ui .dialog-ux {
    display: block !important;
}

/* Style the authorize button in wrapper */
.swagger-ui .wrapper .auth-wrapper .authorize {
    background-color: #49cc90 !important;
    border-color: #49cc90 !important;
    color: white !important;
}
</style>
