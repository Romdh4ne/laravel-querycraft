<?php

namespace Romdh4ne\QueryCraft\Commands;

use Illuminate\Console\Command;
use Romdh4ne\QueryCraft\Services\QueryAnalysisService;
use Romdh4ne\QueryCraft\Analyzers\PerformanceScorer;

class AnalyzeCommand extends Command
{
    protected $signature = 'querycraft:analyze 
                            {--url= : URL to analyze}
                            {--method=GET : HTTP method}
                            {--show-queries : Show all executed queries}
                            {--user= : Authenticate as user ID}';

    protected $description = 'Analyze database queries for performance issues';

    protected $analysisService;

    /**
     * Inject the service
     */
    public function __construct(QueryAnalysisService $analysisService)
    {
        parent::__construct();
        $this->analysisService = $analysisService;
    }

    public function handle()
    {
        $url = $this->option('url');
        $method = strtoupper($this->option('method'));

        if (!$url) {
            $this->showUsageExamples();
            return 1;
        }

        $this->info('🔍 Analyzing: ' . $method . ' ' . $url);
        $this->newLine();

        // Prepare options
        $options = [];

        if ($userId = $this->option('user')) {
            $options['user_id'] = (int) $userId;
            $this->info("🔐 Authenticating as user ID: {$userId}");
            $this->newLine();
        }

        // Use service to analyze
        $result = $this->analysisService->analyze($url, $method, $options);

        // Handle errors
        if (!$result['success']) {
            $this->handleError($result);
            return 1;
        }

        // Display results
        $this->displayResults($result);

        return 0;
    }

    protected function handleError(array $result)
    {
        $this->error('❌ ' . $result['error']);
        $this->newLine();

        // Show suggestions if available
        if (!empty($result['suggestions'])) {
            $this->warn('💡 Similar routes:');

            $tableData = [];
            foreach ($result['suggestions'] as $route) {
                $methods = implode('|', $route['methods']);
                $tableData[] = [$methods, $route['uri'], $route['name'] ?? '-'];
            }

            $this->table(['Method', 'URI', 'Name'], $tableData);
        }

        // Show common fixes
        if ($result['error_type'] === 'auth_required') {
            $this->newLine();
            $this->warn('💡 Try adding: --user=1');
        }
    }

    protected function displayResults(array $result)
    {
        $this->info('✅ Response: ' . $result['status']);
        $this->newLine();

        // Summary
        $this->info('📊 Summary:');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Queries', $result['query_count']],
                ['Total Time', round($result['total_time'], 2) . ' ms'],
                ['Avg Query Time', $result['query_count'] > 0 ? round($result['total_time'] / $result['query_count'], 2) . ' ms' : '0 ms'],
                ['Response Status', $result['status']],
            ]
        );

        $this->newLine();

        // No queries
        if ($result['query_count'] === 0) {
            $this->warn('⚠️  No queries detected');
            $this->line('This endpoint may not use the database.');
            return;
        }

        // Show queries if requested
        if ($this->option('show-queries') && $result['query_count'] <= 20) {
            $this->info('📝 Queries:');
            $this->newLine();

            foreach ($result['queries'] as $index => $query) {
                $this->line(($index + 1) . ". " . $query['sql'] . " (" . round($query['time'], 2) . "ms)");
            }

            $this->newLine();
        }

        // Issues
        if (empty($result['issues'])) {
            $this->info('✅ No issues detected!');
            $this->info('✨ Your queries look great!');
        } else {
            $this->displayIssues($result['issues']);
        }

        // Score
        $this->displayScore($result['score']);
    }

    protected function displayIssues(array $issues)
    {
        $this->warn('⚠️  Found ' . count($issues) . ' issue(s):');
        $this->newLine();

        $severityColors = [
            'critical' => 'error',
            'high' => 'warn',
            'medium' => 'info',
            'low' => 'line',
        ];

        foreach ($issues as $index => $issue) {
            $color = $severityColors[$issue['severity']] ?? 'line';
            $number = $index + 1;

            $this->$color("🔴 Issue #{$number}: " . ucfirst(str_replace('_', ' ', $issue['type'])));
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->line("Severity: " . strtoupper($issue['severity']));

            if (isset($issue['count'])) {
                $this->line("Occurrences: {$issue['count']}");
            }

            if (isset($issue['time'])) {
                $this->line("Time: {$issue['time']}ms");
            } elseif (isset($issue['total_time'])) {
                $this->line("Total Time: {$issue['total_time']}ms");
            }

            if (isset($issue['location'])) {
                $this->line("Location: {$issue['location']['file']}:{$issue['location']['line']}");
            }

            $this->newLine();
            $this->line("Query:");
            $this->line("  " . $issue['query']);

            if (isset($issue['suggestion'])) {
                $this->newLine();
                $this->info("💡 Suggestion:");
                $this->line("  " . $issue['suggestion']);
            }

            $this->newLine();
        }
    }

    protected function displayScore(array $score)
    {
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("⚡ Performance Score: {$score['score']}/100 (Grade: {$score['grade']}) " . PerformanceScorer::getEmoji($score['score']));
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if (!empty($score['suggestions'])) {
            $this->newLine();
            $this->info('🎯 Top Improvements:');
            foreach (array_slice($score['suggestions'], 0, 3) as $suggestion) {
                $this->line("  • {$suggestion['issue']} (+{$suggestion['impact']} points)");
            }
        }
    }

    protected function showUsageExamples()
    {
        $this->error('❌ Please provide a URL to analyze');
        $this->newLine();
        $this->info('📖 Usage Examples:');
        $this->newLine();

        $examples = [
            'Simple GET' => 'php artisan querycraft:analyze --url=/users',
            'With auth' => 'php artisan querycraft:analyze --url=/dashboard --user=1',
            'POST request' => 'php artisan querycraft:analyze --url=/api/posts --method=POST',
            'Show queries' => 'php artisan querycraft:analyze --url=/users --show-queries',
        ];

        foreach ($examples as $desc => $cmd) {
            $this->line("<comment>{$desc}:</comment>");
            $this->line("  {$cmd}");
            $this->newLine();
        }
    }
}