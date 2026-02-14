<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    */
    'enabled' => env('QUERY_DEBUGGER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Detectors
    |--------------------------------------------------------------------------
    */
    'detectors' => [
        'n1' => env('QUERYCRAFT_DETECTOR_N1', true),
        'slow_query' => env('QUERYCRAFT_DETECTOR_SLOW_QUERY', true),
        'missing_index' => env('QUERYCRAFT_DETECTOR_MISSING_INDEX', true),
        'duplicate_query' => env('QUERYCRAFT_DETECTOR_DUPLICATE_QUERY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Thresholds
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'n1_count' => env('QUERY_DEBUGGER_N1_THRESHOLD', 5),
        'slow_query_ms' => env('QUERY_DEBUGGER_SLOW_THRESHOLD', 100),
        'duplicate_count' => env('QUERYCRAFT_DUPLICATE_COUNT', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Score Weights (must total 100)
    |--------------------------------------------------------------------------
    */
    'weights' => [
        'query_count' => env('QUERYCRAFT_WEIGHT_QUERY_COUNT', 40),
        'query_time' => env('QUERYCRAFT_WEIGHT_QUERY_TIME', 30),
        'issues' => env('QUERYCRAFT_WEIGHT_ISSUES', 30),
    ],
];