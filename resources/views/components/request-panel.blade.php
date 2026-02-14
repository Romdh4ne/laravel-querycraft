<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold">Request Builder</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure and analyze your endpoint</p>
    </div>
    
    <div class="p-6 space-y-6">
        <!-- URL Input -->
        <div>
            <label class="block text-sm font-medium mb-2">Endpoint URL</label>
            <div class="flex gap-2">
                <select 
                    x-model="method" 
                    class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl font-medium text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none"
                >
                    <option value="GET">GET</option>
                    <option value="POST">POST</option>
                    <option value="PUT">PUT</option>
                    <option value="PATCH">PATCH</option>
                    <option value="DELETE">DELETE</option>
                </select>
                <input 
                    x-model="url" 
                    type="text" 
                    placeholder="/api/users"
                    class="flex-1 px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none"
                    @keydown.enter="analyze()"
                >
            </div>
        </div>

        <!-- Quick Examples -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <p class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">Quick Examples</p>
            <div class="space-y-1">
                <button 
                    @click="loadExample('/api/users', 'GET')" 
                    class="text-sm text-blue-600 dark:text-blue-400 hover:underline block"
                >
                    GET /api/users
                </button>
                <button 
                    @click="loadExample('/dashboard', 'GET')" 
                    class="text-sm text-blue-600 dark:text-blue-400 hover:underline block"
                >
                    GET /dashboard
                </button>
                <button 
                    @click="loadExample('/api/posts', 'POST')" 
                    class="text-sm text-blue-600 dark:text-blue-400 hover:underline block"
                >
                    POST /api/posts
                </button>
            </div>
        </div>

        <!-- Headers (Collapsible) -->
        <div>
            <button 
                @click="showHeaders = !showHeaders"
                class="flex items-center justify-between w-full text-sm font-medium mb-2"
            >
                <span>Headers (Optional)</span>
                <svg 
                    class="w-5 h-5 transition-transform"
                    :class="showHeaders ? 'rotate-180' : ''"
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            
            <div x-show="showHeaders" x-collapse>
                <div class="space-y-2">
                    <template x-for="(header, index) in headers" :key="index">
                        <div class="flex gap-2">
                            <input 
                                x-model="header.key" 
                                placeholder="Key"
                                class="flex-1 px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none"
                            >
                            <input 
                                x-model="header.value" 
                                placeholder="Value"
                                class="flex-1 px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none"
                            >
                            <button 
                                @click="headers.splice(index, 1)"
                                class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                    <button 
                        @click="headers.push({key: '', value: ''})"
                        class="text-sm text-red-600 hover:text-red-700 font-medium"
                    >
                        + Add Header
                    </button>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div x-show="method !== 'GET' && method !== 'DELETE'">
            <label class="block text-sm font-medium mb-2">Request Body (JSON)</label>
            <textarea 
                x-model="body" 
                rows="6"
                placeholder='{"key": "value"}'
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl code-block focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none resize-none"
            ></textarea>
        </div>

        <!-- Analyze Button -->
        <button 
            @click="analyze()"
            :disabled="loading || !url"
            class="w-full py-3.5 rounded-xl font-semibold shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="loading || !url ? 'bg-gray-300 dark:bg-gray-700 text-gray-500' : 'bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white'"
        >
            <span x-show="!loading" class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Analyze Request
            </span>
            <span x-show="loading" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Analyzing...
            </span>
        </button>

        <!-- Error Message -->
        <div x-show="error" x-cloak class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-red-900 dark:text-red-300">Error</p>
                    <p class="text-sm text-red-700 dark:text-red-400 mt-1" x-text="error"></p>
                </div>
            </div>
        </div>
    </div>
</div>