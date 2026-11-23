<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Liveness and readiness in one response: 200 when every dependency answers,
 * 503 otherwise. Failure details go to the log, never to the client.
 */
final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check('database', function (): void {
                DB::connection()->select('select 1');
            }),
            'cache' => $this->check('cache', function (): void {
                $key = 'health:'.Str::ulid();
                Cache::put($key, 'ok', 10);
                $ok = Cache::pull($key) === 'ok';

                if (! $ok) {
                    throw new \RuntimeException('Cache round trip returned an unexpected value.');
                }
            }),
        ];

        $healthy = ! in_array('fail', $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'queue' => config('queue.default'),
        ], $healthy ? 200 : 503);
    }

    /**
     * @param  Closure(): void  $probe
     */
    private function check(string $name, Closure $probe): string
    {
        try {
            $probe();

            return 'ok';
        } catch (Throwable $e) {
            Log::warning('Health check failed.', ['check' => $name, 'exception' => $e::class]);

            return 'fail';
        }
    }
}
