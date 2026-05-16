<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

/**
 * Generates a Postman Collection v2.1 from registered API routes.
 * Includes: auth headers, variables, example requests.
 */
class GeneratePostmanCollectionCommand extends Command
{
    protected $signature = 'api:postman {--output=public/postman-collection.json}';

    protected $description = 'Generate Postman Collection v2.1 from API routes';

    public function __construct(private readonly Router $router)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $collection = [
            'info' => [
                'name' => 'Muhasebi API',
                'description' => 'Cloud Accounting System for Egyptian Accounting Firms',
                '_postman_id' => Str::uuid()->toString(),
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [['key' => 'token', 'value' => '{{auth_token}}', 'type' => 'string']],
            ],
            'variable' => [
                ['key' => 'base_url', 'value' => config('app.url').'/api/v1'],
                ['key' => 'auth_token', 'value' => ''],
                ['key' => 'tenant_id', 'value' => '1'],
            ],
            'item' => [],
        ];

        $routes = collect($this->router->getRoutes()->getRoutes())
            ->filter(fn (Route $r) => Str::startsWith($r->uri(), 'api/v1'))
            ->sortBy(fn (Route $r) => $r->uri());

        $folders = [];

        foreach ($routes as $route) {
            $methods = collect($route->methods())->filter(fn ($m) => $m !== 'HEAD')->values();
            $uri = $route->uri();
            $path = Str::after($uri, 'api/v1/');
            $folder = $this->guessFolder($path);

            foreach ($methods as $method) {
                $item = [
                    'name' => $this->buildName($route, strtolower($method)),
                    'request' => [
                        'method' => strtoupper($method),
                        'url' => [
                            'raw' => '{{base_url}}/'.preg_replace('/\{(\w+)\}/', ':$1', $path),
                            'host' => ['{{base_url}}'],
                            'path' => explode('/', preg_replace('/\{(\w+)\}/', ':$1', $path)),
                        ],
                        'header' => [
                            ['key' => 'Accept', 'value' => 'application/json'],
                            ['key' => 'Content-Type', 'value' => 'application/json'],
                            ['key' => 'Accept-Language', 'value' => 'ar'],
                            ['key' => 'X-Tenant', 'value' => '{{tenant_id}}'],
                        ],
                    ],
                ];

                // Add body for POST/PUT/PATCH
                if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
                    $item['request']['body'] = [
                        'mode' => 'raw',
                        'raw' => '{}',
                        'options' => ['raw' => ['language' => 'json']],
                    ];
                }

                // Add path variables
                preg_match_all('/\{(\w+)\}/', $uri, $matches);
                if (! empty($matches[1])) {
                    $item['request']['url']['variable'] = array_map(fn ($p) => [
                        'key' => $p,
                        'value' => '1',
                        'description' => ucfirst($p).' ID',
                    ], $matches[1]);
                }

                $folders[$folder][] = $item;
            }
        }

        // Build folder structure
        foreach ($folders as $name => $items) {
            $collection['item'][] = [
                'name' => $name,
                'item' => $items,
            ];
        }

        $outputPath = $this->option('output');
        file_put_contents(base_path($outputPath), json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $routeCount = $routes->count();
        $folderCount = count($folders);
        $this->info("Postman collection generated: {$outputPath} ({$routeCount} requests, {$folderCount} folders)");

        return self::SUCCESS;
    }

    private function guessFolder(string $path): string
    {
        $parts = explode('/', $path);

        if ($parts[0] === 'admin') {
            return 'Admin: '.Str::title($parts[1] ?? 'General');
        }
        if ($parts[0] === 'portal') {
            return 'Portal: '.Str::title($parts[1] ?? 'General');
        }

        return Str::title($parts[0] ?? 'General');
    }

    private function buildName(Route $route, string $method): string
    {
        if ($route->getName()) {
            return Str::title(str_replace(['.', '-', '_'], ' ', Str::afterLast($route->getName(), '.')));
        }

        $actions = ['get' => 'List/Get', 'post' => 'Create', 'put' => 'Update', 'delete' => 'Delete'];

        return ($actions[$method] ?? $method).' '.basename($route->uri());
    }
}
