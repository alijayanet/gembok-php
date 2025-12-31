/**
 * Auto Refresh Data - Gembok
 * Script ini akan auto-refresh data di halaman tanpa perlu reload
 */

// Configuration
const AUTO_REFRESH_CONFIG = {
    dashboard: {
        enabled: true,
        interval: 30000, // 30 seconds
        endpoints: ['/api/dashboard/stats']
    },
    analytics: {
        enabled: true,
        interval: 60000, // 1 minute
        endpoints: ['/api/analytics/summary']
    },
    customers: {
        enabled: false, // Manual refresh only
        interval: 0
    },
    invoices: {
        enabled: true,
        interval: 60000, // 1 minute
        endpoints: ['/api/invoices/recent']
    }
};

// Auto Refresh Manager
class AutoRefreshManager {
    constructor() {
        this.intervals = {};
        this.currentPage = this.detectPage();
    }

    detectPage() {
        const path = window.location.pathname;
        if (path.includes('/dashboard')) return 'dashboard';
        if (path.includes('/analytics')) return 'analytics';
        if (path.includes('/customers')) return 'customers';
        if (path.includes('/invoices')) return 'invoices';
        return null;
    }

    start() {
        if (!this.currentPage) return;
        
        const config = AUTO_REFRESH_CONFIG[this.currentPage];
        if (!config || !config.enabled) return;

        console.log(`Auto-refresh enabled for ${this.currentPage} (${config.interval}ms)`);
        
        // Initial load
        this.refreshData();
        
        // Set interval
        this.intervals[this.currentPage] = setInterval(() => {
            this.refreshData();
        }, config.interval);
    }

    stop() {
        Object.keys(this.intervals).forEach(key => {
            clearInterval(this.intervals[key]);
        });
        this.intervals = {};
    }

    async refreshData() {
        const config = AUTO_REFRESH_CONFIG[this.currentPage];
        if (!config || !config.endpoints) return;

        for (const endpoint of config.endpoints) {
            try {
                const response = await fetch(endpoint, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.updateUI(data);
                }
            } catch (error) {
                console.error('Auto-refresh error:', error);
            }
        }
    }

    updateUI(data) {
        // Update dashboard stats
        if (this.currentPage === 'dashboard' && data.stats) {
            this.updateDashboardStats(data.stats);
        }
        
        // Update analytics
        if (this.currentPage === 'analytics' && data.summary) {
            this.updateAnalyticsSummary(data.summary);
        }
        
        // Update invoices
        if (this.currentPage === 'invoices' && data.invoices) {
            this.updateInvoicesList(data.invoices);
        }
    }

    updateDashboardStats(stats) {
        // Update revenue
        const revenueEl = document.querySelector('[data-stat="revenue"]');
        if (revenueEl && stats.todayRevenue !== undefined) {
            revenueEl.textContent = 'Rp ' + this.formatNumber(stats.todayRevenue);
        }

        // Update online users
        const onlineEl = document.querySelector('[data-stat="online"]');
        if (onlineEl && stats.onlinePppoe !== undefined) {
            onlineEl.textContent = stats.onlinePppoe;
        }

        // Update pending invoices
        const pendingEl = document.querySelector('[data-stat="pending"]');
        if (pendingEl && stats.pendingInvoices !== undefined) {
            pendingEl.textContent = stats.pendingInvoices;
        }
    }

    updateAnalyticsSummary(summary) {
        // Update revenue this month
        const revenueEl = document.querySelector('[data-stat="monthly-revenue"]');
        if (revenueEl && summary.revenueThisMonth !== undefined) {
            revenueEl.textContent = 'Rp ' + this.formatNumber(summary.revenueThisMonth);
        }

        // Update paid invoices
        const paidEl = document.querySelector('[data-stat="paid-invoices"]');
        if (paidEl && summary.paidInvoices !== undefined) {
            paidEl.textContent = summary.paidInvoices;
        }
    }

    updateInvoicesList(invoices) {
        // This would update the invoices table
        // Implementation depends on your table structure
        console.log('Invoices updated:', invoices.length);
    }

    formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const autoRefresh = new AutoRefreshManager();
    autoRefresh.start();

    // Stop on page unload
    window.addEventListener('beforeunload', function() {
        autoRefresh.stop();
    });

    // Manual refresh button
    const refreshBtn = document.querySelector('[data-action="refresh"]');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            autoRefresh.refreshData();
            
            // Show feedback
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.add('fa-spin');
                setTimeout(() => icon.classList.remove('fa-spin'), 1000);
            }
        });
    }
});

// Service Worker for offline caching (optional)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js').then(function(registration) {
            console.log('ServiceWorker registered:', registration.scope);
        }).catch(function(error) {
            console.log('ServiceWorker registration failed:', error);
        });
    });
}
