<div class="container-fluid py-4">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if (session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-receipt text-success me-2"></i> Customer Payment Receipt
            </h3>
            <p class="text-muted mb-0">Record customer payments and allocate to due invoices</p>
        </div>
    </div>

    {{-- Customer Search --}}
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-search me-2"></i> Find Customer with Due Payments
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Search Customer</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-person"></i>
                        </span>
                        <input type="text" class="form-control"
                            placeholder="Search by customer name, phone or email..."
                            wire:model.live="search">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Selected Customer</label>
                    @if($selectedCustomer)
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-1">{{ $selectedCustomer->name }}</h6>
                                <p class="mb-1 text-muted">
                                    <i class="bi bi-telephone me-1"></i>{{ $selectedCustomer->phone }}
                                    @if($selectedCustomer->email)
                                    | <i class="bi bi-envelope me-1"></i>{{ $selectedCustomer->email }}
                                    @endif
                                </p>
                                <span class="badge bg-warning">
                                    {{ count($customerSales) }} Due Invoice(s)
                                </span>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm" wire:click="clearSelectedCustomer">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                    @else
                    <div class="border rounded p-3 text-center text-muted">
                        <i class="bi bi-person-x display-6 d-block mb-2"></i>
                        No customer selected
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Customer List --}}
    @if(!$selectedCustomer)
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-people me-2"></i> Customers with Due Payments
            </h5>
            <span class="badge bg-primary">{{ $customers->total() }} customers</span>
        </div>
        <div class="card-body p-0 overflow-auto">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Customer</th>
                            <th>Contact</th>
                            <th class="text-center">Due Invoices</th>
                            <th class="text-end">Total Due Amount</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        @php
                        $dueInvoices = $customer->sales->whereIn('payment_status', ['pending', 'partial'])->count();
                        $totalDue = (float) $customer->opening_balance + $customer->sales->whereIn('payment_status', ['pending', 'partial'])->sum(function($sale) {
                        $returnAmount = $sale->returns ? $sale->returns->sum('total_amount') : 0;
                        return max(0, $sale->due_amount - $returnAmount);
                        });
                        @endphp
                        <tr wire:key="customer-{{ $customer->id }}">
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $customer->name }}</div>
                                <small class="text-muted">Type: {{ ucfirst($customer->type) }}</small>
                            </td>
                            <td>
                                <div class="text-dark">{{ $customer->phone }}</div>
                                @if($customer->email)
                                <small class="text-muted">{{ $customer->email }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning">{{ $dueInvoices }}</span>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-danger">Rs.{{ number_format($totalDue, 2) }}</span>
                                <br>
                                <small class="text-muted">(Includes opening balance)</small>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-primary btn-sm" wire:click="selectCustomer({{ $customer->id }})">
                                    <i class="bi bi-credit-card me-1"></i> Receive Payment
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-person-check display-4 d-block mb-2"></i>
                                No customers found with due payments.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($customers->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-center">
                    {{ $customers->links('livewire.custom-pagination') }}
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Due Invoices and Payment Allocation --}}
    @if($selectedCustomer && (count($customerSales) > 0 || (float) $selectedCustomer->opening_balance > 0))
    <div class="row">
        {{-- Due Invoices List --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-receipt me-2"></i> Due Invoices - {{ $selectedCustomer->name }}
                    </h5>
                    <div>
                        @if(count($selectedInvoices) > 0)
                        <button class="btn btn-light btn-sm me-2" wire:click="clearInvoiceSelection">
                            <i class="bi bi-x-circle me-1"></i> Clear Selection
                        </button>
                        @endif
                        <button class="btn btn-light btn-sm" wire:click="selectAllInvoices">
                            <i class="bi bi-check-all me-1"></i> Select All
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 overflow-auto">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" width="50">
                                        <i class="bi bi-check-square"></i>
                                    </th>
                                    <th>Invoice</th>
                                    <th class="text-center">Date</th>
                                    <th class="text-end">Original Total</th>
                                    <th class="text-end">Returns</th>
                                    <th class="text-end">Adjusted Total</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Due Amount</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if((float) $selectedCustomer->opening_balance > 0)
                                @php $openingSelected = in_array('opening', $selectedInvoices); @endphp
                                <tr wire:key="opening-balance" class="{{ $openingSelected ? 'table-success' : 'table-warning' }}" style="cursor: pointer;" wire:click="toggleInvoiceSelection('opening')">
                                    <td class="text-center"><input class="form-check-input" type="checkbox" {{ $openingSelected ? 'checked' : '' }} style="pointer-events: none;"></td>
                                    <td class="ps-4"><div class="fw-bold text-danger">Opening Balance</div><small class="text-muted">Previous balance</small></td>
                                    <td class="text-center">{{ $selectedCustomer->created_at?->format('d/m/Y') }}</td>
                                    <td class="text-end">-</td><td class="text-end">-</td><td class="text-end fw-bold">-</td><td class="text-end text-success">-</td>
                                    <td class="text-end fw-bold {{ $openingSelected ? 'text-success' : 'text-danger' }}">Rs.{{ number_format((float) $selectedCustomer->opening_balance, 2) }}</td>
                                    <td class="text-center"><span class="badge bg-danger">Opening Due</span></td><td class="text-center">-</td>
                                </tr>
                                @endif
                                @foreach($customerSales as $sale)
                                @php
                                $isSelected = in_array($sale['id'], $selectedInvoices);
                                @endphp
                                <tr
                                    wire:key="sale-{{ $sale['id'] }}"
                                    class="{{ $isSelected ? 'table-success' : '' }} {{ $sale['has_returns'] ? 'table-info' : 'table-warning' }}"
                                    style="cursor: pointer;"
                                    wire:click="toggleInvoiceSelection({{ $sale['id'] }})">
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                {{ $isSelected ? 'checked' : '' }}
                                                style="pointer-events: none;">
                                        </div>
                                    </td>
                                    <td class="ps-4">
                                        <div class="fw-bold text-primary">{{ $sale['invoice_number'] }}</div>
                                        <small class="text-muted">#{{ $sale['sale_id'] }}</small>
                                        @if($sale['has_returns'])
                                        <br><span class="badge bg-info badge-sm">Has Returns</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $sale['sale_date'] }}</td>
                                    <td class="text-end">
                                        Rs.{{ number_format($sale['original_total_amount'], 2) }}
                                    </td>
                                    <td class="text-end">
                                        @if($sale['has_returns'])
                                        <span class="text-danger fw-bold">-Rs.{{ number_format($sale['return_amount'], 2) }}</span>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">
                                        Rs.{{ number_format($sale['total_amount'], 2) }}
                                    </td>
                                    <td class="text-end text-success">Rs.{{ number_format($sale['paid_amount'], 2) }}</td>
                                    <td class="text-end fw-bold {{ $isSelected ? 'text-success' : 'text-danger' }}">
                                        Rs.{{ number_format($sale['due_amount'], 2) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $sale['payment_status'] == 'partial' ? 'warning' : 'danger' }}">
                                            {{ ucfirst($sale['payment_status']) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-outline-info btn-sm"
                                            wire:click.stop="viewSale({{ $sale['id'] }})"
                                            title="View Invoice Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="7" class="text-end fw-bold ps-4">Total Due After Returns:</td>
                                    <td class="text-end fw-bold text-danger">Rs.{{ number_format($totalDueAmount, 2) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @if(count($selectedInvoices) > 0)
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                {{ count($selectedInvoices) }} invoice(s) selected
                            </span>
                            <span class="fw-bold text-success">
                                Total Due: Rs.{{ number_format($totalDueAmount, 2) }}
                            </span>
                        </div>
                    </div>
                    @else
                    <div class="card-footer bg-light text-center text-muted">
                        <i class="bi bi-hand-index me-2"></i>
                        Click on rows to select invoices for payment
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Payment Allocation --}}
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-success text-white">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-cash-coin me-2"></i> Payment Allocation
                    </h5>
                </div>
                <div class="card-body">
                    @if(count($selectedInvoices) > 0)
                    {{-- Show total due at the top --}}
                    <div class="alert alert-info mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Total Due (Selected):</span>
                            <span class="fw-bold fs-5 text-danger">Rs.{{ number_format($totalDueAmount, 2) }}</span>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            {{ count($selectedInvoices) }} invoice(s) selected for payment
                        </small>
                    </div>

                    {{-- Payment Amount Field --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Enter Payment Amount <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">Rs.</span>
                            <input
                                type="number"
                                min="0.01"
                                max="{{ $totalDueAmount }}"
                                step="0.01"
                                class="form-control"
                                wire:model.live="totalPaymentAmount"
                                placeholder="0">
                        </div>
                        <small class="text-muted">Maximum: Rs.{{ number_format($totalDueAmount, 2) }}</small>
                        @error('totalPaymentAmount')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Remaining Amount Display --}}
                    @if($totalPaymentAmount > 0)
                    <div class="alert alert-{{ $remainingAmount > 0 ? 'warning' : 'success' }} mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Remaining Due:</span>
                            <span class="fw-bold">Rs.{{ number_format($remainingAmount, 2) }}</span>
                        </div>
                    </div>
                    @endif

                    {{-- Process Payment Button --}}
                    <div class="d-grid mt-3">
                        <button
                            class="btn btn-success btn-lg"
                            wire:click="openPaymentModal"
                            wire:loading.attr="disabled"
                            wire:loading.class="disabled"
                            @if($totalPaymentAmount <=0 || $totalPaymentAmount> $totalDueAmount) disabled @endif>
                            <span wire:loading.remove wire:target="openPaymentModal">
                                <i class="bi bi-cash-coin me-2"></i>
                                Process Payment
                                @if($totalPaymentAmount > 0)
                                <span class="ms-1">(Rs.{{ number_format((float)$totalPaymentAmount, 2) }})</span>
                                @endif
                            </span>
                            <span wire:loading wire:target="openPaymentModal">
                                <span class="spinner-border spinner-border-sm me-1"></span>
                                Processing...
                            </span>
                        </button>
                    </div>

                    {{-- Quick Payment Options --}}
                    <div class="mt-3">
                        <small class="text-muted d-block mb-2">Quick Options:</small>
                        <div class="d-grid gap-2">
                            <button
                                class="btn btn-outline-primary btn-sm"
                                wire:click="$set('totalPaymentAmount', {{ $totalDueAmount }})">
                                Pay Full Amount (Rs.{{ number_format($totalDueAmount, 2) }})
                            </button>
                        </div>
                    </div>

                    {{-- Payment Note --}}
                    <div class="alert alert-light border mt-3 mb-0">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Payment will be automatically allocated to selected invoices.
                        </small>
                    </div>
                    @else
                    <div class="alert alert-warning text-center">
                        <i class="bi bi-exclamation-triangle-fill display-4 d-block mb-3"></i>
                        <h6 class="fw-bold mb-2">No Invoices Selected</h6>
                        <p class="mb-0 small">Please select at least one invoice from the list to make a payment.</p>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-secondary btn-lg" disabled>
                            <i class="bi bi-lock me-1"></i> Select Invoices First
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @elseif($selectedCustomer && count($customerSales) == 0 && (float) $selectedCustomer->opening_balance <= 0)
    <div class="alert alert-success">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill fs-2 me-3"></i>
            <div>
                <h5 class="mb-1">No Pending Payments</h5>
                <p class="mb-0">{{ $selectedCustomer->name }} has no pending invoices. All invoices are fully paid (including adjustments for returns).</p>
            </div>
        </div>
    </div>
    @endif

    {{-- View Sale Modal --}}
    @if($showViewModal && $selectedSale)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-eye me-2"></i> Invoice Details - {{ $selectedSale->invoice_number }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeViewModal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">INVOICE INFORMATION</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>Invoice Number:</strong></td>
                                    <td>{{ $selectedSale->invoice_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Sale ID:</strong></td>
                                    <td>{{ $selectedSale->sale_id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Date & Time:</strong></td>
                                    <td>{{ $selectedSale->created_at->format('d/m/Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Original Total:</strong></td>
                                    <td class="fw-bold">Rs.{{ number_format($selectedSale->total_amount, 2) }}</td>
                                </tr>
                                @if($selectedSale->return_amount > 0)
                                <tr>
                                    <td><strong>Returns Amount:</strong></td>
                                    <td class="fw-bold text-danger">-Rs.{{ number_format($selectedSale->return_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Adjusted Total:</strong></td>
                                    <td class="fw-bold text-success">Rs.{{ number_format($selectedSale->adjusted_total, 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td><strong>Due Amount:</strong></td>
                                    <td class="fw-bold text-danger">Rs.{{ number_format($selectedSale->adjusted_due, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Payment Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $selectedSale->payment_status == 'paid' ? 'success' : ($selectedSale->payment_status == 'partial' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($selectedSale->payment_status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">CUSTOMER INFORMATION</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>Name:</strong></td>
                                    <td>{{ $selectedSale->customer->name ?? 'Walk-in Customer' }}</td>
                                </tr>
                                @if($selectedSale->customer)
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td>{{ $selectedSale->customer->phone }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td>{{ ucfirst($selectedSale->customer_type) }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <h6 class="text-muted mb-3">ITEMS SOLD</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-center">Code</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedSale->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->product_name }}</td>
                                    <td class="text-center">{{ $item->product_code }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">Rs.{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end">Rs.{{ number_format($item->total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($selectedSale->returns && count($selectedSale->returns) > 0)
                    <h6 class="text-muted mb-3">RETURNED ITEMS</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-info">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-center">Return Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Return Amount</th>
                                    <th class="text-center">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedSale->returns as $index => $return)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $return->product->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $return->return_quantity }}</td>
                                    <td class="text-end">Rs.{{ number_format($return->selling_price, 2) }}</td>
                                    <td class="text-end fw-bold text-danger">Rs.{{ number_format($return->total_amount, 2) }}</td>
                                    <td class="text-center">{{ $return->created_at->format('d/m/Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total Returns:</td>
                                    <td class="text-end fw-bold text-danger">Rs.{{ number_format($selectedSale->return_amount, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeViewModal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Payment Modal --}}
    @if($showPaymentModal && $selectedCustomer)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" x-data="{ isUploadingPhoto: false }" @photo-uploaded.window="isUploadingPhoto = false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-credit-card me-2"></i> Confirm Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closePaymentModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Customer Info --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">CUSTOMER INFORMATION</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>Name:</strong></td>
                                    <td>{{ $selectedCustomer?->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td>{{ $selectedCustomer?->phone }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td>{{ ucfirst($selectedCustomer?->type ?? 'N/A') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">PAYMENT SUMMARY</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="50%"><strong>Total Due:</strong></td>
                                    <td class="text-end">Rs.{{ number_format($totalDueAmount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Amount Paid:</strong></td>
                                    <td class="text-end text-success fw-bold">Rs.{{ number_format((float)$totalPaymentAmount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Remaining Due:</strong></td>
                                    <td class="text-end text-danger">Rs.{{ number_format($remainingAmount, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- General Payment Info --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase">Payment Date</label>
                            <input type="date" wire:model="paymentDate" class="form-control border-0 shadow-sm rounded-3">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold small text-uppercase">Notes / Reference</label>
                            <input type="text" wire:model="paymentNotes" class="form-control border-0 shadow-sm rounded-3" placeholder="Optional notes for this receipt">
                        </div>
                    </div>

                    {{-- Payment Details Table --}}
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-wallet2 me-2"></i> Payment Methods</h6>
                            <button type="button" class="btn btn-light btn-sm rounded-pill px-3 fw-bold" wire:click="addPaymentRow">
                                <i class="bi bi-plus-circle me-1"></i> Add Method
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table">
                                <table class="table table-borderless align-middle mb-0">
                                    <thead class="bg-white text-muted small text-uppercase fw-bold">
                                        <tr>
                                            <th style="width: 20%;">Method</th>
                                            <th style="width: 15%;">Amount</th>
                                            <th>Details</th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($paymentRows as $index => $row)
                                        <tr class="border-bottom border-light">
                                            <td>
                                                <select wire:model.live="paymentRows.{{ $index }}.method" class="form-select border-0 shadow-sm rounded-3">
                                                    <option value="cash">Cash</option>
                                                    <option value="cheque">Cheque</option>
                                                    <option value="bank_transfer">Bank Transfer</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" wire:model.live="paymentRows.{{ $index }}.amount" class="form-control border-0 shadow-sm rounded-3 text-end fw-bold" placeholder="0.00">
                                            </td>
                                            <td>
                                                @if($row['method'] === 'cheque')
                                                    <div class="row g-2">
                                                        <div class="col-md-4">
                                                            <input type="text" wire:model.defer="paymentRows.{{ $index }}.cheque_number" class="form-control form-control-sm border-0 shadow-sm" placeholder="Cheque No">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <select wire:model.defer="paymentRows.{{ $index }}.bank_name" class="form-select form-select-sm border-0 shadow-sm">
                                                                <option value="">Select Bank</option>
                                                                <option value="Bank of Ceylon">Bank of Ceylon</option>
                                                                <option value="People's Bank">People's Bank</option>
                                                                <option value="Commercial Bank of Ceylon">Commercial Bank of Ceylon</option>
                                                                <option value="Hatton National Bank">Hatton National Bank</option>
                                                                <option value="Sampath Bank">Sampath Bank</option>
                                                                <option value="Seylan Bank">Seylan Bank</option>
                                                                <option value="DFCC Bank">DFCC Bank</option>
                                                                <option value="Nations Trust Bank">Nations Trust Bank</option>
                                                                <option value="National Development Bank">National Development Bank</option>
                                                                <option value="Pan Asia Banking Corporation">Pan Asia Banking Corporation</option>
                                                                <option value="Union Bank of Colombo">Union Bank of Colombo</option>
                                                                <option value="Cargills Bank">Cargills Bank</option>
                                                                <option value="Amana Bank">Amana Bank</option>
                                                                <option value="HSBC Sri Lanka">HSBC Sri Lanka</option>
                                                                <option value="Standard Chartered Bank">Standard Chartered Bank</option>
                                                                <option value="Citibank">Citibank</option>
                                                                <option value="Bank of China">Bank of China</option>
                                                                <option value="State Bank of India">State Bank of India</option>
                                                                <option value="Indian Bank">Indian Bank</option>
                                                                <option value="Indian Overseas Bank">Indian Overseas Bank</option>
                                                                <option value="Habib Bank">Habib Bank</option>
                                                                <option value="MCB Bank">MCB Bank</option>
                                                                <option value="Public Bank Berhad">Public Bank Berhad</option>
                                                                <option value="Deutsche Bank">Deutsche Bank</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <input type="date" wire:model.live="paymentRows.{{ $index }}.cheque_date" class="form-control form-control-sm border-0 shadow-sm @error('paymentRows.'.$index.'.cheque_date') is-invalid @enderror">
                                                            @error('paymentRows.'.$index.'.cheque_date')
                                                                <div class="invalid-feedback small">{{ $message }}</div>
                                                            @enderror
                                                            @if(!empty($row['cheque_date']) && \App\Models\Holiday::isHoliday($row['cheque_date']))
                                                                <div class="text-danger small mt-1">
                                                                    <i class="bi bi-shield-x me-1"></i>
                                                                    <strong>Holiday / Poya:</strong> {{ \App\Models\Holiday::getHolidayReason($row['cheque_date']) }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="col-md-12 mt-2">
                                                            <label class="form-label small fw-bold mb-1">Cheque Photo</label>
                                                            <input type="file" id="chequePhotoInput_{{ $index }}" class="form-control form-control-sm border-0 shadow-sm" accept="image/*" @change="isUploadingPhoto = true; compressAndUploadReceipt($event.target, {{ $index }})">
                                                            <div wire:loading wire:target="paymentRows.{{ $index }}.cheque_photo" class="mt-1">
                                                                <span class="spinner-border spinner-border-sm text-primary"></span>
                                                                <small class="text-primary">Uploading preview...</small>
                                                            </div>
                                                            @if(isset($paymentRows[$index]['cheque_photo']) && $paymentRows[$index]['cheque_photo'])
                                                                <div class="mt-2">
                                                                    <img src="{{ $paymentRows[$index]['cheque_photo']->temporaryUrl() }}" class="img-thumbnail" style="max-height: 100px;">
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @elseif($row['method'] === 'bank_transfer')
                                                    <div class="row g-2">
                                                        <div class="col-md-6">
                                                            <select wire:model.defer="paymentRows.{{ $index }}.bank_name" class="form-select form-select-sm border-0 shadow-sm">
                                                                <option value="">Select Bank</option>
                                                                <option value="Bank of Ceylon">Bank of Ceylon</option>
                                                                <option value="People's Bank">People's Bank</option>
                                                                <option value="Commercial Bank of Ceylon">Commercial Bank of Ceylon</option>
                                                                <option value="Hatton National Bank">Hatton National Bank</option>
                                                                <option value="Sampath Bank">Sampath Bank</option>
                                                                <option value="Seylan Bank">Seylan Bank</option>
                                                                <option value="DFCC Bank">DFCC Bank</option>
                                                                <option value="Nations Trust Bank">Nations Trust Bank</option>
                                                                <option value="National Development Bank">National Development Bank</option>
                                                                <option value="Pan Asia Banking Corporation">Pan Asia Banking Corporation</option>
                                                                <option value="Union Bank of Colombo">Union Bank of Colombo</option>
                                                                <option value="Cargills Bank">Cargills Bank</option>
                                                                <option value="Amana Bank">Amana Bank</option>
                                                                <option value="HSBC Sri Lanka">HSBC Sri Lanka</option>
                                                                <option value="Standard Chartered Bank">Standard Chartered Bank</option>
                                                                <option value="Citibank">Citibank</option>
                                                                <option value="Bank of China">Bank of China</option>
                                                                <option value="State Bank of India">State Bank of India</option>
                                                                <option value="Indian Bank">Indian Bank</option>
                                                                <option value="Indian Overseas Bank">Indian Overseas Bank</option>
                                                                <option value="Habib Bank">Habib Bank</option>
                                                                <option value="MCB Bank">MCB Bank</option>
                                                                <option value="Public Bank Berhad">Public Bank Berhad</option>
                                                                <option value="Deutsche Bank">Deutsche Bank</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="text" wire:model.defer="paymentRows.{{ $index }}.transfer_reference" class="form-control form-control-sm border-0 shadow-sm" placeholder="Ref No">
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted small px-2">Standard cash payment</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if(count($paymentRows) > 1)
                                                <button type="button" wire:click="removePaymentRow({{ $index }})" class="btn btn-link text-danger p-0 shadow-none">
                                                    <i class="bi bi-trash fs-5"></i>
                                                </button>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    @php
                        $enteredTotal = collect($paymentRows)->sum(function($row) {
                            return is_numeric($row['amount']) ? (float) $row['amount'] : 0;
                        });
                        $diff = (float)$totalPaymentAmount - $enteredTotal;
                    @endphp

                    @if(abs($diff) > 0.01)
                    <div class="alert alert-{{ $diff > 0 ? 'warning' : 'danger' }} py-2 mb-4 d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        @if($diff > 0)
                            Remaining to specify: <strong class="ms-1">Rs.{{ number_format($diff, 2) }}</strong>
                        @else
                            Excess amount specified: <strong class="ms-1">Rs.{{ number_format(abs($diff), 2) }}</strong>
                        @endif
                    </div>
                    @endif

                    {{-- Allocation Breakdown --}}
                    <h6 class="text-muted mt-4 mb-3">PAYMENT ALLOCATION BREAKDOWN</h6>
                    <div class="table">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th class="text-end">Due Amount</th>
                                    <th class="text-end">Payment Amount</th>
                                    <th class="text-end">Remaining</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allocations as $allocation)
                                @if($allocation['payment_amount'] > 0)
                                <tr>
                                    <td class="fw-bold">{{ $allocation['invoice_number'] }}</td>
                                    <td class="text-end">Rs.{{ number_format($allocation['due_amount'], 2) }}</td>
                                    <td class="text-end text-success fw-bold">Rs.{{ number_format($allocation['payment_amount'], 2) }}</td>
                                    <td class="text-end text-danger">Rs.{{ number_format($allocation['due_amount'] - $allocation['payment_amount'], 2) }}</td>
                                    <td>
                                        @php
                                        $remaining = $allocation['due_amount'] - $allocation['payment_amount'];
                                        @endphp
                                        @if($remaining == 0)
                                        <span class="badge bg-success">Complete</span>
                                        @elseif($allocation['payment_amount'] > 0 && $remaining > 0)
                                        <span class="badge bg-warning text-dark">Partial Paid</span>
                                        @else
                                        <span class="badge bg-danger">Payment Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closePaymentModal">Cancel</button>
                    <button
                        type="button"
                        class="btn btn-success"
                        wire:click="processPayment"
                        x-bind:disabled="isUploadingPhoto"
                        wire:loading.attr="disabled"
                        wire:loading.class="disabled"
                        wire:target="processPayment">
                        <span wire:loading.remove wire:target="processPayment" x-show="!isUploadingPhoto">
                            Confirm & Save Payment
                        </span>
                        <span wire:loading wire:target="processPayment">
                            <span class="spinner-border spinner-border-sm me-1"></span> Processing...
                        </span>
                        <span x-show="isUploadingPhoto" style="display: none;">
                            <span class="spinner-border spinner-border-sm me-1"></span> Uploading Photo...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Receipt Modal --}}
    @if($showReceiptModal && $latestPayment)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-check-circle me-2"></i> Payment Successful!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeReceiptModal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-check-circle-fill text-success display-1"></i>
                        <h3 class="text-success mt-3">Payment Processed Successfully!</h3>
                        <p class="text-muted">Payment receipt has been generated and saved.</p>
                    </div>

                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-receipt me-2"></i> Payment Receipt
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Receipt ID:</strong></td>
                                            <td>#{{ $latestPayment->payment_reference ?: $latestPayment->id }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Payment Date:</strong></td>
                                            <td>{{ \Carbon\Carbon::parse($latestPayment->payment_date)->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Customer:</strong></td>
                                            <td>{{ $selectedCustomer->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Phone:</strong></td>
                                            <td>{{ $selectedCustomer->phone }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Payment Method:</strong></td>
                                            <td>
                                                @php
                                                    $methods = collect($paymentRows)->pluck('method')->unique();
                                                @endphp
                                                @if($methods->count() > 1)
                                                    <span class="badge bg-info">Multiple Methods</span>
                                                @else
                                                    <span class="text-capitalize">{{ str_replace('_', ' ', $methods->first()) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Amount Paid:</strong></td>
                                            <td class="fw-bold text-success">Rs.{{ number_format($totalPaymentAmount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Reference No:</strong></td>
                                            <td>{{ $latestPayment->payment_reference ?: 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Received By:</strong></td>
                                            <td>{{ Auth::user()->name }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @if($latestPayment->notes)
                            <div class="mt-3">
                                <strong>Notes:</strong>
                                <p class="mb-0">{{ $latestPayment->notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeReceiptModal">
                        <i class="bi bi-x-circle me-1"></i> Close
                    </button>
                    <button type="button" class="btn btn-success" wire:click="downloadReceipt">
                        <i class="bi bi-download me-1"></i> Download Receipt
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="closeReceiptModal">
                        <i class="bi bi-check-circle me-1"></i> Done
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
    .sticky-top {
        position: sticky;
        z-index: 10;
    }

    .table th {
        font-weight: 600;
    }

    .badge {
        font-size: 0.75em;
    }

    .modal.show {
        display: block !important;
    }

    .btn-group-sm>.btn {
        padding: 0.25rem 0.5rem;
    }

    .input-group-lg .form-control {
        font-size: 1.25rem;
        font-weight: 600;
    }

    /* Invoice selection styles */
    .table tbody tr[style*="cursor: pointer"]:hover {
        background-color: rgba(13, 110, 253, 0.1);
    }

    .table tbody tr.table-success {
        background-color: rgba(25, 135, 84, 0.15) !important;
    }

    .table tbody tr.table-success:hover {
        background-color: rgba(25, 135, 84, 0.25) !important;
    }

    .form-check-input {
        width: 1.2em;
        height: 1.2em;
        cursor: pointer;
    }
</style>
@endpush

@push('scripts')
<script>
    // Listen for toast notifications
    window.addEventListener('show-toast', event => {
        const data = event.detail[0] || event.detail;
        const type = data.type || 'info';
        const message = data.message || 'Notification';

        // Create toast element
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

        // Add toast to container
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        container.insertAdjacentHTML('beforeend', toastHtml);

        // Show toast
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 5000
        });
        toast.show();

        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    });

    // Listen for payment completed event and refresh page
    window.addEventListener('payment-completed', event => {
        console.log('Payment completed, refreshing page...');
        // Small delay to allow modal to close smoothly
        setTimeout(() => {
            window.location.reload();
        }, 500);
    });

    // Log when payment modal is opened
    Livewire.on('paymentModalOpened', () => {
        console.log('Payment modal opened');
    });

    // Client-side image compression before Livewire upload
    function compressAndUploadReceipt(input, rowIndex) {
        const file = input.files[0];
        if (!file) return;

        const propertyName = 'paymentRows.' + rowIndex + '.cheque_photo';
        const isJpeg = file.type === 'image/jpeg' || file.type === 'image/jpg';
        const needsCompression = file.size > 900 * 1024;

        const successCb = function() {
            window.dispatchEvent(new CustomEvent('photo-uploaded'));
        };
        const errorCb = function() {
            window.dispatchEvent(new CustomEvent('photo-uploaded'));
            alert('Failed to upload photo. Please try again.');
        };

        if (isJpeg && needsCompression) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;

                    const maxDimension = 1920;
                    if (width > maxDimension || height > maxDimension) {
                        if (width > height) {
                            height = Math.round((height * maxDimension) / width);
                            width = maxDimension;
                        } else {
                            width = Math.round((width * maxDimension) / height);
                            height = maxDimension;
                        }
                    }

                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob(function(blob) {
                        if (blob) {
                            const compressedFile = new File([blob], file.name, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            @this.upload(propertyName, compressedFile, successCb, errorCb, function() {});
                        }
                    }, 'image/jpeg', 0.7);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            @this.upload(propertyName, file, successCb, errorCb, function() {});
        }
    }
</script>
@endpush
