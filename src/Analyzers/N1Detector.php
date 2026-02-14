<?php

namespace Romdh4ne\QueryCraft\Analyzers;

class N1Detector
{
    protected $queries;
    protected $threshold;

    // Paths to skip when looking for app code
    protected array $skipPaths = [
        '/vendor/',
        '/vendor/laravel/',
        'Illuminate/',
        'Sanctum/',
        'QueryCraft/',
        'QueryCollector',
    ];

    public function __construct(array $queries, int $threshold = 5)
    {
        $this->queries = $queries;
        $this->threshold = $threshold;
    }

    public function detect(): array
    {
        $patterns = [];
        $issues = [];

        foreach ($this->queries as $index => $query) {
            $normalized = $this->normalizeQuery($query['sql']);

            if (!isset($patterns[$normalized])) {
                $patterns[$normalized] = [];
            }

            $patterns[$normalized][] = [
                'index' => $index,
                'query' => $query,
            ];
        }

        foreach ($patterns as $pattern => $executions) {
            if (count($executions) >= $this->threshold) {
                $issue = $this->analyzePattern($pattern, $executions);
                if ($issue) {
                    $issues[] = $issue;
                }
            }
        }

        return $issues;
    }

    protected function normalizeQuery(string $sql): string
    {
        $normalized = preg_replace('/\b\d+\b/', '?', $sql);
        $normalized = preg_replace("/'[^']*'/", '?', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim(strtolower($normalized));
    }

    protected function analyzePattern(string $pattern, array $executions): ?array
    {
        $firstQuery = $executions[0]['query'];
        $count = count($executions);
        $totalTime = array_sum(array_column(array_column($executions, 'query'), 'time'));
        $avgTime = $totalTime / $count;

        $location = $this->findSourceLocation($firstQuery['backtrace'] ?? []);
        $suggestion = $this->generateSuggestion($firstQuery['sql']);

        return [
            'type' => 'n+1',
            'severity' => $this->calculateSeverity($count, $totalTime),
            'count' => $count,
            'total_time' => round($totalTime, 2),
            'avg_time' => round($avgTime, 2),
            'query' => $firstQuery['sql'],
            'location' => $location,
            'suggestion' => $suggestion,
        ];
    }

    /**
     * Walk the backtrace and find the first frame that belongs to APP code,
     * not to any vendor / framework / package path.
     */
    protected function findSourceLocation(array $backtrace): array
    {
        $basePath = base_path();

        foreach ($backtrace as $frame) {
            $file = $frame['file'] ?? '';
            $line = $frame['line'] ?? 0;

            if (empty($file)) {
                continue;
            }

            // Skip every vendor / framework / package frame
            if ($this->shouldSkip($file)) {
                continue;
            }

            // Relativise path so it's readable
            $relativePath = str_replace($basePath, '', $file);

            return [
                'file' => $relativePath,
                'line' => $line,
            ];
        }

        // Second pass: if nothing found, look for any frame inside
        // the project root (i.e., NOT in vendor/)
        foreach ($backtrace as $frame) {
            $file = $frame['file'] ?? '';
            if (
                !empty($file)
                && str_starts_with($file, $basePath)
                && !str_contains($file, '/vendor/')
            ) {
                return [
                    'file' => str_replace($basePath, '', $file),
                    'line' => $frame['line'] ?? 0,
                ];
            }
        }

        return ['file' => 'Unknown', 'line' => 0];
    }

    /**
     * Returns true if this file should be skipped (framework / vendor).
     */
    protected function shouldSkip(string $file): bool
    {
        foreach ($this->skipPaths as $skip) {
            if (str_contains($file, $skip)) {
                return true;
            }
        }
        return false;
    }

    protected function generateSuggestion(string $sql): string
    {
        preg_match('/from\s+`?(\w+)`?/i', $sql, $matches);
        $table = $matches[1] ?? 'table';
        $relationship = rtrim($table, 's');

        return "Add eager loading: ->with('{$relationship}')";
    }

    protected function calculateSeverity(int $count, float $totalTime): string
    {
        if ($count > 50 || $totalTime > 1000)
            return 'critical';
        if ($count > 20 || $totalTime > 500)
            return 'high';
        if ($count > 10 || $totalTime > 200)
            return 'medium';
        return 'low';
    }
}