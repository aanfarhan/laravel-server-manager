@extends('server-manager::layouts.app')

@section('title', 'Server Management')

@section('content')
<div x-data="serverManager()" class="space-y-6">
    <!-- Server Management Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-server mr-2"></i>
                    Server Management
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage your servers and monitor their status
                </p>
            </div>
            <a href="{{ route('server-manager.servers.create') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-plus mr-2"></i>
                Add Server
            </a>
        </div>
    </div>

    <!-- Saved Servers List -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                <i class="fas fa-list mr-2"></i>
                Saved Servers
            </h3>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700">
            @if($servers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Host</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Last Connected</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($servers as $server)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $server->name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Port: {{ $server->port }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                        {{ $server->host }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                        {{ $server->username }}
                                        <span class="ml-1 text-xs text-gray-500">({{ $server->connection_type }})</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($server->status === 'connected')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-circle text-green-400 mr-1"></i>
                                                Connected
                                            </span>
                                        @elseif($server->status === 'error')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-exclamation-circle text-red-400 mr-1"></i>
                                                Error
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                <i class="fas fa-circle text-gray-400 mr-1"></i>
                                                Disconnected
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $server->last_connected_at ? $server->last_connected_at->diffForHumans() : 'Never' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <button @click="connectToServer({{ $server->id }})" 
                                                class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-plug"></i>
                                        </button>
                                        <button @click="testServerConnection({{ $server->id }})" 
                                                class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <a href="{{ route('server-manager.servers.show', $server) }}" 
                                           class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('server-manager.servers.edit', $server) }}" 
                                           class="text-yellow-600 hover:text-yellow-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button @click="deleteServer({{ $server->id }})" 
                                                class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-server text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No servers found</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Get started by adding your first server</p>
                    <a href="{{ route('server-manager.servers.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>
                        Add Your First Server
                    </a>
                </div>
            @endif
        </div>
    </div>
    <!-- Connection Form -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                <i class="fas fa-plug mr-2"></i>
                SSH Connection
            </h3>
            <div class="mt-4 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Host</label>
                    <input type="text" x-model="connection.host" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Port</label>
                    <input type="number" x-model="connection.port" value="22"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                    <input type="text" x-model="connection.username" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Auth Type</label>
                    <select x-model="connection.auth_type" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="password">Password</option>
                        <option value="key">Private Key</option>
                    </select>
                </div>
                <div class="sm:col-span-3" x-show="connection.auth_type === 'password'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <input type="password" x-model="connection.password" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div class="sm:col-span-3" x-show="connection.auth_type === 'key'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Private Key</label>
                    <textarea x-model="connection.private_key" rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                </div>
            </div>
            <div class="mt-4 flex space-x-3">
                <button @click="testConnection()" :disabled="loading"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                    <i class="fas fa-check mr-2"></i>
                    Test Connection
                </button>
                <button @click="connect()" :disabled="loading"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50">
                    <i class="fas fa-plug mr-2"></i>
                    Connect
                </button>
                <button @click="disconnect()" x-show="connected"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-times mr-2"></i>
                    Disconnect
                </button>
            </div>
        </div>
    </div>

    <!-- Server Status -->
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

    <!-- Processes Table -->
    <div x-show="connected" class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                <i class="fas fa-list mr-2"></i>
                Top Processes
            </h3>
            <button @click="loadProcesses()" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                <i class="fas fa-sync-alt mr-1"></i>
                Refresh
            </button>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">PID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">CPU %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Memory %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Command</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="process in processes" :key="process.pid">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300" x-text="process.user"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300" x-text="process.pid"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300" x-text="process.cpu + '%'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300" x-text="process.memory + '%'"></td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300 truncate max-w-xs" x-text="process.command"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function serverManager() {
    return {
        connection: {
            host: '',
            port: 22,
            username: '',
            auth_type: 'password',
            password: '',
            private_key: ''
        },
        connected: false,
        loading: false,
        status: {},
        processes: [],

        async testConnection() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("server-manager.servers.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.connection)
                });
                
                const result = await response.json();
                alert(result.message);
            } catch (error) {
                alert('Connection test failed: ' + error.message);
            }
            this.loading = false;
        },

        async testServerConnection(serverId) {
            try {
                const response = await fetch('{{ route("server-manager.servers.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ server_id: serverId })
                });
                
                const result = await response.json();
                alert(result.message);
            } catch (error) {
                alert('Connection test failed: ' + error.message);
            }
        },

        async connectToServer(serverId) {
            this.loading = true;
            try {
                const response = await fetch('{{ route("server-manager.servers.connect") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ server_id: serverId })
                });
                
                const result = await response.json();
                if (result.success) {
                    this.connected = true;
                    this.loadStatus();
                    this.loadProcesses();
                    this.startAutoRefresh();
                    alert('Connected successfully to ' + result.server.name);
                    // Refresh the page to update server status
                    location.reload();
                } else {
                    alert('Connection failed: ' + result.message);
                }
            } catch (error) {
                alert('Connection failed: ' + error.message);
            }
            this.loading = false;
        },

        async deleteServer(serverId) {
            if (!confirm('Are you sure you want to delete this server? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch(`/server-manager/servers/${serverId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('Server deleted successfully');
                    location.reload();
                } else {
                    alert('Failed to delete server: ' + result.message);
                }
            } catch (error) {
                alert('Failed to delete server: ' + error.message);
            }
        },

        async connect() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("server-manager.servers.connect") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.connection)
                });
                
                const result = await response.json();
                if (result.success) {
                    this.connected = true;
                    this.loadStatus();
                    this.loadProcesses();
                    this.startAutoRefresh();
                } else {
                    alert('Connection failed: ' + result.message);
                }
            } catch (error) {
                alert('Connection failed: ' + error.message);
            }
            this.loading = false;
        },

        async disconnect() {
            try {
                await fetch('{{ route("server-manager.servers.disconnect") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                this.connected = false;
                this.status = {};
                this.processes = [];
            } catch (error) {
                console.error('Disconnect error:', error);
            }
        },

        async loadStatus() {
            try {
                const response = await fetch('{{ route("server-manager.servers.status") }}');
                const result = await response.json();
                if (result.success) {
                    this.status = result.data;
                }
            } catch (error) {
                console.error('Status load error:', error);
            }
        },

        async loadProcesses() {
            try {
                const response = await fetch('{{ route("server-manager.servers.processes") }}');
                const result = await response.json();
                if (result.success) {
                    this.processes = result.processes;
                }
            } catch (error) {
                console.error('Processes load error:', error);
            }
        },

        startAutoRefresh() {
            setInterval(() => {
                if (this.connected) {
                    this.loadStatus();
                }
            }, 30000); // Refresh every 30 seconds
        }
    }
}
</script>
@endpush