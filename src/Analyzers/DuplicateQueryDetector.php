<?php

namespace Romdh4ne\QueryCraft\Analyzers;

class DuplicateQueryDetector
{
    protected $queries;

    public function __construct(array $queries)
    {
        $this->queries = $queries;
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
            if (count($executions) > 1) {
                $totalTime = array_sum(array_column(array_column($executions, 'query'), 'time'));

                $issues[] = [
                    'type' => 'duplicate_query',
                    'severity' => 'medium',
                    'query' => $executions[0]['query']['sql'],
                    'bindings' => $executions[0]['query']['bindings'],
                    'count' => count($executions),
                    'total_time' => round($totalTime, 2),
                    'suggestion' => "Cache this query result - executed " . count($executions) . " times with identical parameters",
                ];
            }
        }

        return $issues;
    }
}