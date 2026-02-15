<?php

namespace Romdh4ne\QueryCraft\Services;

use Illuminate\Support\Facades\Route;
use Romdh4ne\QueryCraft\Collectors\QueryCollector;
use Romdh4ne\QueryCraft\Analyzers\N1Detector;
use Romdh4ne\QueryCraft\Analyzers\IndexAnalyzer;
use Romdh4ne\QueryCraft\Analyzers\SlowQueryDetector;
use Romdh4ne\QueryCraft\Analyzers\DuplicateQueryDetector;
use Romdh4ne\QueryCraft\Analyzers\PerformanceScorer;

class QueryAnalysisService
{
    public function analyze(string $url, string $method = 'GET', array $options = []): array
    {
        if (!$this->routeExists($url, $method)) {
            return [
                'success' => false,
                'error' => "Route not found: {$method} {$url}",
                'error_type' => 'route_not_found',
                'suggestions' => $this->getSimilarRoutes($url),
            ];
        }

        $authCheck = $this->checkAuthentication($url, $method);

        QueryCollector::start();

        $requestResult = $this->makeRequest($url, $method, $options);
        QueryCollector::stop();

        // ── 500 server error ──────────────────────────────────────────────────
        if (($requestResult['error_type'] ?? '') === 'server_error') {
            $queries = QueryCollector::getQueries();
            return [
                'success' => false,
                'error_type' => 'server_error',
                'status' => $requestResult['status'] ?? 500,
                'error' => $requestResult['error'] ?? 'Server Error',
                'error_message' => $requestResult['error_message'] ?? null,
                'exception_class' => $requestResult['exception_class'] ?? null,
                'exception_file' => $requestResult['exception_file'] ?? null,
                'exception_line' => $requestResult['exception_line'] ?? null,
                'exception_trace' => $requestResult['exception_trace'] ?? null,
                'queries_captured' => count($queries),
            ];
        }

        // ── other errors ──────────────────────────────────────────────────────
        if (!$requestResult['success']) {
            return array_merge($requestResult, [
                'success' => false,
                'queries_captured' => QueryCollector::count(),
            ]);
        }

        // ── success ───────────────────────────────────────────────────────────
        $queries = QueryCollector::getQueries();
        dd($queries);
        $issues = $this->runAnalyzers($queries, $options['config'] ?? []);
        $score = $this->calculateScore($queries, $issues, $options['config'] ?? []);


        return [
            'success' => true,
            'status' => $requestResult['status'],
            'query_count' => count($queries),
            'total_time' => QueryCollector::totalTime(),
            'queries' => $queries,
            'issues' => $issues,
            'score' => $score,
            'statistics' => $this->getIssueStatistics($issues),
            'auth_required' => $authCheck['protected'],
        ];
    }

    protected function makeRequest(string $url, string $method, array $options): array
    {
        try {
            $kernel = app(\Illuminate\Contracts\Http\Kernel::class);

            $data = [];
            if (isset($options['body'])) {
                $data = is_string($options['body'])
                    ? (json_decode($options['body'], true) ?? [])
                    : $options['body'];
            }

            $request = \Illuminate\Http\Request::create($url, $method, $data);

            // ── Always force JSON response so we get parseable errors ──────────
            $request->headers->set('Accept', 'application/json');
            $request->headers->set('Content-Type', 'application/json');

            // Apply custom headers from user
            if (isset($options['headers'])) {
                foreach ($options['headers'] as $key => $value) {
                    $request->headers->set($key, $value);
                }
                // Re-force Accept after custom headers (user might have overwritten)
                $request->headers->set('Accept', 'application/json');
            }

            if (isset($options['user_id'])) {
                $this->authenticateAsUser($options['user_id']);
            }

            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);

            $status = $response->getStatusCode();
            $content = $response->getContent();

            // ── 500 ───────────────────────────────────────────────────────────
            if ($status >= 500) {
                $details = $this->parseErrorResponse($content);
                return [
                    'success' => false,
                    'status' => $status,
                    'error' => "Server Error (HTTP {$status})",
                    'error_type' => 'server_error',
                    'error_message' => $details['message'],
                    'exception_class' => $details['exception'],
                    'exception_file' => $details['file'],
                    'exception_line' => $details['line'],
                    'exception_trace' => $details['trace'],
                    'raw_content' => strlen($content) < 2000 ? $content : null,
                ];
            }

            if ($status === 401 || $status === 403) {
                return [
                    'success' => false,
                    'status' => $status,
                    'error' => "Authentication required (HTTP {$status})",
                    'error_type' => 'auth_required',
                ];
            }

            if ($status >= 400) {
                $details = $this->parseErrorResponse($content);

                return [
                    'success' => false,
                    'status' => $status,
                    'error' => "Client error (HTTP {$status})",
                    'error_type' => 'client_error',
                    'error_message' => $details['message'],
                ];
            }

            return [
                'success' => true,
                'status' => $status,
                'response' => $response,
            ];

        } catch (\Throwable $e) {
            // Exception thrown before kernel could build a response
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => 'server_error',
                'status' => 500,
                'error_message' => $e->getMessage(),
                'exception_class' => get_class($e),
                'exception_file' => str_replace(base_path() . '/', '', $e->getFile()),
                'exception_line' => $e->getLine(),
                'exception_trace' => $this->formatTrace($e->getTrace()),
            ];
        }
    }

    protected function parseErrorResponse(string $content): array
    {
        $defaults = [
            'message' => 'An unknown server error occurred.',
            'exception' => null,
            'file' => null,
            'line' => null,
            'trace' => null,
        ];

        if (empty(trim($content))) {
            return $defaults;
        }

        $json = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            // Filter trace to app files only
            $cleanTrace = isset($json['trace'])
                ? $this->cleanTrace($json['trace'])
                : [];

            // Find where it really crashed — first app file in trace
            $crashLocation = $this->findCrashLocation($json['trace'] ?? [], $json['file'] ?? null, $json['line'] ?? null);

            return [
                'message' => $json['message'] ?? $defaults['message'],
                'exception' => $json['exception'] ?? null,
                'file' => $crashLocation['file'],
                'line' => $crashLocation['line'],
                'trace' => $cleanTrace,
            ];
        }

        // HTML fallback (APP_DEBUG=false)
        $result = $defaults;

        if (preg_match('/<title>(.*?)<\/title>/is', $content, $m)) {
            $result['message'] = trim(strip_tags($m[1]));
        }

        if (str_contains($content, '<html') || str_contains($content, '<!DOCTYPE')) {
            $result['message'] .= ' — Set APP_DEBUG=true in .env for full details.';
        }

        return $result;
    }

    /**
     * Find the first app file that caused the crash.
     * Falls back to whatever Laravel reported if nothing found.
     */
    protected function findCrashLocation(array $trace, ?string $fallbackFile, ?int $fallbackLine): array
    {
        $basePath = base_path() . '/';

        foreach ($trace as $frame) {
            $file = $frame['file'] ?? '';

            if (empty($file))
                continue;
            if (str_contains($file, '/vendor/'))
                continue;
            if (str_contains($file, 'QueryCraft'))
                continue;

            return [
                'file' => str_replace($basePath, '', $file),
                'line' => $frame['line'] ?? null,
            ];
        }

        // Nothing found in trace — use what Laravel reported directly
        // but still clean the path
        return [
            'file' => $fallbackFile
                ? str_replace($basePath, '', $fallbackFile)
                : null,
            'line' => $fallbackLine,
        ];
    }

    protected function cleanTrace(array $trace): array
    {
        $basePath = base_path() . '/';

        $filtered = array_values(array_filter($trace, function ($frame) {
            $file = $frame['file'] ?? '';

            if (empty($file))
                return false;
            if (str_contains($file, '/vendor/'))
                return false;
            if (str_contains($file, 'QueryCraft'))
                return false;

            return true;
        }));

        return array_map(fn($f) => [
            'file' => isset($f['file'])
                ? str_replace($basePath, '', $f['file'])
                : null,
            'line' => $f['line'] ?? null,
            'function' => $f['function'] ?? null,
            'class' => $f['class'] ?? null,
        ], $filtered);
    }

    protected function formatTrace(array $trace): array
    {
        return $this->cleanTrace($trace);
    }

    public function routeExists(string $url, string $method): bool
    {
        $routes = Route::getRoutes();
        $path = parse_url($url, PHP_URL_PATH) ?? $url;

        foreach ($routes as $route) {
            if (in_array($method, $route->methods())) {
                try {
                    if ($route->matches(request()->create($path, $method))) {
                        return true;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return false;
    }

    public function getSimilarRoutes(string $url, int $limit = 5): array
    {
        $routes = Route::getRoutes();
        $similar = [];

        foreach ($routes as $route) {
            $uri = $route->uri();
            if (str_contains($uri, trim($url, '/')) || similar_text($url, $uri) > 5) {
                $similar[] = [
                    'methods' => $route->methods(),
                    'uri' => '/' . $uri,
                    'name' => $route->getName(),
                ];
                if (count($similar) >= $limit)
                    break;
            }
        }

        return $similar;
    }

    public function checkAuthentication(string $url, string $method): array
    {
        $routes = Route::getRoutes();
        $path = parse_url($url, PHP_URL_PATH) ?? $url;

        foreach ($routes as $route) {
            if (in_array($method, $route->methods())) {
                try {
                    if ($route->matches(request()->create($path, $method))) {
                        $middleware = $route->middleware();
                        $authMiddleware = ['auth', 'auth:sanctum', 'auth:api', 'auth:web'];
                        return [
                            'protected' => !empty(array_intersect($middleware, $authMiddleware)),
                            'middleware' => $middleware,
                        ];
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return ['protected' => false, 'middleware' => []];
    }

    protected function authenticateAsUser(int $userId): void
    {
        $user = \App\Models\User::find($userId);
        if ($user)
            auth()->login($user);
    }

    protected function runAnalyzers(array $queries, array $config = []): array
    {
        $issues = [];

        $n1Threshold = $config['thresholds']['n1_count'] ?? config('querycraft.thresholds.n1_count', 5);
        $slowThreshold = $config['thresholds']['slow_query_ms'] ?? config('querycraft.thresholds.slow_query_ms', 100);
        $duplicateThreshold = $config['thresholds']['duplicate_count'] ?? config('querycraft.thresholds.duplicate_count', 2);

        $detectors = $config['detectors'] ?? config('querycraft.detectors');

        if ($detectors['n1'] ?? true) {
            $issues = array_merge($issues, (new N1Detector($queries, $n1Threshold))->detect());
        }

        if ($detectors['missing_index'] ?? true) {
            $issues = array_merge($issues, (new IndexAnalyzer($queries))->detect());
        }

        if ($detectors['slow_query'] ?? true) {
            $issues = array_merge($issues, (new SlowQueryDetector($queries, $slowThreshold))->detect());
        }

        if ($detectors['duplicate_query'] ?? true) {
            $issues = array_merge($issues, (new DuplicateQueryDetector($queries, $duplicateThreshold))->detect());
        }

        usort($issues, function ($a, $b) {
            $order = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return ($order[$a['severity']] ?? 99) <=> ($order[$b['severity']] ?? 99);
        });

        return $issues;
    }

    protected function calculateScore(array $queries, array $issues, array $config = []): array
    {
        $weights = $config['weights'] ?? config('querycraft.weights');
        return (new PerformanceScorer($queries, $issues, $weights))->calculate();
    }

    public function getIssueStatistics(array $issues): array
    {
        return [
            'total' => count($issues),
            'critical' => count(array_filter($issues, fn($i) => $i['severity'] === 'critical')),
            'high' => count(array_filter($issues, fn($i) => $i['severity'] === 'high')),
            'medium' => count(array_filter($issues, fn($i) => $i['severity'] === 'medium')),
            'low' => count(array_filter($issues, fn($i) => $i['severity'] === 'low')),
            'by_type' => array_count_values(array_column($issues, 'type')),
        ];
    }
}