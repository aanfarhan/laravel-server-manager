@extends('server-manager::layouts.app')

@section('title', 'Server Details - ' . $server->name)

@section('content')
<div x-data="serverDetails()" class="space-y-6">
    <!-- Notification Toast -->
    <div x-show="notification.show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed top-4 right-4 z-50 max-w-sm w-full">
        <div :class="notification.type === 'success' ? 'bg-green-500' : notification.type === 'error' ? 'bg-red-500' : 'bg-blue-500'"
             class="rounded-lg shadow-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i :class="notification.type === 'success' ? 'fas fa-check-circle' : notification.type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle'"
                       class="mr-3"></i>
                    <span x-text="notification.message" class="text-sm font-medium"></span>
                </div>
                <button @click="hideNotification()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-server mr-2"></i>
                    {{ $server->name }}
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ $server->host }}:{{ $server->port }} • {{ $server->username }} ({{ $server->connection_type }})
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('server-manager.servers.edit', $server) }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Server
                </a>
                <a href="{{ route('server-manager.servers.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Servers
                </a>
            </div>
        </div>
    </div>

    <!-- Server Status and Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Connection Status -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if($server->status === 'connected')
                            <i class="fas fa-circle text-2xl text-green-500"></i>
                        @elseif($server->status === 'error')
                            <i class="fas fa-exclamation-circle text-2xl text-red-500"></i>
                        @else
                            <i class="fas fa-circle text-2xl text-gray-500"></i>
                        @endif
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Status</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                @if($server->status === 'connected')
                                    Connected
                                @elseif($server->status === 'error')
                                    Error
                                @else
                                    Disconnected
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
                @if($server->last_error)
                    <div class="mt-3 text-sm text-red-600 dark:text-red-400">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        {{ $server->last_error }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Last Connected -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock text-2xl text-blue-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Last Connected</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $server->last_connected_at ? $server->last_connected_at->diffForHumans() : 'Never' }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connection Type -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-key text-2xl text-purple-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Auth Type</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ ucfirst($server->connection_type) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Connection Actions -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                <i class="fas fa-plug mr-2"></i>
                Connection Management
            </h3>
            <div class="flex space-x-3">
                <button @click="testConnection()" 
                        :disabled="loading"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                    <i class="fas fa-check mr-2"></i>
                    Test Connection
                </button>
                <button @click="connectToServer()" 
                        :disabled="loading || connected"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50">
                    <i class="fas fa-plug mr-2"></i>
                    Connect
                </button>
                <button @click="disconnectFromServer()" 
                        x-show="connected"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-times mr-2"></i>
                    Disconnect
                </button>
            </div>
        </div>
    </div>

    <!-- Server Monitoring (if connected) -->
    <div x-show="connected" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- CPU Usage -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-microchip text-2xl text-blue-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">CPU Usage</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white" x-text="status.cpu?.usage_percent + '%'"></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Memory Usage -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-memory text-2xl text-green-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Memory Usage</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white" x-text="status.memory?.usage_percent + '%'"></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disk Usage -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-hdd text-2xl text-yellow-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Disk Usage</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white" x-text="status.disk?.usage_percent + '%'"></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Uptime -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock text-2xl text-purple-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Uptime</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white" x-text="status.uptime?.pretty"></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Tabs Navigation -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8 px-6">
                <button @click="activeTab = 'monitoring'" 
                        :class="activeTab === 'monitoring' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-chart-line mr-2"></i>
                    Monitoring
                </button>
                <button @click="activeTab = 'logs'" 
                        :class="activeTab === 'logs' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-file-alt mr-2"></i>
                    Logs
                </button>
                <button @click="activeTab = 'terminal'" 
                        :class="activeTab === 'terminal' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-terminal mr-2"></i>
                    Terminal
                </button>
            </nav>
        </div>

        <!-- Monitoring Tab -->
        <div x-show="activeTab === 'monitoring'" class="p-6">
            @if($server->monitoringLogs->count() > 0)
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-chart-line mr-2"></i>
                    Recent Monitoring Data
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">CPU %</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Memory %</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Disk %</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Load</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($server->monitoringLogs as $log)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $log->created_at->format('M d, H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                        {{ $log->cpu_usage ?? '-' }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                        {{ $log->memory_usage ?? '-' }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                        {{ $log->disk_usage ?? '-' }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                        {{ $log->load_1min ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-chart-line text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No monitoring data available</h3>
                    <p class="text-gray-500 dark:text-gray-400">Connect to the server to start collecting monitoring data</p>
                </div>
            @endif
        </div>

        <!-- Logs Tab -->
        <div x-show="activeTab === 'logs'" class="p-6 space-y-6">
            <!-- Log File Browser -->
            <div class="space-y-4">
                <div class="flex justify-between items-center">
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
                    <input type="text" x-model="logDirectory" placeholder="/var/log"
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

            <!-- Log Viewer Controls -->
            <div x-show="selectedLogPath" class="space-y-4">
                <div class="flex justify-between items-center">
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
                
                <div class="flex space-x-4">
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

            <!-- Log Content -->
            <div x-show="logContent.length > 0" class="space-y-4">
                <div class="flex justify-between items-center">
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
                    </div>
                </div>
                
                <div class="bg-gray-900 rounded-lg p-4 max-h-96 overflow-y-auto">
                    <pre class="text-green-400 text-sm font-mono whitespace-pre-wrap"><template x-for="line in logContent" :key="line"><div x-text="line"></div></template></pre>
                </div>
            </div>
        </div>

        <!-- Terminal Tab -->
        <div x-show="activeTab === 'terminal'" class="p-6">
            <div class="space-y-4">
                <!-- Terminal Controls -->
                <div class="flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        <i class="fas fa-terminal mr-2"></i>
                        Terminal Session
                    </h3>
                    <div class="flex space-x-2">
                        <!-- Terminal Mode Selector -->
                        <div class="flex items-center space-x-2 mr-4">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Mode:</span>
                            <select x-model="terminalMode" 
                                    :disabled="terminalLoading || terminalSession || websocketSession"
                                    class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="simple">Simple</option>
                                <option value="websocket">Full (WebSocket)</option>
                            </select>
                        </div>
                        
                        <button @click="createTerminalSession()" 
                                :disabled="terminalLoading || terminalSession || websocketSession"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50">
                            <i class="fas fa-play mr-1"></i>
                            <span x-text="terminalMode === 'websocket' ? 'Start WebSocket Terminal' : 'Start Simple Terminal'"></span>
                        </button>
                        <button @click="closeTerminalSession()" 
                                x-show="terminalSession || websocketSession"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-stop mr-1"></i>
                            <span x-text="websocketSession ? 'Close WebSocket Terminal' : 'Close Terminal'"></span>
                        </button>
                        <button @click="clearTerminal()" 
                                x-show="terminalSession || websocketSession"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                            <i class="fas fa-eraser mr-1"></i>
                            Clear
                        </button>
                        <button @click="checkWebSocketStatus()" 
                                x-show="terminalMode === 'websocket'"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            Check Server Status
                        </button>
                    </div>
                </div>

                <!-- Terminal Container -->
                <div x-show="terminalSession || websocketSession" class="space-y-4">
                    <div class="bg-black rounded-lg border border-gray-300 dark:border-gray-600">
                        <!-- Terminal Header -->
                        <div class="flex items-center justify-between px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-t-lg border-b border-gray-300 dark:border-gray-600">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <span class="ml-4 text-sm text-gray-600 dark:text-gray-400 font-mono" 
                                      x-text="websocketSession ? '{{ $server->name }} - WebSocket Terminal' : '{{ $server->name }} - Simple Terminal'"></span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                <span x-show="terminalConnected || websocketConnected" class="flex items-center">
                                    <i class="fas fa-circle text-green-500 mr-1"></i>
                                    <span x-text="websocketSession ? 'WebSocket Connected' : 'Connected'"></span>
                                </span>
                                <span x-show="!terminalConnected && !websocketConnected" class="flex items-center">
                                    <i class="fas fa-circle text-red-500 mr-1"></i>
                                    Disconnected
                                </span>
                            </div>
                        </div>
                        
                        <!-- WebSocket Terminal (xterm.js) -->
                        <div x-show="websocketSession" class="h-96 relative">
                            <div id="websocket-terminal-container" class="w-full h-full"></div>
                            <div x-show="!websocketConnected" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-75">
                                <div class="text-white text-center">
                                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                    <p>Connecting to WebSocket Terminal...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Simple Command Interface -->
                        <div x-show="terminalSession && !websocketSession" class="p-4">
                            <div class="bg-black text-green-400 font-mono text-sm p-4 rounded h-80 overflow-y-auto" 
                                 id="command-output">
                                <div x-html="commandHistory"></div>
                                <div class="flex items-center">
                                    <span x-text="currentPrompt"></span>
                                    <input type="text" 
                                           x-model="currentCommand"
                                           @keyup.enter="executeSimpleCommand()"
                                           class="bg-transparent border-0 outline-0 text-green-400 flex-1 ml-1"
                                           placeholder="Type command...">
                                </div>
                            </div>
                            
                            <div class="mt-2 text-xs text-gray-500">
                                <span class="inline-flex items-center">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Simple terminal mode - each command executes independently
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terminal Status/Help -->
                <div x-show="!terminalSession && !websocketSession" class="text-center py-8">
                    <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-terminal text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Active Terminal Session</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Click "Start Terminal" to open a new interactive terminal session on {{ $server->name }}</p>
                    <div class="text-sm text-gray-400 dark:text-gray-500">
                        <p>Features:</p>
                        <ul class="list-disc list-inside mt-1 space-y-1">
                            <li>Full interactive terminal with copy/paste support</li>
                            <li>Real-time command execution</li>
                            <li>Resizable terminal window</li>
                            <li>Session persistence until manually closed</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function serverDetails() {
    return {
        connected: @json($server->status === 'connected'),
        loading: false,
        status: {},
        activeTab: 'monitoring',
        
        // Logs functionality
        logDirectory: '/var/log',
        logFiles: [],
        selectedLogPath: '',
        logContent: [],
        searchPattern: '',
        logLines: 100,
        autoRefresh: false,
        refreshInterval: null,
        
        // Terminal functionality
        terminalSession: null,
        terminalLoading: false,
        terminalConnected: false,
        terminalCommand: '',
        xtermLoaded: false,
        terminal: null,
        outputPollingInterval: null,
        pollAttempts: 0,
        
        // Terminal modes
        terminalMode: 'simple',
        websocketSession: null,
        websocketUrl: '',
        websocketToken: '',
        websocketConnected: false,
        websocketStatus: null,
        websocketTerminal: null,
        commandHistory: '',
        currentCommand: '',
        currentPrompt: 'user@server:~$ ',
        
        // Notification system
        notification: {
            show: false,
            message: '',
            type: 'success' // 'success', 'error', 'info'
        },

        async testConnection() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("server-manager.servers.test") }}', {
                    method: 'POST',
                    headers: window.getDefaultHeaders(),
                    body: JSON.stringify({ server_id: {{ $server->id }} })
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('✅ Connection test successful!');
                } else {
                    alert('❌ Connection test failed: ' + result.message);
                }
            } catch (error) {
                alert('❌ Connection test failed: ' + error.message);
            }
            this.loading = false;
        },

        async connectToServer() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("server-manager.servers.connect") }}', {
                    method: 'POST',
                    headers: window.getDefaultHeaders(),
                    body: JSON.stringify({ server_id: {{ $server->id }} })
                });
                
                const result = await response.json();
                if (result.success) {
                    this.connected = true;
                    this.loadStatus();
                    alert('✅ Connected successfully!');
                    location.reload();
                } else {
                    alert('❌ Connection failed: ' + result.message);
                }
            } catch (error) {
                alert('❌ Connection failed: ' + error.message);
            }
            this.loading = false;
        },

        async disconnectFromServer() {
            try {
                // Immediately update UI state to provide instant feedback
                this.connected = false;
                this.status = {};
                
                const response = await fetch('{{ route("server-manager.servers.disconnect") }}', {
                    method: 'POST',
                    headers: window.getDefaultHeaders(),
                    body: JSON.stringify({
                        server_id: {{ $server->id }}
                    })
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    alert('✅ Disconnected successfully');
                    // Add a small delay to ensure server state is updated before reload
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    // Revert UI state if disconnect failed
                    this.connected = true;
                    alert('❌ Disconnect failed: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                // Revert UI state if disconnect failed
                this.connected = true;
                alert('❌ Disconnect failed: ' + error.message);
            }
        },

        async loadStatus() {
            if (!this.connected) return;
            
            try {
                const response = await fetch('{{ route("server-manager.servers.status") }}?server_id={{ $server->id }}');
                const result = await response.json();
                if (result.success) {
                    this.status = result.data;
                }
            } catch (error) {
                console.error('Status load error:', error);
            }
        },

        // Log management methods
        async loadLogFiles() {
            try {
                const response = await fetch('{{ route("server-manager.logs.files") }}?' + 
                    new URLSearchParams({ 
                        directory: this.logDirectory,
                        server_id: {{ $server->id }}
                    }));
                
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
                    new URLSearchParams({ 
                        path: path, 
                        lines: this.logLines,
                        server_id: {{ $server->id }}
                    }));
                
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
                    new URLSearchParams({ 
                        path: path, 
                        lines: 50,
                        server_id: {{ $server->id }}
                    }));
                
                const result = await response.json();
                
                if (result.success) {
                    this.logContent = result.lines;
                    this.startLogAutoRefresh();
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
                        lines: this.logLines,
                        server_id: {{ $server->id }}
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
                    new URLSearchParams({ 
                        path: path,
                        server_id: {{ $server->id }}
                    }));
                
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
                    headers: window.getDefaultHeaders(),
                    body: JSON.stringify({ 
                        path: this.selectedLogPath,
                        server_id: {{ $server->id }}
                    })
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
                    headers: window.getDefaultHeaders(),
                    body: JSON.stringify({ 
                        path: this.selectedLogPath,
                        server_id: {{ $server->id }}
                    })
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

        startLogAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
            
            if (this.autoRefresh) {
                this.refreshInterval = setInterval(() => {
                    this.refreshLog();
                }, 5000);
            }
        },

        // Terminal functionality
        async createTerminalSession() {
            if (this.terminalSession || this.websocketSession) return;
            
            this.terminalLoading = true;
            try {
                if (this.terminalMode === 'websocket') {
                    await this.createWebSocketSession();
                } else {
                    await this.createSimpleSession();
                }
            } catch (error) {
                this.showNotification('❌ Failed to start terminal: ' + error.message, 'error');
            }
            this.terminalLoading = false;
        },
        
        async createSimpleSession() {
            const response = await fetch('{{ route("server-manager.terminal.create") }}', {
                method: 'POST',
                headers: window.getDefaultHeaders(),
                body: JSON.stringify({ 
                    server_id: {{ $server->id }},
                    mode: 'simple'
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.terminalSession = result.session_id;
                this.terminalConnected = true;
                
                // Wait for DOM to update
                await this.$nextTick();
                await new Promise(resolve => setTimeout(resolve, 100));
                
                await this.initializeTerminal();
                
                this.currentPrompt = `{{ $server->username ?? 'user' }}@{{ $server->name }}:~$ `;
                this.commandHistory = `<div class="text-blue-400">Welcome to {{ $server->name }}</div><div class="text-gray-400 text-xs">Simple terminal mode - Type commands and press Enter</div>`;
                
                await new Promise(resolve => setTimeout(resolve, 1000));
                this.startOutputPolling();
                
                this.showNotification('✅ Simple terminal started!', 'success');
            } else {
                this.showNotification('❌ Failed to start terminal: ' + result.message, 'error');
            }
        },
        
        async createWebSocketSession() {
            // Generate WebSocket token
            const response = await fetch('/server-manager/terminal/websocket/token', {
                method: 'POST',
                headers: window.getDefaultHeaders(),
                body: JSON.stringify({ 
                    server_id: {{ $server->id }}
                })
            });
            
            const result = await response.json();
            if (!result.success) {
                this.showNotification('❌ Failed to generate token: ' + result.message, 'error');
                return;
            }
            
            this.websocketToken = result.token;
            this.websocketUrl = result.websocket_url;
            this.websocketSession = result.token_id;
            
            // Wait for DOM to update
            await this.$nextTick();
            await new Promise(resolve => setTimeout(resolve, 100));
            
            await this.initializeWebSocketTerminal();
            
            this.showNotification('✅ WebSocket terminal starting...', 'info');
        },

        async closeTerminalSession() {
            if (this.websocketSession) {
                await this.closeWebSocketSession();
            } else if (this.terminalSession) {
                await this.closeSimpleSession();
            }
        },
        
        async closeSimpleSession() {
            try {
                await fetch('{{ route("server-manager.terminal.close") }}', {
                    method: 'POST',
                    headers: window.getDefaultHeaders(),
                    body: JSON.stringify({ session_id: this.terminalSession })
                });
            } catch (error) {
                console.error('Error closing terminal:', error);
            }
            
            // Clean up
            this.stopOutputPolling();
            this.destroyTerminal();
            this.terminalSession = null;
            this.terminalConnected = false;
            this.pollAttempts = 0;
        },
        
        async closeWebSocketSession() {
            try {
                // Close WebSocket connection
                if (this.websocket) {
                    this.websocket.close();
                    this.websocket = null;
                }
                
                // Dispose of xterm.js terminal
                if (this.websocketTerminal) {
                    this.websocketTerminal.dispose();
                    this.websocketTerminal = null;
                }
                
                // Revoke token
                if (this.websocketSession) {
                    await fetch('/server-manager/terminal/websocket/revoke', {
                        method: 'POST',
                        headers: window.getDefaultHeaders(),
                        body: JSON.stringify({ token_id: this.websocketSession })
                    });
                }
                
                this.showNotification('✅ WebSocket terminal closed', 'success');
            } catch (error) {
                console.error('Error closing WebSocket terminal:', error);
            }
            
            // Clean up
            this.websocketSession = null;
            this.websocketUrl = '';
            this.websocketToken = '';
            this.websocketConnected = false;
            this.websocket = null;
        },

        async executeCommand() {
            if (!this.terminalSession || !this.terminalCommand.trim()) return;
            
            try {
                const response = await fetch('{{ route("server-manager.terminal.execute") }}', {
                    method: 'POST',
                    headers: window.getDefaultHeaders(),
                    body: JSON.stringify({ 
                        session_id: this.terminalSession,
                        command: this.terminalCommand
                    })
                });
                
                const result = await response.json();
                if (result.success && this.terminal) {
                    this.terminal.write(result.output);
                }
                
                this.terminalCommand = '';
            } catch (error) {
                console.error('Command execution failed:', error);
            }
        },

        async initializeTerminal() {
            return new Promise((resolve) => {
                // Load xterm.js if not already loaded
                if (typeof Terminal === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/xterm@5.3.0/lib/xterm.js';
                    script.onload = () => {
                        const css = document.createElement('link');
                        css.rel = 'stylesheet';
                        css.href = 'https://unpkg.com/xterm@5.3.0/css/xterm.css';
                        document.head.appendChild(css);
                        
                        this.createTerminalInstance();
                        resolve();
                    };
                    document.head.appendChild(script);
                } else {
                    this.createTerminalInstance();
                    resolve();
                }
            });
        },

        createTerminalInstance() {
            const container = document.getElementById('terminal-container');
            if (!container) {
                console.error('Terminal container not found');
                return;
            }
            
            // Check if container is visible
            if (container.offsetParent === null) {
                console.error('Terminal container is not visible');
                return;
            }
            
            // Clear container
            container.innerHTML = '';
            
            // Create terminal
            this.terminal = new Terminal({
                cursorBlink: true,
                fontSize: 14,
                fontFamily: 'Consolas, Monaco, "Lucida Console", monospace',
                theme: {
                    background: '#000000',
                    foreground: '#ffffff',
                    cursor: '#ffffff',
                    selection: '#ffffff'
                },
                rows: 24,
                cols: 80
            });
            
            // Handle terminal input
            this.terminal.onData((data) => {
                if (this.terminalSession) {
                    this.sendTerminalInput(data);
                }
            });
            
            // Mount terminal
            this.terminal.open(container);
            this.xtermLoaded = true;
        },

        async sendTerminalInput(data) {
            if (!this.terminalSession) return;
            
            try {
                const response = await fetch('{{ route("server-manager.terminal.input") }}', {
                    method: 'POST',
                    headers: window.getDefaultHeaders(),
                    body: JSON.stringify({ 
                        session_id: this.terminalSession,
                        input: data
                    })
                });
                
                const result = await response.json();
                if (result.success && result.output && this.terminal) {
                    this.terminal.write(result.output);
                }
            } catch (error) {
                console.error('Terminal input failed:', error);
            }
        },

        startOutputPolling() {
            if (this.outputPollingInterval) {
                clearInterval(this.outputPollingInterval);
            }
            
            this.pollAttempts = 0; // Reset poll attempts counter
            const maxGraceAttempts = 10; // Grace period: 10 attempts (5 seconds)
            
            this.outputPollingInterval = setInterval(async () => {
                if (!this.terminalSession) return;
                
                try {
                    const response = await fetch('{{ route("server-manager.terminal.output") }}?' + 
                        new URLSearchParams({ session_id: this.terminalSession }));
                    
                    const result = await response.json();
                    if (result.success && result.output && this.terminal) {
                        this.terminal.write(result.output);
                    }
                    
                    // Only auto-close if session is inactive AND we're past the grace period
                    if (!result.session_active && this.pollAttempts >= maxGraceAttempts) {
                        console.log('Terminal session became inactive after grace period, closing...');
                        this.closeTerminalSession();
                    } else if (!result.session_active) {
                        console.log(`Terminal session not yet active, attempt ${this.pollAttempts + 1}/${maxGraceAttempts}`);
                    }
                    
                    this.pollAttempts++;
                } catch (error) {
                    console.error('Output polling failed:', error);
                    // Don't auto-close on network errors during grace period
                    if (this.pollAttempts >= maxGraceAttempts) {
                        console.log('Terminal polling failed consistently, closing session...');
                        this.closeTerminalSession();
                    }
                    this.pollAttempts++;
                }
            }, 2000); // Poll every 2 seconds (less aggressive since we have interactive input)
        },

        stopOutputPolling() {
            if (this.outputPollingInterval) {
                clearInterval(this.outputPollingInterval);
                this.outputPollingInterval = null;
            }
        },

        clearTerminal() {
            if (this.terminal) {
                this.terminal.clear();
            }
        },

        destroyTerminal() {
            if (this.terminal) {
                this.terminal.dispose();
                this.terminal = null;
            }
            this.xtermLoaded = false;
            
            const container = document.getElementById('terminal-container');
            if (container) {
                container.innerHTML = '';
            }
        },

        showNotification(message, type = 'success') {
            this.notification.message = message;
            this.notification.type = type;
            this.notification.show = true;
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                this.notification.show = false;
            }, 3000);
        },

        hideNotification() {
            this.notification.show = false;
        },

        async executeSimpleCommand() {
            if (!this.terminalSession || !this.currentCommand.trim()) return;
            
            const command = this.currentCommand.trim();
            this.currentCommand = '';
            
            // Add command to history
            this.commandHistory += `<div>${this.currentPrompt}${command}</div>`;
            
            try {
                const response = await fetch('{{ route("server-manager.terminal.execute") }}', {
                    method: 'POST',
                    headers: window.getDefaultHeaders(),
                    body: JSON.stringify({ 
                        session_id: this.terminalSession,
                        command: command
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    // Add output to history
                    const output = result.output || '';
                    this.commandHistory += `<div class="whitespace-pre-wrap">${this.escapeHtml(output)}</div>`;
                } else {
                    this.commandHistory += `<div class="text-red-400">Error: ${result.message || 'Command failed'}</div>`;
                }
            } catch (error) {
                this.commandHistory += `<div class="text-red-400">Error: ${error.message}</div>`;
            }
            
            // Scroll to bottom
            this.$nextTick(() => {
                const output = document.getElementById('command-output');
                if (output) {
                    output.scrollTop = output.scrollHeight;
                }
            });
        },

        async initializeWebSocketTerminal() {
            return new Promise((resolve, reject) => {
                // Load xterm.js if not already loaded
                if (typeof Terminal === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/xterm@5.3.0/lib/xterm.js';
                    script.onload = () => {
                        const css = document.createElement('link');
                        css.rel = 'stylesheet';
                        css.href = 'https://unpkg.com/xterm@5.3.0/css/xterm.css';
                        document.head.appendChild(css);
                        
                        this.createWebSocketTerminalInstance();
                        resolve();
                    };
                    script.onerror = reject;
                    document.head.appendChild(script);
                } else {
                    this.createWebSocketTerminalInstance();
                    resolve();
                }
            });
        },

        createWebSocketTerminalInstance() {
            const container = document.getElementById('websocket-terminal-container');
            if (!container) {
                console.error('WebSocket terminal container not found');
                return;
            }
            
            // Clear container
            container.innerHTML = '';
            
            // Create terminal
            this.websocketTerminal = new Terminal({
                cursorBlink: true,
                fontSize: 14,
                fontFamily: 'Consolas, Monaco, "Lucida Console", monospace',
                theme: {
                    background: '#000000',
                    foreground: '#ffffff',
                    cursor: '#ffffff',
                    selection: '#ffffff'
                },
                rows: 24,
                cols: 80
            });
            
            // Mount terminal
            this.websocketTerminal.open(container);
            
            // Connect to WebSocket server
            this.connectWebSocket();
        },

        connectWebSocket() {
            const wsUrl = `${this.websocketUrl}?token=${encodeURIComponent(this.websocketToken)}`;
            this.websocket = new WebSocket(wsUrl);
            
            this.websocket.onopen = () => {
                console.log('WebSocket connected');
                
                // Send authentication
                this.websocket.send(JSON.stringify({
                    type: 'auth',
                    token: this.websocketToken
                }));
            };
            
            this.websocket.onmessage = (event) => {
                const message = JSON.parse(event.data);
                
                switch (message.type) {
                    case 'connected':
                        console.log('WebSocket server connected:', message.message);
                        break;
                        
                    case 'auth_success':
                        console.log('WebSocket authenticated');
                        // Now connect to SSH
                        this.websocket.send(JSON.stringify({
                            type: 'connect',
                            rows: this.websocketTerminal.rows,
                            cols: this.websocketTerminal.cols
                        }));
                        break;
                        
                    case 'ready':
                        console.log('SSH connection ready');
                        this.websocketConnected = true;
                        this.showNotification('✅ WebSocket terminal connected!', 'success');
                        break;
                        
                    case 'data':
                        if (this.websocketTerminal) {
                            this.websocketTerminal.write(message.data);
                        }
                        break;
                        
                    case 'error':
                        console.error('WebSocket error:', message.message);
                        this.showNotification('❌ WebSocket error: ' + message.message, 'error');
                        break;
                        
                    case 'disconnected':
                        console.log('SSH session ended');
                        this.websocketConnected = false;
                        this.showNotification('⚠️ SSH session ended', 'info');
                        break;
                        
                    case 'pong':
                        // Keep-alive response
                        break;
                }
            };
            
            this.websocket.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.showNotification('❌ WebSocket connection error', 'error');
            };
            
            this.websocket.onclose = (event) => {
                console.log('WebSocket closed:', event.code, event.reason);
                this.websocketConnected = false;
                
                if (event.code !== 1000) { // Not a normal closure
                    this.showNotification('❌ WebSocket connection lost', 'error');
                }
            };
            
            // Handle terminal input
            this.websocketTerminal.onData((data) => {
                if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
                    this.websocket.send(JSON.stringify({
                        type: 'input',
                        data: data
                    }));
                }
            });
            
            // Handle terminal resize
            this.websocketTerminal.onResize(({rows, cols}) => {
                if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
                    this.websocket.send(JSON.stringify({
                        type: 'resize',
                        rows: rows,
                        cols: cols
                    }));
                }
            });
            
            // Send periodic ping to keep connection alive
            this.pingInterval = setInterval(() => {
                if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
                    this.websocket.send(JSON.stringify({ type: 'ping' }));
                }
            }, 30000); // Ping every 30 seconds
        },

        async checkWebSocketStatus() {
            try {
                const response = await fetch('/server-manager/terminal/websocket/status', {
                    method: 'GET',
                    headers: window.getDefaultHeaders()
                });
                
                const result = await response.json();
                this.websocketStatus = result;
                
                if (result.status === 'running') {
                    this.showNotification(`✅ WebSocket server is running on ${result.websocket_url}`, 'success');
                } else {
                    this.showNotification(`❌ WebSocket server is not running: ${result.message}`, 'error');
                }
            } catch (error) {
                this.showNotification('❌ Failed to check WebSocket status: ' + error.message, 'error');
            }
        },

        clearTerminal() {
            if (this.websocketTerminal) {
                this.websocketTerminal.clear();
            } else if (this.terminalSession && this.terminal) {
                this.terminal.clear();
                this.commandHistory = '';
            }
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        init() {
            if (this.connected) {
                this.loadStatus();
                // Auto-refresh status every 30 seconds
                setInterval(() => {
                    if (this.connected) {
                        this.loadStatus();
                    }
                }, 30000);
            }

            // Watch for autoRefresh changes
            this.$watch('autoRefresh', (value) => {
                this.startLogAutoRefresh();
            });

            // Load log files when logs tab is accessed
            this.$watch('activeTab', (value) => {
                if (value === 'logs' && this.logFiles.length === 0) {
                    this.loadLogFiles();
                }
            });
        }
    }
}
</script>
@endpush