<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

class GenerateApiDocsCommand extends Command
{
    protected $signature = 'api:docs {--output=public/api-docs.json : Output file path}';

    protected $description = 'Generate OpenAPI 3.0 specification from registered routes';

    public function __construct(
        private readonly Router $router,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Muhasebi API',
                'description' => 'Cloud Accounting System for Egyptian Accounting Firms. Full REST API for managing clients, invoices, accounting, e-invoicing (ETA), and more.',
                'version' => config('api.version', '1.0'),
                'contact' => [
                    'name' => 'Muhasebi Support',
                    'email' => 'info@muhasebi.com',
                ],
            ],
            'servers' => [
                ['url' => config('app.url').'/api/v1', 'description' => 'Current Environment'],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Sanctum Token',
                    ],
                ],
                'parameters' => [
                    'TenantHeader' => [
                        'name' => 'X-Tenant',
                        'in' => 'header',
                        'description' => 'Tenant ID or slug for multi-tenant context',
                        'required' => false,
                        'schema' => ['type' => 'string'],
                    ],
                    'AcceptLanguage' => [
                        'name' => 'Accept-Language',
                        'in' => 'header',
                        'description' => 'Response language (ar or en)',
                        'required' => false,
                        'schema' => ['type' => 'string', 'enum' => ['ar', 'en'], 'default' => 'ar'],
                    ],
                    'PageParam' => [
                        'name' => 'page',
                        'in' => 'query',
                        'description' => 'Page number for pagination',
                        'required' => false,
                        'schema' => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
                    ],
                ],
            ],
            'paths' => [],
            'tags' => [],
        ];

        $routes = collect($this->router->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => Str::startsWith($route->uri(), 'api/v1'))
            ->sortBy(fn (Route $route) => $route->uri());

        $tags = collect();

        foreach ($routes as $route) {
            $uri = $route->uri();
            $methods = collect($route->methods())->filter(fn ($m) => $m !== 'HEAD')->values();

            // Convert Laravel route params to OpenAPI format
            $path = '/'.preg_replace('/\{(\w+)\}/', '{$1}', Str::after($uri, 'api/v1'));

            foreach ($methods as $method) {
                $method = strtolower($method);
                $tag = $this->guessTag($uri);
                $tags->push($tag);

                $operation = [
                    'tags' => [$tag],
                    'summary' => $this->guessSummary($route, $method),
                    'operationId' => $route->getName() ?: Str::camel($method.'_'.str_replace(['/', '{', '}'], ['_', '', ''], $path)),
                ];

                // Determine if authenticated
                $middleware = $route->gatherMiddleware();
                $isAuth = in_array('auth:sanctum', $middleware);
                $isSuperAdmin = in_array('super_admin', $middleware);

                if ($isAuth) {
                    $operation['security'] = [['bearerAuth' => []]];
                }

                if ($isSuperAdmin) {
                    $operation['description'] = 'Requires super_admin role.';
                }

                // Path parameters
                preg_match_all('/\{(\w+)\}/', $uri, $matches);
                if (! empty($matches[1])) {
                    $operation['parameters'] = array_map(fn ($param) => [
                        'name' => $param,
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => is_numeric($param) ? 'integer' : 'string'],
                    ], $matches[1]);
                }

                // Standard responses
                $operation['responses'] = [
                    '200' => ['description' => 'Successful response'],
                ];

                if ($method === 'post') {
                    $operation['responses']['201'] = ['description' => 'Resource created'];
                    $operation['requestBody'] = [
                        'required' => true,
                        'content' => ['application/json' => ['schema' => ['type' => 'object']]],
                    ];
                }

                if ($method === 'put' || $method === 'patch') {
                    $operation['requestBody'] = [
                        'required' => true,
                        'content' => ['application/json' => ['schema' => ['type' => 'object']]],
                    ];
                }

                if ($isAuth) {
                    $operation['responses']['401'] = ['description' => 'Unauthenticated'];
                    $operation['responses']['403'] = ['description' => 'Forbidden'];
                }

                $spec['paths'][$path][$method] = $operation;
            }
        }

        // Build unique tags
        $spec['tags'] = $tags->unique()->sort()->values()->map(fn ($tag) => [
            'name' => $tag,
            'description' => "Operations related to {$tag}",
        ])->toArray();

        $outputPath = $this->option('output');
        file_put_contents(base_path($outputPath), json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $pathCount = count($spec['paths']);
        $this->info("OpenAPI spec generated: {$outputPath} ({$pathCount} paths, {$tags->unique()->count()} tags)");

        return self::SUCCESS;
    }

    private function guessTag(string $uri): string
    {
        $parts = explode('/', Str::after($uri, 'api/v1/'));

        // Admin routes
        if ($parts[0] === 'admin') {
            return 'Admin: '.Str::title($parts[1] ?? 'General');
        }

        // Portal routes
        if ($parts[0] === 'portal') {
            return 'Portal: '.Str::title($parts[1] ?? 'General');
        }

        return Str::title($parts[0] ?? 'General');
    }

    private function guessSummary(Route $route, string $method): string
    {
        $name = $route->getName();
        if ($name) {
            return Str::title(str_replace(['.', '-', '_'], ' ', Str::afterLast($name, '.')));
        }

        $actions = ['get' => 'List/Get', 'post' => 'Create', 'put' => 'Update', 'patch' => 'Update', 'delete' => 'Delete'];
        $uri = $route->uri();

        return ($actions[$method] ?? $method).' '.Str::title(basename($uri));
    }
}
