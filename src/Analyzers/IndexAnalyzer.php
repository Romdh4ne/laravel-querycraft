<?php

namespace Romdh4ne\QueryCraft\Analyzers;

use Illuminate\Support\Facades\DB;

class IndexAnalyzer
{
    protected $queries;

    public function __construct(array $queries)
    {
        $this->queries = $queries;
    }

    /**
     * Detect missing indexes
     */
    public function detect(): array
    {
        $issues = [];

        foreach ($this->queries as $query) {
            try {
                $explain = $this->explainQuery($query['sql'], $query['bindings']);

                if ($this->needsIndex($explain)) {
                    $issues[] = [
                        'type' => 'missing_index',
                        'severity' => $this->calculateSeverity($explain),
                        'query' => $query['sql'],
                        'table' => $explain['table'] ?? 'unknown',
                        'rows_examined' => $explain['rows'] ?? 0,
                        'suggestion' => $this->suggestIndex($query['sql']),
                    ];
                }
            } catch (\Exception $e) {
                // Skip queries that can't be explained
                continue;
            }
        }

        return $issues;
    }

    /**
     * Run EXPLAIN on query
     */
    protected function explainQuery(string $sql, array $bindings): array
    {
        $explained = DB::select("EXPLAIN " . $sql, $bindings);
        return (array) $explained[0];
    }

    /**
     * Check if query needs an index
     */
    protected function needsIndex(array $explain): bool
    {
        // Full table scan with many rows
        if (($explain['type'] ?? '') === 'ALL' && ($explain['rows'] ?? 0) > 1000) {
            return true;
        }

        // Using filesort (ORDER BY without index)
        if (isset($explain['Extra']) && str_contains($explain['Extra'], 'Using filesort')) {
            return true;
        }

        // Using temporary table
        if (isset($explain['Extra']) && str_contains($explain['Extra'], 'Using temporary')) {
            return true;
        }

        return false;
    }

    /**
     * Suggest index
     */
    protected function suggestIndex(string $sql): string
    {
        $columns = [];

        // Extract WHERE columns
        preg_match_all('/WHERE\s+(\w+)\s*[=<>]/i', $sql, $whereMatches);
        if (!empty($whereMatches[1])) {
            $columns = array_merge($columns, $whereMatches[1]);
        }

        // Extract ORDER BY columns
        preg_match_all('/ORDER\s+BY\s+(\w+)/i', $sql, $orderMatches);
        if (!empty($orderMatches[1])) {
            $columns = array_merge($columns, $orderMatches[1]);
        }

        $columns = array_unique($columns);

        if (!empty($columns)) {
            if (count($columns) === 1) {
                return "Add index: \$table->index('{$columns[0]}')";
            } else {
                $columnsStr = "'" . implode("', '", $columns) . "'";
                return "Add composite index: \$table->index([{$columnsStr}])";
            }
        }

        return "Consider adding an index";
    }

    /**
     * Calculate severity
     */
    protected function calculateSeverity(array $explain): string
    {
        $rows = $explain['rows'] ?? 0;

        if ($rows > 100000)
            return 'critical';
        if ($rows > 10000)
            return 'high';
        if ($rows > 1000)
            return 'medium';
        return 'low';
    }
}