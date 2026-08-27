<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-receipt text-success me-2"></i> Customer Payment List
            </h3>
            <p class="text-muted mb-0">View all customer receipts and payment allocations</p>
        </div>
    </div>

    {{-- Customer List Table --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-light flex-wrap gap-2">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-people me-2"></i> Customers with Payments
            </h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary">{{ $customers->total() }} customers</span>
                <label class="text-sm text-muted fw-medium">Show</label>
                <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 80px;">
                    <option value="30">30</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="500">500</option>
                    <option value="all">All</option>
                </select>
                <button wire:click="exportCSV" class="btn btn-sm btn-success d-flex align-items-center gap-1 shadow-sm ms-2">
                    <i class="bi bi-download"></i> Export CSV
                </button>
            </div>
        </div>
        <div class="card-body p-0 overflow-auto">
            {{-- Date Filters --}}
            <div class="p-3 bg-light border-bottom">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small">From Date</label>
                        <input type="date" class="form-control" wire:model.live="fromDateFilter">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small">To Date</label>
                        <input type="date" class="form-control" wire:model.live="toDateFilter">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100" wire:click="clearDateFilters" title="Reset Dates">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Customer Name</th>
                            <th class="text-center">Total Paid</th>
                            <th class="text-center">No. of Receipts</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}" style="cursor:pointer" wire:click="showCustomerPayments({{ $customer->id }})">
                            <td class="ps-4 fw-semibold">{{ $customer->name }}</td>
                            <td class="text-center">Rs.{{ number_format($customer->total_paid, 2) }}</td>
                            <td class="text-center">{{ $customer->receipts_count }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-x-circle display-4 d-block mb-2"></i>
                                No customer payments found.
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

    {{-- Payment Details Modal --}}
    @if($showPaymentModal && $selectedCustomer)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-gradient-success text-white">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">
                            <i class="bi bi-receipt-cutoff me-2"></i> Payment History
                        </h5>
                        <small class="opacity-75">{{ $selectedCustomer->name }}</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" wire:click="closePaymentModal"></button>
                </div>
                <div class="modal-body p-0">
                    {{-- Customer Info Card --}}
                    <div class="bg-light border-bottom p-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle text-success me-2" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <small class="text-muted d-block">Customer Name</small>
                                        <strong>{{ $selectedCustomer->name }}</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-telephone text-primary me-2" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <small class="text-muted d-block">Mobile</small>
                                        <strong>{{ $selectedCustomer->mobile }}</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-envelope text-info me-2" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <small class="text-muted d-block">Email</small>
                                        <strong>{{ $selectedCustomer->email }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="card bg-white border-0 shadow-sm">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted">Total Payments Made</span>
                                            <span class="badge bg-success rounded-pill">{{ count($groupedPayments) }} receipts</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-success text-white border-0 shadow-sm">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Total Amount Paid</span>
                                            @php
                                                $returnedAmount = \App\Models\Cheque::where('customer_id', $selectedCustomer->id)->whereIn('status', ['return', 'cancelled'])->sum('cheque_amount');
                                                $actualPaid = $payments->sum('amount') - $returnedAmount;
                                            @endphp
                                            <strong class="fs-5">Rs.{{ number_format($actualPaid, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment List --}}
                    <div class="p-3">
                        @forelse($groupedPayments as $ref => $group)
                        @php
                            $firstPayment = $group->first();
                            $totalAmount = $group->sum('amount');
                            $allAllocations = $group->pluck('allocations')->flatten();
                        @endphp
                        <div class="card mb-3 shadow-sm border-start border-4 border-success">
                            <div class="card-header bg-white">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                <strong>#{{ $loop->iteration }}</strong>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold">Receipt {{ Str::startsWith($ref, 'single_') ? '#' . $firstPayment->id : $ref }}</h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar3 me-1"></i>
                                                    {{ $firstPayment->payment_date ? date('M d, Y', strtotime($firstPayment->payment_date)) : '-' }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="fs-4 fw-bold text-success">Rs.{{ number_format($totalAmount, 2) }}</div>
                                        @foreach($group->pluck('payment_method')->unique() as $pmethod)
                                        <span class="badge bg-secondary mb-1">
                                            <i class="bi bi-{{ $pmethod === 'cash' ? 'cash' : ($pmethod === 'cheque' ? 'receipt' : 'bank') }} me-1"></i>
                                            {{ ucfirst(str_replace('_', ' ', $pmethod)) }}
                                        </span>
                                        @endforeach
                                        <button wire:click="downloadReceipt('{{ $ref }}')" class="btn btn-sm btn-outline-success ms-2" title="Download Receipt">
                                            <i class="bi bi-download"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                {{-- Payment Method Details --}}
                                {{-- Payment Method Details --}}
                                @foreach($group as $payment)
                                    @if($payment->payment_method === 'cheque')
                                    <div class="alert alert-info mb-3">
                                        @if($payment->cheques && count($payment->cheques) > 0)
                                        @foreach($payment->cheques as $cheque)
                                        <div class="row {{ !$loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
                                            <div class="col-md-3">
                                                <small class="text-muted d-block">Cheque Number</small>
                                                <strong>{{ $cheque->cheque_number }}</strong>
                                            </div>
                                            <div class="col-md-3">
                                                <small class="text-muted d-block">Bank Name</small>
                                                <strong>{{ $cheque->bank_name }}</strong>
                                            </div>
                                            <div class="col-md-3">
                                                <small class="text-muted d-block">Cheque Date</small>
                                                <strong>{{ $cheque->cheque_date ? date('M d, Y', strtotime($cheque->cheque_date)) : '-' }}</strong>
                                            </div>
                                            <div class="col-md-3">
                                                <small class="text-muted d-block">Status</small>
                                                @if($cheque->status == 'return')
                                                    <span class="badge bg-danger">Returned</span>
                                                @else
                                                    <span class="badge bg-success">{{ ucfirst($cheque->status) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                        @else
                                        <div class="text-center text-muted">
                                            <i class="bi bi-exclamation-circle me-1"></i> Cheque details not available
                                        </div>
                                        @endif
                                    </div>
                                    @elseif($payment->payment_method === 'bank_transfer')
                                    <div class="alert alert-info mb-3">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted d-block">Bank Name</small>
                                                <strong>{{ $payment->bank_name }}</strong>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted d-block">Transaction Reference</small>
                                                <strong>{{ $payment->reference_number }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                @endforeach

                                {{-- Allocated Invoices --}}
                                <div class="mb-2">
                                    <strong class="text-muted d-block mb-2">
                                        <i class="bi bi-file-earmark-text me-1"></i> Allocated to Invoices:
                                    </strong>
                                    @if($allAllocations && count($allAllocations) > 0)
                                    <div class="table">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Invoice ID</th>
                                                    <th>Invoice Number</th>

                                                    <th class="text-end">Allocated Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($allAllocations as $alloc)
                                                <tr>
                                                    <td><span class="badge bg-dark">#{{ $alloc->sale_id }}</span></td>
                                                    <td>{{ $alloc->sale ? $alloc->sale->invoice_number : 'N/A' }}</td>

                                                    <td class="text-end fw-bold text-success">Rs.{{ number_format($alloc->allocated_amount, 2) }}</td>
                                                </tr>
                                                @endforeach
                                                <tr class="table-active">
                                                    <td colspan="2" class="text-end"><strong>Total Allocated:</strong></td>
                                                    <td class="text-end fw-bold text-primary">Rs.{{ number_format($allAllocations->sum('allocated_amount'), 2) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    @else
                                    <div class="alert alert-warning mb-0">
                                        <i class="bi bi-exclamation-triangle me-1"></i> No invoice allocation found for this payment
                                    </div>
                                    @endif
                                </div>

                                {{-- Notes --}}
                                @php $allNotes = $group->pluck('notes')->filter()->join(', '); @endphp
                                @if($allNotes)
                                <div class="mt-3">
                                    <small class="text-muted d-block">Notes:</small>
                                    <div class="alert alert-light mb-0">{{ $allNotes }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">No payments found for this customer.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" wire:click="closePaymentModal">
                        <i class="bi bi-x-circle me-1"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

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

        .table-responsive {
            height: 50vh;
            overflow-y: auto;
        }
    </style>
</div>