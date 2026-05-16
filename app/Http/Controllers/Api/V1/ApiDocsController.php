<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiDocsController extends Controller
{
    /**
     * Serve the OpenAPI JSON spec.
     */
    public function spec(): JsonResponse
    {
        $path = public_path('api-docs.json');

        if (! file_exists($path)) {
            return response()->json(['message' => 'API docs not generated yet. Run: php artisan api:docs'], 404);
        }

        $spec = json_decode(file_get_contents($path), true);

        return response()->json($spec)->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Serve Swagger UI page (uses CDN).
     */
    public function ui(): Response
    {
        $specUrl = url('/api/v1/docs/spec');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muhasebi API Documentation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <style>
        body { margin: 0; background: #fafafa; }
        .swagger-ui .topbar { display: none; }
        .swagger-ui .info { margin: 20px 0; }
        .swagger-ui .info .title { font-size: 28px; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUIBundle({
            url: "{$specUrl}",
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [SwaggerUIBundle.presets.apis, SwaggerUIBundle.SwaggerUIStandalonePreset],
            layout: "BaseLayout",
            defaultModelsExpandDepth: -1,
            docExpansion: "none",
            filter: true,
            tryItOutEnabled: true,
        });
    </script>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}
