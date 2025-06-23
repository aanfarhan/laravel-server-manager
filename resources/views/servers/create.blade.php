@extends('server-manager::layouts.app')

@section('title', 'Add New Server')

@section('content')
<div x-data="serverForm()" class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-server mr-2"></i>
                    Add New Server
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Add a new server to manage and monitor
                </p>
            </div>
            <a href="{{ route('server-manager.servers.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Servers
            </a>
        </div>
    </div>

    <!-- Server Form -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <form @submit.prevent="submitForm()" class="space-y-6 p-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Server Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name"
                           x-model="form.name" 
                           required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                           placeholder="My Production Server">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">A descriptive name for this server</p>
                </div>

                <div class="sm:col-span-2">
                    <label for="host" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Host/IP Address <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="host"
                           x-model="form.host" 
                           required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                           placeholder="192.168.1.100">
                </div>

                <div class="sm:col-span-1">
                    <label for="port" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Port
                    </label>
                    <input type="number" 
                           id="port"
                           x-model="form.port" 
                           min="1" 
                           max="65535"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                           placeholder="22">
                </div>

                <div class="sm:col-span-3">
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Username <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="username"
                           x-model="form.username" 
                           required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                           placeholder="ubuntu">
                </div>

                <div class="sm:col-span-3">
                    <label for="auth_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Authentication Type <span class="text-red-500">*</span>
                    </label>
                    <select id="auth_type"
                            x-model="form.auth_type" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="password">Password Authentication</option>
                        <option value="key">Private Key Authentication</option>
                    </select>
                </div>
            </div>

            <!-- Authentication Details -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-key mr-2"></i>
                    Authentication Details
                </h3>

                <!-- Password Authentication -->
                <div x-show="form.auth_type === 'password'" class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <input type="password" 
                               id="password"
                               x-model="form.password" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Passwords are encrypted before storage
                        </p>
                    </div>
                </div>

                <!-- Private Key Authentication -->
                <div x-show="form.auth_type === 'key'" class="space-y-4">
                    <div>
                        <label for="private_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Private Key <span class="text-red-500">*</span>
                        </label>
                        <textarea id="private_key"
                                  x-model="form.private_key" 
                                  rows="8"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white font-mono text-sm"
                                  placeholder="-----BEGIN PRIVATE KEY-----
...
-----END PRIVATE KEY-----"></textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Private keys are encrypted before storage
                        </p>
                    </div>

                    <div>
                        <label for="private_key_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Private Key Password (if protected)
                        </label>
                        <input type="password" 
                               id="private_key_password"
                               x-model="form.private_key_password" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Only required if your private key is password protected</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex justify-between">
                    <button type="button" 
                            @click="testConnection()"
                            :disabled="loading"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                        <i class="fas fa-check mr-2"></i>
                        Test Connection
                    </button>

                    <div class="flex space-x-3">
                        <a href="{{ route('server-manager.servers.index') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                            Cancel
                        </a>
                        <button type="submit" 
                                :disabled="loading"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                            <i class="fas fa-save mr-2"></i>
                            <span x-text="loading ? 'Saving...' : 'Save Server'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function serverForm() {
    return {
        form: {
            name: '',
            host: '',
            port: 22,
            username: '',
            auth_type: 'password',
            password: '',
            private_key: '',
            private_key_password: ''
        },
        loading: false,

        async testConnection() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("server-manager.servers.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.getCsrfToken()
                    },
                    body: JSON.stringify(this.form)
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

        async submitForm() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("server-manager.servers.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.getCsrfToken()
                    },
                    body: JSON.stringify(this.form)
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('✅ Server saved successfully!');
                    window.location.href = '{{ route("server-manager.servers.index") }}';
                } else {
                    alert('❌ Failed to save server: ' + result.message);
                }
            } catch (error) {
                alert('❌ Failed to save server: ' + error.message);
            }
            this.loading = false;
        }
    }
}
</script>
@endpush