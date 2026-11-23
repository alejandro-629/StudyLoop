<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

it('reports ok when the database and cache answer', function (): void {
    $this->getJson('/health')
        ->assertOk()
        ->assertExactJson([
            'status' => 'ok',
            'checks' => ['database' => 'ok', 'cache' => 'ok'],
            'queue' => config('queue.default'),
        ]);
});

it('returns 503 without leaking details when the database is unreachable', function (): void {
    config(['database.connections.sqlite.database' => '/nonexistent/studyloop/health.sqlite']);
    DB::purge('sqlite');

    $response = $this->getJson('/health')
        ->assertStatus(503)
        ->assertJsonPath('status', 'degraded')
        ->assertJsonPath('checks.database', 'fail')
        ->assertJsonPath('checks.cache', 'ok');

    expect($response->getContent())->not->toContain('nonexistent');
});

it('returns 503 when the cache store fails', function (): void {
    Cache::shouldReceive('put')->andThrow(new RuntimeException('cache down'));

    $this->getJson('/health')
        ->assertStatus(503)
        ->assertJsonPath('checks.cache', 'fail')
        ->assertJsonPath('checks.database', 'ok');
});
