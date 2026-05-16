<?php

declare(strict_types=1);

use App\Http\Middleware\IdempotencyKey;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Cache::flush();
});

it('passes through unchanged when no idempotency key is supplied', function (): void {
    $middleware = new IdempotencyKey;
    $request = Request::create('/test', 'POST', [], [], [], []);

    $response = $middleware->handle($request, fn ($req) => new Response('first', 201));

    expect($response->getStatusCode())->toBe(201);
    expect($response->getContent())->toBe('first');
    expect($response->headers->get('X-Idempotency-Replay'))->toBeNull();
});

it('replays cached response on second request with same key, preserving status', function (): void {
    $middleware = new IdempotencyKey;
    $key = Str::uuid()->toString();

    $first = Request::create('/test', 'POST');
    $first->headers->set('Idempotency-Key', $key);
    $middleware->handle($first, fn ($req) => new Response('created', 201));

    $second = Request::create('/test', 'POST');
    $second->headers->set('Idempotency-Key', $key);
    $replay = $middleware->handle($second, fn ($req) => new Response('should-not-run', 500));

    expect($replay->getStatusCode())->toBe(201);
    expect($replay->getContent())->toBe('created');
    expect($replay->headers->get('X-Idempotency-Replay'))->toBe('true');
});

it('rejects malformed idempotency keys with 422', function (): void {
    $middleware = new IdempotencyKey;
    $request = Request::create('/test', 'POST');
    $request->headers->set('Idempotency-Key', 'not-a-uuid');

    $response = $middleware->handle($request, fn ($req) => new Response('should-not-run', 200));

    expect($response->getStatusCode())->toBe(422);
});

it('skips middleware for GET/HEAD/OPTIONS regardless of key', function (): void {
    $middleware = new IdempotencyKey;
    $key = Str::uuid()->toString();

    $get = Request::create('/test', 'GET');
    $get->headers->set('Idempotency-Key', $key);
    $response = $middleware->handle($get, fn ($req) => new Response('fresh', 200));

    expect($response->headers->get('X-Idempotency-Replay'))->toBeNull();
});

it('does not cache failed responses', function (): void {
    $middleware = new IdempotencyKey;
    $key = Str::uuid()->toString();

    $first = Request::create('/test', 'POST');
    $first->headers->set('Idempotency-Key', $key);
    $middleware->handle($first, fn ($req) => new Response('server error', 500));

    $second = Request::create('/test', 'POST');
    $second->headers->set('Idempotency-Key', $key);
    $second_response = $middleware->handle($second, fn ($req) => new Response('retry-ok', 201));

    expect($second_response->getStatusCode())->toBe(201);
    expect($second_response->getContent())->toBe('retry-ok');
    expect($second_response->headers->get('X-Idempotency-Replay'))->toBeNull();
});
