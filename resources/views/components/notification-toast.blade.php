<div x-data="notificationToast()" 
     x-show="show" 
     x-transition:enter="transform ease-out duration-300 transition"
     x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
     x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed top-4 right-4 max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden z-50">
    
    <div class="p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i :class="{
                    'fas fa-check-circle text-green-400': type === 'success',
                    'fas fa-exclamation-circle text-yellow-400': type === 'warning',
                    'fas fa-times-circle text-red-400': type === 'error',
                    'fas fa-info-circle text-blue-400': type === 'info'
                }" class="h-6 w-6"></i>
            </div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="title"></p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="message"></p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
                <button @click="hide()" class="bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <span class="sr-only">Close</span>
                    <i class="fas fa-times h-5 w-5"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Progress bar for auto-hide -->
    <div x-show="autoHide" class="h-1 bg-gray-200 dark:bg-gray-700">
        <div class="h-full transition-all duration-100 ease-linear" 
             :class="{
                 'bg-green-500': type === 'success',
                 'bg-yellow-500': type === 'warning',
                 'bg-red-500': type === 'error',
                 'bg-blue-500': type === 'info'
             }"
             :style="`width: ${progress}%`"></div>
    </div>
</div>

<script>
function notificationToast() {
    return {
        show: false,
        type: 'info',
        title: '',
        message: '',
        autoHide: true,
        duration: 5000,
        progress: 100,
        progressInterval: null,

        showNotification(type, title, message, autoHide = true, duration = 5000) {
            this.type = type;
            this.title = title;
            this.message = message;
            this.autoHide = autoHide;
            this.duration = duration;
            this.progress = 100;
            this.show = true;

            if (autoHide) {
                this.startProgressBar();
                setTimeout(() => {
                    this.hide();
                }, duration);
            }
        },

        hide() {
            this.show = false;
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
            }
        },

        startProgressBar() {
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
            }
            
            const step = 100 / (this.duration / 100);
            this.progressInterval = setInterval(() => {
                this.progress -= step;
                if (this.progress <= 0) {
                    clearInterval(this.progressInterval);
                }
            }, 100);
        }
    }
}

// Global notification function
window.showNotification = function(type, title, message, autoHide = true, duration = 5000) {
    // Dispatch custom event that the toast component can listen to
    window.dispatchEvent(new CustomEvent('show-notification', {
        detail: { type, title, message, autoHide, duration }
    }));
};

// Listen for global notifications
document.addEventListener('DOMContentLoaded', function() {
    window.addEventListener('show-notification', function(event) {
        const toast = document.querySelector('[x-data*="notificationToast"]');
        if (toast && toast._x_dataStack) {
            const component = toast._x_dataStack[0];
            component.showNotification(
                event.detail.type,
                event.detail.title,
                event.detail.message,
                event.detail.autoHide,
                event.detail.duration
            );
        }
    });
});
</script>