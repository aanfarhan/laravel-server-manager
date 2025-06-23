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
</div>
@endsection

@push('scripts')
<script>
function serverManager() {
    return {
        async testServerConnection(serverId) {
            try {
                const response = await fetch('{{ route("server-manager.servers.test") }}', {
                    method: 'POST',
                    headers: window.getDefaultHeaders(),
                    body: JSON.stringify({ server_id: serverId })
                });
                
                const result = await response.json();
                alert(result.message);
            } catch (error) {
                alert('Connection test failed: ' + error.message);
            }
        },

        async connectToServer(serverId) {
            try {
                const response = await fetch('{{ route("server-manager.servers.connect") }}', {
                    method: 'POST',
                    headers: window.getDefaultHeaders(),
                    body: JSON.stringify({ server_id: serverId })
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('Connected successfully to ' + result.server.name);
                    location.reload();
                } else {
                    alert('Connection failed: ' + result.message);
                }
            } catch (error) {
                alert('Connection failed: ' + error.message);
            }
        },

        async deleteServer(serverId) {
            if (!confirm('Are you sure you want to delete this server? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch(`/server-manager/servers/${serverId}`, {
                    method: 'DELETE',
                    headers: window.getDefaultHeaders()
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
        }
    }
}
</script>
@endpush