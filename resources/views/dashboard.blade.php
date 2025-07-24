@extends('server-manager::layouts.app')

@section('title', 'Dashboard - Server Manager')

@section('content')
<div x-data="dashboard()" x-init="init()" class="space-y-6">
    <!-- Dashboard Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-tachometer-alt mr-2 text-blue-600"></i>
                        Dashboard
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Overview of all your servers and their status
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="refreshAll()" 
                            :disabled="loading"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                        <i class="fas fa-sync-alt mr-2" :class="{ 'animate-spin': loading }"></i>
                        Refresh All
                    </button>
                    <a href="{{ route('server-manager.servers.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-2"></i>
                        Add Server
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Servers -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-server text-2xl text-blue-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Total Servers
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white" x-text="stats.total_servers">
                                0
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connected Servers -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-plug text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Connected
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white" x-text="stats.connected_servers">
                                0
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Servers with Issues -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                With Issues
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white" x-text="stats.error_servers">
                                0
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Sessions -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-terminal text-2xl text-purple-600"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Active Sessions
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white" x-text="stats.active_sessions">
                                0
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Server Grid -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    <i class="fas fa-list mr-2"></i>
                    Server Overview
                </h3>
                <div class="flex items-center space-x-3">
                    <!-- View Toggle -->
                    <div class="flex rounded-md shadow-sm" role="group">
                        <button @click="viewMode = 'grid'" 
                                :class="viewMode === 'grid' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                class="px-3 py-2 text-sm font-medium border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button @click="viewMode = 'list'" 
                                :class="viewMode === 'list' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                class="px-3 py-2 text-sm font-medium border-t border-b border-r border-gray-300 rounded-r-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid View -->
        <div x-show="viewMode === 'grid'" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="server in servers" :key="server.id">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600 hover:shadow-md transition-shadow">
                        <!-- Server Header -->
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white" x-text="server.name"></h4>
                            <div class="flex items-center space-x-2">
                                <span :class="{
                                    'bg-green-100 text-green-800': server.status === 'connected',
                                    'bg-red-100 text-red-800': server.status === 'error',
                                    'bg-gray-100 text-gray-800': server.status === 'disconnected'
                                }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    <i :class="{
                                        'fas fa-circle text-green-400': server.status === 'connected',
                                        'fas fa-exclamation-circle text-red-400': server.status === 'error',
                                        'fas fa-circle text-gray-400': server.status === 'disconnected'
                                    }" class="mr-1"></i>
                                    <span x-text="server.status.charAt(0).toUpperCase() + server.status.slice(1)"></span>
                                </span>
                            </div>
                        </div>

                        <!-- Server Info -->
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-globe-americas w-4 mr-2"></i>
                                <span x-text="server.host + ':' + server.port"></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-user w-4 mr-2"></i>
                                <span x-text="server.username"></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400" x-show="server.last_connected_at">
                                <i class="fas fa-clock w-4 mr-2"></i>
                                <span x-text="'Last: ' + (server.last_connected_at || 'Never')"></span>
                            </div>
                        </div>

                        <!-- Metrics (if connected) -->
                        <div x-show="server.status === 'connected' && server.metrics" class="mb-4">
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div class="text-center">
                                    <div class="text-gray-500 dark:text-gray-400">CPU</div>
                                    <div class="font-medium" x-text="server.metrics?.cpu + '%'"></div>
                                </div>
                                <div class="text-center">
                                    <div class="text-gray-500 dark:text-gray-400">Memory</div>
                                    <div class="font-medium" x-text="server.metrics?.memory + '%'"></div>
                                </div>
                                <div class="text-center">
                                    <div class="text-gray-500 dark:text-gray-400">Disk</div>
                                    <div class="font-medium" x-text="server.metrics?.disk + '%'"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between">
                            <div class="flex space-x-2">
                                <button @click="connectServer(server.id)" 
                                        x-show="server.status !== 'connected'"
                                        class="text-green-600 hover:text-green-900 text-sm">
                                    <i class="fas fa-plug"></i>
                                </button>
                                <button @click="disconnectServer(server.id)" 
                                        x-show="server.status === 'connected'"
                                        class="text-red-600 hover:text-red-900 text-sm">
                                    <i class="fas fa-unplug"></i>
                                </button>
                                <a :href="'/server-manager/servers/' + server.id" 
                                   class="text-blue-600 hover:text-blue-900 text-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                            <a :href="'/server-manager/servers/' + server.id + '/edit'" 
                               class="text-gray-600 hover:text-gray-900 text-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- List View -->
        <div x-show="viewMode === 'list'" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Server</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Metrics</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Last Connected</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="server in servers" :key="server.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="server.name"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="server.username + '@' + server.host + ':' + server.port"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="{
                                    'bg-green-100 text-green-800': server.status === 'connected',
                                    'bg-red-100 text-red-800': server.status === 'error',
                                    'bg-gray-100 text-gray-800': server.status === 'disconnected'
                                }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    <i :class="{
                                        'fas fa-circle text-green-400': server.status === 'connected',
                                        'fas fa-exclamation-circle text-red-400': server.status === 'error',
                                        'fas fa-circle text-gray-400': server.status === 'disconnected'
                                    }" class="mr-1"></i>
                                    <span x-text="server.status.charAt(0).toUpperCase() + server.status.slice(1)"></span>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                <div x-show="server.status === 'connected' && server.metrics" class="flex space-x-4">
                                    <span>CPU: <span x-text="server.metrics?.cpu + '%'"></span></span>
                                    <span>Mem: <span x-text="server.metrics?.memory + '%'"></span></span>
                                    <span>Disk: <span x-text="server.metrics?.disk + '%'"></span></span>
                                </div>
                                <span x-show="server.status !== 'connected'" class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" x-text="server.last_connected_at || 'Never'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button @click="connectServer(server.id)" 
                                        x-show="server.status !== 'connected'"
                                        class="text-green-600 hover:text-green-900">
                                    <i class="fas fa-plug"></i>
                                </button>
                                <button @click="disconnectServer(server.id)" 
                                        x-show="server.status === 'connected'"
                                        class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-unplug"></i>
                                </button>
                                <a :href="'/server-manager/servers/' + server.id" 
                                   class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a :href="'/server-manager/servers/' + server.id + '/edit'" 
                                   class="text-yellow-600 hover:text-yellow-900">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                <i class="fas fa-history mr-2"></i>
                Recent Activity
            </h3>
        </div>
        <div class="p-6">
            <div class="flow-root">
                <ul class="-mb-8">
                    <template x-for="(activity, index) in recentActivity" :key="activity.id">
                        <li>
                            <div class="relative pb-8" x-show="index < recentActivity.length - 1">
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-600" aria-hidden="true"></span>
                            </div>
                            <div class="relative flex space-x-3">
                                <div>
                                    <span :class="{
                                        'bg-green-500': activity.type === 'connected',
                                        'bg-red-500': activity.type === 'disconnected',
                                        'bg-blue-500': activity.type === 'created',
                                        'bg-yellow-500': activity.type === 'updated'
                                    }" class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                        <i :class="{
                                            'fas fa-plug': activity.type === 'connected',
                                            'fas fa-unplug': activity.type === 'disconnected',
                                            'fas fa-plus': activity.type === 'created',
                                            'fas fa-edit': activity.type === 'updated'
                                        }" class="h-4 w-4 text-white"></i>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="activity.message"></p>
                                    </div>
                                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        <time x-text="activity.time"></time>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dashboard() {
    return {
        loading: false,
        viewMode: 'grid',
        servers: [],
        stats: {
            total_servers: 0,
            connected_servers: 0,
            error_servers: 0,
            active_sessions: 0
        },
        recentActivity: [],

        async init() {
            await this.loadData();
            this.startAutoRefresh();
        },

        async loadData() {
            this.loading = true;
            try {
                await Promise.all([
                    this.loadServers(),
                    this.loadStats(),
                    this.loadRecentActivity()
                ]);
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
            }
            this.loading = false;
        },

        async loadServers() {
            try {
                const response = await fetch('{{ route("server-manager.servers.list") }}', {
                    headers: window.getDefaultHeaders()
                });
                const result = await response.json();
                
                if (result.success) {
                    this.servers = result.servers;
                    // Load metrics for connected servers
                    await this.loadServerMetrics();
                }
            } catch (error) {
                console.error('Failed to load servers:', error);
            }
        },

        async loadServerMetrics() {
            for (let server of this.servers) {
                if (server.status === 'connected') {
                    try {
                        const response = await fetch(`{{ route("server-manager.servers.status") }}?server_id=${server.id}`, {
                            headers: window.getDefaultHeaders()
                        });
                        const result = await response.json();
                        
                        if (result.success && result.data) {
                            server.metrics = {
                                cpu: Math.round(result.data.cpu?.usage_percent || 0),
                                memory: Math.round(result.data.memory?.usage_percent || 0),
                                disk: Math.round(result.data.disk?.usage_percent || 0)
                            };
                        }
                    } catch (error) {
                        console.error(`Failed to load metrics for server ${server.id}:`, error);
                    }
                }
            }
        },

        async loadStats() {
            this.stats.total_servers = this.servers.length;
            this.stats.connected_servers = this.servers.filter(s => s.status === 'connected').length;
            this.stats.error_servers = this.servers.filter(s => s.status === 'error').length;
            // TODO: Load active sessions from WebSocket service
            this.stats.active_sessions = 0;
        },

        async loadRecentActivity() {
            // Mock recent activity - in real implementation, this would come from a log/audit table
            this.recentActivity = [
                {
                    id: 1,
                    type: 'connected',
                    message: 'Connected to Production Server',
                    time: '2 minutes ago'
                },
                {
                    id: 2,
                    type: 'created',
                    message: 'Created new server: Staging Server',
                    time: '1 hour ago'
                },
                {
                    id: 3,
                    type: 'disconnected',
                    message: 'Disconnected from Development Server',
                    time: '3 hours ago'
                }
            ];
        },

        async refreshAll() {
            await this.loadData();
        },

        async connectServer(serverId) {
            try {
                const response = await fetch('{{ route("server-manager.servers.connect") }}', {
                    method: 'POST',
                    headers: window.getDefaultHeaders(),
                    body: JSON.stringify({ server_id: serverId })
                });
                
                const result = await response.json();
                if (result.success) {
                    await this.loadServers();
                } else {
                    alert('Connection failed: ' + result.message);
                }
            } catch (error) {
                alert('Connection failed: ' + error.message);
            }
        },

        async disconnectServer(serverId) {
            try {
                const response = await fetch('{{ route("server-manager.servers.disconnect") }}', {
                    method: 'POST',
                    headers: window.getDefaultHeaders(),
                    body: JSON.stringify({ server_id: serverId })
                });
                
                const result = await response.json();
                if (result.success) {
                    await this.loadServers();
                }
            } catch (error) {
                console.error('Disconnect failed:', error);
            }
        },

        startAutoRefresh() {
            setInterval(() => {
                if (!this.loading) {
                    this.loadData();
                }
            }, 30000); // Refresh every 30 seconds
        }
    }
}
</script>
@endpush