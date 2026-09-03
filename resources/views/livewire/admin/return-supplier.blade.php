<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-arrow-return-left text-warning me-2"></i> Supplier Returns
            </h3>
            <p class="text-muted mb-0">Manage product returns to suppliers efficiently</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.list-supplier-return') }}" class="btn btn-outline-primary">
                <i class="bi bi-list-ul me-1"></i> View Returns List
            </a>
        </div>
    </div>

    <!-- Mode Switcher Tabs -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-2">
            <div class="nav nav-pills nav-fill" role="tablist">
                <button class="nav-link {{ $returnMode === 'system' ? 'active fw-bold shadow-sm' : 'text-muted' }}" 
                        wire:click="setReturnMode('system')" type="button">
                    <i class="bi bi-receipt-cutoff me-2"></i> System Purchase Order Return
                    <small class="d-block text-xs {{ $returnMode === 'system' ? 'text-white-50' : 'text-muted' }}">Return items from purchase orders recorded in database</small>
                </button>
                <button class="nav-link {{ $returnMode === 'manual' ? 'active fw-bold shadow-sm' : 'text-muted' }}" 
                        wire:click="setReturnMode('manual')" type="button">
                    <i class="bi bi-journal-plus me-2"></i> Manual / External Supplier Return
                    <small class="d-block text-xs {{ $returnMode === 'manual' ? 'text-white-50' : 'text-muted' }}">Return items without a system purchase order</small>
                </button>
            </div>
        </div>
    </div>

    @if($returnMode === 'system')
    <!-- Supplier Search and Purchase Order Selection -->
    <div class="row mb-4">
        <!-- Supplier Search -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-truck text-primary me-2"></i> Supplier Search
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Search Supplier or PO #</label>
                        <input type="text" class="form-control" wire:model.live="searchSupplier" placeholder="Search by supplier name or purchase order number...">
                    </div>

                    @if($searchSupplier && (count($suppliers) > 0 || count($supplierPurchaseOrders) > 0))
                    <div class="border rounded p-3 bg-light">
                        <h6 class="fw-semibold mb-2">Search Results</h6>
                        <div class="list-group mb-2">
                            @foreach($suppliers as $supplier)
                            <button class="list-group-item list-group-item-action p-2"
                                wire:click="selectSupplier({{ $supplier->id }})"
                                type="button">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-building fs-4 text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $supplier->name }}</div>
                                        <small class="text-muted">{{ $supplier->phone }} | {{ $supplier->email }}</small>
                                    </div>
                                </div>
                            </button>
                            @endforeach
                        </div>
                        <div class="list-group">
                            @foreach($supplierPurchaseOrders as $purchaseOrder)
                            @if(str_contains($purchaseOrder->order_code, $searchSupplier))
                            <button class="list-group-item list-group-item-action p-2"
                                wire:click="selectPurchaseOrderForReturn({{ $purchaseOrder->id }})"
                                type="button">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-receipt fs-4 text-info"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">PO #{{ $purchaseOrder->order_code }}</div>
                                        <small class="text-muted">{{ $purchaseOrder->created_at->format('Y-m-d') }} | Rs.{{ number_format($purchaseOrder->total_amount, 2) }}</small>
                                    </div>
                                </div>
                            </button>
                            @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($selectedSupplier)
                    <div class="mt-3 p-3 bg-info bg-opacity-10 rounded border border-info">
                        <h6 class="fw-semibold text-info mb-2">Selected Supplier</h6>
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="bi bi-building-check fs-4 text-info"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $selectedSupplier->name }}</div>
                                <small class="text-muted">{{ $selectedSupplier->phone }} | {{ $selectedSupplier->email }}</small>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Supplier Purchase Orders -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-receipt text-info me-2"></i> Recent Purchase Orders
                    </h5>
                    <button class="btn btn-info btn-sm" wire:click="loadSupplierPurchaseOrders">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </button>
                </div>
                <div class="card-body overflow-auto">
                    @if($selectedSupplier && count($supplierPurchaseOrders) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">PO #</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($supplierPurchaseOrders as $purchaseOrder)
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-medium text-dark">{{ $purchaseOrder->order_code }}</span>
                                    </td>
                                    <td>{{ $purchaseOrder->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        @if($purchaseOrder->status == 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                        @elseif($purchaseOrder->status == 'received')
                                        <span class="badge bg-success">Received</span>
                                        @elseif($purchaseOrder->status == 'complete')
                                        <span class="badge bg-info">Complete</span>
                                        @else
                                        <span class="badge bg-secondary">{{ ucfirst($purchaseOrder->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark">Rs.{{ number_format($purchaseOrder->total_amount, 2) }}</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-success"
                                                wire:click="selectPurchaseOrderForReturn({{ $purchaseOrder->id }})">
                                                <i class="bi bi-check-circle me-1"></i> Select
                                            </button>
                                            <button class="btn btn-outline-info"
                                                wire:click="viewPurchaseOrder({{ $purchaseOrder->id }})">
                                                <i class="bi bi-eye me-1"></i> View
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="bi bi-receipt-cutoff text-muted fs-1 mb-3"></i>
                        <p class="text-muted mb-0">No purchase orders found for this supplier</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($showReturnSection && $selectedPurchaseOrder)
    <!-- Previous Returns Section -->
    @if(!empty($previousReturns))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning bg-opacity-10 border-bottom border-warning">
                    <h5 class="fw-bold mb-0 text-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i> Previous Returns for PO #{{ $selectedPurchaseOrder->order_code }}
                    </h5>
                </div>
                <div class="card-body overflow-auto">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Total Returned</th>
                                    <th>Total Amount</th>
                                    <th>Return Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($previousReturns as $productId => $returnData)
                                <tr>
                                    <td>{{ $returnData['product_name'] }}</td>
                                    <td><span class="badge bg-warning">{{ $returnData['total_returned'] }} units</span></td>
                                    <td class="fw-bold">Rs.{{ number_format($returnData['total_amount'], 2) }}</td>
                                    <td>
                                        <div class="small">
                                            @foreach($returnData['returns'] as $return)
                                            <div class="mb-1">
                                                <span class="badge bg-secondary">{{ $return['quantity'] }} units</span>
                                                <span class="text-muted">- Rs.{{ number_format($return['amount'], 2) }}</span>
                                                <span class="badge bg-{{ $return['reason'] == 'damaged' ? 'danger' : 'warning' }}">{{ $return['reason'] }}</span>
                                                <span class="text-muted">on {{ $return['date'] }}</span>
                                            </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Purchase Order Items for Return -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-receipt text-info me-2"></i> PO #{{ $selectedPurchaseOrder->order_code }} Items
                            </h5>
                            <p class="text-muted small mb-0">Select return quantity and reason for each item below</p>
                        </div>
                        @if($overallDiscountPerItem > 0)
                        <div class="text-end">
                            <span class="badge bg-success">Overall Discount Applied</span>
                            <p class="small text-muted mb-0">Rs.{{ number_format($overallDiscountPerItem, 2) }} per item</p>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="card-body overflow-auto">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Code</th>
                                    <th>Original Qty</th>
                                    <th>Returned</th>
                                    <th>Total Stock</th>
                                    <th>Available to Return</th>
                                    <th>Return Qty</th>
                                    <th>Return Reason</th>
                                    <th>Unit Price</th>
                                    <th>Item Disc.</th>
                                    <th>Overall Disc.</th>
                                    <th>Net Price</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returnItems as $index => $item)
                                <tr>
                                    <td>{{ $item['name'] }}</td>
                                    <td>{{ $item['code'] }}</td>
                                    <td>{{ $item['original_qty'] }}</td>
                                    <td>
                                        @if($item['already_returned'] > 0)
                                        <span class="badge bg-warning">{{ $item['already_returned'] }}</span>
                                        @else
                                        <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $item['total_stock'] ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $item['max_qty'] > 0 ? 'bg-success' : 'bg-danger' }}">{{ $item['max_qty'] }}</span>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" style="width: 80px;" 
                                            min="0" max="{{ $item['max_qty'] }}"
                                            wire:model.lazy="returnItems.{{ $index }}.return_qty"
                                            @if($item['max_qty'] == 0) disabled @endif>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" wire:model.lazy="returnItems.{{ $index }}.return_reason">
                                            <option value="damaged">Damaged</option>
                                            <option value="defective">Defective</option>
                                            <option value="wrong_item">Wrong Item</option>
                                            <option value="excess">Excess Quantity</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </td>
                                    <td>Rs.{{ number_format($item['unit_price'], 2) }}</td>
                                    <td>
                                        @if($item['discount_percentage'] > 0)
                                        <span class="text-danger fw-bold">{{ $item['discount_percentage'] }}%</span>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item['overall_discount_per_unit'] > 0)
                                        <span class="text-danger">-Rs.{{ number_format($item['overall_discount_per_unit'], 2) }}</span>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold">Rs.{{ number_format($item['net_unit_price'], 2) }}</td>
                                    <td class="fw-bold text-success">
                                        Rs.{{ number_format($item['return_qty'] * $item['net_unit_price'], 2) }}
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="removeFromReturn({{ $index }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 bg-light p-3 rounded overflow-auto">
                        <div>
                            <button class="btn btn-outline-danger" wire:click="clearReturnCart">
                                <i class="bi bi-trash me-1"></i> Clear All
                            </button>
                        </div>
                        <span class="fw-bold fs-4 text-warning">Total Return Value: Rs.{{ number_format($totalReturnValue, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button class="btn btn-success px-4" wire:click="processReturn">
                            <i class="bi bi-check2-circle me-1"></i> Process Return
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Return Processing Modal -->
    <div wire:ignore.self class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-return-left me-2"></i> Confirm Supplier Return
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Supplier:</strong> {{ $selectedSupplier?->name }}</p>
                            <p><strong>Purchase Order:</strong> #{{ $selectedPurchaseOrder?->order_code }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Return Value:</strong> <span class="text-success fw-bold">Rs.{{ number_format($totalReturnValue, 2) }}</span></p>
                            <p><strong>Items:</strong> {{ count(array_filter($returnItems, fn($item) => $item['return_qty'] > 0)) }}</p>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3">Return Items Summary</h6>
                    <div class="table-responsive overflow-auto">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Return Qty</th>
                                    <th>Reason</th>
                                    <th>Net Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returnItems as $item)
                                @if($item['return_qty'] > 0)
                                <tr>
                                    <td>
                                        {{ $item['name'] }}
                                        @if($item['total_discount_per_unit'] > 0)
                                        <br><small class="text-muted">(Discounts applied: Rs.{{ number_format($item['total_discount_per_unit'], 2) }}/unit)</small>
                                        @endif
                                    </td>
                                    <td>{{ $item['return_qty'] }}</td>
                                    <td>
                                        <span class="badge bg-{{ $item['return_reason'] == 'damaged' ? 'danger' : ($item['return_reason'] == 'defective' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst(str_replace('_', ' ', $item['return_reason'])) }}
                                        </span>
                                    </td>
                                    <td>Rs.{{ number_format($item['net_unit_price'], 2) }}</td>
                                    <td class="fw-bold">Rs.{{ number_format($item['return_qty'] * $item['net_unit_price'], 2) }}</td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total Return Amount:</td>
                                    <td class="fw-bold text-success">Rs.{{ number_format($totalReturnValue, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> This action will reduce the product stock and create a supplier return record.
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success" wire:click="confirmReturn">Confirm Return</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Order Details Modal -->
    <div wire:ignore.self class="modal fade" id="purchaseOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-receipt me-2"></i> Purchase Order Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($purchaseOrderModalData)
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>PO Number:</strong> {{ $purchaseOrderModalData['order_code'] }}</p>
                            <p><strong>Supplier:</strong> {{ $purchaseOrderModalData['supplier_name'] }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Date:</strong> {{ $purchaseOrderModalData['date'] }}</p>
                            <p><strong>Total Amount:</strong> Rs.{{ number_format($purchaseOrderModalData['total_amount'], 2) }}</p>
                            @if($purchaseOrderModalData['overall_discount'] > 0)
                            <p><strong>Overall Discount:</strong> <span class="text-danger">Rs.{{ number_format($purchaseOrderModalData['overall_discount'], 2) }}</span></p>
                            @endif
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3">Purchase Order Items</h6>
                    <div class="table-responsive overflow-auto">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Code</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Item Disc.</th>
                                    <th>Overall Disc.</th>
                                    <th>Net Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseOrderModalData['items'] as $item)
                                <tr>
                                    <td>{{ $item['product_name'] }}</td>
                                    <td>{{ $item['product_code'] }}</td>
                                    <td>{{ $item['quantity'] }}</td>
                                    <td>Rs.{{ number_format($item['unit_price'], 2) }}</td>
                                    <td>
                                        @if($item['item_discount_percentage'] > 0)
                                        <span class="text-danger fw-bold">{{ $item['item_discount_percentage'] }}%</span>
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td>
                                        @if($item['overall_discount'] > 0)
                                        <span class="text-danger">-Rs.{{ number_format($item['overall_discount'], 2) }}</span>
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td class="fw-bold">Rs.{{ number_format($item['net_price'], 2) }}</td>
                                    <td class="fw-bold">Rs.{{ number_format($item['total'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($returnMode === 'manual')
    <!-- ========================================== -->
    <!-- MANUAL / EXTERNAL SUPPLIER RETURN SECTION  -->
    <!-- ========================================== -->
    <div class="row g-4">
        <!-- Left Column: Supplier Details & Product Search -->
        <div class="col-lg-5">
            <!-- Supplier Details Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-truck me-2"></i> External Supplier Details
                    </h5>
                    <small class="text-muted">Enter return reference and select or type supplier name</small>
                </div>
                <div class="card-body">
                    <!-- Reference / Invoice Number -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Reference / Bill / PO # <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-hash"></i></span>
                            <input type="text" class="form-control" wire:model="manualReferenceNumber" 
                                   placeholder="e.g. EXT-SUPP-001 or Manual Bill #">
                        </div>
                    </div>

                    <!-- Return Date -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Return Date <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
                            <input type="date" class="form-control" wire:model="manualReturnDate">
                        </div>
                    </div>

                    <!-- Supplier Selection / Search -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Supplier Name / Selection <span class="text-danger">*</span>
                        </label>

                        @if($selectedManualSupplier)
                        <div class="p-3 bg-light border rounded position-relative">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px;">
                                        <i class="bi bi-truck fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $selectedManualSupplier->name }}</div>
                                        <small class="text-muted">{{ $selectedManualSupplier->phone ?? 'No phone' }} | {{ $selectedManualSupplier->businessname ?? 'No business name' }}</small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="clearManualSupplier" title="Change Supplier">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        @else
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" wire:model.live="manualSupplierSearch" 
                                   placeholder="Search existing supplier by name, business, or phone...">
                        </div>

                        @if(count($manualSuppliers) > 0)
                        <div class="border rounded p-2 bg-white shadow-sm mb-2" style="max-height: 180px; overflow-y: auto;">
                            <small class="text-muted fw-bold px-2 d-block mb-1">Search Results:</small>
                            <div class="list-group list-group-flush">
                                @foreach($manualSuppliers as $supp)
                                <button type="button" class="list-group-item list-group-item-action py-2 px-2 d-flex justify-content-between align-items-center"
                                        wire:click="selectManualSupplier({{ $supp->id }})">
                                    <div>
                                        <span class="fw-bold">{{ $supp->name }}</span>
                                        <small class="text-muted d-block">{{ $supp->phone ?? $supp->businessname }}</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">Select</span>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="mt-2">
                            <small class="text-muted d-block mb-1">Or type supplier name directly:</small>
                            <input type="text" class="form-control form-control-sm" wire:model="manualSupplierName" 
                                   placeholder="Supplier name (e.g. Lanka Tiles PLC)">
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Product Search & Selector Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="fw-bold mb-0 text-success">
                        <i class="bi bi-box-seam me-2"></i> Search Products to Return
                    </h5>
                    <small class="text-muted">Find items from inventory to return to supplier</small>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" wire:model.live="manualProductSearch" 
                               placeholder="Type product name, code, or barcode...">
                    </div>

                    @if(count($manualProductSearchResults) > 0)
                    <div class="list-group mb-3 border rounded shadow-sm" style="max-height: 280px; overflow-y: auto;">
                        @foreach($manualProductSearchResults as $prod)
                        @php
                            $cost = $prod->price ? (float)$prod->price->supplier_price : 0;
                            if ($cost == 0 && $prod->price) $cost = (float)$prod->price->cost_price;
                            $avail = $prod->stock ? (float)$prod->stock->available_stock : 0;
                            $dmg = $prod->stock ? (float)$prod->stock->damage_stock : 0;
                        @endphp
                        <button type="button" class="list-group-item list-group-item-action p-2 d-flex align-items-center justify-content-between"
                                wire:click="addManualProduct({{ $prod->id }})">
                            <div class="d-flex align-items-center">
                                @if($prod->image)
                                <img src="{{ asset($prod->image) }}" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.src='/images/product.jpg'">
                                @else
                                <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-box text-muted"></i>
                                </div>
                                @endif
                                <div>
                                    <div class="fw-bold text-dark">{{ $prod->name }}</div>
                                    <small class="text-muted">Code: {{ $prod->code }} | Avail: <span class="badge bg-info text-dark">{{ $avail }}</span> | Dmg: <span class="badge bg-danger">{{ $dmg }}</span></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-success">Rs.{{ number_format($cost, 2) }}</div>
                                <span class="badge bg-success"><i class="bi bi-plus"></i> Add</span>
                            </div>
                        </button>
                        @endforeach
                    </div>
                    @elseif(strlen($manualProductSearch) > 1)
                    <div class="alert alert-warning py-2 mb-0">
                        <i class="bi bi-exclamation-circle me-1"></i> No matching products found for "{{ $manualProductSearch }}".
                    </div>
                    @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-search fs-3 d-block mb-2 text-muted"></i>
                        <small>Search products above to add them to the return cart</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column: Return Items Cart & Processing -->
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-cart-check text-success me-2"></i> Items to Return ({{ count($manualReturnItems) }})
                        </h5>
                        <small class="text-muted">Review return quantities, supplier cost, and return condition</small>
                    </div>
                    @if(count($manualReturnItems) > 0)
                    <button type="button" class="btn btn-outline-danger btn-sm" wire:click="clearManualReturnCart">
                        <i class="bi bi-trash me-1"></i> Clear Cart
                    </button>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if(count($manualReturnItems) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 text-dark">Product</th>
                                    <th class="text-dark" style="width: 120px;">Unit Cost (Rs.)</th>
                                    <th class="text-dark" style="width: 90px;">Return Qty</th>
                                    <th class="text-dark" style="width: 120px;">Deduct From</th>
                                    <th class="text-dark" style="width: 130px;">Reason</th>
                                    <th class="text-end text-dark">Total</th>
                                    <th class="text-center text-dark" style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($manualReturnItems as $index => $item)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark">{{ $item['name'] }}</div>
                                        <small class="text-muted">Code: {{ $item['code'] }} | Avail: <span class="badge bg-info text-dark">{{ $item['available_stock'] }}</span> | Dmg: <span class="badge bg-danger">{{ $item['damage_stock'] }}</span></small>
                                        <input type="text" class="form-control form-control-sm mt-1" 
                                               wire:model.lazy="manualReturnItems.{{ $index }}.notes" 
                                               placeholder="Notes / Remarks (optional)">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                               wire:model.lazy="manualReturnItems.{{ $index }}.unit_price">
                                    </td>
                                    <td>
                                        <input type="number" step="1" min="1" class="form-control form-control-sm text-center fw-bold"
                                               wire:model.lazy="manualReturnItems.{{ $index }}.return_qty">
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" wire:model="manualReturnItems.{{ $index }}.return_condition">
                                            <option value="usable">Available Stock</option>
                                            <option value="damage">Damage Stock</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" wire:model="manualReturnItems.{{ $index }}.return_reason">
                                            <option value="damaged">Damaged</option>
                                            <option value="expired">Expired</option>
                                            <option value="wrong_item">Wrong Item</option>
                                            <option value="quality_issue">Quality Issue</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        Rs.{{ number_format(((float)$item['return_qty']) * ((float)$item['unit_price']), 2) }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm p-1" 
                                                wire:click="removeManualReturnItem({{ $index }})" title="Remove item">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3 bg-light border-top">
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <div class="small text-muted">
                                    <i class="bi bi-info-circle me-1"></i> Returning items reduces inventory. Items marked "Damage Stock" are deducted from damaged inventory; items marked "Available Stock" are deducted from available stock.
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="text-muted small">Total Return Value</div>
                                <div class="fs-3 fw-bold text-success">
                                    Rs.{{ number_format($manualTotalReturnValue, 2) }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-outline-secondary" wire:click="clearManualReturnCart">
                                Cancel
                            </button>
                            <button type="button" class="btn btn-success px-4 fw-bold shadow-sm" wire:click="processManualReturn">
                                <i class="bi bi-check-circle me-1"></i> Process Manual Return
                            </button>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="bi bi-cart-x text-muted" style="font-size: 3.5rem;"></i>
                        <h6 class="fw-bold mt-3 text-secondary">No items in return cart</h6>
                        <p class="text-muted small mb-0">Use the product search on the left to select items from inventory</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Supplier Return Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="manualReturnModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-return-left me-2"></i> Confirm External Supplier Return
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <i class="bi bi-info-circle me-1"></i> This return is for an external/manual supplier return. Records will be saved to the manual supplier returns database and selected inventory will be deducted.
                    </div>

                    <div class="row mb-3 g-2">
                        <div class="col-md-6">
                            <div class="p-2 border rounded bg-light">
                                <small class="text-muted d-block">Reference #</small>
                                <strong>{{ $manualReferenceNumber }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded bg-light">
                                <small class="text-muted d-block">Return Date</small>
                                <strong>{{ $manualReturnDate }}</strong>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="p-2 border rounded bg-light">
                                <small class="text-muted d-block">Supplier</small>
                                <strong>{{ $selectedManualSupplier ? $selectedManualSupplier->name : ($manualSupplierName ?: 'General Supplier') }}</strong>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-2">Return Items Summary</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-dark">Product</th>
                                    <th class="text-center text-dark">Qty</th>
                                    <th class="text-dark">Deduct Stock</th>
                                    <th class="text-dark">Reason</th>
                                    <th class="text-end text-dark">Unit Price</th>
                                    <th class="text-end text-dark">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($manualReturnItems as $item)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $item['name'] }}</div>
                                        <small class="text-muted">Code: {{ $item['code'] }}</small>
                                        @if(!empty($item['notes']))
                                        <small class="d-block text-secondary">Note: {{ $item['notes'] }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold">{{ $item['return_qty'] }}</td>
                                    <td>
                                        <span class="badge bg-{{ ($item['return_condition'] ?? 'usable') === 'damage' ? 'danger' : 'info text-dark' }}">
                                            {{ ($item['return_condition'] ?? 'usable') === 'damage' ? 'Damage Stock' : 'Available Stock' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ ucwords(str_replace('_', ' ', $item['return_reason'] ?? 'damaged')) }}
                                        </span>
                                    </td>
                                    <td class="text-end">Rs.{{ number_format($item['unit_price'], 2) }}</td>
                                    <td class="text-end fw-bold text-success">
                                        Rs.{{ number_format(((float)$item['return_qty']) * ((float)$item['unit_price']), 2) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Grand Total:</td>
                                    <td class="text-end fw-bold fs-5 text-success">Rs.{{ number_format($manualTotalReturnValue, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success px-4 fw-bold" wire:click="confirmManualReturn">
                        <i class="bi bi-check-circle me-1"></i> Confirm & Save Return
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
    }

    .card-header {
        background-color: white;
        border-bottom: 1px solid #dee2e6;
        border-radius: 12px 12px 0 0 !important;
        padding: 1.25rem 1.5rem;
    }

    .table th {
        border-top: none;
        font-weight: 600;
        color: #ffffff;
       
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .table td {
        vertical-align: middle;
        padding: 0.75rem;
    }

    .btn-group-sm>.btn {
        padding: 0.25rem 0.5rem;
    }

    .modal-header {
        border-bottom: 1px solid #dee2e6;
    }

    .badge {
        font-size: 0.75em;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.025);
    }

    .border-warning {
        border-width: 2px !important;
    }

    .form-select-sm {
        padding: 0.25rem 2.25rem 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
</style>
@endpush

@push('scripts')
<script>
    window.addEventListener('alert', event => {
        Swal.fire('Success', event.detail.message, 'success');
    });

    Livewire.on('show-return-modal', () => {
        var modalEl = document.getElementById('returnModal');
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
    });

    Livewire.on('show-purchase-order-modal', () => {
        var modalEl = document.getElementById('purchaseOrderModal');
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
    });

    Livewire.on('close-return-modal', () => {
        var modalEl = document.getElementById('returnModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    });

    Livewire.on('show-manual-return-modal', () => {
        var modalEl = document.getElementById('manualReturnModal');
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
    });

    Livewire.on('close-manual-return-modal', () => {
        var modalEl = document.getElementById('manualReturnModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    });

    Livewire.on('reload-page', () => {
        window.location.reload();
    });
</script>
@endpush
