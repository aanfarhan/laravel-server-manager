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
        <nav class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ route('server-manager.index') }}" class="text-xl font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            <i class="fas fa-server mr-2"></i>
                            Server Manager
                        </a>
                    </div>
                    <div class="flex items-center space-x-1">
                        <a href="{{ route('server-manager.index') }}" 
                           class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium transition-colors {{ request()->routeIs('server-manager.index') ? 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400' : '' }}">
                            <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                        </a>
                        <a href="{{ route('server-manager.servers.index') }}" 
                           class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium transition-colors {{ request()->routeIs('server-manager.servers.*') ? 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400' : '' }}">
                            <i class="fas fa-server mr-1"></i> Servers
                        </a>
                        <a href="{{ route('server-manager.logs.index') }}" 
                           class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium transition-colors {{ request()->routeIs('server-manager.logs.*') ? 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400' : '' }}">
                            <i class="fas fa-file-alt mr-1"></i> Logs
                        </a>
                        
                        <!-- Theme Toggle -->
                        <button id="theme-toggle" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-moon dark:hidden"></i>
                            <i class="fas fa-sun hidden dark:inline"></i>
                        </button>
                        
                        <!-- User Menu -->
                        <div class="relative ml-3" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-user-circle text-2xl text-gray-700 dark:text-gray-300"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                                <div class="py-1">
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-cog mr-2"></i> Settings
                                    </a>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-question-circle mr-2"></i> Help
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Breadcrumb Navigation -->
        <nav class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600" aria-label="Breadcrumb">
            <div class="max-w-7xl mx-auto px-4 py-3">
                <ol class="flex items-center space-x-2 text-sm">
                    <li>
                        <a href="{{ route('server-manager.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    @if(request()->routeIs('server-manager.servers.*'))
                        <li class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <a href="{{ route('server-manager.servers.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                                Servers
                            </a>
                        </li>
                        @if(request()->routeIs('server-manager.servers.create'))
                            <li class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                <span class="text-gray-700 dark:text-gray-300">Add Server</span>
                            </li>
                        @elseif(request()->routeIs('server-manager.servers.edit'))
                            <li class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                <span class="text-gray-700 dark:text-gray-300">Edit Server</span>
                            </li>
                        @elseif(request()->routeIs('server-manager.servers.show'))
                            <li class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                <span class="text-gray-700 dark:text-gray-300">Server Details</span>
                            </li>
                        @endif
                    @elseif(request()->routeIs('server-manager.logs.*'))
                        <li class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-gray-700 dark:text-gray-300">Logs</span>
                        </li>
                    @endif
                </ol>
            </div>
        </nav>
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 px-4">
            @yield('content')
        </main>
        
        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12">
            <div class="max-w-7xl mx-auto py-6 px-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        © 2024 Laravel Server Manager. Built with ❤️ for server administrators.
                    </div>
                    <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                        <a href="#" class="hover:text-gray-700 dark:hover:text-gray-300">Documentation</a>
                        <a href="#" class="hover:text-gray-700 dark:hover:text-gray-300">Support</a>
                        <a href="#" class="hover:text-gray-700 dark:hover:text-gray-300">GitHub</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Theme Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');
            const html = document.documentElement;
            
            // Check for saved theme preference or default to 'dark'
            const currentTheme = localStorage.getItem('theme') || 'dark';
            html.classList.toggle('dark', currentTheme === 'dark');
            
            themeToggle.addEventListener('click', function() {
                const isDark = html.classList.contains('dark');
                html.classList.toggle('dark', !isDark);
                localStorage.setItem('theme', isDark ? 'light' : 'dark');
            });
        });
        
        // Centralized CSRF token management
        window.getCsrfToken = function() {
            const token = document.head.querySelector('meta[name="csrf-token"]');
            if (!token) {
                console.error('CSRF token not found in meta tag');
                return '';
            }
            return token.content;
        };
        
        // Helper function to get default headers with fresh CSRF token
        window.getDefaultHeaders = function() {
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.getCsrfToken()
            };
        };
        
        // Debug function to check CSRF token
        window.debugCsrfToken = function() {
            const token = window.getCsrfToken();
            const metaTag = document.head.querySelector('meta[name="csrf-token"]');
            console.log('CSRF Debug Info:');
            console.log('- Meta tag exists:', !!metaTag);
            console.log('- Meta tag content:', metaTag ? metaTag.content : 'N/A');
            console.log('- Token length:', token ? token.length : 0);
            console.log('- Token preview:', token ? token.substring(0, 10) + '...' : 'EMPTY/NOT FOUND');
            console.log('- Headers object:', window.getDefaultHeaders());
            return {
                metaExists: !!metaTag,
                metaContent: metaTag ? metaTag.content : null,
                tokenLength: token ? token.length : 0,
                token: token
            };
        };
        
        // Auto-debug on page load
        window.addEventListener('load', function() {
            console.log('🔍 Auto CSRF Debug on page load:');
            window.debugCsrfToken();
        });
    </script>
    @stack('scripts')
</body>
</html>