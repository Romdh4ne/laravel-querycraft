<?php

namespace Romdh4ne\QueryCraft\Analyzers;

class PerformanceScorer
{
    protected $queries;
    protected $issues;
    protected $totalTime;
    protected $weights;




    public function __construct(array $queries, array $issues = [], array $weights = [])
    {
        $this->queries = $queries;
        $this->issues = $issues;
        $this->totalTime = array_sum(array_column($queries, 'time'));
        $this->weights = [
            'query_count' => $weights['query_count'] ?? 40,
            'query_time' => $weights['query_time'] ?? 30,
            'issues' => $weights['issues'] ?? 30,
        ];
    }

    /**
     * Calculate overall performance score
     */
    public function calculate(): array
    {
        $scores = [
            'query_count' => $this->scoreQueryCount(),
            'query_time' => $this->scoreQueryTime(),
            'issues' => $this->scoreIssues(),
        ];

        $totalScore =
            ($scores['query_count'] * $this->weights['query_count'] / 100) +
            ($scores['query_time'] * $this->weights['query_time'] / 100) +
            ($scores['issues'] * $this->weights['issues'] / 100);


        return [
            'score' => round($totalScore),
            'grade' => $this->getGrade($totalScore),
            'breakdown' => $scores,
            'suggestions' => $this->getSuggestions($scores),
        ];
    }




    /**
     * Score based on query count
     */
    protected function scoreQueryCount(): int
    {
        $count = count($this->queries);

        if ($count === 0)
            return 100;
        if ($count <= 5)
            return 100;
        if ($count <= 10)
            return 90;
        if ($count <= 20)
            return 75;
        if ($count <= 30)
            return 60;
        if ($count <= 50)
            return 40;
        if ($count <= 100)
            return 20;

        return 0;
    }

    /**
     * Score based on total query time
     */
    protected function scoreQueryTime(): int
    {
        $time = $this->totalTime;

        if ($time === 0)
            return 100;
        if ($time < 50)
            return 100;
        if ($time < 100)
            return 90;
        if ($time < 200)
            return 75;
        if ($time < 500)
            return 50;
        if ($time < 1000)
            return 25;

        return 0;
    }

    /**
     * Score based on issues found
     */
    protected function scoreIssues(): int
    {
        $criticalCount = count(array_filter($this->issues, fn($i) => $i['severity'] === 'critical'));
        $highCount = count(array_filter($this->issues, fn($i) => $i['severity'] === 'high'));
        $mediumCount = count(array_filter($this->issues, fn($i) => $i['severity'] === 'medium'));

        $penalty = ($criticalCount * 40) + ($highCount * 20) + ($mediumCount * 10);

        return max(0, 100 - $penalty);
    }

    /**
     * Get letter grade
     */
    protected function getGrade(float $score): string
    {
        if ($score >= 90)
            return 'A';
        if ($score >= 80)
            return 'B';
        if ($score >= 70)
            return 'C';
        if ($score >= 60)
            return 'D';

        return 'F';
    }

    /**
     * Get improvement suggestions
     */
    protected function getSuggestions(array $scores): array
    {
        $suggestions = [];

        if ($scores['query_count'] < 80) {
            $suggestions[] = [
                'issue' => 'Too many queries',
                'impact' => 100 - $scores['query_count'],
                'fix' => 'Use eager loading to reduce query count',
            ];
        }

        if ($scores['query_time'] < 80) {
            $suggestions[] = [
                'issue' => 'Slow query execution time',
                'impact' => 100 - $scores['query_time'],
                'fix' => 'Add indexes or optimize queries',
            ];
        }

        if ($scores['issues'] < 100) {
            $suggestions[] = [
                'issue' => count($this->issues) . ' performance issues detected',
                'impact' => 100 - $scores['issues'],
                'fix' => 'Fix critical and high severity issues first',
            ];
        }

        // Sort by impact
        usort($suggestions, function ($a, $b) {
            return $b['impact'] <=> $a['impact'];
        });

        return $suggestions;
    }

    /**
     * Get emoji for score
     */
    public static function getEmoji(int $score): string
    {
        if ($score >= 90)
            return '🟢';
        if ($score >= 70)
            return '🟡';
        if ($score >= 50)
            return '🟠';

        return '🔴';
    }

    /**
     * Get badge color
     */
    public static function getBadgeColor(int $score): string
    {
        if ($score >= 90)
            return 'brightgreen';
        if ($score >= 80)
            return 'green';
        if ($score >= 70)
            return 'yellowgreen';
        if ($score >= 60)
            return 'yellow';
        if ($score >= 50)
            return 'orange';

        return 'red';
    }
}