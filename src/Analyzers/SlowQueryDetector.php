<?php

namespace Romdh4ne\QueryCraft\Analyzers;

class SlowQueryDetector
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

    public function __construct(array $queries, int $threshold = 100)
    {
        $this->queries = $queries;
        $this->threshold = $threshold;
    }

    /**
     * Detect slow queries
     */
    public function detect(): array
    {
        $issues = [];

        foreach ($this->queries as $query) {
            if ($query['time'] > $this->threshold) {
                $issues[] = [
                    'type' => 'slow_query',
                    'severity' => $this->calculateSeverity($query['time']),
                    'query' => $query['sql'],
                    'time' => round($query['time'], 2),
                    'threshold' => $this->threshold,
                    'location' => $this->findSourceLocation($query['backtrace'] ?? []),
                    'suggestion' => $this->analyzeSlow($query),
                ];
            }
        }

        return $issues;
    }

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



    /**
     * Calculate severity
     */
    protected function calculateSeverity(float $time): string
    {
        if ($time > 1000)
            return 'critical'; // > 1 second
        if ($time > 500)
            return 'high';      // > 500ms
        if ($time > 200)
            return 'medium';    // > 200ms
        return 'low';
    }

    /**
     * Analyze slow query
     */
    protected function analyzeSlow(array $query): string
    {
        $sql = strtolower($query['sql']);

        if (str_contains($sql, 'select *')) {
            return "Use specific columns instead of SELECT *";
        }

        if (str_contains($sql, 'order by') && !str_contains($sql, 'limit')) {
            return "Add LIMIT to ORDER BY query";
        }

        if (str_contains($sql, 'like')) {
            return "LIKE queries are slow - consider full-text search or different approach";
        }

        if (str_contains($sql, 'count(*)')) {
            return "COUNT(*) on large tables is slow - consider caching or approximation";
        }

        return "Consider adding an index or optimizing this query";
    }
}