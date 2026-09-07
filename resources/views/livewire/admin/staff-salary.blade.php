<div class="container-fluid py-3">
    {{-- Top Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-wallet2 text-primary me-2"></i> Staff Salary Management
            </h3>
            <p class="text-muted mb-0">Track monthly salaries, partial payments, and month-to-month balance adjustments</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.manage-staff') }}" class="btn btn-outline-secondary">
                <i class="bi bi-people me-1"></i> Manage Staff
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                {{-- Search Bar --}}
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0"
                            placeholder="Search staff by name, phone, or email..."
                            wire:model.live.debounce.300ms="search">
                        @if(!empty($search))
                            <button class="btn btn-outline-secondary border-start-0" type="button" wire:click="$set('search', '')">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Month Filter & Quick Navigation --}}
                <div class="col-12 col-md-7">
                    <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                        <div class="d-flex align-items-center gap-1">
                            <button class="btn btn-sm btn-outline-secondary" wire:click="previousMonth" title="Previous Month">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <input type="month" class="form-control form-control-sm text-center fw-bold"
                                style="width: 170px;"
                                max="{{ date('Y-m') }}"
                                wire:model.live="selectedMonth">
                            <button class="btn btn-sm btn-outline-secondary" wire:click="nextMonth" title="Next Month"
                                @if(($selectedMonth ?: date('Y-m')) >= date('Y-m')) disabled @endif>
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                        <button class="btn btn-sm {{ ($selectedMonth ?: date('Y-m')) === date('Y-m') ? 'btn-primary text-white' : 'btn-light border' }} fw-semibold" wire:click="currentMonth">
                            This Month
                        </button>
                        <span class="badge bg-primary-subtle text-primary px-3 py-2 fs-6 fw-bold border border-primary-subtle">
                            <i class="bi bi-calendar3 me-1"></i> {{ $monthFormatted }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Metric Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 p-3 bg-primary-subtle text-primary me-3 fs-3">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Staff</div>
                        <div class="fs-4 fw-bold text-dark">{{ $totalStaffCount }}</div>
                        <small class="text-muted">Registered staff</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 p-3 bg-info-subtle text-info me-3 fs-3">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Basic Payroll</div>
                        <div class="fs-5 fw-bold text-dark">Rs. {{ number_format($totalMonthlyPayroll, 2) }}</div>
                        <small class="text-muted">Monthly basic sum</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 p-3 bg-success-subtle text-success me-3 fs-3">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Paid This Month</div>
                        <div class="fs-5 fw-bold text-success">Rs. {{ number_format($totalPaidThisMonth, 2) }}</div>
                        <small class="text-muted">Disbursed for {{ $monthFormatted }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 p-3 bg-warning-subtle text-warning me-3 fs-3">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Remaining Due</div>
                        <div class="fs-5 fw-bold text-warning">Rs. {{ number_format($totalRemainingDue, 2) }}</div>
                        <small class="text-muted">Pending balance</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Salary List Table --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-table text-primary me-2"></i> Salary Details for {{ $monthFormatted }}
                </h5>
                @if(!empty($search))
                    <span class="badge bg-light text-dark border">Searching: "{{ $search }}"</span>
                @endif
            </div>
            <div class="text-muted small">
                Showing {{ count($salaryList) }} staff records
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">#</th>
                            <th>Staff Name</th>
                            <th>Basic Salary</th>
                            <th>Prev. Adjustment</th>
                            <th>Net Payable</th>
                            <th>Amount Already Paid</th>
                            <th>Remaining Amount / Adjustment</th>
                            <th>Salary Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salaryList as $item)
                        <tr>
                            <td class="ps-4 fw-semibold text-muted">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2 bg-primary-subtle text-primary fw-bold">
                                        {{ strtoupper(substr($item['name'] ?? 'S', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $item['name'] }}</div>
                                        <small class="text-muted">
                                            @if(!empty($item['contact']))
                                                <i class="bi bi-telephone me-1"></i> {{ $item['contact'] }}
                                            @elseif(!empty($item['email']))
                                                <i class="bi bi-envelope me-1"></i> {{ $item['email'] }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">Rs. {{ number_format($item['basic_salary'], 2) }}</span>
                            </td>
                            <td>
                                @if($item['previous_adjustment'] < 0)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold"
                                        title="Deducted from previous month's overpayment">
                                        <i class="bi bi-dash-circle me-1"></i> Rs. {{ number_format(abs($item['previous_adjustment']), 2) }}
                                    </span>
                                @elseif($item['previous_adjustment'] > 0)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
                                        +Rs. {{ number_format($item['previous_adjustment'], 2) }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold text-dark">Rs. {{ number_format($item['net_payable'], 2) }}</span>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-bold text-success">Rs. {{ number_format($item['paid_amount'], 2) }}</span>
                                    @if($item['net_payable'] > 0)
                                        @php
                                            $percent = min(100, round(($item['paid_amount'] / $item['net_payable']) * 100));
                                        @endphp
                                        <div class="progress mt-1" style="height: 5px; width: 110px;">
                                            <div class="progress-bar {{ $percent >= 100 ? 'bg-success' : 'bg-primary' }}"
                                                role="progressbar" style="width: {{ $percent }}%"></div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($item['remaining_amount'] > 0)
                                    <span class="fw-bold text-warning">
                                        Rs. {{ number_format($item['remaining_amount'], 2) }}
                                    </span>
                                @elseif($item['remaining_amount'] < 0)
                                    <span class="fw-bold text-info" title="Extra amount paid, will be deducted next month">
                                        <i class="bi bi-arrow-up-right me-1"></i> -Rs. {{ number_format(abs($item['remaining_amount']), 2) }}
                                        <small class="d-block text-muted" style="font-size: 0.72rem;">Extra (Next Month Adj)</small>
                                    </span>
                                @else
                                    <span class="fw-bold text-muted">Rs. 0.00</span>
                                @endif
                            </td>
                            <td>
                                @if($item['status'] === 'paid')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i> Paid
                                    </span>
                                @elseif($item['status'] === 'partial')
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                        <i class="bi bi-hourglass-split me-1"></i> Partially Paid
                                    </span>
                                @elseif($item['status'] === 'overpaid')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                                        <i class="bi bi-arrow-up-circle-fill me-1"></i> Overpaid
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                        <i class="bi bi-clock me-1"></i> Pending
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1">
                                    <button class="btn btn-sm btn-info text-white" wire:click="openHistoryModal({{ $item['staff_id'] }})" title="View Staff Salary Details">
                                        <i class="bi bi-eye me-1"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-primary" wire:click="openPayModal({{ $item['staff_id'] }})" title="Record Salary Payment">
                                        <i class="bi bi-cash-coin me-1"></i> Pay Salary
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="bi bi-search display-4 d-block mb-2 text-muted"></i>
                                <span class="fs-6">No staff members found matching criteria</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white border-top py-3">
                <div class="d-flex justify-content-center">
                    {{ $staffMembers->links('livewire.custom-pagination') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Pay Salary Modal --}}
    @if($showPayModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-cash-coin me-2"></i> Record Salary Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closePayModal"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- Summary Box --}}
                    <div class="card bg-light border-0 rounded-3 mb-4">
                        <div class="card-body p-3">
                            <div class="row g-2 align-items-center">
                                <div class="col-12 col-md-6">
                                    <span class="text-muted small text-uppercase fw-semibold">Staff Member</span>
                                    <h5 class="fw-bold mb-0 text-dark">{{ $payStaffName }}</h5>
                                </div>
                                <div class="col-12 col-md-6 text-md-end">
                                    <span class="badge bg-primary px-3 py-2 fs-6">
                                        <i class="bi bi-calendar-event me-1"></i> {{ $monthFormatted }}
                                    </span>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="row text-center g-2">
                                <div class="col-6 col-md-3">
                                    <span class="text-muted small d-block">Basic Salary</span>
                                    <span class="fw-bold fs-6 text-dark">Rs. {{ number_format($payBasicSalary, 2) }}</span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <span class="text-muted small d-block">Prev. Adjustment</span>
                                    <span class="fw-bold fs-6 {{ $payPrevAdjustment < 0 ? 'text-danger' : 'text-muted' }}">
                                        {{ $payPrevAdjustment < 0 ? '-Rs. ' . number_format(abs($payPrevAdjustment), 2) : 'Rs. 0.00' }}
                                    </span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <span class="text-muted small d-block">Net Payable</span>
                                    <span class="fw-bold fs-6 text-primary">Rs. {{ number_format($payNetPayable, 2) }}</span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <span class="text-muted small d-block">Remaining Due</span>
                                    <span class="fw-bold fs-6 {{ $payRemaining > 0 ? 'text-warning' : ($payRemaining < 0 ? 'text-info' : 'text-success') }}">
                                        {{ $payRemaining < 0 ? 'Extra: Rs. ' . number_format(abs($payRemaining), 2) : 'Rs. ' . number_format($payRemaining, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Form --}}
                    <form wire:submit.prevent="recordPayment">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Payment Amount (Rs.) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rs.</span>
                                    <input type="number" step="0.01" min="0.01"
                                        class="form-control @error('paymentAmount') is-invalid @enderror"
                                        wire:model="paymentAmount" placeholder="Enter amount to pay" required>
                                    @if($payRemaining > 0)
                                        <button class="btn btn-outline-secondary" type="button" wire:click="fillRemainingAmount" title="Fill Remaining Balance">
                                            Pay Full
                                        </button>
                                    @endif
                                </div>
                                @error('paymentAmount') <span class="text-danger small">{{ $message }}</span> @enderror
                                <small class="text-muted">You may pay partially or enter extra (overpayment will automatically deduct from next month).</small>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                                <input type="date"
                                    min="{{ $payMonthStart }}"
                                    max="{{ $payMonthEnd }}"
                                    class="form-control @error('paymentDate') is-invalid @enderror"
                                    wire:model="paymentDate" required>
                                @error('paymentDate') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select @error('paymentMethod') is-invalid @enderror" wire:model="paymentMethod" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('paymentMethod') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Notes / Reference</label>
                                <input type="text" class="form-control @error('paymentNotes') is-invalid @enderror"
                                    wire:model="paymentNotes" placeholder="e.g. Voucher #102, Bank Ref">
                                @error('paymentNotes') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-light" wire:click="closePayModal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4" wire:loading.attr="disabled">
                                <span wire:loading.remove><i class="bi bi-check-circle me-1"></i> Save Payment</span>
                                <span wire:loading><i class="spinner-border spinner-border-sm me-1"></i> Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- View Staff Salary Details & History Modal --}}
    @if($showHistoryModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5); z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg">
                {{-- Clean White Modal Header --}}
                <div class="modal-header bg-white border-bottom py-3 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-circle bg-primary text-white fw-bold shadow-sm fs-5">
                            {{ strtoupper(substr($historyStaffName ?? 'S', 0, 1)) }}
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0">{{ $historyStaffName }}</h5>
                            <span class="text-muted small">Salary Details &bull; Basic: <strong class="text-dark">Rs. {{ number_format($historyStaffData['basic_salary'] ?? 0, 2) }}</strong></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" wire:click="closeHistoryModal"></button>
                </div>

                {{-- Subheader with Tabs and Month Selector (No Quick Months) --}}
                <div class="bg-light border-bottom px-4 py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        {{-- Tabs --}}
                        <div class="nav nav-pills bg-white p-1 rounded-3 border" role="tablist">
                            <button class="nav-link py-1 px-3 fw-semibold {{ $activeViewTab === 'month_details' ? 'active' : '' }}"
                                wire:click="$set('activeViewTab', 'month_details')">
                                <i class="bi bi-calendar-check me-1"></i> Month Salary & Payments
                            </button>
                            <button class="nav-link py-1 px-3 fw-semibold {{ $activeViewTab === 'all_months' ? 'active' : '' }}"
                                wire:click="$set('activeViewTab', 'all_months')">
                                <i class="bi bi-list-ul me-1"></i> All Months (List Wise)
                            </button>
                        </div>

                        {{-- Month Selector with Step Controls --}}
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small fw-bold text-nowrap">Month:</span>
                            <div class="d-flex align-items-center gap-1">
                                <button class="btn btn-sm btn-outline-secondary" wire:click="previousViewMonth" title="Previous Month">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <input type="month" class="form-control form-control-sm text-center fw-bold"
                                    style="width: 155px;"
                                    max="{{ date('Y-m') }}"
                                    wire:model.live="viewMonth">
                                <button class="btn btn-sm btn-outline-secondary" wire:click="nextViewMonth" title="Next Month"
                                    @if(($viewMonth ?: date('Y-m')) >= date('Y-m')) disabled @endif>
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-body p-4" id="printablePayslip">
                    {{-- TAB 1: Selected Month Details & Payment List --}}
                    @if($activeViewTab === 'month_details')
                        {{-- Printable Header (Print Only) --}}
                        <div class="d-none d-print-block text-center mb-4">
                            <h3 class="fw-bold mb-1">SALARY PAYSLIP</h3>
                            <p class="text-muted mb-0">Employee: {{ $historyStaffName }} | Period: {{ $historyStaffData['month_formatted'] ?? $monthFormatted }}</p>
                        </div>

                        {{-- Month Overview Banner --}}
                        <div class="card border-0 bg-light rounded-3 p-3 mb-4 border">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="p-2 rounded-3 bg-primary text-white fs-4 d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                                        <i class="bi bi-calendar3"></i>
                                    </div>
                                    <div>
                                        <span class="text-muted small fw-bold text-uppercase d-block">Viewing Salary For</span>
                                        <h4 class="fw-bold text-dark mb-0">{{ $historyStaffData['month_formatted'] ?? $monthFormatted }}</h4>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    @if(($historyStaffData['status'] ?? '') === 'paid')
                                        <span class="badge bg-success px-3 py-2 fs-6">
                                            <i class="bi bi-check-circle-fill me-1"></i> Fully Paid
                                        </span>
                                    @elseif(($historyStaffData['status'] ?? '') === 'partial')
                                        <span class="badge bg-warning text-dark px-3 py-2 fs-6">
                                            <i class="bi bi-hourglass-split me-1"></i> Partially Paid
                                        </span>
                                    @elseif(($historyStaffData['status'] ?? '') === 'overpaid')
                                        <span class="badge bg-info text-dark px-3 py-2 fs-6">
                                            <i class="bi bi-arrow-up-circle-fill me-1"></i> Overpaid (Extra)
                                        </span>
                                    @else
                                        <span class="badge bg-secondary px-3 py-2 fs-6">
                                            <i class="bi bi-clock me-1"></i> Pending Payment
                                        </span>
                                    @endif

                                    @if(($historyStaffData['remaining_amount'] ?? 0) > 0)
                                    <button type="button" class="btn btn-sm btn-primary px-3"
                                        wire:click="payFromViewModal({{ $historyStaffId }}, '{{ $viewMonth }}')">
                                        <i class="bi bi-cash-coin me-1"></i> Pay Balance
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Modern KPI Stat Cards for Breakdown --}}
                        <div class="row g-3 mb-4">
                            {{-- Basic Salary --}}
                            <div class="col-12 col-sm-6 col-md">
                                <div class="salary-metric-card">
                                    <div class="text-muted small fw-bold text-uppercase mb-1">Basic Salary</div>
                                    <div class="fs-5 fw-bold text-dark">Rs. {{ number_format($historyStaffData['basic_salary'] ?? 0, 2) }}</div>
                                    <small class="text-muted">Standard base</small>
                                </div>
                            </div>

                            {{-- Previous Adjustment --}}
                            <div class="col-12 col-sm-6 col-md">
                                <div class="salary-metric-card {{ ($historyStaffData['previous_adjustment'] ?? 0) < 0 ? 'border-danger-subtle bg-danger-subtle' : '' }}">
                                    <div class="text-muted small fw-bold text-uppercase mb-1">Prev. Adjustment</div>
                                    <div class="fs-5 fw-bold {{ ($historyStaffData['previous_adjustment'] ?? 0) < 0 ? 'text-danger' : 'text-dark' }}">
                                        {{ ($historyStaffData['previous_adjustment'] ?? 0) < 0 ? '-Rs. ' . number_format(abs($historyStaffData['previous_adjustment']), 2) : 'Rs. 0.00' }}
                                    </div>
                                    <small class="text-muted">{{ ($historyStaffData['previous_adjustment'] ?? 0) < 0 ? 'Deducted from last overpayment' : 'None' }}</small>
                                </div>
                            </div>

                            {{-- Net Payable --}}
                            <div class="col-12 col-sm-6 col-md">
                                <div class="salary-metric-card border-primary-subtle bg-primary-subtle">
                                    <div class="text-primary small fw-bold text-uppercase mb-1">Net Payable</div>
                                    <div class="fs-5 fw-bold text-primary">Rs. {{ number_format($historyStaffData['net_payable'] ?? 0, 2) }}</div>
                                    <small class="text-muted">Target for month</small>
                                </div>
                            </div>

                            {{-- Total Paid --}}
                            <div class="col-12 col-sm-6 col-md">
                                <div class="salary-metric-card border-success-subtle bg-success-subtle">
                                    <div class="text-success small fw-bold text-uppercase mb-1">Total Paid</div>
                                    <div class="fs-5 fw-bold text-success">Rs. {{ number_format($historyStaffData['paid_amount'] ?? 0, 2) }}</div>
                                    <small class="text-muted">Disbursed amount</small>
                                </div>
                            </div>

                            {{-- Remaining / Extra --}}
                            <div class="col-12 col-sm-6 col-md">
                                @php
                                    $rem = $historyStaffData['remaining_amount'] ?? 0;
                                @endphp
                                <div class="salary-metric-card {{ $rem > 0 ? 'border-warning-subtle bg-warning-subtle' : ($rem < 0 ? 'border-info-subtle bg-info-subtle' : '') }}">
                                    <div class="small fw-bold text-uppercase mb-1 {{ $rem > 0 ? 'text-warning' : ($rem < 0 ? 'text-info' : 'text-muted') }}">
                                        {{ $rem < 0 ? 'Extra (Next Adj)' : 'Remaining Due' }}
                                    </div>
                                    <div class="fs-5 fw-bold {{ $rem > 0 ? 'text-warning' : ($rem < 0 ? 'text-info' : 'text-success') }}">
                                        {{ $rem < 0 ? '-Rs. ' . number_format(abs($rem), 2) : 'Rs. ' . number_format($rem, 2) }}
                                    </div>
                                    <small class="text-muted">{{ $rem < 0 ? 'Carries to next month' : ($rem > 0 ? 'Pending payment' : 'Settled') }}</small>
                                </div>
                            </div>
                        </div>

                        {{-- Itemized Payment List for Selected Month (List-Wise) --}}
                        <div class="card border rounded-3 p-3 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="bi bi-clock-history text-primary me-2"></i> Payment Transactions for {{ $historyStaffData['month_formatted'] ?? $monthFormatted }}
                                </h6>
                                <span class="badge bg-light text-dark border">{{ count($historyPayments) }} payment(s) recorded</span>
                            </div>

                            @if(count($historyPayments) > 0)
                            <div class="table-responsive">
                                <table class="table modal-salary-table align-middle border mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-3" style="width: 50px;">#</th>
                                            <th>Payment Date</th>
                                            <th>Amount Paid</th>
                                            <th>Payment Method</th>
                                            <th>Notes / Ref</th>
                                            <th>Recorded By</th>
                                            <th class="text-end pe-3 d-print-none">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($historyPayments as $p)
                                        <tr>
                                            <td class="ps-3 fw-semibold text-muted">{{ $loop->iteration }}</td>
                                            <td>
                                                <i class="bi bi-calendar-check text-muted me-1"></i>
                                                {{ \Carbon\Carbon::parse($p['payment_date'])->format('d M, Y') }}
                                            </td>
                                            <td class="fw-bold text-success fs-6">
                                                Rs. {{ number_format($p['amount'], 2) }}
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border text-capitalize px-2 py-1">
                                                    {{ str_replace('_', ' ', $p['payment_method']) }}
                                                </span>
                                            </td>
                                            <td>{{ $p['notes'] ?: '-' }}</td>
                                            <td class="text-muted small">
                                                {{ $p['created_by']['name'] ?? 'System Admin' }}
                                            </td>
                                            <td class="text-end pe-3 d-print-none">
                                                <button class="btn btn-sm btn-outline-danger"
                                                    wire:click="confirmDeletePayment({{ $p['id'] }})" title="Delete Payment Record">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="text-center text-muted py-4 bg-light rounded-3">
                                <i class="bi bi-receipt display-6 d-block mb-2 text-muted"></i>
                                <span class="fw-semibold">No salary payments recorded for {{ $historyStaffData['month_formatted'] ?? $monthFormatted }}.</span>
                            </div>
                            @endif
                        </div>

                    {{-- TAB 2: All Months Salary History List-Wise --}}
                    @else
                        <div class="card border rounded-3 p-3 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="bi bi-calendar-range text-primary me-2"></i> Month-by-Month Salary History (List Wise)
                                </h6>
                                <span class="text-muted small">Showing past 12 months</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table modal-salary-table align-middle border mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-3">Month</th>
                                            <th>Basic Salary</th>
                                            <th>Prev. Adjustment</th>
                                            <th>Net Payable</th>
                                            <th>Amount Paid</th>
                                            <th>Remaining / Extra</th>
                                            <th>Status</th>
                                            <th class="text-end pe-3">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($historyMonthList as $m)
                                        <tr class="{{ $viewMonth === $m['month_key'] ? 'active-month-row' : '' }}" style="cursor: pointer;"
                                            wire:click="selectViewMonth('{{ $m['month_key'] }}')">
                                            <td class="ps-3 fw-bold text-dark">
                                                <i class="bi bi-calendar-month text-primary me-1"></i> {{ $m['month_name'] }}
                                                @if($m['month_key'] === date('Y-m'))
                                                    <span class="badge bg-primary text-white ms-1" style="font-size: 0.68rem;">Current</span>
                                                @endif
                                            </td>
                                            <td>Rs. {{ number_format($m['basic_salary'], 2) }}</td>
                                            <td>
                                                @if($m['previous_adjustment'] < 0)
                                                    <span class="text-danger fw-semibold">-Rs. {{ number_format(abs($m['previous_adjustment']), 2) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="fw-bold text-dark">Rs. {{ number_format($m['net_payable'], 2) }}</td>
                                            <td class="fw-bold text-success">Rs. {{ number_format($m['paid_amount'], 2) }}</td>
                                            <td>
                                                @if($m['remaining_amount'] > 0)
                                                    <span class="fw-bold text-warning">Rs. {{ number_format($m['remaining_amount'], 2) }}</span>
                                                @elseif($m['remaining_amount'] < 0)
                                                    <span class="fw-bold text-info">-Rs. {{ number_format(abs($m['remaining_amount']), 2) }} (Extra)</span>
                                                @else
                                                    <span class="text-muted">Rs. 0.00</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($m['status'] === 'paid')
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Paid</span>
                                                @elseif($m['status'] === 'partial')
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Partial</span>
                                                @elseif($m['status'] === 'overpaid')
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Overpaid</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Pending</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-3">
                                                <button class="btn btn-sm btn-outline-primary"
                                                    wire:click.stop="selectViewMonth('{{ $m['month_key'] }}')">
                                                    <i class="bi bi-eye me-1"></i> View Month
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="modal-footer bg-white border-top py-2 px-4 d-flex justify-content-between">
                    <div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="printPayslipSection()">
                            <i class="bi bi-printer me-1"></i> Print Payslip
                        </button>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm px-4" wire:click="closeHistoryModal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Confirm Delete Payment Modal --}}
    @if($showDeletePaymentModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5); z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white py-2">
                    <h6 class="modal-title fw-bold">Delete Payment</h6>
                    <button type="button" class="btn-close btn-close-white" wire:click="cancelDeletePayment"></button>
                </div>
                <div class="modal-body text-center p-3">
                    <i class="bi bi-exclamation-triangle text-danger display-5 d-block mb-2"></i>
                    <p class="mb-0">Are you sure you want to delete this payment record? Balances will be recalculated immediately.</p>
                </div>
                <div class="modal-footer justify-content-center p-2 bg-light border-0">
                    <button type="button" class="btn btn-sm btn-secondary" wire:click="cancelDeletePayment">Cancel</button>
                    <button type="button" class="btn btn-sm btn-danger" wire:click="deletePayment">Delete</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
    .avatar-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Scoped styling to override global layout th colors inside modals */
    .modal-salary-table th {
        background: #f1f5f9 !important;
        color: #1e293b !important;
        font-weight: 700 !important;
        font-size: 0.78rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        border-bottom: 2px solid #cbd5e1 !important;
        padding: 0.75rem 1rem !important;
    }

    .modal-salary-table td {
        background: #ffffff !important;
        color: #0f172a !important;
        padding: 0.75rem 1rem !important;
        border-bottom: 1px solid #f1f5f9 !important;
        vertical-align: middle !important;
    }

    .modal-salary-table tbody tr:hover td {
        background-color: #f8fafc !important;
    }

    .modal-salary-table tr.active-month-row td {
        background-color: #eff6ff !important;
        font-weight: 600;
    }

    .salary-metric-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #ffffff;
        padding: 1rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .salary-metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .nav-pills .nav-link {
        color: #475569;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .nav-pills .nav-link.active {
        background-color: #16285A !important;
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(22, 40, 90, 0.25);
    }

    @media print {
        body * {
            visibility: hidden;
        }
        #printablePayslip, #printablePayslip * {
            visibility: visible;
        }
        #printablePayslip {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function printPayslipSection() {
        window.print();
    }
</script>
@endpush
