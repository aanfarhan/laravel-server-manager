@extends('server-manager::layouts.app')

@section('title', 'Server Details - ' . $server->name)

@section('content')
<div x-data="serverDetails()" class="space-y-6">
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

        <!-- Deployments Count -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-rocket text-2xl text-purple-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Deployments</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ $server->deployments->count() }}</dd>
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

    <!-- Recent Deployments -->
    @if($server->deployments->count() > 0)
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                <i class="fas fa-rocket mr-2"></i>
                Recent Deployments
            </h3>
            <a href="{{ route('server-manager.deployments.index') }}" 
               class="text-sm text-blue-600 hover:text-blue-900 dark:text-blue-400">
                View All
            </a>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Repository</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Last Deployed</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($server->deployments->take(5) as $deployment)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $deployment->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $deployment->repository }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($deployment->status === 'success')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Success
                                        </span>
                                    @elseif($deployment->status === 'failed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Failed
                                        </span>
                                    @elseif($deployment->status === 'deploying')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Deploying
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Idle
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $deployment->last_deployed_at ? $deployment->last_deployed_at->diffForHumans() : 'Never' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

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

        async testConnection() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("server-manager.servers.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
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
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
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
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
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
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
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
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
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