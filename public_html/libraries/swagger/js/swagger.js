window.onload = () => {
    window.ui = SwaggerUIBundle({
        url: root + '/api/v1/docs',
        dom_id: '#swagger-ui',
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset
        ],
        layout: "StandaloneLayout",
    });
};