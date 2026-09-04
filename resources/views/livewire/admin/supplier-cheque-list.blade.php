<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-file-earmark-check text-primary me-2"></i> Supplier Cheque Management
            </h3>
            <p class="text-muted mb-0">Record, track, and realize cheques issued to suppliers (CHEQ SHEETS)</p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{-- View Mode Switcher --}}
            <div class="btn-group me-1" role="group" aria-label="View Mode">
                <button type="button" 
                    class="btn btn-sm {{ $viewMode === 'table' ? 'btn-primary' : 'btn-outline-primary' }}" 
                    wire:click="setViewMode('table')"
                    title="Standard Table View">
                    <i class="bi bi-table me-1"></i> Table View
                </button>
                <button type="button" 
                    class="btn btn-sm {{ $viewMode === 'sheet' ? 'btn-primary' : 'btn-outline-primary' }}" 
                    wire:click="setViewMode('sheet')"
                    title="Cheque Sheet Ledger View">
                    <i class="bi bi-file-spreadsheet me-1"></i> Cheq Sheet View
                </button>
            </div>

            {{-- Print Cheq Sheet --}}
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print Current View">
                <i class="bi bi-printer me-1"></i> Print Sheet
            </button>

            {{-- Export CSV --}}
            <button wire:click="exportCSV" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
            </button>

            {{-- Add Cheque --}}
            <button type="button" class="btn btn-sm btn-primary" wire:click="openAddModal">
                <i class="bi bi-plus-circle me-1"></i> Add Supplier Cheque
            </button>
        </div>
    </div>

    {{-- Statistics Cards (Hidden on print) --}}
    <div class="row g-3 mb-4 no-print">
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-warning border-4 shadow-sm h-100 py-2">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Pending Cheques</div>
                            <div class="h5 mb-0 fw-bold text-dark">Rs. {{ number_format($pendingAmount, 2) }}</div>
                            <div class="text-xs text-muted mt-1">{{ $pendingCount }} Cheque(s)</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clock-history fs-2 text-warning opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-success border-4 shadow-sm h-100 py-2">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Cleared / Completed</div>
                            <div class="h5 mb-0 fw-bold text-dark">Rs. {{ number_format($completeAmount, 2) }}</div>
                            <div class="text-xs text-muted mt-1">{{ $completeCount }} Cheque(s)</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check2-circle fs-2 text-success opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-danger border-4 shadow-sm h-100 py-2">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Returned Cheques</div>
                            <div class="h5 mb-0 fw-bold text-dark">Rs. {{ number_format($returnAmount, 2) }}</div>
                            <div class="text-xs text-muted mt-1">{{ $returnCount }} Cheque(s)</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-arrow-counterclockwise fs-2 text-danger opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-primary border-4 shadow-sm h-100 py-2">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Cheques Issued</div>
                            <div class="h5 mb-0 fw-bold text-dark">Rs. {{ number_format($totalAmount, 2) }}</div>
                            <div class="text-xs text-muted mt-1">{{ $totalCount }} Cheque(s)</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-wallet2 fs-2 text-primary opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Container Card --}}
    <div class="card shadow-sm border-0 mb-4">
        {{-- Filter Header (Hidden in Print) --}}
        <div class="card-header bg-white py-3 no-print">
            <div class="row g-2 align-items-center">
                {{-- Search --}}
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" 
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search cheque no, bank, supplier...">
                    </div>
                </div>

                {{-- Supplier Filter --}}
                <div class="col-md-2">
                    <select wire:model.live="supplierFilter" class="form-select form-select-sm">
                        <option value="all">All Suppliers</option>
                        @foreach($suppliers as $supp)
                            <option value="{{ $supp->id }}">{{ $supp->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Bank Filter --}}
                <div class="col-md-2">
                    <select wire:model.live="bankFilter" class="form-select form-select-sm">
                        <option value="all">All Banks</option>
                        @foreach($bankNames as $bName)
                            <option value="{{ $bName }}">{{ $bName }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter --}}
                <div class="col-md-2">
                    <select wire:model.live="statusFilter" class="form-select form-select-sm">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="complete">Complete</option>
                        <option value="return">Returned</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                {{-- Date Range --}}
                <div class="col-md-3 d-flex align-items-center gap-1">
                    <input type="date" class="form-control form-control-sm" wire:model.live="dateFrom" title="From date">
                    <span class="text-muted">–</span>
                    <input type="date" class="form-control form-control-sm" wire:model.live="dateTo" title="To date">
                    @if($search || $statusFilter !== 'all' || $supplierFilter !== 'all' || $bankFilter !== 'all' || $dateFrom || $dateTo)
                    <button class="btn btn-sm btn-outline-secondary" wire:click="clearFilters" title="Clear all filters">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    @endif
                </div>
            </div>

            {{-- Secondary Row --}}
            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-list-check me-1"></i> {{ $cheques->total() }} Record(s)
                    </span>
                    <span class="small text-muted">
                        Active Mode: <strong>{{ $viewMode === 'sheet' ? 'CHEQ SHEET (Ledger)' : 'Standard Table' }}</strong>
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <label class="text-sm text-muted fw-medium mb-0">Show</label>
                    <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 85px;">
                        <option value="30">30</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                        <option value="500">500</option>
                        <option value="all">All</option>
                    </select>
                    <span class="text-sm text-muted">entries</span>
                </div>
            </div>
        </div>

        {{-- Printable Header (Visible only in print) --}}
        <div class="print-only p-3 text-center border-bottom mb-2" style="display: none;">
            <h2 class="fw-bold mb-1" style="letter-spacing: 2px;">CHEQ SHEETS</h2>
            <div class="text-muted small">
                Printed on {{ now()->format('d/m/Y h:i A') }} 
                @if($dateFrom || $dateTo)
                    | Period: {{ $dateFrom ?: 'Beginning' }} to {{ $dateTo ?: 'Current' }}
                @endif
            </div>
        </div>

        {{-- Card Body --}}
        <div class="card-body p-0 overflow-auto">

            @if($viewMode === 'sheet')
            {{-- ============================================================ --}}
            {{-- VIEW MODE 2: CHEQ SHEET VIEW (MATCHING USER'S ATTACHED PDF) --}}
            {{-- ============================================================ --}}
            <div class="table-responsive cheq-sheet-wrapper">
                <table class="table table-bordered cheq-sheet-table mb-0 align-middle">
                    <thead>
                        <tr class="table-secondary text-center">
                            <th style="width: 130px;">DATE</th>
                            <th style="width: 140px;">DAY</th>
                            <th style="width: 140px;">CHEQUE NO</th>
                            <th style="width: 110px;">BANK</th>
                            <th>PAYEE / SUPPLIER</th>
                            <th class="text-end" style="width: 170px;">AMOUNT</th>
                            <th class="text-center no-print" style="width: 100px;">STATUS</th>
                            <th class="text-center no-print" style="width: 90px;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cheques as $cheque)
                        <tr wire:key="sheet-cheque-{{ $cheque->id }}" class="sheet-row {{ $cheque->status === 'complete' ? 'sheet-complete' : ($cheque->status === 'return' ? 'sheet-return' : '') }}">
                            {{-- Date --}}
                            <td class="text-center fw-semibold">
                                {{ $cheque->cheque_date ? $cheque->cheque_date->format('m/d/Y') : '-' }}
                                @if($cheque->is_holiday)
                                    <br><span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle py-0 px-1 small-badge" title="{{ $cheque->holiday_reason }}">
                                        Holiday
                                    </span>
                                @endif
                            </td>

                            {{-- Day of Week --}}
                            <td class="text-center {{ in_array($cheque->day_name, ['Saturday', 'Sunday']) ? 'text-danger fw-bold' : '' }}">
                                {{ $cheque->day_name ?: '-' }}
                            </td>

                            {{-- Cheque No --}}
                            <td class="text-center fw-bold font-monospace text-primary">
                                {{ $cheque->cheque_number }}
                            </td>

                            {{-- Bank --}}
                            <td class="text-center fw-semibold">
                                {{ $cheque->bank_name }}
                            </td>

                            {{-- Payee / Supplier --}}
                            <td class="fw-bold ps-3 text-uppercase">
                                {{ $cheque->display_payee }}
                                @if($cheque->notes)
                                    <span class="text-muted fw-normal small d-block">({{ $cheque->notes }})</span>
                                @endif
                            </td>

                            {{-- Amount --}}
                            <td class="text-end fw-bold font-monospace pe-3">
                                {{ number_format($cheque->amount, 2) }}
                            </td>

                            {{-- Status Badge (No print) --}}
                            <td class="text-center no-print">
                                @if($cheque->status === 'complete')
                                    <span class="badge bg-success">Complete</span>
                                @elseif($cheque->status === 'return')
                                    <span class="badge bg-danger">Return</span>
                                @elseif($cheque->status === 'cancelled')
                                    <span class="badge bg-secondary">Cancelled</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>

                            {{-- Actions (No print) --}}
                            <td class="text-center no-print">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary p-1 py-0 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        @if($cheque->status !== 'complete')
                                        <li>
                                            <button class="dropdown-item text-success" wire:click="completeCheque({{ $cheque->id }})">
                                                <i class="bi bi-check-circle me-2"></i> Mark Complete / Cleared
                                            </button>
                                        </li>
                                        @endif
                                        @if($cheque->status !== 'return')
                                        <li>
                                            <button class="dropdown-item text-danger" wire:click="returnCheque({{ $cheque->id }})">
                                                <i class="bi bi-arrow-counterclockwise me-2"></i> Mark Returned
                                            </button>
                                        </li>
                                        @endif
                                        <li>
                                            <button class="dropdown-item text-primary" wire:click="openEditModal({{ $cheque->id }})">
                                                <i class="bi bi-pencil-square me-2"></i> Edit Cheque
                                            </button>
                                        </li>
                                        @if($cheque->cheque_photo_url)
                                        <li>
                                            <a class="dropdown-item text-info" href="{{ $this->resolveChequePhotoUrl($cheque->cheque_photo_url) }}" target="_blank">
                                                <i class="bi bi-image me-2"></i> View Photo
                                            </a>
                                        </li>
                                        @endif
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item text-danger" 
                                                onclick="if(confirm('Delete this supplier cheque?')) { @this.deleteCheque({{ $cheque->id }}); }">
                                                <i class="bi bi-trash me-2"></i> Delete
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox display-6 d-block mb-2 text-secondary"></i>
                                No supplier cheques match your criteria.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold font-monospace">
                        <tr>
                            <td colspan="5" class="text-end pe-3">PAGE TOTAL:</td>
                            <td class="text-end pe-3 text-primary fs-6">
                                Rs. {{ number_format($cheques->sum('amount'), 2) }}
                            </td>
                            <td colspan="2" class="no-print"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @else
            {{-- ============================================================ --}}
            {{-- VIEW MODE 1: STANDARD TABLE VIEW WITH ADVANCED CONTROLS     --}}
            {{-- ============================================================ --}}
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">
                                <input type="checkbox" class="form-check-input" id="selectAllSupplierCheques" onclick="toggleAllSupplierRows(this)">
                            </th>
                            <th class="ps-3">Cheque No</th>
                            <th>Date / Day</th>
                            <th class="text-center">Bank</th>
                            <th>Payee / Supplier</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Photo</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cheques as $cheque)
                        <tr wire:key="cheque-row-{{ $cheque->id }}" class="table-row" data-id="{{ $cheque->id }}">
                            <td class="ps-4" onclick="event.stopPropagation();">
                                <input type="checkbox" class="form-check-input row-checkbox" onchange="toggleSupplierRowHighlight(this)">
                            </td>
                            <td class="ps-3 fw-bold font-monospace text-primary">
                                {{ $cheque->cheque_number }}
                            </td>
                            <td>
                                <div>{{ $cheque->cheque_date ? $cheque->cheque_date->format('d/m/Y') : '-' }}</div>
                                <small class="text-muted">{{ $cheque->day_name }}</small>
                                @if($cheque->is_holiday)
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle py-0 px-1 ms-1" style="font-size: 0.68rem;" title="{{ $cheque->holiday_reason }}">
                                        Holiday
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $cheque->bank_name }}</span>
                            </td>
                            <td class="fw-semibold">
                                {{ $cheque->display_payee }}
                                @if($cheque->purchase_payment_id)
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle ms-1" style="font-size: 0.7rem;" title="Linked to Payment #{{ $cheque->purchase_payment_id }}">
                                        Linked Receipt
                                    </span>
                                @endif
                            </td>
                            <td class="text-end fw-bold font-monospace">
                                Rs. {{ number_format($cheque->amount, 2) }}
                            </td>
                            <td class="text-center">
                                @if($cheque->status === 'complete')
                                    <span class="badge bg-success">Complete</span>
                                @elseif($cheque->status === 'return')
                                    <span class="badge bg-danger">Returned</span>
                                @elseif($cheque->status === 'cancelled')
                                    <span class="badge bg-secondary">Cancelled</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($cheque->cheque_photo_url)
                                    <a href="{{ $this->resolveChequePhotoUrl($cheque->cheque_photo_url) }}" target="_blank" class="btn btn-sm btn-outline-info p-1 py-0" title="View Cheque Photo">
                                        <i class="bi bi-image"></i>
                                    </a>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-gear-fill me-1"></i> Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        @if($cheque->status !== 'complete')
                                        <li>
                                            <button class="dropdown-item text-success" wire:click="completeCheque({{ $cheque->id }})">
                                                <i class="bi bi-check2-circle me-2"></i> Mark as Cleared
                                            </button>
                                        </li>
                                        @endif
                                        @if($cheque->status !== 'return')
                                        <li>
                                            <button class="dropdown-item text-danger" wire:click="returnCheque({{ $cheque->id }})">
                                                <i class="bi bi-arrow-counterclockwise me-2"></i> Mark as Returned
                                            </button>
                                        </li>
                                        @endif
                                        <li>
                                            <button class="dropdown-item text-primary" wire:click="openEditModal({{ $cheque->id }})">
                                                <i class="bi bi-pencil-square me-2"></i> Edit Cheque
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item text-danger" 
                                                onclick="if(confirm('Are you sure you want to delete this cheque record?')) { @this.deleteCheque({{ $cheque->id }}); }">
                                                <i class="bi bi-trash me-2"></i> Delete Record
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox display-6 d-block mb-2 text-secondary"></i>
                                No supplier cheques found matching the criteria.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif

            {{-- Pagination (Hidden on print) --}}
            @if($cheques->hasPages())
            <div class="card-footer bg-white no-print">
                <div class="d-flex justify-content-center">
                    {{ $cheques->links('livewire.custom-pagination') }}
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL 1: ADD SUPPLIER CHEQUE                                 --}}
    {{-- ============================================================ --}}
    @if($showAddModal)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2"></i> Add Supplier Cheque
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeAddModal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        {{-- Cheque Number --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cheque Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('newCheque.cheque_number') is-invalid @enderror"
                                wire:model="newCheque.cheque_number" placeholder="e.g. 632576 or CB 548279">
                            @error('newCheque.cheque_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Bank Name --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" list="add_sl_banks_list" class="form-control @error('newCheque.bank_name') is-invalid @enderror"
                                wire:model="newCheque.bank_name" placeholder="Select or enter bank name">
                            <datalist id="add_sl_banks_list">
                                <option value="BOC">Bank of Ceylon (BOC)</option>
                                <option value="People's Bank">People's Bank</option>
                                <option value="COM">Commercial Bank (COM)</option>
                                <option value="HNB">Hatton National Bank (HNB)</option>
                                <option value="Sampath Bank">Sampath Bank</option>
                                <option value="Seylan Bank">Seylan Bank</option>
                                <option value="DFCC Bank">DFCC Bank</option>
                                <option value="NTB">Nations Trust Bank (NTB)</option>
                                <option value="NDB">National Development Bank (NDB)</option>
                                <option value="Pan Asia Bank">Pan Asia Bank</option>
                                <option value="Union Bank">Union Bank</option>
                                <option value="Cargills Bank">Cargills Bank</option>
                                <option value="Amana Bank">Amana Bank</option>
                            </datalist>
                            @error('newCheque.bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Cheque Date --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cheque Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('newCheque.cheque_date') is-invalid @enderror"
                                wire:model.live="newCheque.cheque_date">
                            @error('newCheque.cheque_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if(!empty($newCheque['cheque_date']) && \App\Models\Holiday::isHoliday($newCheque['cheque_date']))
                                <div class="alert alert-danger py-1 px-2 mt-2 mb-0 small border-0">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <strong>Holiday:</strong> {{ \App\Models\Holiday::getHolidayReason($newCheque['cheque_date']) }}. Cheque realization is blocked on this date.
                                </div>
                            @endif
                        </div>

                        {{-- Amount --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cheque Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control @error('newCheque.amount') is-invalid @enderror"
                                wire:model="newCheque.amount" placeholder="0.00">
                            @error('newCheque.amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Supplier Dropdown --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Select Supplier (Optional)</label>
                            <select class="form-select @error('newCheque.supplier_id') is-invalid @enderror"
                                wire:model.live="newCheque.supplier_id">
                                <option value="">-- Select Registered Supplier --</option>
                                @foreach($suppliers as $supp)
                                    <option value="{{ $supp->id }}">{{ $supp->name }}</option>
                                @endforeach
                            </select>
                            @error('newCheque.supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Payee Name (Free text / Auto-filled) --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payee Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('newCheque.payee_name') is-invalid @enderror"
                                wire:model="newCheque.payee_name" placeholder="e.g. ESKEMA RC, TPS, HUIDA">
                            @error('newCheque.payee_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Notes --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes / Purpose (Optional)</label>
                            <input type="text" class="form-control" wire:model="newCheque.notes" placeholder="Additional details or reference">
                        </div>

                        {{-- Cheque Photo --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">Cheque Photo (Optional)</label>
                            <input type="file" class="form-control @error('newChequePhoto') is-invalid @enderror"
                                wire:model="newChequePhoto" accept="image/*">
                            @error('newChequePhoto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if ($newChequePhoto)
                            <div class="mt-2">
                                <img src="{{ $newChequePhoto->temporaryUrl() }}" class="img-thumbnail" style="max-height: 120px;">
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" wire:click="closeAddModal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="saveNewCheque" wire:loading.attr="disabled">
                        <span wire:loading wire:target="saveNewCheque" class="spinner-border spinner-border-sm me-1"></span>
                        <i class="bi bi-check-lg me-1" wire:loading.remove wire:target="saveNewCheque"></i> Save Cheque
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ============================================================ --}}
    {{-- MODAL 2: EDIT SUPPLIER CHEQUE                                --}}
    {{-- ============================================================ --}}
    @if($showEditModal)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square me-2"></i> Edit Supplier Cheque
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeEditModal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        {{-- Cheque Number --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cheque Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('editChequeNumber') is-invalid @enderror"
                                wire:model="editChequeNumber">
                            @error('editChequeNumber')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Bank Name --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" list="edit_sl_banks_list" class="form-control @error('editBankName') is-invalid @enderror"
                                wire:model="editBankName">
                            <datalist id="edit_sl_banks_list">
                                <option value="BOC">Bank of Ceylon (BOC)</option>
                                <option value="People's Bank">People's Bank</option>
                                <option value="COM">Commercial Bank (COM)</option>
                                <option value="HNB">Hatton National Bank (HNB)</option>
                                <option value="Sampath Bank">Sampath Bank</option>
                                <option value="Seylan Bank">Seylan Bank</option>
                                <option value="DFCC Bank">DFCC Bank</option>
                                <option value="NTB">Nations Trust Bank (NTB)</option>
                                <option value="NDB">National Development Bank (NDB)</option>
                                <option value="Pan Asia Bank">Pan Asia Bank</option>
                                <option value="Union Bank">Union Bank</option>
                                <option value="Cargills Bank">Cargills Bank</option>
                                <option value="Amana Bank">Amana Bank</option>
                            </datalist>
                            @error('editBankName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Cheque Date --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cheque Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('editChequeDate') is-invalid @enderror"
                                wire:model.live="editChequeDate">
                            @error('editChequeDate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if(!empty($editChequeDate) && \App\Models\Holiday::isHoliday($editChequeDate))
                                <div class="alert alert-danger py-1 px-2 mt-2 mb-0 small border-0">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <strong>Holiday:</strong> {{ \App\Models\Holiday::getHolidayReason($editChequeDate) }}.
                                </div>
                            @endif
                        </div>

                        {{-- Amount --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cheque Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control @error('editChequeAmount') is-invalid @enderror"
                                wire:model="editChequeAmount">
                            @error('editChequeAmount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Supplier --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier</label>
                            <select class="form-select" wire:model="editSupplierId">
                                <option value="">-- Unlinked / Standalone --</option>
                                @foreach($suppliers as $supp)
                                    <option value="{{ $supp->id }}">{{ $supp->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Payee Name --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payee Name</label>
                            <input type="text" class="form-control" wire:model="editPayeeName">
                        </div>

                        {{-- Notes --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <input type="text" class="form-control" wire:model="editNotes">
                        </div>

                        {{-- Photo --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">Cheque Photo</label>
                            @if($editChequePhotoUrl)
                            <div class="mb-2">
                                <span class="text-xs text-muted d-block mb-1">Current Photo:</span>
                                <img src="{{ $this->resolveChequePhotoPreviewUrl($editChequePhotoUrl) }}" class="img-thumbnail" style="max-height: 120px;">
                            </div>
                            @endif
                            <input type="file" class="form-control @error('editChequePhoto') is-invalid @enderror"
                                wire:model="editChequePhoto" accept="image/*">
                            @error('editChequePhoto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if ($editChequePhoto)
                            <div class="mt-2">
                                <span class="text-xs text-success d-block mb-1">New Photo:</span>
                                <img src="{{ $editChequePhoto->temporaryUrl() }}" class="img-thumbnail" style="max-height: 120px;">
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" wire:click="closeEditModal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="updateCheque" wire:loading.attr="disabled">
                        <span wire:loading wire:target="updateCheque" class="spinner-border spinner-border-sm me-1"></span>
                        <i class="bi bi-check-lg me-1" wire:loading.remove wire:target="updateCheque"></i> Update Cheque
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
    /* Cheq Sheet Specific Table Styling - matching user's document */
    .cheq-sheet-table {
        border: 2px solid #212529 !important;
        font-size: 0.95rem;
    }
    .cheq-sheet-table th, 
    .cheq-sheet-table td {
        border: 1px solid #495057 !important;
        padding: 6px 10px;
    }
    .cheq-sheet-table thead th {
        background-color: #e9ecef !important;
        color: #000 !important;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .sheet-complete {
        background-color: rgba(25, 135, 84, 0.05);
    }
    .sheet-return {
        background-color: rgba(220, 53, 69, 0.08);
    }
    .small-badge {
        font-size: 0.65rem;
        padding: 2px 4px;
    }

    /* Row selection highlight */
    tr.selected-row,
    table tbody tr.selected-row,
    table.table tbody tr.selected-row {
        background-color: #223046 !important;
        color: #fff !important;
    }
    tr.selected-row td {
        background-color: #223046 !important;
        color: #fff !important;
    }
    tr.selected-row td .text-muted {
        color: #e0e0e0 !important;
    }

    /* Print styles */
    @media print {
        .no-print, 
        .sidebar, 
        .top-bar, 
        .navbar, 
        nav, 
        footer {
            display: none !important;
        }
        .print-only {
            display: block !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        body {
            background: #fff !important;
            color: #000 !important;
            font-size: 11pt;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .cheq-sheet-table {
            width: 100% !important;
            border-collapse: collapse !important;
            border: 2px solid #000 !important;
        }
        .cheq-sheet-table th, 
        .cheq-sheet-table td {
            border: 1px solid #000 !important;
            color: #000 !important;
            padding: 4px 6px !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function toggleSupplierRowHighlight(checkbox) {
        const row = checkbox.closest('tr');
        if (checkbox.checked) {
            row.classList.add('selected-row');
            row.style.backgroundColor = '#d4e6f1';
        } else {
            row.classList.remove('selected-row');
            row.style.backgroundColor = '';
            const selectAll = document.getElementById('selectAllSupplierCheques');
            if (selectAll) selectAll.checked = false;
        }
    }

    function toggleAllSupplierRows(selectAllCheckbox) {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
            toggleSupplierRowHighlight(checkbox);
        });
    }

    document.addEventListener('livewire:init', function() {
        Livewire.on('toast', (data) => {
            const type = data.type || 'info';
            const message = data.message || '';
            const colors = {
                success: '#198754',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#0dcaf0'
            };
            const toast = document.createElement('div');
            toast.style.cssText = `position:fixed;top:20px;right:20px;z-index:99999;padding:12px 20px;border-radius:6px;color:#fff;background:${colors[type]||colors.info};font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,.2);transition:opacity .5s;`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        });
    });
</script>
@endpush
