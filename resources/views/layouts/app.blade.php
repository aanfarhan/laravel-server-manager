<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Server Manager')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            <i class="fas fa-server mr-2"></i>
                            Server Manager
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('server-manager.servers.index') }}" 
                           class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-server mr-1"></i> Servers
                        </a>
                        <a href="{{ route('server-manager.deployments.index') }}" 
                           class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-rocket mr-1"></i> Deployments
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 px-4">
            @yield('content')
        </main>
    </div>

    <script>
        // Ensure CSRF token is accessible globally
        window.getCsrfToken = function() {
            const token = document.head.querySelector('meta[name="csrf-token"]');
            return token ? token.content : '';
        };
        
        // Set CSRF token for fetch requests
        window.defaultHeaders = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': window.getCsrfToken()
        };
    </script>
    @stack('scripts')
</body>
</html>