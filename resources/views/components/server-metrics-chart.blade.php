<div x-data="metricsChart({{ json_encode($serverId ?? null) }})" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            <i class="fas fa-chart-line mr-2 text-blue-600"></i>
            Server Metrics
        </h3>
        <div class="flex items-center space-x-2">
            <button @click="timeRange = '1h'; loadData()" 
                    :class="timeRange === '1h' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-3 py-1 rounded text-sm font-medium">1H</button>
            <button @click="timeRange = '6h'; loadData()" 
                    :class="timeRange === '6h' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-3 py-1 rounded text-sm font-medium">6H</button>
            <button @click="timeRange = '24h'; loadData()" 
                    :class="timeRange === '24h' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-3 py-1 rounded text-sm font-medium">24H</button>
        </div>
    </div>

    <!-- Current Values -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="text-center">
            <div class="text-2xl font-bold" :class="{
                'text-green-600': currentMetrics.cpu < 70,
                'text-yellow-600': currentMetrics.cpu >= 70 && currentMetrics.cpu < 90,
                'text-red-600': currentMetrics.cpu >= 90
            }" x-text="currentMetrics.cpu + '%'">0%</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">CPU Usage</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold" :class="{
                'text-green-600': currentMetrics.memory < 70,
                'text-yellow-600': currentMetrics.memory >= 70 && currentMetrics.memory < 90,
                'text-red-600': currentMetrics.memory >= 90
            }" x-text="currentMetrics.memory + '%'">0%</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Memory Usage</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold" :class="{
                'text-green-600': currentMetrics.disk < 70,
                'text-yellow-600': currentMetrics.disk >= 70 && currentMetrics.disk < 90,
                'text-red-600': currentMetrics.disk >= 90
            }" x-text="currentMetrics.disk + '%'">0%</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Disk Usage</div>
        </div>
    </div>

    <!-- Chart Container -->
    <div class="relative h-64">
        <canvas id="metricsChart" class="w-full h-full"></canvas>
        <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-white dark:bg-gray-800 bg-opacity-75">
            <div class="flex items-center space-x-2">
                <i class="fas fa-spinner animate-spin text-blue-600"></i>
                <span class="text-gray-600 dark:text-gray-400">Loading metrics...</span>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function metricsChart(serverId) {
    return {
        serverId: serverId,
        timeRange: '1h',
        loading: false,
        chart: null,
        currentMetrics: {
            cpu: 0,
            memory: 0,
            disk: 0
        },

        async init() {
            if (!this.serverId) return;
            
            this.initChart();
            await this.loadData();
            this.startAutoRefresh();
        },

        initChart() {
            const ctx = document.getElementById('metricsChart').getContext('2d');
            
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'CPU %',
                            data: [],
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Memory %',
                            data: [],
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Disk %',
                            data: [],
                            borderColor: 'rgb(245, 158, 11)',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        x: {
                            type: 'time',
                            time: {
                                displayFormats: {
                                    minute: 'HH:mm',
                                    hour: 'HH:mm'
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        },

        async loadData() {
            if (!this.serverId) return;
            
            this.loading = true;
            try {
                // Load current metrics
                const statusResponse = await fetch(`/server-manager/servers/status?server_id=${this.serverId}`, {
                    headers: window.getDefaultHeaders()
                });
                const statusResult = await statusResponse.json();
                
                if (statusResult.success && statusResult.data) {
                    this.currentMetrics = {
                        cpu: Math.round(statusResult.data.cpu?.usage_percent || 0),
                        memory: Math.round(statusResult.data.memory?.usage_percent || 0),
                        disk: Math.round(statusResult.data.disk?.usage_percent || 0)
                    };
                }

                // Load historical data (mock for now - in real implementation, this would come from monitoring_logs table)
                const historicalData = this.generateMockHistoricalData();
                this.updateChart(historicalData);
                
            } catch (error) {
                console.error('Failed to load metrics:', error);
            }
            this.loading = false;
        },

        generateMockHistoricalData() {
            const now = new Date();
            const data = [];
            const points = this.timeRange === '1h' ? 12 : this.timeRange === '6h' ? 36 : 144;
            const interval = this.timeRange === '1h' ? 5 : this.timeRange === '6h' ? 10 : 10; // minutes
            
            for (let i = points; i >= 0; i--) {
                const time = new Date(now.getTime() - (i * interval * 60 * 1000));
                data.push({
                    time: time,
                    cpu: Math.max(0, this.currentMetrics.cpu + (Math.random() - 0.5) * 20),
                    memory: Math.max(0, this.currentMetrics.memory + (Math.random() - 0.5) * 15),
                    disk: Math.max(0, this.currentMetrics.disk + (Math.random() - 0.5) * 5)
                });
            }
            
            return data;
        },

        updateChart(data) {
            if (!this.chart) return;
            
            this.chart.data.labels = data.map(d => d.time);
            this.chart.data.datasets[0].data = data.map(d => d.cpu);
            this.chart.data.datasets[1].data = data.map(d => d.memory);
            this.chart.data.datasets[2].data = data.map(d => d.disk);
            
            this.chart.update();
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