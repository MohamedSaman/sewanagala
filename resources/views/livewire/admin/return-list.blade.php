<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-arrow-return-left text-success me-2"></i> Product Returns List
            </h3>
            <p class="text-muted mb-0">View and manage all customer product returns</p>
        </div>
        <div>
            <a href="{{ route('admin.return-product') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> New Product Return
            </a>
        </div>
    </div>

    <!-- Source Switcher Tabs -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-2">
            <div class="nav nav-pills nav-fill" role="tablist">
                <button class="nav-link {{ $returnSource === 'system' ? 'active fw-bold shadow-sm' : 'text-muted' }}" 
                        wire:click="setReturnSource('system')" type="button">
                    <i class="bi bi-receipt me-2"></i> System Invoice Returns
                </button>
                <button class="nav-link {{ $returnSource === 'manual' ? 'active fw-bold shadow-sm' : 'text-muted' }}" 
                        wire:click="setReturnSource('manual')" type="button">
                    <i class="bi bi-journal-plus me-2"></i> Manual / External Sale Returns
                </button>
            </div>
        </div>
    </div>

    <!-- Returns Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-list-ul text-primary me-2"></i> 
                    {{ $returnSource === 'manual' ? 'Manual / External Returns' : 'System Returns' }}
                </h5>
                <span class="badge bg-primary">{{ $returns->total() }} records</span>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 flex-grow-1"
                style="max-width: 500px;">
                <!-- 🔍 Search Bar -->
                <div class="search-bar flex-grow-1">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" wire:model.live="returnSearch"
                            placeholder="Search by invoice, customer, or product...">
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="text-sm text-muted fw-medium">Show</label>
                <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 80px;">
                    <option value="30">30</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="500">500</option>
                    <option value="all">All</option>
                </select>
                <span class="text-sm text-muted">entries</span>
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
                            <th class="ps-4" style="width: 50px;">
                                <input type="checkbox" class="form-check-input" id="selectAll"
                                    onclick="toggleAllRows(this)">
                            </th>
                            <th class="ps-4">#</th>
                            <th>Invoice Number</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Condition</th>
                            <th class="text-center">Return Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Total</th>
                            <th>Date</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $index => $return)
                        @php
                            $isManual = ($returnSource === 'manual');
                            $invNum = $isManual ? $return->invoice_number : ($return->sale?->invoice_number ?? '-');
                            $custName = $isManual ? ($return->customer_name ?? ($return->customer?->name ?? 'Walk-in Customer')) : ($return->sale?->customer?->name ?? 'Walk-in Customer');
                            $unitPrice = $isManual ? $return->unit_price : $return->selling_price;
                            $cond = $return->return_condition ?: 'usable';
                        @endphp
                        <tr style="cursor:pointer" wire:key="return-{{ $return->id }}" class="table-row"
                            data-id="{{ $return->id }}">
                            <td class="ps-4" onclick="event.stopPropagation();">
                                <input type="checkbox" class="form-check-input row-checkbox"
                                    onchange="toggleRowHighlight(this)">
                            </td>
                            <td class="ps-4">{{ $returns->firstItem() ? ($returns->firstItem() + $index) : ($index + 1) }}</td>
                            <td wire:click="showReceipt({{ $return->id }}, {{ $isManual ? 'true' : 'false' }})">
                                <span class="fw-bold text-primary">#{{ $invNum }}</span>
                                @if($isManual)
                                <span class="badge bg-secondary ms-1">Manual</span>
                                @endif
                            </td>
                            <td wire:click="showReceipt({{ $return->id }}, {{ $isManual ? 'true' : 'false' }})">
                                {{ $custName }}
                            </td>
                            <td wire:click="showReceipt({{ $return->id }}, {{ $isManual ? 'true' : 'false' }})">
                                <div class="fw-semibold">{{ $return->product?->name ?? '-' }}</div>
                                <small class="text-muted">{{ $return->product?->code ?? '' }}</small>
                            </td>
                            <td wire:click="showReceipt({{ $return->id }}, {{ $isManual ? 'true' : 'false' }})">
                                <span class="badge bg-{{ $cond === 'usable' ? 'success' : ($cond === 'damage' ? 'danger' : 'warning text-dark') }}">
                                    {{ ucwords(str_replace('_', ' ', $cond)) }}
                                </span>
                            </td>
                            <td class="text-center fw-bold" wire:click="showReceipt({{ $return->id }}, {{ $isManual ? 'true' : 'false' }})">
                                {{ $return->return_quantity }}
                            </td>
                            <td class="text-end" wire:click="showReceipt({{ $return->id }}, {{ $isManual ? 'true' : 'false' }})">
                                Rs.{{ number_format($unitPrice, 2) }}
                            </td>
                            <td class="text-end fw-bold text-success" wire:click="showReceipt({{ $return->id }}, {{ $isManual ? 'true' : 'false' }})">
                                Rs.{{ number_format($return->total_amount, 2) }}
                            </td>
                            <td wire:click="showReceipt({{ $return->id }}, {{ $isManual ? 'true' : 'false' }})">
                                {{ $return->created_at?->format('d/m/Y') }}
                            </td>
                            <td class="text-end pe-4" onclick="event.stopPropagation();">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-gear-fill"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <button class="dropdown-item" wire:click="showReceipt({{ $return->id }}, {{ $isManual ? 'true' : 'false' }})">
                                                <i class="bi bi-eye text-primary me-2"></i> View Receipt
                                            </button>
                                        </li>
                                        @if(auth()->user()->hasPermission('menu_return_customer_delete'))
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item text-danger" wire:click="deleteReturn({{ $return->id }}, {{ $isManual ? 'true' : 'false' }})">
                                                <i class="bi bi-trash me-2"></i> Delete
                                            </button>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                <i class="bi bi-arrow-return-left display-4 d-block mb-2 text-muted"></i>
                                No returns found in this view.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($returns->hasPages())
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-center">
                    {{ $returns->links('livewire.custom-pagination') }}
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Receipt Modal (Bill Style) -->
    <div wire:ignore.self class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" id="printableReturnReceipt">
                <!-- Header – logo + company name -->
                <div class="modal-header text-center border-0"
                    style="background: linear-gradient(90deg, #3b5b0c, #8eb922); color: #fff;">
                    <div class="w-100">
                        <img src="{{ asset('images/USN.png') }}" alt="Logo" class="img-fluid mb-2"
                            style="max-height:60px;">
                        <h4 class="mb-0 fw-bold">{{ config('shop.name') }}</h4>
                    </div>
                    <button type="button" class="btn-close btn-close-white closebtn" wire:click="closeModal"></button>
                </div>

                @if($selectedReturn)
                @php
                    $isManualModal = $isManualSelected;
                    $mCustName = $isManualModal ? ($selectedReturn->customer_name ?? ($selectedReturn->customer?->name ?? 'Walk-in Customer')) : ($selectedReturn->sale?->customer?->name ?? 'Walk-in Customer');
                    $mCustPhone = $isManualModal ? ($selectedReturn->customer?->phone ?? '') : ($selectedReturn->sale?->customer?->phone ?? '');
                    $mCustAddress = $isManualModal ? ($selectedReturn->customer?->address ?? '') : ($selectedReturn->sale?->customer?->address ?? '');
                    $mInvNumber = $isManualModal ? $selectedReturn->invoice_number : ($selectedReturn->sale?->invoice_number ?? '-');
                    $mUnitPrice = $isManualModal ? $selectedReturn->unit_price : $selectedReturn->selling_price;
                @endphp
                <div class="modal-body">
                    <!-- Customer + Return info (two columns) -->
                    <div class="row mb-4">
                        <div class="col-6">
                            <strong>Customer :</strong><br>
                            {{ $mCustName }}<br>
                            @if($mCustAddress) {{ $mCustAddress }}<br> @endif
                            @if($mCustPhone) Tel: {{ $mCustPhone }} @endif
                        </div>
                        <div class="col-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Return No :</strong></td>
                                    <td>#{{ $selectedReturn->id }} {{ $isManualModal ? '(Manual)' : '' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Invoice No :</strong></td>
                                    <td>{{ $mInvNumber }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Return Status :</strong></td>
                                    <td><span class="badge bg-success">Completed</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Return Date :</strong></td>
                                    <td>{{ $selectedReturn->created_at ? $selectedReturn->created_at->format('d/m/Y H:i') : '' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Items table -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:5%">#</th>
                                    <th style="width:15%">ITEM CODE</th>
                                    <th>DESCRIPTION</th>
                                    <th class="text-center" style="width:12%">RETURN QTY</th>
                                    <th class="text-end" style="width:12%">UNIT PRICE</th>
                                    <th class="text-end" style="width:12%">SUBTOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>{{ $selectedReturn->product?->code ?? 'N/A' }}</td>
                                    <td>{{ $selectedReturn->product?->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $selectedReturn->return_quantity }} Pc(s)</td>
                                    <td class="text-end">Rs.{{ number_format($mUnitPrice, 2) }}</td>
                                    <td class="text-end fw-bold">Rs.{{ number_format($selectedReturn->total_amount, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals – right-aligned block -->
                    <div class="row">
                        <div class="col-7"></div>
                        <div class="col-5">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-end"><strong>Total Return Amount (LKR)</strong></td>
                                    <td class="text-end fw-bold text-success">Rs.{{ number_format($selectedReturn->total_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end"><strong>Refunded Amount (LKR)</strong></td>
                                    <td class="text-end">Rs.{{ number_format($selectedReturn->total_amount, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if($selectedReturn->notes)
                    <div class="row mt-4 note">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <strong>Notes / Reason:</strong><br>
                                    {{ $selectedReturn->notes }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Footer -->
                    <div class="mt-4 text-center small">
                        <p class="mb-0">
                            <strong>ADDRESS :</strong>
                            {{ config('shop.address', 'N 122/1H, Kandy Road, Thihariya, Sri Lanka.') }}<br>
                            <strong>TEL :</strong> {{ config('shop.phone', '+0332 290 295') }}, <strong>EMAIL :</strong> {{ config('shop.email', 'thihariyatilecenter@gmail.com') }}
                        </p>
                        <p class="mt-1 text-muted">
                            Goods return will be accepted within 14 days only.
                        </p>
                    </div>
                </div>
                @endif

                <!-- Modal footer buttons -->
                <div class="modal-footer bg-light justify-content-between">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">
                        <i class="bi bi-x-circle me-1"></i> Close
                    </button>
                    <div>
                        @if($currentReturnId)
                        <button type="button" class="btn btn-primary" onclick="printReturnReceipt()">
                            <i class="bi bi-printer me-1"></i> Print Receipt
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="deleteReturnModal" tabindex="-1"
        aria-labelledby="deleteReturnModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle me-2"></i> Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    @if($selectedReturn)
                    <div class="alert alert-danger">
                        <h6 class="alert-heading">Warning!</h6>
                        <p class="mb-0">You are about to delete this return record. This action cannot be undone and will adjust product stock accordingly.</p>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <p><strong>Return ID:</strong> #{{ $selectedReturn->id }}</p>
                            <p><strong>Product:</strong> {{ $selectedReturn->product?->name ?? '-' }}</p>
                            <p><strong>Quantity:</strong> {{ $selectedReturn->return_quantity }}</p>
                            <p><strong>Amount:</strong> Rs.{{ number_format($selectedReturn->total_amount, 2) }}</p>
                            <p><strong>Date:</strong> {{ $selectedReturn->created_at?->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="confirmDeleteReturn">Confirm Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .nav-pills .nav-link {
        border-radius: 8px;
        padding: 0.75rem 1rem;
        transition: all 0.2s ease;
    }

    .nav-pills .nav-link.active {
        background-color: #0d6efd;
    }

    .selected-row {
        background-color: #d4e6f1 !important;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #printableReturnReceipt,
        #printableReturnReceipt * {
            visibility: visible;
        }

        #printableReturnReceipt {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            background: white !important;
        }

        .modal-footer,
        .btn-close,
        .closebtn {
            display: none !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function toggleRowHighlight(checkbox) {
        const row = checkbox.closest('tr');
        if (checkbox.checked) {
            row.classList.add('selected-row');
        } else {
            row.classList.remove('selected-row');
            document.getElementById('selectAll').checked = false;
        }
    }

    function toggleAllRows(selectAllCheckbox) {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
            toggleRowHighlight(checkbox);
        });
    }

    document.addEventListener('livewire:initialized', () => {
        Livewire.on('showModal', (modalId) => {
            const el = document.getElementById(modalId);
            if (el) bootstrap.Modal.getOrCreateInstance(el).show();
        });

        Livewire.on('hideModal', (modalId) => {
            const el = document.getElementById(modalId);
            if (el) {
                const modal = bootstrap.Modal.getInstance(el);
                if (modal) modal.hide();
            }
        });

        Livewire.on('showToast', (e) => {
            if (typeof Swal !== 'undefined') {
                Swal.fire(e.type === 'success' ? 'Success' : 'Notice', e.message, e.type === 'success' ? 'success' : 'error');
            }
        });
    });

    function printReturnReceipt() {
        window.print();
    }
</script>
@endpush