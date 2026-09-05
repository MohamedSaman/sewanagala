<div>
    {{-- Toast Alert --}}
    <div
        x-data="{ show: false, type: '', message: '' }"
        x-init="
            window.addEventListener('show-toast', e => {
                const data = Array.isArray(e.detail) ? e.detail[0] : e.detail;
                type = data.type || 'info';
                message = data.message || 'Notification';
                show = true;
                setTimeout(() => show = false, 4000);
            });
        "
        style="position: fixed; top: 24px; right: 24px; z-index: 9999; min-width: 350px;">
        <template x-if="show">
            <div :class="type === 'success' ? 'alert alert-success shadow-lg' : 'alert alert-danger shadow-lg'" class="fade show border-0" role="alert">
                <div class="d-flex align-items-center">
                    <i :class="type === 'success' ? 'bi bi-check-circle-fill me-2' : 'bi bi-exclamation-triangle-fill me-2'" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1" x-text="message"></div>
                    <button type="button" class="btn-close ms-2" @click="show = false" aria-label="Close"></button>
                </div>
            </div>
        </template>
    </div>

    {{-- Quick Create Supplier Modal --}}
    @if($showCreateSupplierModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <form wire:submit.prevent="saveNewSupplier">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-person-plus-fill me-2"></i> Add New Supplier
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeCreateSupplierModal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Supplier Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('newSupplierName') is-invalid @enderror" wire:model.blur="newSupplierName" placeholder="Enter supplier name">
                                @error('newSupplierName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Business Name</label>
                                <input type="text" class="form-control @error('newSupplierBusinessName') is-invalid @enderror" wire:model.blur="newSupplierBusinessName" placeholder="Enter business / company name">
                                @error('newSupplierBusinessName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone Number</label>
                                <input type="text" class="form-control @error('newSupplierPhone') is-invalid @enderror" wire:model.blur="newSupplierPhone" placeholder="e.g. 0771234567 or +94...">
                                @error('newSupplierPhone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contact Person / Number</label>
                                <input type="text" class="form-control @error('newSupplierContact') is-invalid @enderror" wire:model.blur="newSupplierContact" placeholder="Contact person or alternate number">
                                @error('newSupplierContact') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address</label>
                                <input type="email" class="form-control @error('newSupplierEmail') is-invalid @enderror" wire:model.blur="newSupplierEmail" placeholder="supplier@example.com">
                                @error('newSupplierEmail') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Opening Balance (Due) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rs.</span>
                                    <input type="number" step="0.01" min="0" class="form-control @error('newSupplierOpeningBalance') is-invalid @enderror" wire:model.blur="newSupplierOpeningBalance" placeholder="0.00">
                                </div>
                                <small class="text-muted">Initial balance owed to supplier before today</small>
                                @error('newSupplierOpeningBalance') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea class="form-control @error('newSupplierAddress') is-invalid @enderror" wire:model.blur="newSupplierAddress" rows="2" placeholder="Enter supplier address"></textarea>
                            @error('newSupplierAddress') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control @error('newSupplierNotes') is-invalid @enderror" wire:model.blur="newSupplierNotes" rows="2" placeholder="Optional notes..."></textarea>
                            @error('newSupplierNotes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" wire:click="closeCreateSupplierModal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="bi bi-check-circle me-1"></i> Save & Select Supplier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Payment Modal (Multi-Cheque & Multi-Payment Support) --}}
    @if($showPaymentModal && $selectedSupplier)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-credit-card me-2"></i> Confirm Supplier Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closePaymentModal"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- Supplier & Payment Summary --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3 fw-bold text-uppercase small">Supplier Information</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td width="40%"><strong>Name:</strong></td>
                                    <td>{{ $selectedSupplier?->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td>{{ $selectedSupplier?->phone ?: ($selectedSupplier?->contact ?: 'N/A') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $selectedSupplier?->email ?: 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3 fw-bold text-uppercase small">Payment Summary</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td width="50%"><strong>Total Due:</strong></td>
                                    <td class="text-end">Rs.{{ number_format($totalDueAmount, 2) }}</td>
                                </tr>
                                @if((float)$overpaymentToApply > 0)
                                <tr>
                                    <td><strong>Overpayment Credit:</strong></td>
                                    <td class="text-end text-info fw-bold">- Rs.{{ number_format((float)$overpaymentToApply, 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td><strong>Amount to Pay:</strong></td>
                                    <td class="text-end text-success fw-bold">Rs.{{ number_format((float)$totalPaymentAmount, 2) }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td><strong>Total Payment:</strong></td>
                                    <td class="text-end text-primary fw-bold">Rs.{{ number_format((float)$totalPaymentAmount + (float)$overpaymentToApply, 2) }}</td>
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
                            <label class="form-label fw-bold small text-uppercase">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" wire:model="paymentDate" class="form-control shadow-sm rounded-3">
                            @error('paymentDate') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold small text-uppercase">Notes / Reference</label>
                            <input type="text" wire:model="paymentNotes" class="form-control shadow-sm rounded-3" placeholder="Optional notes for this payment receipt">
                        </div>
                    </div>

                    {{-- Payment Details Table (Multi-Method / Multi-Cheque Support) --}}
                    <div class="card bg-light border-0 mb-4 shadow-sm">
                        <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-wallet2 me-2"></i> Payment Methods</h6>
                            <button type="button" class="btn btn-light btn-sm rounded-pill px-3 fw-bold" wire:click="addPaymentRow">
                                <i class="bi bi-plus-circle me-1"></i> Add Method
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-borderless align-middle mb-0">
                                    <thead class="bg-white text-muted small text-uppercase fw-bold">
                                        <tr>
                                            <th style="width: 22%;">Method</th>
                                            <th style="width: 20%;">Amount</th>
                                            <th>Details</th>
                                            <th style="width: 40px;"></th>
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
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white border-0">Rs.</span>
                                                    <input type="number" step="0.01" wire:model.live="paymentRows.{{ $index }}.amount" class="form-control border-0 shadow-sm text-end fw-bold" placeholder="0.00">
                                                </div>
                                                @error('paymentRows.'.$index.'.amount')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </td>
                                            <td>
                                                @if($row['method'] === 'cheque')
                                                    <div class="row g-2">
                                                        <div class="col-md-4">
                                                            <input type="text" wire:model="paymentRows.{{ $index }}.cheque_number" class="form-control form-control-sm border-0 shadow-sm @error('paymentRows.'.$index.'.cheque_number') is-invalid @enderror" placeholder="Cheque No *">
                                                            @error('paymentRows.'.$index.'.cheque_number')
                                                                <div class="invalid-feedback small">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="col-md-4">
                                                            <select wire:model="paymentRows.{{ $index }}.bank_name" class="form-select form-select-sm border-0 shadow-sm @error('paymentRows.'.$index.'.bank_name') is-invalid @enderror">
                                                                <option value="">Select Bank *</option>
                                                                <option value="Bank of Ceylon">Bank of Ceylon (BOC)</option>
                                                                <option value="People's Bank">People's Bank</option>
                                                                <option value="Commercial Bank of Ceylon">Commercial Bank (COM)</option>
                                                                <option value="Hatton National Bank">Hatton National Bank (HNB)</option>
                                                                <option value="Sampath Bank">Sampath Bank</option>
                                                                <option value="Seylan Bank">Seylan Bank</option>
                                                                <option value="DFCC Bank">DFCC Bank</option>
                                                                <option value="Nations Trust Bank">Nations Trust Bank (NTB)</option>
                                                                <option value="National Development Bank">National Development Bank (NDB)</option>
                                                                <option value="Pan Asia Banking Corporation">Pan Asia Bank</option>
                                                                <option value="Union Bank of Colombo">Union Bank</option>
                                                                <option value="Cargills Bank">Cargills Bank</option>
                                                                <option value="Amana Bank">Amana Bank</option>
                                                                <option value="HSBC Sri Lanka">HSBC</option>
                                                                <option value="Standard Chartered Bank">Standard Chartered</option>
                                                            </select>
                                                            @error('paymentRows.'.$index.'.bank_name')
                                                                <div class="invalid-feedback small">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="col-md-4">
                                                            <input type="date" wire:model.live="paymentRows.{{ $index }}.cheque_date" class="form-control form-control-sm border-0 shadow-sm @error('paymentRows.'.$index.'.cheque_date') is-invalid @enderror">
                                                            @error('paymentRows.'.$index.'.cheque_date')
                                                                <div class="invalid-feedback small">{{ $message }}</div>
                                                            @enderror
                                                            @if(!empty($row['cheque_date']) && \App\Models\Holiday::isHoliday($row['cheque_date']))
                                                                <div class="text-danger small mt-1 fw-bold">
                                                                    <i class="bi bi-exclamation-triangle"></i> Holiday: {{ \App\Models\Holiday::getHolidayReason($row['cheque_date']) }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @elseif($row['method'] === 'bank_transfer')
                                                    <div class="row g-2">
                                                        <div class="col-md-4">
                                                            <input type="text" wire:model="paymentRows.{{ $index }}.bank_name" class="form-control form-control-sm border-0 shadow-sm @error('paymentRows.'.$index.'.bank_name') is-invalid @enderror" placeholder="Bank Name *">
                                                            @error('paymentRows.'.$index.'.bank_name')
                                                                <div class="invalid-feedback small">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="col-md-4">
                                                            <input type="date" wire:model="paymentRows.{{ $index }}.transfer_date" class="form-control form-control-sm border-0 shadow-sm" placeholder="Transfer Date">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <input type="text" wire:model="paymentRows.{{ $index }}.transfer_reference" class="form-control form-control-sm border-0 shadow-sm" placeholder="Ref / Txn No">
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                                        <i class="bi bi-cash me-1"></i> Cash Payment
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if(count($paymentRows) > 1)
                                                <button type="button" class="btn btn-outline-danger btn-sm rounded-circle" wire:click="removePaymentRow({{ $index }})" title="Remove Method">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                               @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @php
                                $sumOfRows = collect($paymentRows)->sum('amount');
                                $diff = abs((float)$sumOfRows - (float)$totalPaymentAmount);
                            @endphp
                            <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center">
                                <small class="text-muted">Sum of Methods: <strong class="{{ $diff > 0.01 ? 'text-danger' : 'text-success' }}">Rs.{{ number_format($sumOfRows, 2) }}</strong></small>
                                @if($diff > 0.01)
                                <small class="text-danger fw-bold"><i class="bi bi-exclamation-circle me-1"></i>Must equal Rs.{{ number_format((float)$totalPaymentAmount, 2) }} (Difference: Rs.{{ number_format(abs((float)$totalPaymentAmount - $sumOfRows), 2) }})</small>
                                @else
                                <small class="text-success fw-bold"><i class="bi bi-check-circle me-1"></i>Amounts Match</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Allocations Preview --}}
                    <h6 class="text-muted mb-3 fw-bold text-uppercase small">Payment Allocations</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Order / Balance</th>
                                    <th class="text-end">Due Amount</th>
                                    <th class="text-end">Allocated</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allocations as $id => $alloc)
                                <tr>
                                    <td class="fw-bold {{ $id === 'opening' ? 'text-danger' : '' }}">{{ $alloc['order_code'] }}</td>
                                    <td class="text-end">Rs.{{ number_format($alloc['due_amount'], 2) }}</td>
                                    <td class="text-end text-success fw-bold">Rs.{{ number_format($alloc['payment_amount'], 2) }}</td>
                                    <td class="text-center">
                                        @if($alloc['is_fully_paid'])
                                            <span class="badge bg-success">Fully Paid</span>
                                        @elseif($alloc['payment_amount'] > 0)
                                            <span class="badge bg-warning text-dark">Partial</span>
                                        @else
                                            <span class="badge bg-secondary">Unpaid</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" wire:click="closePaymentModal">Cancel</button>
                    <button type="button" class="btn btn-success px-4 fw-bold" wire:click="processPayment" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="processPayment">
                            <i class="bi bi-check-circle me-1"></i> Process & Settle Payment
                        </span>
                        <span wire:loading wire:target="processPayment">
                            <span class="spinner-border spinner-border-sm me-1"></span> Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Order Details Modal --}}
    @if($showOrderDetailsModal && $selectedOrderForView)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-info-circle me-2"></i> Purchase Order Details - {{ $selectedOrderForView->order_code }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeOrderDetailsModal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td width="40%"><strong>Order Code:</strong></td>
                                    <td>{{ $selectedOrderForView->order_code }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Order Date:</strong></td>
                                    <td>{{ $selectedOrderForView->order_date ? date('M d, Y', strtotime($selectedOrderForView->order_date)) : '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Supplier:</strong></td>
                                    <td>{{ $selectedOrderForView->supplier->name }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td width="50%"><strong>Total Amount:</strong></td>
                                    <td class="text-end fw-bold">Rs.{{ number_format($selectedOrderForView->total_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Due Amount:</strong></td>
                                    <td class="text-end fw-bold text-danger">Rs.{{ number_format($selectedOrderForView->due_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td class="text-end">
                                        <span class="badge bg-{{ $selectedOrderForView->due_amount == 0 ? 'success' : 'warning' }}">
                                            {{ $selectedOrderForView->due_amount == 0 ? 'Fully Paid' : 'Pending Payment' }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h6 class="text-muted fw-bold text-uppercase small mb-3">Order Items</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedOrderForView->items as $item)
                                <tr>
                                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">Rs.{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end">Rs.{{ number_format($item->total_price, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" wire:click="closeOrderDetailsModal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Receipt Modal --}}
    @if($showReceiptModal && $lastPayment)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-body p-0">
                    {{-- Success Message --}}
                    <div class="bg-success text-white p-4 text-center">
                        <i class="bi bi-check-circle display-4 d-block mb-2"></i>
                        <h4 class="mb-1 fw-bold">Payment Successful!</h4>
                        <p class="mb-0">Supplier payment has been recorded and allocated successfully.</p>
                    </div>

                    {{-- Receipt Content --}}
                    <div class="p-4" id="receipt-content">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold text-dark">Supplier Payment Receipt</h4>
                            <p class="text-muted small mb-0">{{ config('shop.name') }}</p>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Receipt #:</strong> #{{ $lastPayment->id }}</p>
                                <p class="mb-1"><strong>Payment Date:</strong> {{ date('d/m/Y', strtotime($lastPayment->payment_date)) }}</p>
                                <p class="mb-1"><strong>Supplier:</strong> {{ $lastPayment->supplier->name }}</p>
                                <p class="mb-0"><strong>Phone:</strong> {{ $lastPayment->supplier->phone ?: ($lastPayment->supplier->contact ?: '-') }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Reference No:</strong> {{ $lastPayment->payment_reference ?? 'N/A' }}</p>
                                @if($lastPayment->overpayment_used > 0)
                                <p class="mb-1"><strong>Overpayment Credit Used:</strong> <span class="text-info fw-bold">Rs.{{ number_format($lastPayment->overpayment_used, 2) }}</span></p>
                                @endif
                                <p class="mb-1"><strong>Total Amount Settled:</strong> <span class="text-success fw-bold">Rs.{{ number_format($lastPayment->amount + ($lastPayment->overpayment_used ?? 0), 2) }}</span></p>
                                <p class="mb-0"><strong>Processed By:</strong> {{ auth()->user()->name ?? 'Admin' }}</p>
                            </div>
                        </div>

                        {{-- Payment Methods & Cheques Breakdown --}}
                        @if(isset($lastPayment->grouped_payments) && count($lastPayment->grouped_payments) > 0)
                        <h6 class="fw-bold text-muted mb-2 text-uppercase small">Payment Methods</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Method</th>
                                        <th>Details</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lastPayment->grouped_payments as $grpPay)
                                    <tr>
                                        <td class="fw-bold">{{ ucfirst(str_replace('_', ' ', $grpPay->payment_method)) }}</td>
                                        <td>
                                            @if($grpPay->payment_method === 'cheque')
                                                <strong>Cheque No:</strong> {{ $grpPay->cheque_number }} | 
                                                <strong>Bank:</strong> {{ $grpPay->bank_name }} | 
                                                <strong>Date:</strong> {{ $grpPay->cheque_date ? date('d/m/Y', strtotime($grpPay->cheque_date)) : '-' }}
                                            @elseif($grpPay->payment_method === 'bank_transfer')
                                                <strong>Bank:</strong> {{ $grpPay->bank_name }} | 
                                                <strong>Ref:</strong> {{ $grpPay->bank_transaction ?? '-' }}
                                            @else
                                                Cash Transaction
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold text-success">Rs.{{ number_format($grpPay->amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        {{-- Allocated Orders / Opening Balance --}}
                        <h6 class="fw-bold text-muted mb-2 text-uppercase small">Payment Allocations</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order / Balance</th>
                                        <th class="text-end">Allocated Amount</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lastPayment->allocations as $allocation)
                                    <tr>
                                        <td class="fw-bold">{{ $allocation->order->order_code ?? 'N/A' }}</td>
                                        <td class="text-end text-success fw-bold">Rs.{{ number_format($allocation->allocated_amount, 2) }}</td>
                                        <td class="text-center">
                                            @if($allocation->order && $allocation->order->due_amount == 0)
                                                <span class="badge bg-success">Fully Paid</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Partial Paid</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                    @if($lastPayment->allocations->isEmpty())
                                    <tr>
                                        <td class="fw-bold text-danger">Opening Balance / Previous Balance Payment</td>
                                        <td class="text-end text-success fw-bold">Rs.{{ number_format($lastPayment->amount + ($lastPayment->overpayment_used ?? 0), 2) }}</td>
                                        <td class="text-center"><span class="badge bg-success">Paid</span></td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        @if($lastPayment->notes)
                        <div class="mt-3">
                            <h6 class="fw-bold text-muted small">NOTES</h6>
                            <p class="mb-0 text-muted">{{ $lastPayment->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" wire:click="closeReceiptModal">
                        <i class="bi bi-x-circle me-1"></i> Close
                    </button>
                    <div>
                        <button type="button" class="btn btn-primary me-2" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i> Print
                        </button>
                        <button type="button" class="btn btn-success" wire:click="downloadReceipt">
                            <i class="bi bi-download me-1"></i> Download PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Main Container --}}
    <div class="container-fluid py-3">
        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">
                    <i class="bi bi-receipt text-success me-2"></i> Supplier Payment Receipt
                </h3>
                <p class="text-muted mb-0">Record supplier payments, split across multiple cheques, and settle due purchase orders or opening balances</p>
            </div>
            <div>
                <button class="btn btn-primary" wire:click="openCreateSupplierModal">
                    <i class="bi bi-person-plus-fill me-2"></i> Add New Supplier
                </button>
            </div>
        </div>

        {{-- Supplier Search Card --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-light py-3">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-search text-primary me-2"></i> Find Supplier with Due Payments
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    {{-- Search Input --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Search Supplier</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control"
                                placeholder="Search by supplier name, phone, contact..."
                                wire:model.live.debounce.300ms="search">
                        </div>
                    </div>

                    {{-- Selected Supplier Card --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Selected Supplier</label>
                        @if($selectedSupplier)
                        <div class="border rounded p-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">{{ $selectedSupplier->name }}</h6>
                                    <p class="mb-1 text-muted small">
                                        <i class="bi bi-telephone me-1"></i>{{ $selectedSupplier->phone ?: ($selectedSupplier->contact ?: '-') }}
                                        @if($selectedSupplier->email)
                                        | <i class="bi bi-envelope me-1"></i>{{ $selectedSupplier->email }}
                                        @endif
                                    </p>
                                    @if($supplierOverpayment > 0)
                                    <p class="mb-0 text-success fw-bold small">
                                        <i class="bi bi-wallet2 me-1"></i>Overpayment Credit: Rs.{{ number_format($supplierOverpayment, 2) }}
                                    </p>
                                    @endif
                                </div>
                                <button class="btn btn-outline-danger btn-sm" wire:click="clearSelectedSupplier" title="Clear selection">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        @else
                        <div class="border rounded p-3 text-center text-muted">
                            <i class="bi bi-person-x display-6 d-block mb-1"></i>
                            No supplier selected. Search above or select from the list below.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Supplier List Table (When No Supplier Selected) --}}
        @if(!$selectedSupplier)
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-people text-primary me-2"></i> Suppliers with Due Payments
                </h5>
                <span class="badge bg-primary px-3 py-2">{{ $suppliers->total() }} suppliers</span>
            </div>
            <div class="card-body p-0 overflow-auto">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Supplier Name</th>
                                <th>Contact / Phone</th>
                                <th class="text-center">Opening Due</th>
                                <th class="text-center">Due Orders</th>
                                <th class="text-end">Total Due</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($suppliers as $supplier)
                            @php
                            $dueOrdersCount = $supplier->orders->count();
                            $ordersDueSum = (float) $supplier->orders->sum('due_amount');
                            $openingDue = (float) ($supplier->balance_total ?? 0);
                            $totalDue = $openingDue + $ordersDueSum;
                            @endphp
                            <tr wire:key="supplier-{{ $supplier->id }}">
                                <td class="ps-4 fw-semibold text-dark">{{ $supplier->name }}</td>
                                <td>{{ $supplier->phone ?: ($supplier->contact ?: '-') }}</td>
                                <td class="text-center">
                                    @if($openingDue > 0)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Rs.{{ number_format($openingDue, 2) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark">{{ $dueOrdersCount }} order(s)</span>
                                </td>
                                <td class="text-end text-danger fw-bold">
                                    Rs.{{ number_format($totalDue, 2) }}
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-primary btn-sm px-3" wire:click="selectSupplier({{ $supplier->id }})">
                                        <i class="bi bi-credit-card me-1"></i> Pay
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-search display-4 d-block mb-3 text-muted"></i>
                                    @if($search)
                                    No suppliers found for "<span class="fw-bold">{{ $search }}</span>".
                                    @else
                                    No suppliers with due payments or opening balances found.
                                    @endif
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($suppliers->hasPages())
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-center">
                        {{ $suppliers->links('livewire.custom-pagination') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Due Invoices / Orders and Payment Allocation (When Supplier Selected) --}}
        @if($selectedSupplier && (count($supplierOrders) > 0 || (float)($selectedSupplier->balance_total ?? 0) > 0))
        <div class="row">
            {{-- Due Items List --}}
            <div class="col-lg-8">
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-receipt me-2"></i> Due Invoices & Orders - {{ $selectedSupplier->name }}
                        </h5>
                        <div>
                            @if(count($selectedOrders) > 0)
                                <button class="btn btn-light btn-sm me-2 fw-semibold" wire:click="clearOrderSelection">
                                    <i class="bi bi-x-circle me-1"></i> Clear Selection
                                </button>
                            @endif
                            <button class="btn btn-dark btn-sm fw-semibold" wire:click="selectAllOrders">
                                <i class="bi bi-check-all me-1"></i> Select All
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0 overflow-auto">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" width="50">
                                            <i class="bi bi-check-square"></i>
                                        </th>
                                        <th>Invoice / Order Code</th>
                                        <th>Date</th>
                                        <th>Original Total</th>
                                        <th>Due Amount</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center" width="90">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Opening Balance Row --}}
                                    @if((float)($selectedSupplier->balance_total ?? 0) > 0)
                                    @php $openingSelected = in_array('opening', $selectedOrders); @endphp
                                    <tr 
                                        wire:key="opening-balance-{{ $selectedSupplier->id }}" 
                                        class="{{ $openingSelected ? 'table-success' : 'table-warning' }}"
                                        style="cursor: pointer;"
                                        wire:click="toggleOrderSelection('opening')">
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input 
                                                    class="form-check-input" 
                                                    type="checkbox" 
                                                    {{ $openingSelected ? 'checked' : '' }}
                                                    style="pointer-events: none;">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-danger">Opening Balance</div>
                                            <small class="text-muted">Previous balance</small>
                                        </td>
                                        <td>{{ $selectedSupplier->created_at ? $selectedSupplier->created_at->format('d/m/Y') : '-' }}</td>
                                        <td>-</td>
                                        <td class="fw-bold {{ $openingSelected ? 'text-success' : 'text-danger' }}">
                                            Rs.{{ number_format((float)$selectedSupplier->balance_total, 2) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger">Opening Due</span>
                                        </td>
                                        <td class="text-center">-</td>
                                    </tr>
                                    @endif

                                    {{-- Due Orders Rows --}}
                                    @foreach($supplierOrders as $order)
                                    @php
                                        $isSelected = in_array($order->id, $selectedOrders);
                                    @endphp
                                    <tr 
                                        wire:key="order-{{ $order->id }}" 
                                        class="{{ $isSelected ? 'table-success' : 'table-warning' }}"
                                        style="cursor: pointer;"
                                        wire:click="toggleOrderSelection({{ $order->id }})">
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input 
                                                    class="form-check-input" 
                                                    type="checkbox" 
                                                    {{ $isSelected ? 'checked' : '' }}
                                                    style="pointer-events: none;">
                                            </div>
                                        </td>
                                        <td class="fw-bold text-dark">{{ $order->order_code }}</td>
                                        <td>{{ $order->order_date ? date('d/m/Y', strtotime($order->order_date)) : '-' }}</td>
                                        <td>Rs.{{ number_format($order->total_amount, 2) }}</td>
                                        <td class="fw-bold {{ $isSelected ? 'text-success' : 'text-danger' }}">
                                            Rs.{{ number_format($order->due_amount, 2) }}
                                        </td>
                                        <td class="text-center">
                                            @if($order->due_amount == 0)
                                                <span class="badge bg-success">Complete</span>
                                            @elseif($order->due_amount > 0 && $order->due_amount < $order->total_amount)
                                                <span class="badge bg-warning text-dark">Partial Paid</span>
                                            @else
                                                <span class="badge bg-danger">Payment Pending</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-outline-primary btn-sm" wire:click.stop="viewOrderDetails({{ $order->id }})">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(count($selectedOrders) > 0)
                            <div class="card-footer bg-light py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        {{ count($selectedOrders) }} item(s) selected
                                    </span>
                                    <span class="fw-bold text-success">
                                        Total Due: Rs.{{ number_format($totalDueAmount, 2) }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Cheques Given to Selected Supplier --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-card-checklist text-warning me-2"></i> Cheques Issued to {{ $selectedSupplier->name }}
                        </h6>
                        <div>
                            <span class="badge bg-warning text-dark me-2">{{ count($supplierGivenCheques) }} Cheque(s)</span>
                            <a href="{{ route('admin.supplier-cheque-list') }}" class="btn btn-sm btn-outline-light py-0 px-2" target="_blank">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Cheques Page
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0 overflow-auto">
                        <div class="table-responsive" style="min-height: auto; max-height: 350px;">
                            <table class="table table-hover align-middle mb-0 table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Cheque No</th>
                                        <th>Bank</th>
                                        <th>Date</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-center pe-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($supplierGivenCheques as $cheq)
                                    <tr>
                                        <td class="ps-3 fw-bold">{{ $cheq->cheque_number }}</td>
                                        <td>{{ $cheq->bank_name }}</td>
                                        <td>{{ $cheq->cheque_date ? date('d/m/Y', strtotime($cheq->cheque_date)) : '-' }}</td>
                                        <td class="text-end fw-bold text-dark">Rs. {{ number_format($cheq->amount, 2) }}</td>
                                        <td class="text-center pe-3">
                                            @if($cheq->status === 'complete')
                                                <span class="badge bg-success">Complete</span>
                                            @elseif($cheq->status === 'return')
                                                <span class="badge bg-danger">Returned</span>
                                            @elseif($cheq->status === 'cancelled')
                                                <span class="badge bg-secondary">Cancelled</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            <i class="bi bi-inbox me-1"></i> No cheques recorded yet for this supplier.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment Allocation (Right Sidebar) --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-success text-white py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-cash-coin me-2"></i> Payment Allocation
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        @if(count($selectedOrders) > 0 || $totalDueAmount > 0)
                            <div class="alert alert-info border-0 shadow-sm mb-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold">Total Due (Selected):</span>
                                    <span class="fs-5 fw-bold text-danger">Rs.{{ number_format($totalDueAmount, 2) }}</span>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    {{ count($selectedOrders) }} item(s) selected for payment
                                </small>
                            </div>

                            {{-- Overpayment Credit Section --}}
                            @if($supplierOverpayment > 0)
                            <div class="alert alert-success border-0 shadow-sm mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">
                                        <i class="bi bi-wallet2 me-1"></i> Overpayment Credit Available
                                    </span>
                                    <span class="fw-bold text-success">Rs.{{ number_format($supplierOverpayment, 2) }}</span>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="useOverpayment" 
                                        wire:click="toggleOverpayment" {{ $useOverpayment ? 'checked' : '' }}>
                                    <label class="form-check-label" for="useOverpayment">
                                        Apply overpayment credit to this payment
                                    </label>
                                </div>
                                @if($useOverpayment)
                                <div class="mt-2">
                                    <label class="form-label small fw-semibold">Credit Amount to Apply</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rs.</span>
                                        <input type="number" class="form-control" 
                                            wire:model.live="overpaymentToApply" placeholder="0" 
                                            min="0" 
                                            max="{{ min($supplierOverpayment, $totalDueAmount) }}" 
                                            step="0.01">
                                    </div>
                                    <small class="text-muted">Max: Rs.{{ number_format(min($supplierOverpayment, $totalDueAmount), 2) }}</small>
                                </div>
                                @endif
                            </div>
                            @endif

                            {{-- Enter Payment Amount --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Enter Payment Amount <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light">Rs.</span>
                                    <input type="number" class="form-control fw-bold" wire:model.live="totalPaymentAmount" min="0" max="{{ $totalDueAmount - (float)$overpaymentToApply }}" step="0.01" placeholder="0.00">
                                </div>
                                <small class="text-muted">Maximum: Rs.{{ number_format($totalDueAmount - (float)$overpaymentToApply, 2) }}</small>
                                @error('totalPaymentAmount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Process Payment Button --}}
                            <div class="d-grid mb-3">
                                <button
                                    class="btn btn-success btn-lg fw-bold py-3 shadow-sm"
                                    wire:click="openPaymentModal"
                                    wire:loading.attr="disabled"
                                    {{ ((float)$totalPaymentAmount <= 0 && (float)$overpaymentToApply <= 0) || empty($selectedOrders) ? 'disabled' : '' }}>
                                    <span wire:loading.remove wire:target="openPaymentModal">
                                        <i class="bi bi-credit-card me-1"></i> Process Payment
                                    </span>
                                    <span wire:loading wire:target="openPaymentModal">
                                        <span class="spinner-border spinner-border-sm"></span> Processing...
                                    </span>
                                </button>
                            </div>

                            {{-- Quick Payment Options --}}
                            <div class="mt-3">
                                <small class="text-muted d-block mb-2 fw-semibold">Quick Options:</small>
                                <div class="d-grid gap-2">
                                    @php
                                        $remainingAfterOverpayment = max(0, $totalDueAmount - (float)$overpaymentToApply);
                                    @endphp
                                    <button
                                        class="btn btn-outline-primary btn-sm fw-semibold"
                                        wire:click="$set('totalPaymentAmount', {{ $remainingAfterOverpayment }})">
                                        Pay Full Amount (Rs.{{ number_format($remainingAfterOverpayment, 2) }})
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning text-center border-0 shadow-sm">
                                <i class="bi bi-exclamation-triangle-fill display-4 d-block mb-3 text-warning"></i>
                                <h6 class="fw-bold mb-2">No Items Selected</h6>
                                <p class="mb-0 small text-muted">Please select at least one due invoice or opening balance to make a payment.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @elseif($selectedSupplier && count($supplierOrders) == 0 && (float)($selectedSupplier->balance_total ?? 0) <= 0)
        <div class="alert alert-success border-0 shadow-sm p-4 text-center">
            <i class="bi bi-check-circle-fill display-4 d-block mb-3 text-success"></i>
            <h5 class="fw-bold">No Due Payments for {{ $selectedSupplier->name }}</h5>
            <p class="text-muted mb-3">This supplier has no outstanding purchase orders or opening balance due.</p>
            <button class="btn btn-outline-primary" wire:click="clearSelectedSupplier">
                <i class="bi bi-search me-1"></i> Search Another Supplier
            </button>
        </div>
        @endif
    </div>
</div>
