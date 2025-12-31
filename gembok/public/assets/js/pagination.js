/**
 * Dynamic Pagination with AJAX
 * No hard refresh needed for paginated data
 */

class DynamicPagination {
    constructor(options = {}) {
        this.container = options.container || '.data-table tbody';
        this.paginationContainer = options.paginationContainer || '.pagination-controls';
        this.apiEndpoint = options.apiEndpoint;
        this.perPage = options.perPage || 50;
        this.currentPage = 1;
        this.totalPages = 1;
        this.totalRecords = 0;
        this.renderRow = options.renderRow;
        this.onLoad = options.onLoad;

        this.init();
    }

    init() {
        this.loadData(1);
        this.setupEventListeners();
    }

    async loadData(page = 1) {
        try {
            const url = `${this.apiEndpoint}?page=${page}&per_page=${this.perPage}`;
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Failed to load data');

            const data = await response.json();

            if (data.success) {
                this.currentPage = data.pagination.current_page;
                this.totalPages = data.pagination.total_pages;
                this.totalRecords = data.pagination.total_records;

                this.renderData(data.data);
                this.renderPagination();

                if (this.onLoad) this.onLoad(data);
            }
        } catch (error) {
            console.error('Pagination error:', error);
            this.showError('Gagal memuat data. Silakan refresh halaman.');
        }
    }

    renderData(data) {
        const tbody = document.querySelector(this.container);
        if (!tbody) return;

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="100" style="text-align:center;padding:2rem;color:var(--text-muted)">Tidak ada data</td></tr>';
            return;
        }

        tbody.innerHTML = data.map(row => this.renderRow(row)).join('');

        // Re-attach event listeners for action buttons
        this.attachRowEventListeners();
    }

    renderPagination() {
        const container = document.querySelector(this.paginationContainer);
        if (!container) return;

        let html = '<div class="pagination-wrapper">';

        // Info
        html += `<div class="pagination-info">
            Menampilkan ${((this.currentPage - 1) * this.perPage) + 1} - 
            ${Math.min(this.currentPage * this.perPage, this.totalRecords)} 
            dari ${this.totalRecords} data
        </div>`;

        // Buttons
        html += '<div class="pagination-buttons">';

        // First & Previous
        html += `<button class="btn btn-sm" data-page="1" ${this.currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-angle-double-left"></i>
        </button>`;
        html += `<button class="btn btn-sm" data-page="${this.currentPage - 1}" ${this.currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-angle-left"></i>
        </button>`;

        // Page numbers
        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(this.totalPages, this.currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="btn btn-sm ${i === this.currentPage ? 'btn-primary' : ''}" data-page="${i}">${i}</button>`;
        }

        // Next & Last
        html += `<button class="btn btn-sm" data-page="${this.currentPage + 1}" ${this.currentPage === this.totalPages ? 'disabled' : ''}>
            <i class="fas fa-angle-right"></i>
        </button>`;
        html += `<button class="btn btn-sm" data-page="${this.totalPages}" ${this.currentPage === this.totalPages ? 'disabled' : ''}>
            <i class="fas fa-angle-double-right"></i>
        </button>`;

        html += '</div></div>';

        container.innerHTML = html;
    }

    setupEventListeners() {
        // Pagination clicks
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-page]');
            if (!btn || btn.disabled) return;

            const page = parseInt(btn.dataset.page);
            if (page >= 1 && page <= this.totalPages) {
                this.loadData(page);
            }
        });

        // Per page selector
        const perPageSelect = document.querySelector('[data-per-page]');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', (e) => {
                this.perPage = parseInt(e.target.value);
                this.loadData(1);
            });
        }

        // Search
        const searchInput = document.querySelector('[data-search]');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.search(e.target.value);
                }, 500);
            });
        }
    }

    attachRowEventListeners() {
        // Override this in specific implementations
        // Example: attach edit, delete button listeners
    }

    async search(query) {
        try {
            const url = `${this.apiEndpoint}?page=1&per_page=${this.perPage}&search=${encodeURIComponent(query)}`;
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            if (data.success) {
                this.currentPage = 1;
                this.totalPages = data.pagination.total_pages;
                this.totalRecords = data.pagination.total_records;
                this.renderData(data.data);
                this.renderPagination();
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    showError(message) {
        const tbody = document.querySelector(this.container);
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="100" style="text-align:center;padding:2rem;color:red">${message}</td></tr>`;
        }
    }

    refresh() {
        this.loadData(this.currentPage);
    }
}

// ============================================
// SPECIFIC IMPLEMENTATIONS
// ============================================

// Customers Pagination
if (window.location.pathname.includes('/customers')) {
    const customersPagination = new DynamicPagination({
        apiEndpoint: '/api/customers/list',
        perPage: 50,
        renderRow: (customer) => `
            <tr>
                <td>${customer.id}</td>
                <td>${customer.name}</td>
                <td>${customer.phone || '-'}</td>
                <td>${customer.pppoe_username}</td>
                <td>${customer.package_name}</td>
                <td>
                    <span class="badge badge-${customer.status === 'active' ? 'success' : 'danger'}">
                        ${customer.status}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editCustomer(${customer.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteCustomer(${customer.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `
    });

    // Auto-refresh every 2 minutes
    setInterval(() => customersPagination.refresh(), 120000);
}

// Invoices Pagination
if (window.location.pathname.includes('/invoices')) {
    const invoicesPagination = new DynamicPagination({
        apiEndpoint: '/api/invoices/list',
        perPage: 50,
        renderRow: (invoice) => `
            <tr>
                <td>${invoice.invoice_number}</td>
                <td>${invoice.customer_name}</td>
                <td>Rp ${new Intl.NumberFormat('id-ID').format(invoice.amount)}</td>
                <td>${invoice.due_date}</td>
                <td>
                    <span class="badge badge-${invoice.paid ? 'success' : 'warning'}">
                        ${invoice.paid ? 'Lunas' : 'Belum Lunas'}
                    </span>
                </td>
                <td>
                    ${!invoice.paid ? `
                        <button class="btn btn-sm btn-success" onclick="payInvoice(${invoice.id})">
                            <i class="fas fa-check"></i> Bayar
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-primary" onclick="printInvoice(${invoice.id})">
                        <i class="fas fa-print"></i>
                    </button>
                </td>
            </tr>
        `
    });

    // Auto-refresh every 1 minute
    setInterval(() => invoicesPagination.refresh(), 60000);
}

// MikroTik Users Pagination
if (window.location.pathname.includes('/mikrotik')) {
    const mikrotikPagination = new DynamicPagination({
        apiEndpoint: '/api/mikrotik/users',
        perPage: 50,
        renderRow: (user) => `
            <tr>
                <td>${user.name}</td>
                <td>${user.profile}</td>
                <td>${user.service || 'pppoe'}</td>
                <td>
                    <span class="badge badge-${user.disabled === 'false' ? 'success' : 'danger'}">
                        ${user.disabled === 'false' ? 'Active' : 'Disabled'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editMikrotikUser('${user.name}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteMikrotikUser('${user.name}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `
    });

    // Auto-refresh every 30 seconds
    setInterval(() => mikrotikPagination.refresh(), 30000);
}

// Manual refresh button
document.addEventListener('DOMContentLoaded', function () {
    const refreshBtn = document.querySelector('[data-action="refresh-table"]');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            // Find active pagination instance and refresh
            if (window.customersPagination) window.customersPagination.refresh();
            if (window.invoicesPagination) window.invoicesPagination.refresh();
            if (window.mikrotikPagination) window.mikrotikPagination.refresh();

            // Visual feedback
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.add('fa-spin');
                setTimeout(() => icon.classList.remove('fa-spin'), 1000);
            }
        });
    }
});
