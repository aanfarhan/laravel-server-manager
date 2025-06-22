@extends('server-manager::layouts.app')

@section('title', 'Server Management')

@section('content')
<div x-data="serverManager()" class="space-y-6">
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