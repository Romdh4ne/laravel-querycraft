
<div x-show="showConfig" x-cloak class="fixed inset-0 z-50 flex">

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showConfig = false"></div>

    {{-- Slide-over --}}
    <div class="fixed right-0 top-0 h-full w-full max-w-md bg-white dark:bg-gray-900 shadow-2xl border-l border-gray-200 dark:border-gray-700 flex flex-col"
         @click.stop>

        {{-- Panel Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-white">Configuration</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Saved to .env — persists across sessions</p>
                </div>
            </div>
            <button @click="showConfig = false"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Loading --}}
        <div x-show="configLoading" class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <svg class="animate-spin w-8 h-8 text-red-500 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="20 60" stroke-linecap="round"/>
                </svg>
                <div class="text-sm text-gray-500">Loading config...</div>
            </div>
        </div>

        {{-- Content --}}
        <div x-show="!configLoading" class="flex-1 overflow-y-auto px-6 py-5 space-y-6">

            {{-- ── Detectors ────────────────────────────────────────── --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Detectors
                </h3>
                <div class="space-y-2">
                    <template x-for="detector in [
                        { key: 'n1',              label: 'N+1 Detection',   desc: 'Repeated query patterns',          icon: '🔁' },
                        { key: 'slow_query',      label: 'Slow Query',      desc: 'Queries exceeding time limit',     icon: '🐢' },
                        { key: 'missing_index',   label: 'Missing Index',   desc: 'Full table scan detection',        icon: '🗂' },
                        { key: 'duplicate_query', label: 'Duplicate Query', desc: 'Identical queries in one request', icon: '📋' },
                    ]" :key="detector.key">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-2.5">
                                <span class="text-lg" x-text="detector.icon"></span>
                                <div>
                                    <div class="text-sm font-medium" x-text="detector.label"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400" x-text="detector.desc"></div>
                                </div>
                            </div>
                            <button @click="config.detectors[detector.key] = !config.detectors[detector.key]"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors flex-shrink-0"
                                    :class="config.detectors[detector.key] ? 'bg-red-500' : 'bg-gray-300 dark:bg-gray-600'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                                      :class="config.detectors[detector.key] ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ── Thresholds ───────────────────────────────────────── --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                    </svg>
                    Thresholds
                </h3>
                <div class="space-y-4">

                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium">N+1 Min Repetitions</label>
                            <span class="text-sm font-bold text-red-600 dark:text-red-400" x-text="config.thresholds.n1_count"></span>
                        </div>
                        <input type="range" min="2" max="20" step="1"
                               x-model="config.thresholds.n1_count"
                               class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-red-500">
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span>2</span><span>Flag after N repeats</span><span>20</span>
                        </div>
                    </div>

                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium">Slow Query Limit</label>
                            <span class="text-sm font-bold text-red-600 dark:text-red-400" x-text="config.thresholds.slow_query_ms + 'ms'"></span>
                        </div>
                        <input type="range" min="50" max="2000" step="50"
                               x-model="config.thresholds.slow_query_ms"
                               class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-red-500">
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span>50ms</span><span>Flag slower than this</span><span>2000ms</span>
                        </div>
                    </div>

                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium">Duplicate Min Count</label>
                            <span class="text-sm font-bold text-red-600 dark:text-red-400" x-text="config.thresholds.duplicate_count"></span>
                        </div>
                        <input type="range" min="2" max="10" step="1"
                               x-model="config.thresholds.duplicate_count"
                               class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-red-500">
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span>2</span><span>Flag after N duplicates</span><span>10</span>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ── Score Weights ─────────────────────────────────────── --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Score Weights
                </h3>

                <div class="mb-3 flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400">Total must equal 100%</span>
                    <span class="font-bold px-2 py-0.5 rounded-full"
                          :class="weightsTotal === 100
                              ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                              : 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'"
                          x-text="weightsTotal + '%'">
                    </span>
                </div>

                <div class="space-y-4">
                    <template x-for="w in [
                        { key: 'query_count', label: 'Query Count' },
                        { key: 'query_time',  label: 'Query Time'  },
                        { key: 'issues',      label: 'Issues Found'},
                    ]" :key="w.key">
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-sm font-medium" x-text="w.label"></label>
                                <span class="text-sm font-bold text-red-600 dark:text-red-400"
                                      x-text="config.weights[w.key] + '%'"></span>
                            </div>
                            <input type="range" min="0" max="100" step="5"
                                   x-model="config.weights[w.key]"
                                   class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-red-500">
                        </div>
                    </template>
                </div>
            </div>

        </div>

        {{-- Panel Footer --}}
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0 bg-gray-50 dark:bg-gray-800/50">

            <button @click="doResetConfig()"
                    :disabled="configSaving"
                    class="flex items-center gap-1.5 px-3 py-2 text-sm text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Reset
            </button>

            <div class="flex items-center gap-1.5 text-xs text-green-600 dark:text-green-400"
                 x-show="configSaved" x-transition>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Saved to .env
            </div>

            <div class="text-xs text-red-500 dark:text-red-400 max-w-[160px] text-right"
                 x-show="configError" x-text="configError">
            </div>

            <button @click="doSaveConfig()"
                    :disabled="configSaving || weightsTotal !== 100"
                    class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-colors">
                <svg x-show="configSaving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="20 60" stroke-linecap="round"/>
                </svg>
                <span x-text="configSaving ? 'Saving...' : 'Save'"></span>
            </button>

        </div>
    </div>
</div>