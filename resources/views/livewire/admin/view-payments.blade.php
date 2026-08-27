<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-credit-card text-success me-2"></i> Payment Records
            </h3>
            <p class="text-muted mb-0">View and manage all payment records</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-5">
        <!-- Total Payments Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card summary-card total h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-container bg-success bg-opacity-10 me-3">
                            <i class="bi bi-wallet text-success fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Total Received (Net)</p>
                            <h4 class="fw-bold mb-0">Rs.{{ number_format($totalPayments, 2) }}</h4>
                            <span class="badge bg-success bg-opacity-10 text-success">Net Received</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payments Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card summary-card pending h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-container bg-warning bg-opacity-10 me-3">
                            <i class="bi bi-hourglass-split text-warning fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Pending Payments</p>
                            <h4 class="fw-bold mb-0">Rs.{{ number_format($pendingPayments, 2) }}</h4>
                            <span class="badge bg-warning bg-opacity-10 text-warning">Awaiting</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remaining Cheque Balance Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card summary-card completed h-100" style="border-left-color: #0d6efd;">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-container bg-primary bg-opacity-10 me-3">
                            <i class="bi bi-wallet2 text-primary fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Remaining Balance</p>
                            <h4 class="fw-bold mb-0 text-primary">Rs.{{ number_format($remainingChequeBalance ?? 0, 2) }}</h4>
                            <span class="badge bg-primary bg-opacity-10 text-primary">To be recovered</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Returned Cheques Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card summary-card approved h-100" style="border-left-color: #dc3545;">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-container bg-danger bg-opacity-10 me-3">
                            <i class="bi bi-exclamation-triangle text-danger fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Returned Cheques</p>
                            <h4 class="fw-bold mb-0" style="color: #dc3545;">Rs.{{ number_format($returnedChequesTotal, 2) }}</h4>
                            <span class="badge bg-danger bg-opacity-10 text-danger">Cheque Returned</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
    </div>

    {{-- Payment Records Table --}}
    <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold text-dark mb-1">
                    <i class="bi bi-journal-text text-primary me-2"></i> Payment Records
                </h5>
                <p class="text-muted small mb-0">View and manage all payment transactions</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="text-sm text-muted fw-medium">Show</label>
                <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 80px;">
                    <option value="30">30</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="500">500</option>
                </select>
                <span class="text-sm text-muted">entries</span>
                <button wire:click="exportCSV" class="btn btn-success btn-sm d-flex align-items-center gap-1 shadow-sm ms-2">
                    <i class="bi bi-download"></i> Export CSV
                </button>
            </div>
        </div>
        <div class="card-body">
            {{-- Search and Filters --}}
            <div class="card bg-light border-0 mb-4" style="background-color: rgba(0, 0, 0, 0.03) !important;">
                <div class="card-body py-3">
                    <div class="row g-3 align-items-end">
                        <!-- Search Bar -->
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-bold text-dark small mb-1">Search</label>
                            <div class="input-group shadow-sm rounded">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 bg-white"
                                    placeholder="Search by invoice, customer name..."
                                    wire:model.live.debounce.300ms="search">
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-12 col-md-2">
                            <label class="form-label fw-bold text-dark small mb-1">Payment Status</label>
                            <select class="form-select bg-white shadow-sm" wire:model.live="filters.status">
                                <option value="">All Status</option>
                                <option value="pending">Pending Approval</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>

                        <!-- Payment Method Filter -->
                        <div class="col-12 col-md-2">
                            <label class="form-label fw-bold text-dark small mb-1">Payment Method</label>
                            <select class="form-select bg-white shadow-sm" wire:model.live="filters.paymentMethod">
                                <option value="">All Methods</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>

                        <!-- From Date Filter -->
                        <div class="col-12 col-md-2">
                            <label class="form-label fw-bold text-dark small mb-1">From Date</label>
                            <input type="date" class="form-control bg-white shadow-sm" wire:model.live="filters.startDate">
                        </div>

                        <!-- To Date Filter -->
                        <div class="col-12 col-md-2">
                            <label class="form-label fw-bold text-dark small mb-1">To Date</label>
                            <input type="date" class="form-control bg-white shadow-sm" wire:model.live="filters.endDate">
                        </div>

                        <!-- Reset Buttons -->
                        <div class="col-12 col-md-1">
                            <button class="btn btn-warning w-100 shadow-sm d-flex align-items-center justify-content-center" 
                                    wire:click="resetFilters" 
                                    style="background: linear-gradient(135deg, #16285A 0%, #E65F1E 100%); border: none; color: white; height: 38px; font-weight: 500;"
                                    title="Reset Filters">
                                <i class="bi bi-arrow-repeat me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payments Table --}}
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Invoice</th>
                            <th>Customer</th>
                            <th class="text-center">Amount</th>
                            <th class="text-center">Method</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Date</th>
                            <th class="text-center">Staff</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                        <tr>
                            <td class="ps-4">
                                <div>
                                    @if($payment->sale)
                                        <span class="fw-medium text-dark">{{ $payment->sale->invoice_number }}</span>
                                    @elseif($payment->allocations->count() > 0)
                                        <span class="fw-medium text-muted" title="{{ $payment->allocations->map(fn($a) => $a->sale?->invoice_number)->filter()->implode(', ') }}">
                                            {{ $payment->allocations->count() }} Invoices
                                        </span>
                                    @else
                                        <span class="fw-medium text-muted">General Payment</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="icon-container bg-primary bg-opacity-10 me-3">
                                        <i class="bi bi-person text-primary"></i>
                                    </div>
                                    @php
                                        $customer = $payment->customer ?? $payment->sale?->customer ?? ($payment->allocations->first()?->sale?->customer);
                                    @endphp
                                    <div>
                                        @if($customer)
                                            <span class="fw-medium text-dark">{{ $customer->name }}</span>
                                            @if($customer->phone)
                                                <div class="text-muted small">{{ $customer->phone }}</div>
                                            @endif
                                        @else
                                            <span class="fw-medium text-dark">Walk-in Customer</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-dark">Rs.{{ number_format($payment->amount, 2) }}</span>
                                <div class="text-muted small">
                                    {{ $payment->is_completed ? 'Completed' : 'Scheduled' }}
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark">{{ ucfirst($payment->payment_method) }}</span>
                            </td>
                            <td class="text-center">
                                @php
                                    $cheque = $payment->cheques->first();
                                @endphp
                                @if($cheque && $cheque->status === 'return')
                                    <span class="badge bg-danger">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Cheque Returned
                                    </span>
                                @elseif($cheque && $cheque->status === 'cancelled')
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-x-circle-fill me-1"></i> Cheque Cancelled
                                    </span>
                                @else
                                    <span class="badge bg-{{ 
                                            $payment->status === 'pending' ? 'warning' : 
                                            ($payment->status === 'approved' ? 'success' : 
                                            ($payment->status === 'rejected' ? 'danger' : 
                                            ($payment->is_completed ? 'success' : 'secondary'))) }}">
                                        {{ $payment->status ? ucfirst($payment->status) : ($payment->is_completed ? 'Paid' : 'Scheduled') }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div>
                                    <span class="fw-medium text-dark">
                                        {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : 
                                               ($payment->due_date ? 'Due: '.$payment->due_date->format('d/m/Y') : 'N/A') }}
                                    </span>
                                    @if($payment->payment_date)
                                    <div class="text-muted small">{{ $payment->payment_date->format('h:i A') }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fw-medium text-dark">{{ $payment->sale?->user?->name ?? 'N/A' }}</span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-link text-primary p-0"
                                    wire:click="viewPaymentDetails({{ $payment->id }})"
                                    wire:loading.attr="disabled"
                                    title="View Receipt">
                                    <i class="bi bi-eye fs-6"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-credit-card display-4 d-block mb-2"></i>
                                No payment records found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($payments->hasPages())
            <div class="card-footer bg-white border-0 pt-3">
                <div class="d-flex justify-content-center">
                    {{ $payments->links('livewire.custom-pagination') }}
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Payment Receipt Modal -->
    <div wire:ignore.self class="modal fade" id="payment-receipt-modal" tabindex="-1" aria-labelledby="payment-receipt-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-receipt text-primary me-2"></i> Payment Receipt
                    </h5>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="printReceiptContent()">
                            <i class="bi bi-printer me-1"></i> Print
                        </button>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-4" id="receiptContent">
                    @if ($selectedPayment)
                    <div class="receipt-container">
                        <!-- Receipt Header -->
                        <div class="text-center mb-4">
                            <h3 class="mb-0">{{ config('shop.name') }}</h3>
                            <p class="mb-0 text-muted small">{{ config('shop.address', 'N 122/1H, Kandy Road, Thihariya, Sri Lanka.') }}</p>
                            <p class="mb-0 text-muted small">Phone: {{ config('shop.phone', '+0332 290 295') }} | Email: {{ config('shop.email', 'thihariyatilecenter@gmail.com') }}</p>
                            <h4 class="mt-3 border-bottom border-2 pb-2">PAYMENT RECEIPT</h4>
                        </div>

                        <!-- Payment Details -->
                        <div class="row g-4 mb-4">
                            <div class="col-12 col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="fw-bold text-dark mb-0">
                                            <i class="bi bi-credit-card text-primary me-2"></i> Payment Information
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <label class="form-label fw-semibold text-muted small">Payment ID</label>
                                                <p class="fw-bold text-dark mb-0">#{{ $selectedPayment->id }}</p>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold text-muted small">Amount</label>
                                                <p class="fw-bold text-primary h5 mb-0">Rs.{{ number_format($selectedPayment->amount, 2) }}</p>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold text-muted small">Method</label>
                                                <p class="fw-bold text-dark mb-0">{{ ucfirst($selectedPayment->payment_method) }}</p>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold text-muted small">Date</label>
                                                <p class="fw-bold text-dark mb-0">
                                                    {{ $selectedPayment->payment_date ? 
                                                           $selectedPayment->payment_date->format('d/m/Y h:i A') : 
                                                           'N/A' }}
                                                </p>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold text-muted small">Status</label>
                                                <div>
                                                    @php
                                                        $modalCheque = $selectedPayment->cheques->first();
                                                     @endphp
                                                     @if($modalCheque && $modalCheque->status === 'return')
                                                         <span class="badge bg-danger">
                                                             <i class="bi bi-exclamation-triangle-fill me-1"></i> Cheque Returned
                                                         </span>
                                                     @elseif($modalCheque && $modalCheque->status === 'cancelled')
                                                         <span class="badge bg-secondary">
                                                             <i class="bi bi-x-circle-fill me-1"></i> Cheque Cancelled
                                                         </span>
                                                     @else
                                                        <span class="badge bg-{{ 
                                                                $selectedPayment->status === 'pending' ? 'warning' : 
                                                                ($selectedPayment->status === 'approved' ? 'success' : 
                                                                ($selectedPayment->status === 'rejected' ? 'danger' : 
                                                                ($selectedPayment->is_completed ? 'success' : 'secondary'))) }}">
                                                            {{ $selectedPayment->status ? ucfirst($selectedPayment->status) : 
                                                                   ($selectedPayment->is_completed ? 'Paid' : 'Scheduled') }}
                                                        </span>
                                                     @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="card h-100">
                                    @if($selectedPayment->sale)
                                    <div class="card-header">
                                        <h6 class="fw-bold text-dark mb-0">
                                            <i class="bi bi-file-text text-primary me-2"></i> Invoice Information
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <label class="form-label fw-semibold text-muted small">Invoice</label>
                                                <p class="fw-bold text-dark mb-0">{{ $selectedPayment->sale->invoice_number }}</p>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold text-muted small">Sale Date</label>
                                                <p class="fw-bold text-dark mb-0">{{ $selectedPayment->sale->created_at->format('d/m/Y h:i A') }}</p>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold text-muted small">Subtotal</label>
                                                <p class="fw-bold text-dark mb-0">Rs.{{ number_format($selectedPayment->sale->subtotal, 2) }}</p>
                                            </div>
                                            @if($selectedPayment->sale->discount_amount > 0)
                                            <div class="col-12">
                                                <label class="form-label fw-semibold text-muted small">Discount</label>
                                                <p class="fw-bold text-danger mb-0">Rs.{{ number_format($selectedPayment->sale->discount_amount, 2) }}</p>
                                            </div>
                                            @endif
                                            <div class="col-12">
                                                <label class="form-label fw-semibold text-muted small">Total</label>
                                                <p class="fw-bold text-success h5 mb-0">Rs.{{ number_format($selectedPayment->sale->total_amount, 2) }}</p>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold text-muted small">Due Amount</label>
                                                <p class="fw-bold text-dark mb-0">Rs.{{ number_format($selectedPayment->sale->due_amount, 2) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    @elseif($selectedPayment->allocations->count() > 0)
                                    <div class="card-header">
                                        <h6 class="fw-bold text-dark mb-0">
                                            <i class="bi bi-file-text text-primary me-2"></i> Allocated Invoices
                                        </h6>
                                    </div>
                                    <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                                        @foreach($selectedPayment->allocations as $alloc)
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                <div>
                                                    <span class="fw-bold text-dark">{{ $alloc->sale?->invoice_number ?? 'N/A' }}</span>
                                                    <div class="text-muted small">Total: Rs.{{ number_format($alloc->sale?->total_amount ?? 0, 2) }} | Due: Rs.{{ number_format($alloc->sale?->due_amount ?? 0, 2) }}</div>
                                                </div>
                                                <span class="badge bg-success bg-opacity-10 text-success fw-bold">Rs.{{ number_format($alloc->allocated_amount, 2) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    @else
                                    <div class="card-header">
                                        <h6 class="fw-bold text-dark mb-0">
                                            <i class="bi bi-file-text text-primary me-2"></i> Payment Allocation
                                        </h6>
                                    </div>
                                    <div class="card-body d-flex align-items-center justify-content-center">
                                        <p class="text-muted mb-0">This payment is not allocated to any specific invoice.</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Customer & Staff Information -->
                         <div class="row g-4 mb-4">
                             <div class="col-12 col-md-6">
                                 <div class="card h-100">
                                     <div class="card-header">
                                         <h6 class="fw-bold text-dark mb-0">
                                             <i class="bi bi-person text-primary me-2"></i> Customer Information
                                         </h6>
                                     </div>
                                     @php
                                         $selectedCustomer = $selectedPayment->customer ?? $selectedPayment->sale?->customer ?? $selectedPayment->allocations->first()?->sale?->customer;
                                     @endphp
                                     <div class="card-body">
                                         <div class="d-flex align-items-center mb-3">
                                             <div class="icon-container bg-primary bg-opacity-10 me-3">
                                                 <i class="bi bi-person text-primary"></i>
                                             </div>
                                             <div>
                                                 <h6 class="mb-0 fw-bold">{{ $selectedCustomer->name ?? 'Walk-in Customer' }}</h6>
                                                 <p class="text-muted small mb-0">
                                                     {{ $selectedCustomer ? ucfirst($selectedCustomer->customer_type ?? 'regular') : 'Walk-in' }} Customer
                                                 </p>
                                             </div>
                                         </div>
                                         @if($selectedCustomer)
                                         <div class="text-muted small">
                                             @if($selectedCustomer->phone)
                                             <div class="mb-1">
                                                 <i class="bi bi-telephone me-2"></i>{{ $selectedCustomer->phone }}
                                             </div>
                                             @endif
                                             @if($selectedCustomer->email)
                                             <div class="mb-1">
                                                 <i class="bi bi-envelope me-2"></i>{{ $selectedCustomer->email }}
                                             </div>
                                             @endif
                                             @if($selectedCustomer->address)
                                             <div class="mb-0">
                                                 <i class="bi bi-geo-alt me-2"></i>{{ $selectedCustomer->address }}
                                             </div>
                                             @endif
                                         </div>
                                         @endif
                                     </div>
                                 </div>
                             </div>

                             <div class="col-12 col-md-6">
                                 <div class="card h-100">
                                     <div class="card-header">
                                         <h6 class="fw-bold text-dark mb-0">
                                             <i class="bi bi-person-badge text-primary me-2"></i> Staff Information
                                         </h6>
                                     </div>
                                     @php
                                         $selectedStaff = $selectedPayment->sale?->user ?? ($selectedPayment->allocations->first()?->sale?->user);
                                     @endphp
                                     <div class="card-body">
                                         <div class="d-flex align-items-center mb-3">
                                             <div class="icon-container bg-info bg-opacity-10 me-3">
                                                 <i class="bi bi-person-badge text-info"></i>
                                             </div>
                                             <div>
                                                 <h6 class="mb-0 fw-bold">{{ $selectedStaff->name ?? 'System' }}</h6>
                                                 <p class="text-muted small mb-0">Staff Member</p>
                                             </div>
                                         </div>
                                         @if($selectedStaff && $selectedStaff->email)
                                         <div class="text-muted small">
                                             <div class="mb-0">
                                                 <i class="bi bi-envelope me-2"></i>{{ $selectedStaff->email }}
                                             </div>
                                         </div>
                                         @endif
                                     </div>
                                 </div>
                             </div>
                         </div>

                        <!-- Payment Attachments -->
                        @if($selectedPayment->payment_method == 'cheque' || $selectedPayment->payment_method == 'bank_transfer')
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="bi bi-paperclip text-primary me-2"></i>
                                    {{ $selectedPayment->payment_method == 'cheque' ? 'Cheque Details' : 'Bank Transfer Details' }}
                                </h6>
                            </div>
                            <div class="card-body">
                                @if($selectedPayment->payment_reference)
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-muted small">Payment Reference</label>
                                    @if(pathinfo($selectedPayment->payment_reference, PATHINFO_EXTENSION) == 'pdf')
                                    <div class="d-flex align-items-center">
                                        <div class="icon-container bg-danger bg-opacity-10 me-3">
                                            <i class="bi bi-file-pdf text-danger"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="fw-bold text-dark mb-1">PDF Document</p>
                                            <div class="d-flex gap-2">
                                                <a href="{{ asset('storage/' . $selectedPayment->payment_reference) }}"
                                                    class="btn btn-sm btn-outline-primary"
                                                    target="_blank">
                                                    <i class="bi bi-eye me-1"></i> View
                                                </a>
                                                <a href="{{ asset('storage/' . $selectedPayment->payment_reference) }}"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    download>
                                                    <i class="bi bi-download me-1"></i> Download
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    @else
                                    <div class="text-center">
                                        <img src="{{ asset('storage/' . $selectedPayment->payment_reference) }}"
                                            class="img-fluid border rounded"
                                            style="max-height: 200px; cursor: pointer;"
                                            alt="Payment reference"
                                            onclick="openFullImage('{{ asset('storage/' . $selectedPayment->payment_reference) }}')">
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/' . $selectedPayment->payment_reference) }}"
                                                class="btn btn-sm btn-outline-primary me-2"
                                                download>
                                                <i class="bi bi-download me-1"></i> Download
                                            </a>
                                            <button class="btn btn-sm btn-outline-info"
                                                onclick="openFullImage('{{ asset('storage/' . $selectedPayment->payment_reference) }}')">
                                                <i class="bi bi-zoom-in me-1"></i> Zoom
                                            </button>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                @if($selectedPayment->payment_method == 'cheque')
                                <div class="mb-2">
                                    <label class="form-label fw-semibold text-muted small">Cheque Number</label>
                                    <p class="fw-bold text-dark mb-0">{{ $selectedPayment->cheque_number ?? $selectedPayment->cheques->first()?->cheque_number ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fw-semibold text-muted small">Cheque Date</label>
                                    <p class="fw-bold text-dark mb-0">
                                        {{ $selectedPayment->cheque_date ? $selectedPayment->cheque_date->format('d/m/Y') : ($selectedPayment->cheques->first()?->cheque_date ? \Carbon\Carbon::parse($selectedPayment->cheques->first()->cheque_date)->format('d/m/Y') : 'N/A') }}
                                    </p>
                                </div>
                                @php
                                    $chq = $selectedPayment->cheques->first();
                                @endphp
                                @if($chq)
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-muted small">Cheque Status</label>
                                    <div>
                                        <span class="badge bg-{{ 
                                            $chq->status === 'cleared' ? 'success' : 
                                            ($chq->status === 'return' ? 'danger' : 
                                            ($chq->status === 'cancelled' ? 'secondary' : 'warning')) }}">
                                            {{ ucfirst($chq->status) }}
                                        </span>
                                    </div>
                                </div>
                                @endif
                                @endif

                                @if($selectedPayment->bank_name)
                                <div class="mb-2">
                                    <label class="form-label fw-semibold text-muted small">Bank Name</label>
                                    <p class="fw-bold text-dark mb-0">{{ $selectedPayment->bank_name }}</p>
                                </div>
                                @endif

                                @if($selectedPayment->card_number)
                                <div class="mb-2">
                                    <label class="form-label fw-semibold text-muted small">Account/Card Number</label>
                                    <p class="fw-bold text-dark mb-0">{{ $selectedPayment->card_number }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                         @if ($selectedPayment->sale)
                         <!-- Sale Items -->
                         <div class="card">
                             <div class="card-header">
                                 <h6 class="fw-bold text-dark mb-0">
                                     <i class="bi bi-list-ul text-primary me-2"></i> Purchased Items
                                 </h6>
                             </div>
                             <div class="card-body p-0">
                                 <div class="table-responsive">
                                     <table class="table table-hover mb-0">
                                         <thead class="table-light">
                                             <tr>
                                                 <th class="ps-4">#</th>
                                                 <th>Item</th>
                                                 <th>Code</th>
                                                 <th class="text-center">Price</th>
                                                 <th class="text-center">Qty</th>
                                                 <th class="text-center">Discount</th>
                                                 <th class="text-end pe-4">Total</th>
                                             </tr>
                                         </thead>
                                         <tbody>
                                             @foreach ($selectedPayment->sale->items as $index => $item)
                                             <tr>
                                                 <td class="ps-4">{{ $index + 1 }}</td>
                                                 <td>{{ $item->product_name }}</td>
                                                 <td>{{ $item->product_code }}</td>
                                                 <td class="text-center">Rs.{{ number_format($item->unit_price, 2) }}</td>
                                                 <td class="text-center">{{ $item->quantity }}</td>
                                                 <td class="text-center">Rs.{{ number_format($item->discount, 2) }}</td>
                                                 <td class="text-end pe-4">Rs.{{ number_format($item->total, 2) }}</td>
                                             </tr>
                                             @endforeach
                                         </tbody>
                                     </table>
                                 </div>
                             </div>
                         </div>

                         <!-- Notes Section -->
                         @if ($selectedPayment->sale->notes)
                         <div class="card mt-4">
                             <div class="card-header">
                                 <h6 class="fw-bold text-dark mb-0">
                                     <i class="bi bi-sticky text-primary me-2"></i> Sale Notes
                                 </h6>
                             </div>
                             <div class="card-body">
                                 <p class="mb-0">{{ $selectedPayment->sale->notes }}</p>
                             </div>
                         </div>
                         @endif
                         @endif

                         @if ($selectedPayment->notes)
                         <div class="card mt-4">
                             <div class="card-header">
                                 <h6 class="fw-bold text-dark mb-0">
                                     <i class="bi bi-sticky text-primary me-2"></i> Payment Notes
                                 </h6>
                             </div>
                             <div class="card-body">
                                 <p class="mb-0">{{ $selectedPayment->notes }}</p>
                             </div>
                         </div>
                         @endif

                         <!-- Footer -->
                         <div class="text-center mt-4 pt-3 border-top">
                             <p class="mb-0 text-muted small">This is a computer-generated receipt.</p>
                             <p class="mb-0 text-muted small">{{ now()->format('d/m/Y h:i A') }}</p>
                         </div>
                    @else
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading payment information...</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal for viewing attachments -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Payment Attachment</h5>
                    <div class="ms-auto">
                        <a id="downloadImageLink" href="#" class="btn btn-sm btn-primary me-2" download>
                            <i class="bi bi-download me-1"></i> Download
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body text-center p-0">
                    <div class="position-relative">
                        <img id="fullSizeImage" src="" class="img-fluid w-100" alt="Payment proof">
                        <div class="position-absolute top-0 end-0 p-3">
                            <button id="zoomInBtn" class="btn btn-sm btn-light rounded-circle me-1">
                                <i class="bi bi-zoom-in"></i>
                            </button>
                            <button id="zoomOutBtn" class="btn btn-sm btn-light rounded-circle">
                                <i class="bi bi-zoom-out"></i>
                            </button>
                        </div>
                    </div>
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

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    }

    .card-header {
        background-color: white;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 12px 12px 0 0 !important;
        padding: 1.25rem 1.5rem;
    }

    .summary-card {
        border-left: 4px solid;
        transition: all 0.3s ease;
    }

    .summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    }

    .summary-card.total {
        border-left-color: #4361ee;
    }

    .summary-card.pending {
        border-left-color: #ffc107;
    }

    .summary-card.approved {
        border-left-color: #198754;
    }

    .summary-card.completed {
        border-left-color: #0dcaf0;
    }

    .icon-container {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .table th {
        border-top: none;
        font-weight: 600;
        color: #6c757d;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table td {
        vertical-align: middle;
        padding: 1rem 0.75rem;
    }

    .btn-link {
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-link:hover {
        transform: scale(1.1);
    }

    .modal-content {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .form-control,
    .form-select {
        border-radius: 8px;
        padding: 0.75rem 1rem;
        border: 1px solid #e2e8f0;
    }

    .form-control:focus,
    .form-select:focus {
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        border-color: #4361ee;
    }

    .btn {
        border-radius: 8px;
        font-weight: 500;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background-color: #4361ee;
        border-color: #4361ee;
    }

    .btn-primary:hover {
        background-color: #3f37c9;
        border-color: #3f37c9;
        transform: translateY(-2px);
    }

    .btn-outline-primary {
        color: #4361ee;
        border-color: #4361ee;
    }

    .btn-outline-primary:hover {
        background-color: #4361ee;
        border-color: #4361ee;
        transform: translateY(-2px);
    }

    .alert {
        border-radius: 8px;
        border: none;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
        border-radius: 6px;
    }

    .input-group-text {
        border-radius: 8px 0 0 8px;
    }

    .dropdown-menu {
        border-radius: 12px;
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    /* Print Styles */
    @media print {
        body * {
            visibility: hidden;
        }

        #receiptContent,
        #receiptContent * {
            visibility: visible;
        }

        #receiptContent {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        .modal-header,
        .modal-footer,
        .btn {
            display: none !important;
        }

        .modal-body {
            padding: 0 !important;
        }

        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Define printReceiptContent in global scope
    window.printReceiptContent = function() {
        const printContent = document.getElementById('receiptContent').cloneNode(true);

        // Create an iframe for printing
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        document.body.appendChild(iframe);

        // Get the iframe's document
        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

        // Write the print content to the iframe
        iframeDoc.open();
        iframeDoc.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Print Payment Receipt</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 0;
                        padding: 20px;
                        font-size: 14px;
                    }
                    .receipt-container {
                        width: 100%;
                        max-width: 800px;
                        margin: 0 auto;
                    }
                    .row {
                        display: flex;
                        flex-wrap: wrap;
                        margin-right: -15px;
                        margin-left: -15px;
                    }
                    .col-md-6 {
                        flex: 0 0 50%;
                        max-width: 50%;
                        padding-right: 15px;
                        padding-left: 15px;
                    }
                    .col-12 {
                        flex: 0 0 100%;
                        max-width: 100%;
                        padding-right: 15px;
                        padding-left: 15px;
                    }
                    .card {
                        border: 1px solid #eee;
                        border-radius: 8px;
                        margin-bottom: 15px;
                    }
                    .card-body {
                        padding: 15px;
                    }
                    .table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 1rem;
                    }
                    .table th, .table td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                    }
                    .table thead th {
                        background-color: #f5f5f5;
                    }
                    .text-center {
                        text-align: center;
                    }
                    .badge {
                        display: inline-block;
                        padding: 0.25em 0.4em;
                        font-weight: bold;
                        line-height: 1;
                        text-align: center;
                        white-space: nowrap;
                        vertical-align: baseline;
                        border-radius: 0.25rem;
                    }
                    .bg-warning { background-color: #ffc107; color: #000; }
                    .bg-success { background-color: #198754; color: #fff; }
                    .bg-danger { background-color: #dc3545; color: #fff; }
                    .bg-secondary { background-color: #6c757d; color: #fff; }
                    .text-primary { color: #4361ee !important; }
                    .text-success { color: #198754 !important; }
                    .text-danger { color: #dc3545 !important; }
                    .fw-bold { font-weight: bold; }
                    .fw-semibold { font-weight: 600; }
                    .text-muted { color: #6c757d; }
                    .border-bottom { border-bottom: 1px solid #dee2e6; }
                    .border-top { border-top: 1px solid #dee2e6; }
                    .mb-0 { margin-bottom: 0; }
                    .mb-1 { margin-bottom: 0.25rem; }
                    .mb-2 { margin-bottom: 0.5rem; }
                    .mb-3 { margin-bottom: 1rem; }
                    .mb-4 { margin-bottom: 1.5rem; }
                    .mt-3 { margin-top: 1rem; }
                    .mt-4 { margin-top: 1.5rem; }
                    .pt-3 { padding-top: 1rem; }
                    .pb-2 { padding-bottom: 0.5rem; }
                    .small { font-size: 0.875rem; }
                    .h5 { font-size: 1.25rem; }
                    .h4 { font-size: 1.5rem; }
                    .h3 { font-size: 1.75rem; }
                </style>
            </head>
            <body>
                ${printContent.innerHTML}
            </body>
            </html>
        `);
        iframeDoc.close();

        // Print the iframe content
        iframe.contentWindow.focus();
        iframe.contentWindow.print();

        // Remove the iframe after printing
        setTimeout(() => {
            document.body.removeChild(iframe);
        }, 1000);
    }

    // Image modal functionality
    function openFullImage(imageSrc) {
        const fullSizeImage = document.getElementById('fullSizeImage');
        const downloadImageLink = document.getElementById('downloadImageLink');
        const zoomInBtn = document.getElementById('zoomInBtn');
        const zoomOutBtn = document.getElementById('zoomOutBtn');

        fullSizeImage.src = imageSrc;
        downloadImageLink.href = imageSrc;

        // Reset zoom
        fullSizeImage.style.transform = 'scale(1)';
        fullSizeImage.style.transformOrigin = 'center center';

        // Zoom functionality
        let currentScale = 1;
        const zoomStep = 0.2;

        zoomInBtn.onclick = function() {
            currentScale += zoomStep;
            fullSizeImage.style.transform = `scale(${currentScale})`;
        };

        zoomOutBtn.onclick = function() {
            if (currentScale > 1) {
                currentScale -= zoomStep;
                fullSizeImage.style.transform = `scale(${currentScale})`;
            }
        };

        // Initialize modal
        const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
        imageModal.show();
    }

    // Livewire event listeners for opening payment modal
    document.addEventListener('livewire:initialized', function() {
        Livewire.on('open-payment-modal', function() {
            const modal = new bootstrap.Modal(document.getElementById('payment-receipt-modal'));
            modal.show();
        });
    });
</script>
@endpush