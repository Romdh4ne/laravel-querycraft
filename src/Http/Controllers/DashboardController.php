<?php

namespace Romdh4ne\QueryCraft\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Romdh4ne\QueryCraft\Services\QueryAnalysisService;

class DashboardController extends Controller
{
    protected $analysisService;

    public function __construct(QueryAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    public function index()
    {
        return view('querycraft::dashboard');
    }

    public function analyze(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|string',
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'headers' => 'nullable|array',
            'body' => 'nullable|string',
        ]);

        $result = $this->analysisService->analyze(
            $validated['url'],
            $validated['method'],
            [
                'headers' => $validated['headers'] ?? [],
                'body' => $validated['body'] ?? null,
                'config' => $this->loadConfig(),
            ]
        );

        if (($result['error_type'] ?? '') === 'client_error') {
            return response()->json([
                'success' => false,
                'error_type' => 'client_error',
                'status' => $result['status'],
                'error' => $result['error'],
                'error_message' => $result['error_message'] ?? null,
            ], 200);
        }

        if (($result['error_type'] ?? '') === 'server_error') {
            return response()->json([
                'success' => false,
                'error_type' => 'server_error',
                'status' => $result['status'] ?? 500,
                'error' => $result['error'] ?? 'Server Error',
                'error_message' => $result['error_message'] ?? null,
                'exception_class' => $result['exception_class'] ?? null,
                'exception_file' => $result['exception_file'] ?? null,
                'exception_line' => $result['exception_line'] ?? null,
                'exception_trace' => $result['exception_trace'] ?? null,
                'queries_captured' => $result['queries_captured'] ?? 0,
            ], 200);
        }

        if (($result['error_type'] ?? '') === 'route_not_found') {
            return response()->json([
                'success' => false,
                'error_type' => 'route_not_found',
                'error' => $result['error'],
                'suggestions' => $result['suggestions'] ?? [],
            ], 200);
        }

        if (($result['error_type'] ?? '') === 'auth_required') {
            return response()->json([
                'success' => false,
                'error_type' => 'auth_required',
                'error' => $result['error'],
                'status' => $result['status'] ?? 401,
            ], 200);
        }

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error_type' => $result['error_type'] ?? 'unknown',
                'error' => $result['error'] ?? 'Something went wrong',
            ], 200);
        }

        return response()->json([
            'success' => true,
            'analysis' => [
                'query_count' => $result['query_count'],
                'total_time' => $result['total_time'],
                'queries' => $result['queries'],
                'issues' => $result['issues'],
                'score' => $result['score'],
                'statistics' => $this->analysisService->getIssueStatistics($result['issues']),
            ],
        ], 200);
    }

    // ── Config endpoints ──────────────────────────────────────────────────────

    public function getConfig()
    {
        return response()->json([
            'success' => true,
            'config' => $this->loadConfig(),
        ]);
    }

    public function saveConfig(Request $request)
    {
        $validated = $request->validate([
            'detectors' => 'required|array',
            'detectors.n1' => 'required|boolean',
            'detectors.slow_query' => 'required|boolean',
            'detectors.missing_index' => 'required|boolean',
            'detectors.duplicate_query' => 'required|boolean',
            'thresholds' => 'required|array',
            'thresholds.n1_count' => 'required|integer|min:2|max:50',
            'thresholds.slow_query_ms' => 'required|integer|min:10|max:5000',
            'thresholds.duplicate_count' => 'required|integer|min:2|max:20',
            'weights' => 'required|array',
            'weights.query_count' => 'required|integer|min:0|max:100',
            'weights.query_time' => 'required|integer|min:0|max:100',
            'weights.issues' => 'required|integer|min:0|max:100',
        ]);

        // Write back to .env
        $this->writeEnv([
            'QUERYCRAFT_DETECTOR_N1' => $validated['detectors']['n1'] ? 'true' : 'false',
            'QUERYCRAFT_DETECTOR_SLOW_QUERY' => $validated['detectors']['slow_query'] ? 'true' : 'false',
            'QUERYCRAFT_DETECTOR_MISSING_INDEX' => $validated['detectors']['missing_index'] ? 'true' : 'false',
            'QUERYCRAFT_DETECTOR_DUPLICATE_QUERY' => $validated['detectors']['duplicate_query'] ? 'true' : 'false',
            'QUERY_DEBUGGER_N1_THRESHOLD' => $validated['thresholds']['n1_count'],
            'QUERY_DEBUGGER_SLOW_THRESHOLD' => $validated['thresholds']['slow_query_ms'],
            'QUERYCRAFT_DUPLICATE_COUNT' => $validated['thresholds']['duplicate_count'],
            'QUERYCRAFT_WEIGHT_QUERY_COUNT' => $validated['weights']['query_count'],
            'QUERYCRAFT_WEIGHT_QUERY_TIME' => $validated['weights']['query_time'],
            'QUERYCRAFT_WEIGHT_ISSUES' => $validated['weights']['issues'],
        ]);

        // Clear config cache so changes take effect immediately
        try {
            \Artisan::call('config:clear');
        } catch (\Throwable $e) {
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuration saved.',
            'config' => $this->loadConfig(),
        ]);
    }

    public function resetConfig()
    {
        $this->writeEnv([
            'QUERYCRAFT_DETECTOR_N1' => 'true',
            'QUERYCRAFT_DETECTOR_SLOW_QUERY' => 'true',
            'QUERYCRAFT_DETECTOR_MISSING_INDEX' => 'true',
            'QUERYCRAFT_DETECTOR_DUPLICATE_QUERY' => 'true',
            'QUERY_DEBUGGER_N1_THRESHOLD' => '5',
            'QUERY_DEBUGGER_SLOW_THRESHOLD' => '100',
            'QUERYCRAFT_DUPLICATE_COUNT' => '2',
            'QUERYCRAFT_WEIGHT_QUERY_COUNT' => '40',
            'QUERYCRAFT_WEIGHT_QUERY_TIME' => '30',
            'QUERYCRAFT_WEIGHT_ISSUES' => '30',
        ]);

        try {
            \Artisan::call('config:clear');
        } catch (\Throwable $e) {
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuration reset to defaults.',
            'config' => $this->loadConfig(),
        ]);
    }

    public function routes(Request $request)
    {
        $search = $request->get('search', '');

        if (empty($search)) {
            return response()->json(['routes' => []]);
        }

        return response()->json([
            'routes' => $this->analysisService->getSimilarRoutes($search, 10),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function loadConfig(): array
    {
        return [
            'detectors' => config('querycraft.detectors'),
            'thresholds' => config('querycraft.thresholds'),
            'weights' => config('querycraft.weights'),
        ];
    }

    /**
     * Update specific keys in the .env file without touching anything else.
     */
    protected function writeEnv(array $values): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $env = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            if (preg_match("/^{$key}=.*/m", $env)) {
                $env = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $env
                );
            } else {
                $env .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $env);
    }
}