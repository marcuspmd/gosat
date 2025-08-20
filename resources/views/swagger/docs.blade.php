<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Consultation API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
        #swagger-ui {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen", "Ubuntu", "Cantarell", "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif;
        }
        .swagger-ui .topbar {
            background-color: #2c3e50;
        }
        .swagger-ui .topbar .download-url-wrapper .select-label {
            color: #fff;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-standalone-preset.js"></script>
    <script>
        // Fetch the spec directly - ignore HTTP status, just parse JSON
        fetch("{{ $apiDocsUrl }}")
            .then(response => {
                // Ignore status code and just try to parse JSON
                // The Laravel app is sending the correct JSON even with 404 status
                return response.json();
            })
            .then(spec => {
                console.log('Spec loaded successfully:', spec);
                
                // Initialize Swagger UI with the loaded spec
                const ui = SwaggerUIBundle({
                    spec: spec, // Use spec instead of url
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
                    validatorUrl: null,
                    docExpansion: "list",
                    filter: true,
                    tryItOutEnabled: true,
                    requestInterceptor: function(request) {
                        // Add any global request headers here if needed
                        return request;
                    },
                    responseInterceptor: function(response) {
                        // Process responses here if needed
                        return response;
                    },
                    onComplete: function() {
                        console.log("Swagger UI loaded successfully");
                    },
                    onFailure: function(data) {
                        console.error("Failed to load Swagger UI", data);
                    }
                });
            })
            .catch(error => {
                console.error('Failed to load API spec:', error);
                
                // Fallback: try with URL method
                const ui = SwaggerUIBundle({
                    url: "{{ $apiDocsUrl }}",
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
                    validatorUrl: null,
                    docExpansion: "list",
                    filter: true,
                    tryItOutEnabled: true,
                    onFailure: function(data) {
                        console.error("Failed to load Swagger UI with URL method", data);
                        document.getElementById('swagger-ui').innerHTML = 
                            '<div style="padding: 20px; text-align: center;">' +
                            '<h2>Failed to Load API Documentation</h2>' +
                            '<p>Error: ' + error.message + '</p>' +
                            '<p>Please check the console for more details.</p>' +
                            '</div>';
                    }
                });
            });

        // Custom styling
        window.addEventListener('load', function() {
            // Add custom header
            const topbar = document.querySelector('.topbar');
            if (topbar) {
                const header = document.createElement('div');
                header.innerHTML = '<h2 style="color: white; margin: 10px 20px;">GoSat Credit Consultation API</h2>';
                topbar.prepend(header);
            }
        });
    </script>
</body>
</html>