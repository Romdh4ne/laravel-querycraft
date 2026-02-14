<?php

namespace Romdh4ne\QueryCraft\Collectors;

use Illuminate\Support\Facades\DB;

class QueryCollector
{
    protected static $queries = [];
    protected static $enabled = false;

    public static function start(): void
    {
        if (self::$enabled) {
            return;
        }

        self::$enabled = true;
        self::$queries = [];

        DB::listen(function ($query) {
            $rawTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 50); // ← 50 frames

            // Pre-filter: only keep frames that have a file path
            $backtrace = array_values(array_filter($rawTrace, fn($f) => isset($f['file'])));

            self::$queries[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
                'backtrace' => $backtrace,
                'timestamp' => microtime(true),
            ];
        });
    }

    public static function stop(): void
    {
        self::$enabled = false;
    }

    public static function getQueries(): array
    {
        return self::$queries;
    }

    public static function clear(): void
    {
        self::$queries = [];
    }

    public static function count(): int
    {
        return count(self::$queries);
    }

    public static function totalTime(): float
    {
        return array_sum(array_column(self::$queries, 'time'));
    }
}