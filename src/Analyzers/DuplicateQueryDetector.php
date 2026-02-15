<?php

namespace Romdh4ne\QueryCraft\Analyzers;

class DuplicateQueryDetector
{
    protected $queries;
    protected $threshold;

    protected array $skipPaths = [
        '/vendor/',
        '/vendor/laravel/',
        'Illuminate/',
        'Sanctum/',
        'QueryCraft/',
        'QueryCollector',
    ];

    public function __construct(array $queries, int $threshold = 2)
    {
        $this->queries = $queries;
        $this->threshold = $threshold;
    }

    /**
     * Detect duplicate queries
     */
    public function detect(): array
    {
        $issues = [];
        $queryMap = [];

        foreach ($this->queries as $index => $query) {
            // Create fingerprint with bindings
            $fingerprint = md5($query['sql'] . json_encode($query['bindings']));

            if (!isset($queryMap[$fingerprint])) {
                $queryMap[$fingerprint] = [];
            }

            $queryMap[$fingerprint][] = [
                'index' => $index,
                'query' => $query,
            ];
        }

        // Find duplicates (identical query + bindings)
        foreach ($queryMap as $fingerprint => $executions) {
            if (count($executions) >= $this->threshold) {
                $totalTime = array_sum(array_column(array_column($executions, 'query'), 'time'));

                $issues[] = [
                    'type' => 'duplicate_query',
                    'severity' => 'medium',
                    'query' => $executions[0]['query']['sql'],
                    'bindings' => $executions[0]['query']['bindings'],
                    'count' => count($executions),
                    'total_time' => round($totalTime, 2),
                    'location' => $this->findSourceLocation($executions[0]['query']['backtrace'] ?? []),
                    'suggestion' => "Cache this query result - executed " . count($executions) . " times with identical parameters",
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
}