<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rise CRM REST API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui.css">
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        
        .topbar {
            display: none;
        }
        
        .swagger-ui .info .title {
            font-size: 36px;
        }
        
        #header {
            background: #1b1b1b;
            color: white;
            padding: 20px 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        #header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        #header p {
            margin: 5px 0 0 0;
            opacity: 0.8;
        }
        
        #api-credentials {
            background: #f7f7f7;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 40px;
        }
        
        #api-credentials h3 {
            margin-top: 0;
            color: #3b4151;
        }
        
        #api-credentials input {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        #api-credentials button {
            background: #4990e2;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        #api-credentials button:hover {
            background: #357abd;
        }
        
        .auth-info {
            margin-top: 15px;
            padding: 10px;
            background: #e7f3ff;
            border-left: 3px solid #4990e2;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div id="header">
        <h1>Rise CRM REST API Documentation</h1>
        <p>Interactive OpenAPI 3.0 Specification</p>
    </div>

    <div id="api-credentials">
        <h3>API Authentication</h3>
        <p>Enter your API credentials to test endpoints directly from this interface:</p>
        <label>API Key:</label>
        <input type="text" id="api-key" placeholder="Enter your API Key">
        <label>API Secret:</label>
        <input type="password" id="api-secret" placeholder="Enter your API Secret">
        <button onclick="setApiCredentials()">Set Credentials</button>
        
        <div class="auth-info">
            <strong>Note:</strong> This API supports two authentication methods:
            <ul>
                <li><strong>Headers:</strong> X-API-Key and X-API-Secret (recommended)</li>
                <li><strong>Bearer Token:</strong> Authorization: Bearer {base64(api_key:api_secret)}</li>
            </ul>
        </div>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.0/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "<?= $spec_url ?>",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                validatorUrl: null, // Disable online validator
                requestInterceptor: function(request) {
                    // Add API credentials if set
                    const apiKey = localStorage.getItem('risecrm_api_key');
                    const apiSecret = localStorage.getItem('risecrm_api_secret');
                    
                    if (apiKey && apiSecret) {
                        request.headers['X-API-Key'] = apiKey;
                        request.headers['X-API-Secret'] = apiSecret;
                    }
                    
                    return request;
                }
            });

            window.ui = ui;
            
            // Load saved credentials
            const savedKey = localStorage.getItem('risecrm_api_key');
            const savedSecret = localStorage.getItem('risecrm_api_secret');
            if (savedKey) document.getElementById('api-key').value = savedKey;
            if (savedSecret) document.getElementById('api-secret').value = savedSecret;
        };

        function setApiCredentials() {
            const apiKey = document.getElementById('api-key').value;
            const apiSecret = document.getElementById('api-secret').value;
            
            if (!apiKey || !apiSecret) {
                alert('Please enter both API Key and API Secret');
                return;
            }
            
            // Save to localStorage
            localStorage.setItem('risecrm_api_key', apiKey);
            localStorage.setItem('risecrm_api_secret', apiSecret);
            
            alert('API credentials saved! You can now test endpoints directly.');
        }
    </script>
</body>
</html>

