@extends('server-manager::layouts.app')

@section('title', 'Deployment Management')

@section('content')
<div x-data="deploymentManager()" class="space-y-6">
    <!-- Deployment Form -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                <i class="fas fa-rocket mr-2"></i>
                Deploy Application
            </h3>
            <div class="mt-4 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Repository URL</label>
                        <input type="text" x-model="deployment.repository" placeholder="https://github.com/user/repo.git"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Branch</label>
                        <input type="text" x-model="deployment.branch" placeholder="main"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Deploy Path</label>
                    <input type="text" x-model="deployment.deploy_path" placeholder="/var/www/html"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pre-Deploy Commands</label>
                        <textarea x-model="deployment.pre_deploy_commands" rows="3" placeholder="npm install&#10;composer install"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Build Commands</label>
                        <textarea x-model="deployment.build_commands" rows="3" placeholder="npm run build&#10;php artisan optimize"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Post-Deploy Commands</label>
                        <textarea x-model="deployment.post_deploy_commands" rows="3" placeholder="php artisan migrate&#10;php artisan cache:clear"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex space-x-3">
                <button @click="deploy()" :disabled="loading"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50">
                    <i class="fas fa-rocket mr-2"></i>
                    <span x-show="!loading">Deploy</span>
                    <span x-show="loading">Deploying...</span>
                </button>
                <button @click="getStatus()" :disabled="!deployment.deploy_path"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                    <i class="fas fa-info-circle mr-2"></i>
                    Get Status
                </button>
                <button @click="rollback()" :disabled="!deployment.deploy_path"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                    <i class="fas fa-undo mr-2"></i>
                    Rollback
                </button>
            </div>
        </div>
    </div>

    <!-- Deployment Status -->
    <div x-show="deploymentStatus.success" class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                <i class="fas fa-info-circle mr-2"></i>
                Current Deployment Status
            </h3>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Latest Commit</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono" x-text="deploymentStatus.commit_hash?.substring(0, 12)"></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Author</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white" x-text="deploymentStatus.author"></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white" x-text="deploymentStatus.date"></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Message</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white" x-text="deploymentStatus.message"></dd>
                </div>
            </div>
        </div>
    </div>

    <!-- Deployment Log -->
    <div x-show="deploymentLog.length > 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                <i class="fas fa-terminal mr-2"></i>
                Deployment Log
            </h3>
            <div class="mt-4 bg-gray-900 rounded-lg p-4 max-h-96 overflow-y-auto">
                <pre class="text-green-400 text-sm font-mono whitespace-pre-wrap" x-text="deploymentLog.join('\n')"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deploymentManager() {
    return {
        deployment: {
            repository: '',
            branch: 'main',
            deploy_path: '',
            pre_deploy_commands: '',
            build_commands: '',
            post_deploy_commands: ''
        },
        loading: false,
        deploymentStatus: {},
        deploymentLog: [],

        async deploy() {
            this.loading = true;
            this.deploymentLog = [];
            
            try {
                const deploymentData = {
                    ...this.deployment,
                    pre_deploy_commands: this.deployment.pre_deploy_commands.split('\n').filter(cmd => cmd.trim()),
                    build_commands: this.deployment.build_commands.split('\n').filter(cmd => cmd.trim()),
                    post_deploy_commands: this.deployment.post_deploy_commands.split('\n').filter(cmd => cmd.trim())
                };

                const response = await fetch('{{ route("server-manager.deployments.deploy") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(deploymentData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.deploymentLog = result.log || [];
                    alert('Deployment completed successfully!');
                } else {
                    this.deploymentLog = result.log || [];
                    alert('Deployment failed: ' + result.message);
                }
            } catch (error) {
                alert('Deployment failed: ' + error.message);
            }
            
            this.loading = false;
        },

        async rollback() {
            if (!confirm('Are you sure you want to rollback the last deployment?')) {
                return;
            }

            try {
                const response = await fetch('{{ route("server-manager.deployments.rollback") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        deploy_path: this.deployment.deploy_path,
                        commits_back: 1
                    })
                });
                
                const result = await response.json();
                alert(result.message);
                
                if (result.success) {
                    this.getStatus();
                }
            } catch (error) {
                alert('Rollback failed: ' + error.message);
            }
        },

        async getStatus() {
            try {
                const response = await fetch('{{ route("server-manager.deployments.status") }}?' + 
                    new URLSearchParams({ deploy_path: this.deployment.deploy_path }));
                
                const result = await response.json();
                
                if (result.success) {
                    this.deploymentStatus = result;
                } else {
                    alert('Failed to get deployment status: ' + result.message);
                }
            } catch (error) {
                alert('Failed to get deployment status: ' + error.message);
            }
        }
    }
}
</script>
@endpush