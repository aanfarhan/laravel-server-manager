@extends('server-manager::layouts.app')

@section('title', 'Log Management')

@section('content')
<div x-data="logManager()" class="space-y-6">
    <!-- Log File Browser -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    <i class="fas fa-folder mr-2"></i>
                    Log Files
                </h3>
                <button @click="loadLogFiles()" 
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                    <i class="fas fa-sync-alt mr-1"></i>
                    Refresh
                </button>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Directory</label>
                <input type="text" x-model="directory" placeholder="/var/log"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg max-h-64 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">File</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Modified</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="file in logFiles" :key="file.path">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300 font-mono" x-text="file.path"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" x-text="file.size"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" x-text="file.modified"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button @click="viewLog(file.path)" 
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                                        View
                                    </button>
                                    <button @click="tailLog(file.path)" 
                                            class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3">
                                        Tail
                                    </button>
                                    <button @click="downloadLog(file.path)" 
                                            class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300">
                                        Download
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Log Viewer Controls -->
    <div x-show="selectedLogPath" class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    <i class="fas fa-file-alt mr-2"></i>
                    Log Viewer: <span class="font-mono text-sm" x-text="selectedLogPath"></span>
                </h3>
                <div class="flex space-x-2">
                    <button @click="clearLog()" 
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <i class="fas fa-trash mr-1"></i>
                        Clear
                    </button>
                    <button @click="rotateLog()" 
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                        <i class="fas fa-rotate mr-1"></i>
                        Rotate
                    </button>
                </div>
            </div>
            
            <div class="flex space-x-4 mb-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search Pattern</label>
                    <input type="text" x-model="searchPattern" placeholder="error|warning|exception"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div class="w-32">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lines</label>
                    <input type="number" x-model="logLines" min="1" max="1000" value="100"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div class="flex items-end">
                    <button @click="searchLog()" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-search mr-2"></i>
                        Search
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Content -->
    <div x-show="logContent.length > 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    <i class="fas fa-terminal mr-2"></i>
                    Log Content
                    <span class="text-sm text-gray-500 dark:text-gray-400" x-text="`(${logContent.length} lines)`"></span>
                </h3>
                <div class="flex space-x-2">
                    <button @click="autoRefresh = !autoRefresh" 
                            :class="autoRefresh ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-600 hover:bg-gray-700'"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2">
                        <i class="fas fa-sync-alt mr-1"></i>
                        <span x-text="autoRefresh ? 'Auto ON' : 'Auto OFF'"></span>
                    </button>
                    <button @click="refreshLog()" 
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                        <i class="fas fa-sync-alt mr-1"></i>
                        Refresh
                    </button>
                    <a href="{{ route('server-manager.index') }}" 
                       class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                        <i class="fas fa-tachometer-alt mr-1"></i>
                        Dashboard
                    </a>
                </div>
            </div>
            
            <div class="bg-gray-900 rounded-lg p-4 max-h-96 overflow-y-auto">
                <pre class="text-green-400 text-sm font-mono whitespace-pre-wrap"><template x-for="line in logContent" :key="line"><div x-text="line"></div></template></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function logManager() {
    return {
        directory: '/var/log',
        logFiles: [],
        selectedLogPath: '',
        logContent: [],
        searchPattern: '',
        logLines: 100,
        autoRefresh: false,
        refreshInterval: null,

        async loadLogFiles() {
            try {
                const response = await fetch('{{ route("server-manager.logs.files") }}?' + 
                    new URLSearchParams({ directory: this.directory }));
                
                const result = await response.json();
                
                if (result.success) {
                    this.logFiles = result.files;
                } else {
                    alert('Failed to load log files: ' + result.message);
                }
            } catch (error) {
                alert('Failed to load log files: ' + error.message);
            }
        },

        async viewLog(path) {
            this.selectedLogPath = path;
            try {
                const response = await fetch('{{ route("server-manager.logs.read") }}?' + 
                    new URLSearchParams({ path: path, lines: this.logLines }));
                
                const result = await response.json();
                
                if (result.success) {
                    this.logContent = result.lines;
                } else {
                    alert('Failed to read log: ' + result.message);
                }
            } catch (error) {
                alert('Failed to read log: ' + error.message);
            }
        },

        async tailLog(path) {
            this.selectedLogPath = path;
            try {
                const response = await fetch('{{ route("server-manager.logs.tail") }}?' + 
                    new URLSearchParams({ path: path, lines: 50 }));
                
                const result = await response.json();
                
                if (result.success) {
                    this.logContent = result.lines;
                    this.startAutoRefresh();
                } else {
                    alert('Failed to tail log: ' + result.message);
                }
            } catch (error) {
                alert('Failed to tail log: ' + error.message);
            }
        },

        async searchLog() {
            if (!this.selectedLogPath || !this.searchPattern) return;
            
            try {
                const response = await fetch('{{ route("server-manager.logs.search") }}?' + 
                    new URLSearchParams({ 
                        path: this.selectedLogPath, 
                        pattern: this.searchPattern,
                        lines: this.logLines 
                    }));
                
                const result = await response.json();
                
                if (result.success) {
                    this.logContent = result.lines;
                } else {
                    alert('Search failed: ' + result.message);
                }
            } catch (error) {
                alert('Search failed: ' + error.message);
            }
        },

        async refreshLog() {
            if (this.selectedLogPath) {
                await this.viewLog(this.selectedLogPath);
            }
        },

        async downloadLog(path) {
            try {
                const response = await fetch('{{ route("server-manager.logs.download") }}?' + 
                    new URLSearchParams({ path: path }));
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = path.split('/').pop();
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                } else {
                    const result = await response.json();
                    alert('Download failed: ' + result.message);
                }
            } catch (error) {
                alert('Download failed: ' + error.message);
            }
        },

        async clearLog() {
            if (!this.selectedLogPath) return;
            
            if (!confirm('Are you sure you want to clear this log file?')) {
                return;
            }

            try {
                const response = await fetch('{{ route("server-manager.logs.clear") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.getCsrfToken()
                    },
                    body: JSON.stringify({ path: this.selectedLogPath })
                });
                
                const result = await response.json();
                alert(result.message);
                
                if (result.success) {
                    this.logContent = [];
                }
            } catch (error) {
                alert('Clear failed: ' + error.message);
            }
        },

        async rotateLog() {
            if (!this.selectedLogPath) return;
            
            try {
                const response = await fetch('{{ route("server-manager.logs.rotate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.getCsrfToken()
                    },
                    body: JSON.stringify({ path: this.selectedLogPath })
                });
                
                const result = await response.json();
                alert(result.message);
                
                if (result.success) {
                    this.loadLogFiles();
                    this.logContent = [];
                }
            } catch (error) {
                alert('Rotate failed: ' + error.message);
            }
        },

        startAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
            
            if (this.autoRefresh) {
                this.refreshInterval = setInterval(() => {
                    this.refreshLog();
                }, 5000);
            }
        },

        init() {
            this.loadLogFiles();
            
            this.$watch('autoRefresh', (value) => {
                this.startAutoRefresh();
            });
        }
    }
}
</script>
@endpush