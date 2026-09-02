<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-arrow-return-left text-success me-2"></i> Product Returns
            </h3>
            <p class="text-muted mb-0">Manage customer returns for system invoices and manual external sales</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.return-list') }}" class="btn btn-outline-primary">
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
                    <i class="bi bi-receipt-cutoff me-2"></i> System Invoice Return
                    <small class="d-block text-xs {{ $returnMode === 'system' ? 'text-white-50' : 'text-muted' }}">Return items from sales recorded in database</small>
                </button>
                <button class="nav-link {{ $returnMode === 'manual' ? 'active fw-bold shadow-sm' : 'text-muted' }}" 
                        wire:click="setReturnMode('manual')" type="button">
                    <i class="bi bi-journal-plus me-2"></i> Manual / External Sale Return
                    <small class="d-block text-xs {{ $returnMode === 'manual' ? 'text-white-50' : 'text-muted' }}">Return items from legacy or non-DB invoices</small>
                </button>
            </div>
        </div>
    </div>

    @if($returnMode === 'system')
    <!-- ========================================== -->
    <!-- SYSTEM SALES RETURN SECTION               -->
    <!-- ========================================== -->
    <div class="row mb-4">
        <!-- Customer Search -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-person-search text-primary me-2"></i> Customer Search
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Search Customer or Invoice #</label>
                        <input type="text" class="form-control" wire:model.live="searchCustomer" placeholder="Search by customer name, phone, or invoice number...">
                    </div>

                    @if($searchCustomer && (count($customers) > 0 || count($customerInvoices) > 0))
                    <div class="border rounded p-3 bg-light mb-3">
                        <h6 class="fw-semibold mb-2">Search Results</h6>
                        @if(count($customers) > 0)
                        <div class="list-group mb-2">
                            @foreach($customers as $customer)
                            <button class="list-group-item list-group-item-action p-2"
                                wire:click="selectCustomer({{ $customer->id }})"
                                type="button">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-person-circle fs-4 text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $customer->name }}</div>
                                        <small class="text-muted">{{ $customer->phone }} | {{ $customer->email }}</small>
                                    </div>
                                </div>
                            </button>
                            @endforeach
                        </div>
                        @endif

                        @if(count($customerInvoices) > 0)
                        <div class="list-group">
                            @foreach($customerInvoices as $invoice)
                            <button class="list-group-item list-group-item-action p-2"
                                wire:click="selectInvoiceForReturn({{ $invoice->id }})"
                                type="button">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-receipt fs-4 text-info"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">Invoice #{{ $invoice->invoice_number }}</div>
                                        <small class="text-muted">{{ $invoice->created_at->format('Y-m-d') }} | Rs.{{ number_format($invoice->total_amount, 2) }}</small>
                                    </div>
                                </div>
                            </button>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endif

                    @if($selectedCustomer)
                    <div class="mt-3 p-3 bg-info bg-opacity-10 rounded border border-info">
                        <h6 class="fw-semibold text-info mb-2">Selected Customer</h6>
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="bi bi-person-check fs-4 text-info"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $selectedCustomer->name }}</div>
                                <small class="text-muted">{{ $selectedCustomer->phone }} | {{ $selectedCustomer->email }}</small>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Customer Invoices -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-receipt text-info me-2"></i> Recent Invoices
                    </h5>
                    <button class="btn btn-info btn-sm text-white" wire:click="loadCustomerInvoices">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    @if($selectedCustomer && count($customerInvoices) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 text-dark">Invoice #</th>
                                    <th class="text-dark">Date</th>
                                    <th class="text-dark">Total</th>
                                    <th class="text-end pe-3 text-dark">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customerInvoices as $invoice)
                                <tr>
                                    <td class="ps-3">
                                        <span class="fw-medium text-dark">{{ $invoice->invoice_number }}</span>
                                    </td>
                                    <td>{{ $invoice->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <span class="fw-bold text-dark">Rs.{{ number_format($invoice->total_amount, 2) }}</span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-success"
                                                wire:click="selectInvoiceForReturn({{ $invoice->id }})">
                                                <i class="bi bi-check-circle me-1"></i> Select
                                            </button>
                                            <button class="btn btn-outline-info"
                                                wire:click="viewInvoice({{ $invoice->id }})">
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
                        <p class="text-muted mb-0">No invoices found for this customer</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($showReturnSection && $selectedInvoice)
    <!-- Previous Returns Section -->
    @if(!empty($previousReturns))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning bg-opacity-10 border-bottom border-warning">
                    <h5 class="fw-bold mb-0 text-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i> Previous Returns for Invoice #{{ $selectedInvoice->invoice_number }}
                    </h5>
                </div>
                <div class="card-body overflow-auto">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-dark">Product</th>
                                    <th class="text-dark">Condition</th>
                                    <th class="text-dark">Total Returned</th>
                                    <th class="text-dark">Total Amount</th>
                                    <th class="text-dark">Return Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($previousReturns as $productId => $returnData)
                                <tr>
                                    <td>{{ $returnData['product_name'] }}</td>
                                    <td>
                                        @foreach($returnData['conditions'] as $condition)
                                        <span class="badge me-1 bg-{{ $condition === 'usable' ? 'success' : ($condition === 'damage' ? 'danger' : 'warning text-dark') }}">
                                            {{ ucwords(str_replace('_', ' ', $condition)) }}
                                        </span>
                                        @endforeach
                                    </td>
                                    <td><span class="badge bg-warning text-dark">{{ $returnData['total_returned'] }} units</span></td>
                                    <td class="fw-bold">Rs.{{ number_format($returnData['total_amount'], 2) }}</td>
                                    <td>
                                        <div class="small">
                                            @foreach($returnData['returns'] as $return)
                                            <div class="mb-1">
                                                <span class="badge bg-secondary">{{ $return['quantity'] }} units</span>
                                                <span class="badge bg-{{ $return['condition'] === 'usable' ? 'success' : ($return['condition'] === 'damage' ? 'danger' : 'warning text-dark') }}">
                                                    {{ ucwords(str_replace('_', ' ', $return['condition'])) }}
                                                </span>
                                                <span class="text-muted">- Rs.{{ number_format($return['amount'], 2) }}</span>
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

    <!-- Invoice Items for Return -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-receipt text-info me-2"></i> Invoice #{{ $selectedInvoice->invoice_number }} Items
                            </h5>
                            <p class="text-muted small mb-0">Select return quantity for each item below</p>
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
                                    <th class="text-dark">Product</th>
                                    <th class="text-dark">Code</th>
                                    <th class="text-dark">Original Qty</th>
                                    <th class="text-dark">Returned</th>
                                    <th class="text-dark">Available</th>
                                    <th class="text-dark">Return Qty</th>
                                    <th class="text-dark">Condition</th>
                                    <th class="text-dark">Unit Price</th>
                                    <th class="text-dark">Unit Disc.</th>
                                    <th class="text-dark">Overall Disc.</th>
                                    <th class="text-dark">Net Price</th>
                                    <th class="text-dark">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returnItems as $index => $item)
                                <tr>
                                    <td>{{ $item['name'] }}</td>
                                    <td>{{ isset($selectedInvoice->items[$index]->product) ? $selectedInvoice->items[$index]->product->code : '-' }}</td>
                                    <td>{{ $item['original_qty'] }}</td>
                                    <td>
                                        @if($item['already_returned'] > 0)
                                        <span class="badge bg-warning text-dark">{{ $item['already_returned'] }}</span>
                                        @else
                                        <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-success">{{ $item['max_qty'] }}</span>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" style="width: 80px;"
                                            min="0" max="{{ $item['max_qty'] }}"
                                            wire:model.lazy="returnItems.{{ $index }}.return_qty"
                                            @if($item['max_qty']==0) disabled @endif>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" style="min-width: 110px;"
                                            wire:model="returnItems.{{ $index }}.return_condition"
                                            @if($item['max_qty']==0) disabled @endif>
                                            <option value="usable">Usable</option>
                                            <option value="damage">Damage</option>
                                            <option value="company_fault">Company Fault</option>
                                        </select>
                                    </td>
                                    <td>Rs.{{ number_format($item['unit_price'], 2) }}</td>
                                    <td>
                                        @if($item['discount_per_unit'] > 0)
                                        <span class="text-danger">-Rs.{{ number_format($item['discount_per_unit'], 2) }}</span>
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
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3 bg-light p-3 rounded">
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

    @else
    <!-- ========================================== -->
    <!-- MANUAL / EXTERNAL SALES RETURN SECTION    -->
    <!-- ========================================== -->
    <div class="row g-4">
        <!-- Left Column: Invoice Details & Product Search -->
        <div class="col-lg-5">
            <!-- Invoice & Customer Details Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-file-earmark-text me-2"></i> External Sale Details
                    </h5>
                    <small class="text-muted">Enter manual invoice details and select customer</small>
                </div>
                <div class="card-body">
                    <!-- Invoice Number -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Manual Invoice # <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-hash"></i></span>
                            <input type="text" class="form-control" wire:model="manualInvoiceNumber" 
                                   placeholder="e.g. INV-2025-001 or Legacy #1042">
                        </div>
                    </div>

                    <!-- Invoice Date -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Invoice Date <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
                            <input type="date" class="form-control" wire:model="manualInvoiceDate">
                        </div>
                    </div>

                    <!-- Customer Selection / Search -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Customer Name / Selection <span class="text-danger">*</span>
                        </label>

                        @if($selectedManualCustomer)
                        <div class="p-3 bg-light border rounded position-relative">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px;">
                                        <i class="bi bi-person-fill fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $selectedManualCustomer->name }}</div>
                                        <small class="text-muted">{{ $selectedManualCustomer->phone ?? 'No phone' }} | {{ $selectedManualCustomer->address ?? 'No address' }}</small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="clearManualCustomer" title="Change Customer">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        @else
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" wire:model.live="manualCustomerSearch" 
                                   placeholder="Search existing customer by name or phone...">
                        </div>

                        @if(count($manualCustomers) > 0)
                        <div class="border rounded p-2 bg-white shadow-sm mb-2" style="max-height: 180px; overflow-y: auto;">
                            <small class="text-muted fw-bold px-2 d-block mb-1">Search Results:</small>
                            <div class="list-group list-group-flush">
                                @foreach($manualCustomers as $cust)
                                <button type="button" class="list-group-item list-group-item-action py-2 px-2 d-flex justify-content-between align-items-center"
                                        wire:click="selectManualCustomer({{ $cust->id }})">
                                    <div>
                                        <span class="fw-bold">{{ $cust->name }}</span>
                                        <small class="text-muted d-block">{{ $cust->phone }}</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">Select</span>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="mt-2">
                            <small class="text-muted d-block mb-1">Or type customer name directly:</small>
                            <input type="text" class="form-control form-control-sm" wire:model="manualCustomerName" 
                                   placeholder="Customer name (e.g. John Silva)">
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
                    <small class="text-muted">Find items in inventory and add to return list</small>
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
                            $sp = $prod->price ? (float)$prod->price->selling_price : 0;
                            $st = $prod->stock ? (float)$prod->stock->available_stock : 0;
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
                                    <small class="text-muted">Code: {{ $prod->code }} | Stock: <span class="badge bg-info text-dark">{{ $st }}</span></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-success">Rs.{{ number_format($sp, 2) }}</div>
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
                        <small class="text-muted">Review quantities, selling prices, and conditions</small>
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
                                    <th class="text-dark" style="width: 120px;">Unit Price (Rs.)</th>
                                    <th class="text-dark" style="width: 90px;">Qty</th>
                                    <th class="text-dark" style="width: 130px;">Condition</th>
                                    <th class="text-end text-dark">Total</th>
                                    <th class="text-center text-dark" style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($manualReturnItems as $index => $item)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark">{{ $item['name'] }}</div>
                                        <small class="text-muted">Code: {{ $item['code'] }} | Curr. Stock: {{ $item['available_stock'] }}</small>
                                        <input type="text" class="form-control form-control-sm mt-1" 
                                               wire:model.lazy="manualReturnItems.{{ $index }}.notes" 
                                               placeholder="Reason / Notes (optional)">
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
                                            <option value="usable">Usable</option>
                                            <option value="damage">Damage</option>
                                            <option value="company_fault">Company Fault</option>
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
                                    <i class="bi bi-info-circle me-1"></i> Returned usable items will be added back to <strong>available stock</strong>. Damaged items will be added to <strong>damage stock</strong>.
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

    <!-- Manual Return Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="manualReturnModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-return-left me-2"></i> Confirm External Sale Return
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <i class="bi bi-info-circle me-1"></i> This return is for a manual/external sale. Records will be saved to the manual returns database and item stocks will be restocked.
                    </div>

                    <div class="row mb-3 g-2">
                        <div class="col-md-6">
                            <div class="p-2 border rounded bg-light">
                                <small class="text-muted d-block">Manual Invoice #</small>
                                <strong>{{ $manualInvoiceNumber }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded bg-light">
                                <small class="text-muted d-block">Invoice Date</small>
                                <strong>{{ $manualInvoiceDate }}</strong>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="p-2 border rounded bg-light">
                                <small class="text-muted d-block">Customer</small>
                                <strong>{{ $selectedManualCustomer ? $selectedManualCustomer->name : ($manualCustomerName ?: 'Walk-in Customer') }}</strong>
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
                                    <th class="text-dark">Condition</th>
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
                                        <span class="badge bg-{{ ($item['return_condition'] ?? 'usable') === 'usable' ? 'success' : (($item['return_condition'] ?? 'usable') === 'damage' ? 'danger' : 'warning text-dark') }}">
                                            {{ ucwords(str_replace('_', ' ', $item['return_condition'] ?? 'usable')) }}
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
                                    <td colspan="4" class="text-end fw-bold">Grand Total:</td>
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

    <!-- System Return Processing Modal -->
    <div wire:ignore.self class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-return-left me-2"></i> Confirm Product Return
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Customer:</strong> {{ $selectedCustomer?->name }}</p>
                            <p><strong>Invoice:</strong> #{{ $selectedInvoice?->invoice_number }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Return Value:</strong> <span class="text-success fw-bold">Rs.{{ number_format($totalReturnValue, 2) }}</span></p>
                            <p><strong>Items:</strong> {{ count(array_filter($returnItems, fn($item) => $item['return_qty'] > 0)) }}</p>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3">Return Items Summary</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-dark">Product</th>
                                    <th class="text-dark">Return Qty</th>
                                    <th class="text-dark">Condition</th>
                                    <th class="text-dark">Net Unit Price</th>
                                    <th class="text-dark">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returnItems as $item)
                                @if($item['return_qty'] > 0)
                                <tr>
                                    <td>
                                        {{ $item['name'] }}
                                        @if($item['total_discount_per_unit'] > 0)
                                        <br><small class="text-muted">(Discounts applied: Rs.{{ number_format($item['overall_discount_per_unit'], 2) }}/unit)</small>
                                        @endif
                                    </td>
                                    <td>{{ $item['return_qty'] }}</td>
                                    <td>
                                        <span class="badge bg-{{ ($item['return_condition'] ?? 'usable') === 'usable' ? 'success' : (($item['return_condition'] ?? 'usable') === 'damage' ? 'danger' : 'warning text-dark') }}">
                                            {{ ucwords(str_replace('_', ' ', $item['return_condition'] ?? 'usable')) }}
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
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success" wire:click="confirmReturn">Confirm Return</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Details Modal -->
    <div wire:ignore.self class="modal fade" id="invoiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-receipt me-2"></i> Invoice Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($invoiceModalData)
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Invoice Number:</strong> {{ $invoiceModalData['invoice_number'] }}</p>
                            <p><strong>Customer:</strong> {{ $invoiceModalData['customer_name'] }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Date:</strong> {{ $invoiceModalData['date'] }}</p>
                            <p><strong>Total Amount:</strong> Rs.{{ number_format($invoiceModalData['total_amount'], 2) }}</p>
                            @if($invoiceModalData['overall_discount'] > 0)
                            <p><strong>Overall Discount:</strong> <span class="text-danger">Rs.{{ number_format($invoiceModalData['overall_discount'], 2) }}</span></p>
                            @endif
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3">Invoice Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-dark">Product</th>
                                    <th class="text-dark">Code</th>
                                    <th class="text-dark">Qty</th>
                                    <th class="text-dark">Unit Price</th>
                                    <th class="text-dark">Item Disc.</th>
                                    <th class="text-dark">Overall Disc.</th>
                                    <th class="text-dark">Net Price</th>
                                    <th class="text-dark">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoiceModalData['items'] as $item)
                                <tr>
                                    <td>{{ $item['product_name'] }}</td>
                                    <td>{{ $item['product_code'] }}</td>
                                    <td>{{ $item['quantity'] }}</td>
                                    <td>Rs.{{ number_format($item['unit_price'], 2) }}</td>
                                    <td>
                                        @if($item['item_discount'] > 0)
                                        <span class="text-danger">-Rs.{{ number_format($item['item_discount'], 2) }}</span>
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
</div>

@push('styles')
<style>
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .card-header {
        background-color: white;
        border-bottom: 1px solid #dee2e6;
        border-radius: 12px 12px 0 0 !important;
        padding: 1.25rem 1.5rem;
    }

    .table th {
        font-weight: 600;
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

    .badge {
        font-size: 0.75em;
    }

    .nav-pills .nav-link {
        border-radius: 8px;
        padding: 0.75rem 1rem;
        transition: all 0.2s ease;
    }

    .nav-pills .nav-link.active {
        background-color: #0d6efd;
    }
</style>
@endpush

@push('scripts')
<script>
    window.addEventListener('alert', event => {
        Swal.fire('Success', event.detail.message || (event.detail && event.detail[0] ? event.detail[0].message : 'Action completed'), 'success');
    });

    Livewire.on('show-return-modal', () => {
        var modalEl = document.getElementById('returnModal');
        if (modalEl) {
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    });

    Livewire.on('show-manual-return-modal', () => {
        var modalEl = document.getElementById('manualReturnModal');
        if (modalEl) {
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    });

    Livewire.on('close-manual-return-modal', () => {
        var modalEl = document.getElementById('manualReturnModal');
        if (modalEl) {
            var modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }
    });

    Livewire.on('show-invoice-modal', () => {
        var modalEl = document.getElementById('invoiceModal');
        if (modalEl) {
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    });

    Livewire.on('close-return-modal', () => {
        var modalEl = document.getElementById('returnModal');
        if (modalEl) {
            var modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }
    });

    Livewire.on('reload-page', () => {
        window.location.reload();
    });
</script>
@endpush