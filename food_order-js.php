<?php
$page_title = "Food Ordering - Yum's berchg";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Hero Banner -->
    <div class="estimator-hero-banner mb-4 d-print-none">
        <div class="row align-items-center g-3">
            <div class="col-lg-8 text-center text-lg-start">
                <div class="d-inline-flex align-items-center gap-2 mb-2 estimator-badge">
                    <i class="bi bi-cart-check text-primary"></i> Interactive Ordering Suite
                </div>
                <h2 class="display-6 fw-bold mb-0 font-heading hero-title">
                    Food Ordering System
                </h2>
            </div>
            <div class="col-lg-4 d-none d-lg-flex justify-content-end gap-2">
                <div class="badge bg-primary bg-opacity-10 p-3 rounded-4 text-start border border-primary border-opacity-25 d-flex align-items-center gap-3">
                    <div class="bg-primary rounded-circle p-2 text-white fs-4 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-hdd-network-fill"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark small">Local Persistence</div>
                        <div class="extra-small text-muted">localStorage Synced</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-Time KPI Stats Cards -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="card glass-estimator-card p-3 border-start border-primary border-4">
                <div class="text-uppercase text-muted extra-small fw-bold">Total Orders</div>
                <div class="fs-3 fw-bold text-dark my-1" id="kpiTotalOrders">0</div>
                <div class="text-muted extra-small">Live orders in system</div>
            </div>
        </div>
        <div class="col">
            <div class="card glass-estimator-card p-3 border-start border-success border-4">
                <div class="text-uppercase text-muted extra-small fw-bold">Total Revenue</div>
                <div class="fs-3 fw-bold text-success my-1" id="kpiTotalRevenue">₱0.00</div>
                <div class="text-muted extra-small">Cumulative gross sales</div>
            </div>
        </div>
        <div class="col">
            <div class="card glass-estimator-card p-3 border-start border-warning border-4">
                <div class="text-uppercase text-muted extra-small fw-bold">Kitchen Queue</div>
                <div class="fs-3 fw-bold text-warning my-1" id="kpiPendingOrders">0</div>
                <div class="text-muted extra-small">Pending & Preparing</div>
            </div>
        </div>
        <div class="col">
            <div class="card glass-estimator-card p-3 border-start border-info border-4">
                <div class="text-uppercase text-muted extra-small fw-bold">Completed</div>
                <div class="fs-3 fw-bold text-info my-1" id="kpiCompletedOrders">0</div>
                <div class="text-muted extra-small">Delivered & Fulfilled</div>
            </div>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="row g-4">
        <!-- Order Form Card (Left Column - Create / Update) -->
        <div class="col-lg-4 d-print-none">
            <div class="glass-estimator-card h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between rounded-top-4">
                    <h5 class="card-title fw-bold mb-0 d-flex align-items-center gap-2 font-heading text-dark fs-5" id="formCardTitle">
                        <i class="bi bi-plus-circle-fill text-primary fs-5"></i> Add New Order
                    </h5>
                    <span class="badge bg-primary text-white fw-bold px-3 py-1 rounded-pill small" id="formModeBadge">Create</span>
                </div>
                
                <div class="card-body p-4">
                    <form id="jsOrderForm" onsubmit="return false;">
                        <input type="hidden" id="orderId" value="">

                        <!-- Customer Name -->
                        <div class="mb-3">
                            <label for="custName" class="form-label fw-bold text-dark fs-6 mb-1">
                                <i class="bi bi-person me-1 text-primary"></i> Customer Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-glow text-dark" id="custName" placeholder="e.g. Alex Morgan" required>
                        </div>

                        <!-- Item Name -->
                        <div class="mb-3">
                            <label for="itemName" class="form-label fw-bold text-dark fs-6 mb-1">
                                <i class="bi bi-egg-fried me-1 text-primary"></i> Food Item Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-glow text-dark" id="itemName" placeholder="e.g. Cheesy Bacon Burger" required>
                        </div>

                        <!-- Category -->
                        <div class="mb-3">
                            <label for="itemCategory" class="form-label fw-bold text-dark fs-6 mb-1">
                                <i class="bi bi-tags me-1 text-primary"></i> Category
                            </label>
                            <select class="form-select form-select-glow text-dark" id="itemCategory">
                                <option value="Burgers">Burgers</option>
                                <option value="Pizzas">Pizzas</option>
                                <option value="Drinks">Drinks</option>
                                <option value="Desserts">Desserts</option>
                                <option value="Combos">Combos & Meals</option>
                            </select>
                        </div>

                        <!-- Price & Quantity Row -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="unitPrice" class="form-label fw-bold text-dark fs-6 mb-1">
                                    Price (₱) <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" min="0.01" class="form-control form-control-glow text-dark" id="unitPrice" placeholder="15.00" required>
                            </div>
                            <div class="col-6">
                                <label for="orderQty" class="form-label fw-bold text-dark fs-6 mb-1">
                                    Qty <span class="text-danger">*</span>
                                </label>
                                <input type="number" min="1" max="100" class="form-control form-control-glow text-dark" id="orderQty" value="1" required>
                            </div>
                        </div>

                        <!-- Order Status -->
                        <div class="mb-3">
                            <label for="orderStatus" class="form-label fw-bold text-dark fs-6 mb-1">
                                <i class="bi bi-flag me-1 text-primary"></i> Order Status
                            </label>
                            <select class="form-select form-select-glow text-dark" id="orderStatus">
                                <option value="Pending">Pending (Received)</option>
                                <option value="Preparing">Preparing (Kitchen)</option>
                                <option value="Completed">Completed (Delivered)</option>
                            </select>
                        </div>

                        <!-- Special Notes -->
                        <div class="mb-4">
                            <label for="orderNotes" class="form-label fw-bold text-dark fs-6 mb-1">
                                <i class="bi bi-card-text me-1 text-primary"></i> Special Notes / Request
                            </label>
                            <textarea class="form-control form-control-glow text-dark" id="orderNotes" rows="2" placeholder="e.g. Extra sauce, no onions"></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <button type="submit" id="saveOrderBtn" class="btn btn-primary btn-lg shadow rounded-3 fw-bold text-white" style="background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%); border: none;">
                                <i class="bi bi-check-circle me-1"></i> Save Order
                            </button>
                            <button type="button" id="cancelEditBtn" class="btn btn-light border text-muted d-none">
                                <i class="bi bi-x-circle me-1"></i> Cancel Editing
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Orders Table & Management Card (Right Column - Read / Search / Filter / Delete) -->
        <div class="col-lg-8">
            <div class="glass-estimator-card h-100 p-4">
                
                <!-- Controls Header: Search, Filters & Export -->
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold text-dark mb-0 font-heading">
                        <i class="bi bi-list-stars text-primary me-1"></i> Food Orders Registry
                    </h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="clearAllData()">
                            <i class="bi bi-trash me-1"></i> Clear All Orders
                        </button>
                        <button class="btn btn-outline-success btn-sm rounded-pill px-3" onclick="exportCSV()">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
                        </button>
                    </div>
                </div>

                <!-- Search & Status Filter Pills -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control form-control-glow border-start-0 text-dark" id="searchInput" placeholder="Search customer or item name...">
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-center justify-content-md-end">
                        <div class="guest-chip-group" id="filterStatusGroup">
                            <button type="button" class="guest-chip active-chip" data-filter="All">All</button>
                            <button type="button" class="guest-chip" data-filter="Pending">Pending</button>
                            <button type="button" class="guest-chip" data-filter="Preparing">Preparing</button>
                            <button type="button" class="guest-chip" data-filter="Completed">Completed</button>
                        </div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="table-responsive rounded-3 border">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark bg-dark text-white">
                            <tr>
                                <th># ID</th>
                                <th>Customer & Details</th>
                                <th>Item & Category</th>
                                <th>Price × Qty</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <!-- Dynamically generated via JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div id="emptyOrdersState" class="text-center py-5 d-none">
                    <div class="text-muted fs-1 mb-2"><i class="bi bi-inbox"></i></div>
                    <h6 class="fw-bold text-dark mb-1">No Orders Found</h6>
                    <p class="text-muted small mb-0">Try adjusting your search filter or add a new order using the form.</p>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Pure Vanilla JavaScript CRUD Engine -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const STORAGE_KEY = 'yum_js_orders_v1';

    // Form elements
    const form = document.getElementById('jsOrderForm');
    const orderIdInput = document.getElementById('orderId');
    const custNameInput = document.getElementById('custName');
    const itemNameInput = document.getElementById('itemName');
    const itemCategorySelect = document.getElementById('itemCategory');
    const unitPriceInput = document.getElementById('unitPrice');
    const orderQtyInput = document.getElementById('orderQty');
    const orderStatusSelect = document.getElementById('orderStatus');
    const orderNotesInput = document.getElementById('orderNotes');
    const saveOrderBtn = document.getElementById('saveOrderBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const formCardTitle = document.getElementById('formCardTitle');
    const formModeBadge = document.getElementById('formModeBadge');

    // Controls elements
    const searchInput = document.getElementById('searchInput');
    const ordersTableBody = document.getElementById('ordersTableBody');
    const emptyOrdersState = document.getElementById('emptyOrdersState');

    // KPI Counters
    const kpiTotalOrders = document.getElementById('kpiTotalOrders');
    const kpiTotalRevenue = document.getElementById('kpiTotalRevenue');
    const kpiPendingOrders = document.getElementById('kpiPendingOrders');
    const kpiCompletedOrders = document.getElementById('kpiCompletedOrders');

    let activeFilter = 'All';

    // Default data seed (Empty dataset)
    const defaultSampleOrders = [];

    // Data Accessors
    function getOrders() {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (!stored) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify([]));
            return [];
        }
        try {
            return JSON.parse(stored);
        } catch (e) {
            return [];
        }
    }

    function saveOrders(orders) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(orders));
        renderOrders();
    }

    // Render Orders & KPIs
    function renderOrders() {
        const orders = getOrders();
        const searchTerm = searchInput.value.trim().toLowerCase();

        // Calculate KPI metrics
        let totalRev = 0;
        let pendingCount = 0;
        let completedCount = 0;

        orders.forEach(o => {
            const itemTotal = (parseFloat(o.price) || 0) * (parseInt(o.qty, 10) || 1);
            totalRev += itemTotal;
            if (o.status === 'Completed') {
                completedCount++;
            } else {
                pendingCount++;
            }
        });

        kpiTotalOrders.textContent = orders.length;
        kpiTotalRevenue.textContent = `₱${totalRev.toFixed(2)}`;
        kpiPendingOrders.textContent = pendingCount;
        kpiCompletedOrders.textContent = completedCount;

        // Filter list
        const filtered = orders.filter(o => {
            const matchesFilter = activeFilter === 'All' || o.status === activeFilter;
            const matchesSearch = o.customer.toLowerCase().includes(searchTerm) || 
                                  o.item.toLowerCase().includes(searchTerm) ||
                                  o.category.toLowerCase().includes(searchTerm);
            return matchesFilter && matchesSearch;
        });

        ordersTableBody.innerHTML = '';

        if (filtered.length === 0) {
            emptyOrdersState.classList.remove('d-none');
            return;
        }

        emptyOrdersState.classList.add('d-none');

        filtered.forEach(o => {
            const row = document.createElement('tr');
            const totalCost = (parseFloat(o.price) * parseInt(o.qty, 10)).toFixed(2);

            let statusBadgeClass = 'bg-warning text-dark';
            if (o.status === 'Preparing') statusBadgeClass = 'bg-info text-white';
            if (o.status === 'Completed') statusBadgeClass = 'bg-success text-white';

            row.innerHTML = `
                <td><span class="fw-bold text-secondary extra-small">${o.id}</span></td>
                <td>
                    <div class="fw-bold text-dark">${escapeHtml(o.customer)}</div>
                    <div class="extra-small text-muted">${o.timestamp || 'Just now'} ${o.notes ? '• ' + escapeHtml(o.notes) : ''}</div>
                </td>
                <td>
                    <div class="fw-semibold text-dark">${escapeHtml(o.item)}</div>
                    <span class="badge bg-light text-secondary border extra-small">${escapeHtml(o.category)}</span>
                </td>
                <td>₱${parseFloat(o.price).toFixed(2)} × ${o.qty}</td>
                <td>
                    <button type="button" class="btn btn-link p-0 text-decoration-none" onclick="cycleStatus('${o.id}')" title="Click to cycle status">
                        <span class="badge ${statusBadgeClass} rounded-pill px-3 py-1 fw-bold">${o.status}</span>
                    </button>
                </td>
                <td class="fw-bold text-primary">₱${totalCost}</td>
                <td class="text-end">
                    <button class="btn btn-outline-primary btn-sm me-1 rounded-circle" onclick="editOrder('${o.id}')" title="Edit Order">
                        <i class="bi bi-pencil-fill"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm rounded-circle" onclick="deleteOrder('${o.id}')" title="Delete Order">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </td>
            `;
            ordersTableBody.appendChild(row);
        });
    }

    // Helper HTML Escape
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    // Form Submission (Create & Update)
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        
        const id = orderIdInput.value.trim();
        const customer = custNameInput.value.trim();
        const item = itemNameInput.value.trim();
        const category = itemCategorySelect.value;
        const price = parseFloat(unitPriceInput.value);
        const qty = parseInt(orderQtyInput.value, 10);
        const status = orderStatusSelect.value;
        const notes = orderNotesInput.value.trim();

        if (!customer || !item || isNaN(price) || price <= 0 || isNaN(qty) || qty <= 0) {
            alert('Please fill out all required fields with valid values.');
            return;
        }

        let orders = getOrders();

        if (id) {
            // UPDATE existing order
            const index = orders.findIndex(o => o.id === id);
            if (index !== -1) {
                orders[index] = {
                    ...orders[index],
                    customer,
                    item,
                    category,
                    price,
                    qty,
                    status,
                    notes
                };
            }
        } else {
            // CREATE new order
            const newId = 'ORD-' + Math.floor(100 + Math.random() * 900);
            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            orders.unshift({
                id: newId,
                customer,
                item,
                category,
                price,
                qty,
                status,
                notes,
                timestamp: timeStr
            });
        }

        saveOrders(orders);
        resetForm();
    });

    // Edit Order
    window.editOrder = function (id) {
        const orders = getOrders();
        const target = orders.find(o => o.id === id);
        if (!target) return;

        orderIdInput.value = target.id;
        custNameInput.value = target.customer;
        itemNameInput.value = target.item;
        itemCategorySelect.value = target.category;
        unitPriceInput.value = target.price;
        orderQtyInput.value = target.qty;
        orderStatusSelect.value = target.status;
        orderNotesInput.value = target.notes || '';

        // Update form UI to Edit mode
        formCardTitle.innerHTML = '<i class="bi bi-pencil-square text-warning fs-5"></i> Edit Order ' + target.id;
        formModeBadge.textContent = 'Edit Mode';
        formModeBadge.className = 'badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill small';
        saveOrderBtn.innerHTML = '<i class="bi bi-save me-1"></i> Update Order';
        cancelEditBtn.classList.remove('d-none');

        custNameInput.focus();
    };

    // Cancel Edit
    cancelEditBtn.addEventListener('click', resetForm);

    function resetForm() {
        form.reset();
        orderIdInput.value = '';
        formCardTitle.innerHTML = '<i class="bi bi-plus-circle-fill text-primary fs-5"></i> Add New Order';
        formModeBadge.textContent = 'Create';
        formModeBadge.className = 'badge bg-primary text-white fw-bold px-3 py-1 rounded-pill small';
        saveOrderBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save Order';
        cancelEditBtn.classList.add('d-none');
    }

    // Delete Order
    window.deleteOrder = function (id) {
        if (confirm(`Are you sure you want to delete order ${id}?`)) {
            let orders = getOrders();
            orders = orders.filter(o => o.id !== id);
            saveOrders(orders);
            if (orderIdInput.value === id) {
                resetForm();
            }
        }
    };

    // Cycle Status (Pending -> Preparing -> Completed -> Pending)
    window.cycleStatus = function (id) {
        let orders = getOrders();
        const index = orders.findIndex(o => o.id === id);
        if (index !== -1) {
            const current = orders[index].status;
            if (current === 'Pending') orders[index].status = 'Preparing';
            else if (current === 'Preparing') orders[index].status = 'Completed';
            else orders[index].status = 'Pending';
            saveOrders(orders);
        }
    };

    // Clear All Orders Data
    window.clearAllData = function () {
        if (confirm('Are you sure you want to delete all order records?')) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify([]));
            resetForm();
            renderOrders();
        }
    };

    // Export CSV
    window.exportCSV = function () {
        const orders = getOrders();
        if (orders.length === 0) {
            alert('No order data to export.');
            return;
        }

        let csv = 'Order ID,Customer Name,Item Name,Category,Price,Quantity,Total,Status,Timestamp,Notes\n';
        orders.forEach(o => {
            const total = (parseFloat(o.price) * parseInt(o.qty, 10)).toFixed(2);
            csv += `"${o.id}","${o.customer}","${o.item}","${o.category}",${o.price},${o.qty},${total},"${o.status}","${o.timestamp || ''}","${o.notes || ''}"\n`;
        });

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `food_orders_${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
    };

    // Filter Chips Event Listener
    document.querySelectorAll('#filterStatusGroup .guest-chip').forEach(chip => {
        chip.addEventListener('click', function () {
            document.querySelectorAll('#filterStatusGroup .guest-chip').forEach(c => c.classList.remove('btn-primary', 'text-white'));
            activeFilter = this.getAttribute('data-filter');
            renderOrders();
        });
    });

    // Real-time Search Event Listener
    searchInput.addEventListener('input', renderOrders);

    // Initial Render
    renderOrders();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
