<div x-show="results" x-cloak class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 animate-fade-in">
    <!-- Score Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Score</span>
            <span class="text-2xl" x-text="scoreHelpers.getEmoji(results?.score?.score)"></span>
        </div>
        <div class="flex items-baseline gap-2">
            <span class="text-3xl font-bold" :class="scoreHelpers.getColor(results?.score?.score)" x-text="results?.score?.score"></span>
            <span class="text-sm text-gray-500 dark:text-gray-400">/100</span>
        </div>
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Grade <span class="font-semibold" x-text="results?.score?.grade"></span>
        </div>
    </div>

    <!-- Queries Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Queries</span>
            <span class="text-2xl">📊</span>
        </div>
        <div class="text-3xl font-bold" x-text="results?.query_count"></div>
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Total executed</div>
    </div>

    <!-- Time Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Time</span>
            <span class="text-2xl">⚡</span>
        </div>
        <div class="text-3xl font-bold">
            <span x-text="results?.total_time?.toFixed(1)"></span><span class="text-lg text-gray-500">ms</span>
        </div>
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Total duration</div>
    </div>

    <!-- Issues Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Issues</span>
            <span class="text-2xl" x-text="results?.issues?.length > 0 ? '⚠️' : '✅'"></span>
        </div>
        <div class="text-3xl font-bold" :class="results?.issues?.length > 0 ? 'text-red-600' : 'text-green-600'" x-text="results?.issues?.length"></div>
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            <span x-text="results?.issues?.length === 0 ? 'No issues' : 'Found'"></span>
        </div>
    </div>
</div>