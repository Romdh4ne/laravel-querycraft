<?php

namespace Romdh4ne\QueryCraft\Analyzers;

class SlowQueryDetector
{
    protected $queries;
    protected $threshold;

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
                    'suggestion' => $this->analyzeSlow($query),
                ];
            }
        }

        return $issues;
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