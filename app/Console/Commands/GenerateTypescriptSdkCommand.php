<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Generates a TypeScript API client SDK from the OpenAPI spec.
 * Outputs a single .ts file with typed methods for all endpoints.
 */
class GenerateTypescriptSdkCommand extends Command
{
    protected $signature = 'api:typescript-sdk
        {--spec=public/api-docs.json : OpenAPI spec file}
        {--output=docs/muhasebi-sdk.ts : Output TypeScript file}';

    protected $description = 'Generate TypeScript SDK from OpenAPI spec';

    public function handle(): int
    {
        $specPath = base_path($this->option('spec'));

        if (! file_exists($specPath)) {
            $this->error('OpenAPI spec not found. Run: php artisan api:docs');

            return self::FAILURE;
        }

        $spec = json_decode(file_get_contents($specPath), true);
        $paths = $spec['paths'] ?? [];

        $ts = $this->buildHeader();
        $ts .= $this->buildClientClass($paths);

        $outputPath = $this->option('output');
        $fullPath = base_path($outputPath);
        $dir = dirname($fullPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, $ts);

        $methodCount = $this->countMethods($paths);
        $this->info("TypeScript SDK generated: {$outputPath} ({$methodCount} methods)");

        return self::SUCCESS;
    }

    private function buildHeader(): string
    {
        return <<<'TS'
/**
 * Muhasebi API TypeScript SDK
 * Auto-generated from OpenAPI specification.
 * Do not edit manually — regenerate with: php artisan api:typescript-sdk
 *
 * Usage:
 *   const api = new MuhasebiApi('https://api.muhasebi.com/api/v1', 'your-token');
 *   const invoices = await api.invoices.list({ page: 1 });
 */

export interface ApiConfig {
  baseUrl: string;
  token?: string;
  tenantId?: string;
  locale?: 'ar' | 'en';
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: { current_page: number; last_page: number; total: number };
}

async function request<T>(
  config: ApiConfig,
  method: string,
  path: string,
  body?: any,
  params?: Record<string, string>,
): Promise<T> {
  const url = new URL(config.baseUrl + path);
  if (params) {
    Object.entries(params).forEach(([k, v]) => { if (v) url.searchParams.set(k, v); });
  }

  const headers: Record<string, string> = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Accept-Language': config.locale || 'ar',
  };
  if (config.token) headers['Authorization'] = `Bearer ${config.token}`;
  if (config.tenantId) headers['X-Tenant'] = config.tenantId;

  const res = await fetch(url.toString(), {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
  });

  if (!res.ok) {
    const error = await res.json().catch(() => ({ message: res.statusText }));
    throw new ApiError(res.status, error.message || res.statusText, error.errors);
  }

  return res.json();
}

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public errors?: Record<string, string[]>,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}


TS;
    }

    private function buildClientClass(array $paths): string
    {
        $groups = [];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                $tags = $operation['tags'] ?? ['General'];
                $tag = Str::camel(str_replace([':', ' '], ['_', '_'], strtolower($tags[0])));
                $operationId = $operation['operationId'] ?? $this->generateOperationId($method, $path);
                $methodName = Str::camel(Str::afterLast($operationId, '.'));

                // Clean path: replace {param} with ${param} for template literals
                $tsPath = preg_replace('/\{(\w+)\}/', '\${$1}', $path);
                $hasPathParams = preg_match_all('/\{(\w+)\}/', $path, $paramMatches);
                $params = $hasPathParams ? $paramMatches[1] : [];

                $paramList = array_map(fn ($p) => "{$p}: string | number", $params);
                $hasBody = in_array(strtoupper($method), ['POST', 'PUT', 'PATCH']);

                if ($hasBody) {
                    $paramList[] = 'body?: any';
                }
                $paramList[] = 'params?: Record<string, string>';

                $paramString = implode(', ', $paramList);

                $groups[$tag][] = [
                    'name' => $methodName,
                    'method' => strtoupper($method),
                    'path' => $tsPath,
                    'params' => $paramString,
                    'hasBody' => $hasBody,
                    'summary' => $operation['summary'] ?? '',
                ];
            }
        }

        $ts = "export class MuhasebiApi {\n";
        $ts .= "  private config: ApiConfig;\n\n";
        $ts .= "  constructor(baseUrl: string, token?: string, tenantId?: string, locale?: 'ar' | 'en') {\n";
        $ts .= "    this.config = { baseUrl, token, tenantId, locale };\n";
        $ts .= "  }\n\n";
        $ts .= "  setToken(token: string) { this.config.token = token; }\n";
        $ts .= "  setTenant(tenantId: string) { this.config.tenantId = tenantId; }\n\n";

        foreach ($groups as $group => $methods) {
            $ts .= "  /** {$group} */\n";
            $ts .= "  {$group} = {\n";

            foreach ($methods as $m) {
                $summary = $m['summary'] ? "/** {$m['summary']} */\n    " : '';
                $bodyArg = $m['hasBody'] ? ', body' : '';
                $ts .= "    {$summary}{$m['name']}: ({$m['params']}): Promise<any> =>\n";
                $ts .= "      request(this.config, '{$m['method']}', `{$m['path']}`{$bodyArg}, params),\n\n";
            }

            $ts .= "  };\n\n";
        }

        $ts .= "}\n";

        return $ts;
    }

    private function generateOperationId(string $method, string $path): string
    {
        return Str::camel($method.'_'.str_replace(['/', '{', '}'], ['_', '', ''], $path));
    }

    private function countMethods(array $paths): int
    {
        $count = 0;
        foreach ($paths as $methods) {
            $count += count($methods);
        }

        return $count;
    }
}
