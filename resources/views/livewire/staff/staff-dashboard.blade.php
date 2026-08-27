<div class="dashboard-content">
    @push('styles')
        <style>
            .dashboard-content {
                color: #111827;
                font-size: 14px;
                line-height: 1.45;
            }

            .dashboard-content .text-muted {
                color: #4b5563 !important;
            }

            .text-phoenix {
                color: #E65F1E !important;
            }

            /* Base styles */
            .stat-card {
                background: #ffffff;
                border: 1px solid #E2E8F0;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0 8px 24px rgba(17, 24, 39, 0.06);
                height: 100%;
            }

            .stat-value {
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 5px;
                color: #111827;
            }

            .stat-label {
                color: #374151;
                font-size: 15px;
                font-weight: 600;
                margin-bottom: 5px;
            }

            .chart-card {
                background: #ffffff;
                border: 1px solid #eadfca;
                box-shadow: 0 8px 24px rgba(17, 24, 39, 0.06);
                border-radius: 10px;
                margin-bottom: 20px;
                height: 100%;
            }

            .chart-header {
                background-color: #fff8ea;
                padding: 1rem 1.5rem;
                border-bottom: 1px solid #eadfca;
                border-top-left-radius: 10px;
                border-top-right-radius: 10px;
            }

            .chart-container {
                position: relative;
                height: 300px;
                padding: 1.5rem;
            }

            .chart-scroll-container {
                overflow-x: auto;
            }

            .widget-container {
                background-color: #fff;
                border: 1px solid #eadfca;
                border-radius: 10px;
                box-shadow: 0 8px 24px rgba(17, 24, 39, 0.06);
                padding: 20px;
                margin-bottom: 20px;
                height: 100%;
            }

            .widget-header {
                margin-bottom: 15px;
            }

            .widget-header h6 {
                font-size: 1.25rem;
                margin-bottom: 5px;
                font-weight: 700;
                color: #111827;
            }

            .widget-header p {
                font-size: 0.875rem;
                color: #4b5563;
                margin-bottom: 0;
            }

            .item-row {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
            }

            .item-details {
                flex-grow: 1;
                margin-right: 10px;
            }

            .item-details h6 {
                font-size: 1rem;
                margin-bottom: 3px;
                color: #111827;
                font-weight: 600;
            }

            .status-badge {
                padding: 0.25rem 0.5rem;
                border-radius: 5px;
                font-size: 0.8rem;
                font-weight: bold;
                white-space: nowrap;
            }

            .in-stock {
                background-color: #d1e7dd;
                color: #0f5132;
            }

            .low-stock {
                background-color: #fff3cd;
                color: #664d03;
            }

            .out-of-stock {
                background-color: #f8d7da;
                color: #842029;
            }

            .progress {
                height: 0.5rem;
                margin-top: 5px;
                background-color: #e5e7eb;
                border-radius: 0.25rem;
                overflow: hidden;
            }

            .progress-bar {
                background-color: #E65F1E;
                height: 4px;
            }

            /* Content Tabs */
            .content-tabs {
                display: flex;
                overflow-x: auto;
                border-bottom: 1px solid #E2E8F0;
                margin-bottom: 20px;
                padding-bottom: 2px;
                gap: 1.5rem;
            }

            .content-tab {
                padding: 10px 5px;
                cursor: pointer;
                font-weight: 600;
                color: #64748B;
                border-bottom: 3px solid transparent;
                transition: all 0.2s;
                white-space: nowrap;
                font-size: 15px;
            }

            .content-tab.active {
                color: #E65F1E;
                border-bottom-color: #E65F1E;
            }

            .content-tab:hover:not(.active) {
                color: #16285A;
            }

            /* Recent Sales Avatar */
            .avatar {
                width: 40px;
                height: 40px;
                background-color: #EFF6FF;
                border: 1px solid #DBEAFE;
                border-radius: 50%;
                display: flex;
                justify-content: center;
                align-items: center;
                margin-right: 15px;
                color: #16285A;
                font-weight: bold;
                font-size: 14px;
            }

            @media (max-width: 768px) {
                .stat-card { padding: 15px; }
                .stat-value { font-size: 22px; }
                .widget-header h6 { font-size: 1.1rem; }
            }
        </style>
    @endpush

    <div class="container-fluid p-0">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-2">
                    <i class="bi bi-speedometer2 text-phoenix me-2"></i> Overview
                </h3>
                <p class="text-muted mb-0">Your personalized performance dashboard and sales overview.</p>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="content-tabs">
            <div class="content-tab active" data-tab="overview">Overview</div>
            <div class="content-tab" data-tab="analytics">Analytics</div>
            <div class="content-tab" data-tab="reports">Reports</div>
            <div class="content-tab" data-tab="notifications">Notifications</div>
        </div>

        <!-- Overview Content -->
        <div id="overview" class="tab-content active">
            <!-- Stats Cards Row -->
            <div class="row mb-4">
                <!-- Total Revenue Card -->
                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value">Rs.{{ number_format($totalRevenue, 2) }}</div>
                        <div class="stat-info mt-2">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Collected</small>
                                <small class="fw-bold">{{ $revenuePercentage }}%</small>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $revenuePercentage }}%;"></div>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="bi bi-check-circle-fill text-success me-1"></i> Paid</small>
                                <span class="badge bg-success">Rs.{{ number_format($fullPaidAmount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Due Amount Card -->
                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-label">Total Due Amount</div>
                        <div class="stat-value text-danger">Rs.{{ number_format($totalDueAmount, 2) }}</div>
                        <div class="stat-info mt-2">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Due Ratio</small>
                                <small class="fw-bold text-danger">{{ $duePercentage }}%</small>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $duePercentage }}%;"></div>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="bi bi-clock-fill text-danger me-1"></i> Pending</small>
                                <span class="badge bg-danger">{{ $partialPaidCount }} Invoices</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Status Card -->
                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-label">Inventory Status</div>
                        <div class="stat-value text-phoenix">{{ number_format($totalInventory) }} <span class="fs-6 text-muted">units</span></div>
                        <div class="stat-info mt-2">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Sold stock</small>
                                <small class="fw-bold text-phoenix">{{ $soldPercentage }}%</small>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: {{ $soldPercentage }}%;"></div>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="bi bi-box-seam-fill text-phoenix me-1"></i> Available</small>
                                <span class="badge bg-primary">Rs.{{ number_format($availableStockValue, 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Status Card -->
                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-label">Customer Status</div>
                        <div class="stat-value text-info">{{ $totalCustomers }} <span class="fs-6 text-muted">active</span></div>
                        <div class="stat-info mt-2">
                            @foreach ($customerTypes as $type => $count)
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">{{ ucfirst($type) }}</small>
                                    <small class="fw-bold">{{ round(($count / max(1, $totalCustomers)) * 100) }}%</small>
                                </div>
                                <div class="progress mb-2">
                                    <div class="progress-bar {{ $type == 'wholesale' ? 'bg-info' : 'bg-success' }}" role="progressbar" style="width: {{ round(($count / max(1, $totalCustomers)) * 100) }}%;"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Recent Sales Row -->
            <div class="row">
                <!-- Sales Overview By Categories -->
                <div class="col-lg-8 col-md-12 mb-4">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h6 class="mb-1">Sales Overview By Categories</h6>
                            <p class="text-muted mb-0 small">Your sales performance breakdown by Product categories</p>
                        </div>
                        <div class="chart-scroll-container">
                            <div class="chart-container" style="min-width: {{ max(300, count($categorySales) * 60) }}px;">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Sales -->
                <div class="col-lg-4 col-md-12 mb-4">
                    <div class="widget-container">
                        <div class="widget-header">
                            <h6>Recent Sales</h6>
                            <p class="text-muted small mb-0">Your latest transactions</p>
                        </div>
                        <div class="list-group list-group-flush" style="max-height: 380px; overflow-y: auto;">
                            @forelse($recentSales as $sale)
                                <div class="list-group-item px-0 py-3 d-flex align-items-center border-0 border-bottom">
                                    <div class="avatar">
                                        {{ strtoupper(substr($sale->name, 0, 1)) }}{{ strtoupper(substr(strpos($sale->name, ' ') !== false ? substr($sale->name, strpos($sale->name, ' ') + 1, 1) : '', 0, 1)) }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 text-dark">{{ $sale->name }}</h6>
                                        <small class="text-muted">{{ $sale->invoice_number }}</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success">Rs.{{ number_format($sale->total_amount, 0) }}</div>
                                        @if ($sale->due_amount > 0)
                                            <small class="text-danger">Due: Rs.{{ number_format($sale->due_amount, 0) }}</small>
                                        @else
                                            <span class="badge bg-success-subtle text-success">Paid</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <i class="bi bi-receipt text-muted opacity-25" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2">No sales recorded yet</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Inventory Status -->
            <div class="row">
                <div class="col-12">
                    <div class="widget-container">
                        <div class="widget-header d-flex justify-content-between align-items-start">
                            <div>
                                <h6>Product Inventory Status</h6>
                                <p class="text-muted small mb-0">Your current assigned stock levels</p>
                            </div>
                            <a href="{{ route('staff.Productes') }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-box-seam me-1"></i> View All
                            </a>
                        </div>

                        <div class="row mt-3">
                            @forelse($productInventory as $item)
                                @php
                                    $stockPercentage = $item->total_quantity > 0 ? round(($item->available_quantity / $item->total_quantity) * 100, 1) : 0;
                                    if ($stockPercentage == 0) { $statusBadge = 'out-of-stock'; $statusText = 'Out of Stock'; $progressColor = 'bg-danger'; }
                                    elseif ($stockPercentage <= 20) { $statusBadge = 'low-stock'; $statusText = 'Low Stock'; $progressColor = 'bg-warning'; }
                                    else { $statusBadge = 'in-stock'; $statusText = 'In Stock'; $progressColor = 'bg-success'; }
                                @endphp
                                <div class="col-md-6 mb-3">
                                    <div class="p-3 border rounded-3 bg-light-subtle h-100">
                                        <div class="item-row mb-2">
                                            <div class="item-details">
                                                <h6 class="mb-0">{{ $item->name }}</h6>
                                                <small class="text-muted">{{ $item->brand_name }} | {{ $item->model }}</small>
                                            </div>
                                            <span class="status-badge {{ $statusBadge }} ms-auto">{{ $statusText }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted">Stock: {{ number_format($item->available_quantity) }} / {{ number_format($item->total_quantity) }}</small>
                                            <small class="fw-bold">{{ $stockPercentage }}%</small>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar {{ $progressColor }}" role="progressbar" style="width: {{ $stockPercentage }}%;"></div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center py-4">
                                    <p class="text-muted">No products assigned to you yet.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const categoryLabels = @json(collect($categorySales)->pluck('category'));
                const categoryTotals = @json(collect($categorySales)->pluck('total_sales'));

                const ctx = document.getElementById('salesChart');
                if (ctx) {
                    new Chart(ctx.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: categoryLabels,
                            datasets: [{
                                label: 'Sales by Category',
                                backgroundColor: 'rgba(230, 95, 30, 0.85)',
                                borderColor: '#16285A',
                                borderWidth: 1,
                                data: categoryTotals,
                                borderRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: { enabled: true }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: '#f3f4f6' }
                                },
                                x: {
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }

                // Tab switching logic (simple JS since it's just for UI demo in the screenshot)
                const tabs = document.querySelectorAll('.content-tab');
                tabs.forEach(tab => {
                    tab.addEventListener('click', () => {
                        tabs.forEach(t => t.classList.remove('active'));
                        tab.classList.add('active');
                        // In a real app, you'd hide/show content or use Livewire
                    });
                });
            });
        </script>
    @endpush
</div>
