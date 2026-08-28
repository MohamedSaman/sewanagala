<div class="container-fluid py-3">

    {{-- Page Header --}}
    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-gear-fill text-success fs-2"></i>
        <div class="ms-3">
            <h1 class="h3 fw-bold mb-0">System Settings</h1>
            <p class="text-muted mb-0">Manage all system configurations.</p>
        </div>
    </div>

    {{-- Accordion --}}
    <div class="accordion" id="settingsAccordion">

        {{-- Holiday & Poya Calendar Accordion --}}
        <div class="accordion-item border-0 mb-4 shadow-sm rounded-4">
            <h2 class="accordion-header" id="headingHolidays">
                <button class="accordion-button fw-semibold bg-white text-dark rounded-4"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseHolidays" aria-expanded="true"
                    aria-controls="collapseHolidays">
                    <i class="bi bi-calendar-event fs-5 me-3 text-danger"></i>
                    Holiday & Poya Calendar (Cheque Restrictions)
                </button>
            </h2>
            <div id="collapseHolidays" class="accordion-collapse collapse show"
                aria-labelledby="headingHolidays" data-bs-parent="#settingsAccordion">
                <div class="accordion-body">
                    @php
                        $currentMonthCarbon = \Illuminate\Support\Carbon::createFromDate($calendarYear, $calendarMonth, 1);
                        $daysInMonth = $currentMonthCarbon->daysInMonth;
                        $startDayOfWeek = $currentMonthCarbon->dayOfWeek; // 0 for Sunday, 6 for Saturday
                        $todayDateStr = now()->format('Y-m-d');
                        
                        $holidaysByDate = $holidays->keyBy(function($h) {
                            return \Illuminate\Support\Carbon::parse($h->date)->format('Y-m-d');
                        });

                        $thisMonthHolidays = $holidays->filter(function($h) use ($calendarYear, $calendarMonth) {
                            $d = \Illuminate\Support\Carbon::parse($h->date);
                            return (int)$d->year === (int)$calendarYear && (int)$d->month === (int)$calendarMonth;
                        });
                    @endphp

                    {{-- Notice Alert --}}
                    <div class="alert alert-warning border-0 shadow-sm mb-4 d-flex align-items-center">
                        <i class="bi bi-shield-exclamation fs-3 me-3 text-warning"></i>
                        <div class="small">
                            <strong>Cheque Realization & Deposit Restriction:</strong>
                            Dates marked with <em>"Cheque Blocked"</em> prevent users from selecting them as cheque realization or deposit dates in billing, POS, customer receipt, and supplier payment modules.
                        </div>
                    </div>

                    {{-- Calendar Header Controls --}}
                    <div class="card shadow-sm border-0 mb-4 bg-light rounded-3">
                        <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-white btn-sm border shadow-sm" wire:click="prevCalendarMonth" title="Previous Month">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <button type="button" class="btn btn-white btn-sm border shadow-sm" wire:click="todayCalendarMonth" title="Current Month">
                                        Today
                                    </button>
                                    <button type="button" class="btn btn-white btn-sm border shadow-sm" wire:click="nextCalendarMonth" title="Next Month">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                                <h4 class="mb-0 fw-bold text-dark ms-2">
                                    {{ $currentMonthCarbon->format('F Y') }}
                                </h4>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle ms-2">
                                    {{ $thisMonthHolidays->count() }} Holiday(s) this month
                                </span>
                            </div>

                            <div>
                                <button class="btn btn-danger shadow-sm" wire:click="openAddHolidayModal">
                                    <i class="bi bi-plus-circle me-1"></i> Add Holiday / Poya Day
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Visual Calendar Grid --}}
                    <div class="card shadow-sm border-0 mb-4 overflow-hidden rounded-3">
                        <div class="calendar-grid-container">
                            {{-- Day Headers --}}
                            <div class="calendar-grid calendar-grid-header">
                                <div class="calendar-header-cell text-danger">Sun</div>
                                <div class="calendar-header-cell">Mon</div>
                                <div class="calendar-header-cell">Tue</div>
                                <div class="calendar-header-cell">Wed</div>
                                <div class="calendar-header-cell">Thu</div>
                                <div class="calendar-header-cell">Fri</div>
                                <div class="calendar-header-cell text-danger">Sat</div>
                            </div>

                            {{-- Days Cells --}}
                            <div class="calendar-grid">
                                {{-- Empty leading cells --}}
                                @for($i = 0; $i < $startDayOfWeek; $i++)
                                    <div class="calendar-cell calendar-cell-muted"></div>
                                @endfor

                                {{-- Days in Month --}}
                                @for($day = 1; $day <= $daysInMonth; $day++)
                                    @php
                                        $cellDate = sprintf('%04d-%02d-%02d', $calendarYear, $calendarMonth, $day);
                                        $dayCarbon = \Illuminate\Support\Carbon::createFromDate($calendarYear, $calendarMonth, $day);
                                        $isToday = ($cellDate === $todayDateStr);
                                        $isWeekend = in_array($dayCarbon->dayOfWeek, [0, 6]);
                                        $hasHoliday = $holidaysByDate->has($cellDate);
                                        $holidayItem = $hasHoliday ? $holidaysByDate->get($cellDate) : null;
                                    @endphp
                                    <div class="calendar-cell {{ $isToday ? 'is-today' : '' }} {{ $hasHoliday ? 'has-holiday' : '' }}">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="day-number {{ $isToday ? 'badge bg-primary text-white rounded-circle' : ($isWeekend ? 'text-danger fw-bold' : 'fw-bold text-dark') }}">
                                                {{ $day }}
                                            </span>
                                            @if(!$hasHoliday)
                                                <button type="button" class="btn btn-xs btn-link text-muted p-0 quick-add-btn" 
                                                        wire:click="openAddHolidayModal('{{ $cellDate }}')" 
                                                        title="Mark as holiday">
                                                    <i class="bi bi-plus-circle"></i>
                                                </button>
                                            @endif
                                        </div>

                                        @if($hasHoliday)
                                            <div class="holiday-card p-1 rounded-2 shadow-xs" 
                                                 wire:click="openEditHolidayModal({{ $holidayItem->id }})"
                                                 title="Click to edit: {{ $holidayItem->description }}">
                                                <div class="d-flex align-items-center mb-1">
                                                    <i class="bi bi-moon-stars-fill text-danger me-1 small"></i>
                                                    <span class="holiday-title text-truncate fw-semibold text-danger small">
                                                        {{ $holidayItem->description }}
                                                    </span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    @if($holidayItem->is_blocked_for_cheque)
                                                        <span class="badge bg-danger text-white px-1" style="font-size: 0.65rem;">
                                                            <i class="bi bi-lock-fill"></i> Blocked
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary text-white px-1" style="font-size: 0.65rem;">
                                                            Allowed
                                                        </span>
                                                    @endif
                                                    <button type="button" class="btn btn-xs text-danger p-0 ms-1 border-0 bg-transparent"
                                                            wire:click.stop="confirmDeleteHoliday({{ $holidayItem->id }})" 
                                                            title="Delete Holiday">
                                                        <i class="bi bi-x-circle-fill"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endfor

                                {{-- Trailing empty cells --}}
                                @php
                                    $totalCells = $startDayOfWeek + $daysInMonth;
                                    $trailingCells = (7 - ($totalCells % 7)) % 7;
                                @endphp
                                @for($i = 0; $i < $trailingCells; $i++)
                                    <div class="calendar-cell calendar-cell-muted"></div>
                                @endfor
                            </div>
                        </div>
                    </div>

                    {{-- All Configured Holidays Table --}}
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-list-check me-2 text-danger"></i> All Registered Holidays & Poya Days ({{ $holidays->count() }})
                            </h6>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-3 py-1">
                                <i class="bi bi-shield-x me-1"></i> {{ $holidays->where('is_blocked_for_cheque', true)->count() }} Cheque Blocked
                            </span>
                        </div>
                        <div class="card-body p-0">
                            @if($holidays->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3" style="width: 160px;">Date</th>
                                            <th style="width: 120px;">Day</th>
                                            <th>Holiday / Event Description</th>
                                            <th class="text-center" style="width: 180px;">Cheque Settlement</th>
                                            <th style="width: 150px;">Created By</th>
                                            <th class="text-center pe-3" style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($holidays as $hItem)
                                        @php
                                            $hCarbon = \Illuminate\Support\Carbon::parse($hItem->date);
                                            $isPast = $hCarbon->isPast() && !$hCarbon->isToday();
                                        @endphp
                                        <tr class="{{ $isPast ? 'text-muted bg-light bg-opacity-25' : '' }}">
                                            <td class="ps-3 fw-bold text-dark">
                                                <i class="bi bi-calendar-event me-2 text-danger"></i>
                                                {{ $hCarbon->format('d M Y') }}
                                            </td>
                                            <td>
                                                <span class="badge {{ in_array($hCarbon->dayOfWeek, [0,6]) ? 'bg-danger bg-opacity-10 text-danger' : 'bg-secondary bg-opacity-10 text-secondary' }} border">
                                                    {{ $hCarbon->format('l') }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $hItem->description }}</div>
                                            </td>
                                            <td class="text-center">
                                                @if($hItem->is_blocked_for_cheque)
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1">
                                                        <i class="bi bi-shield-x me-1"></i> Blocked
                                                    </span>
                                                @else
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1">
                                                        <i class="bi bi-check-circle me-1"></i> Allowed
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="bi bi-person me-1"></i>{{ $hItem->creator->name ?? 'Admin' }}
                                                </small>
                                            </td>
                                            <td class="text-center pe-3">
                                                <button class="btn btn-sm btn-outline-primary me-1" 
                                                        wire:click="openEditHolidayModal({{ $hItem->id }})" 
                                                        title="Edit Holiday">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        wire:click="confirmDeleteHoliday({{ $hItem->id }})" 
                                                        title="Delete Holiday">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x display-4 d-block mb-3 text-secondary"></i>
                                No holidays or Poya days registered yet.<br>
                                <small>Click "Add Holiday / Poya Day" above to configure your first holiday.</small>
                            </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>


        {{-- Expense Categories Management Accordion --}}
        <div class="accordion-item border-0 mb-4 shadow-sm rounded-4">
            <h2 class="accordion-header" id="headingExpenseCategories">
                <button class="accordion-button fw-semibold bg-white text-dark rounded-4 collapsed"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseExpenseCategories" aria-expanded="false"
                    aria-controls="collapseExpenseCategories">
                    <i class="bi bi-tag fs-5 me-3 text-info"></i>
                    Expense Categories & Types
                </button>
            </h2>
            <div id="collapseExpenseCategories" class="accordion-collapse collapse"
                aria-labelledby="headingExpenseCategories" data-bs-parent="#settingsAccordion">
                <div class="accordion-body">
                    {{-- Add Button --}}
                    <div class="mb-3 d-flex justify-content-end">
                        <button class="btn btn-info shadow-sm" wire:click="openAddCategoryModal">
                            <i class="bi bi-plus-circle"></i> Add Expense Category/Type
                        </button>
                    </div>
                    @php
                        $allCategories = \App\Models\ExpenseCategory::orderBy('expense_category')->orderBy('type')->get()->groupBy('expense_category');
                    @endphp

                    @if($allCategories->isNotEmpty())
                    <div class="row">
                        @foreach($allCategories as $categoryName => $items)
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-info bg-opacity-10">
                                    <h6 class="mb-0 fw-bold text-dark">
                                        <i class="bi bi-folder2-open me-2"></i>{{ $categoryName }}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        @foreach($items as $item)
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span><i class="bi bi-tag me-2 text-muted"></i>{{ $item->type }}</span>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    wire:click="confirmDeleteCategoryType({{ $item->id }})"
                                                    title="Delete this type">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                        No expense categories found.
                    </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- Staff Permissions Accordion --}}
        <div class="accordion-item border-0 mb-4 shadow-sm rounded-4">
            <h2 class="accordion-header" id="headingStaffPermissions">
                <button class="accordion-button fw-semibold bg-white text-dark rounded-4 collapsed"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseStaffPermissions" aria-expanded="false"
                    aria-controls="collapseStaffPermissions">
                    <i class="bi bi-shield-lock fs-5 me-3 text-primary"></i>
                    Staff Permissions Management
                </button>
            </h2>
            <div id="collapseStaffPermissions" class="accordion-collapse collapse"
                aria-labelledby="headingStaffPermissions" data-bs-parent="#settingsAccordion">
                <div class="accordion-body">
                    <div class="alert alert-info border-0 shadow-sm mb-4">
                        <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>How Permission System Works</h6>
                        <ul class="mb-0 small">
                            <li><strong>Admin users:</strong> Have full access to all menus automatically</li>
                            <li><strong>Staff with no permissions:</strong> Have full access by default</li>
                            <li><strong>Staff with permissions assigned:</strong> Only see menus they have access to</li>
                            <li><strong>Important:</strong> Parent menu permission is required to access sub-menus</li>
                            <li><strong>After changes:</strong> Staff must refresh their page to see updated menus</li>
                        </ul>
                    </div>

                    @if($staffMembers->isNotEmpty())
                    <div class="row g-3">
                        @foreach($staffMembers as $staff)
                        <div class="col-md-6 col-lg-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                                            <i class="bi bi-person-badge fs-4 text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold">{{ $staff->name }}</h6>
                                            <small class="text-muted">{{ $staff->email }}</small>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        @php
                                            $permCount = \App\Models\StaffPermission::where('user_id', $staff->id)
                                                ->where('is_active', true)
                                                ->count();
                                        @endphp
                                        <small class="text-muted">
                                            <i class="bi bi-key me-1"></i>
                                            {{ $permCount }} permission(s) assigned
                                        </small>
                                    </div>
                                    <button class="btn btn-primary btn-sm w-100" 
                                            wire:click="openPermissionModal({{ $staff->id }})">
                                        <i class="bi bi-gear me-2"></i>Manage Permissions
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-people display-4 d-block mb-3"></i>
                        No staff members found. <br>
                        <small>Add staff members from the Manage Staff page to configure their permissions.</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- System Configurations Accordion --}}
        <div class="accordion-item border-0 mb-4 shadow-sm rounded-4">
            <h2 class="accordion-header" id="headingSystemConfigs">
                <button class="accordion-button fw-semibold bg-white text-dark rounded-4 collapsed"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseSystemConfigs" aria-expanded="false"
                    aria-controls="collapseSystemConfigs">
                    <i class="bi bi-sliders fs-5 me-3 text-success"></i>
                    System Configurations
                </button>
            </h2>
            <div id="collapseSystemConfigs" class="accordion-collapse collapse"
                aria-labelledby="headingSystemConfigs" data-bs-parent="#settingsAccordion">
                <div class="accordion-body">

                    {{-- Add Button inside accordion --}}
                    <div class="mb-3 d-flex justify-content-end">
                        <button class="btn btn-primary shadow-sm" wire:click="openAddModal">
                            <i class="bi bi-plus-circle"></i> Add Configuration
                        </button>
                    </div>

                    {{-- Existing Configurations --}}
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            @if($settings->isNotEmpty())
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-dark fw-bold">Key</th>
                                        <th class="text-dark fw-bold">Value</th>
                                        <th class="text-center text-dark fw-bold" style="width: 180px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($settings as $setting)
                                    <tr>
                                        <td class="text-dark">{{ $setting->key }}</td>
                                        <td class="text-dark">{{ $setting->value }}</td>
                                        <td class="text-center">
    <div class="dropdown">
        <button class="btn btn-sm btn-light border-0 dropdown-toggle" 
                type="button" 
                data-bs-toggle="dropdown" 
                aria-expanded="false">
            <i class="bi bi-three-dots-vertical"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li>
                <a class="dropdown-item text-primary" 
                   href="#" 
                   wire:click.prevent="openEditModal({{ $setting->id }})">
                    <i class="bi bi-pencil me-2"></i>Edit
                </a>
            </li>
            <li>
                <a class="dropdown-item text-danger" 
                   href="#" 
                   wire:click.prevent="confirmDelete({{ $setting->id }})">
                    <i class="bi bi-trash me-2"></i>Delete
                </a>
            </li>
        </ul>
    </div>
</td>


                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                No configurations found. <br>
                                <small>Click "Add Configuration" to create your first setting.</small>
                            </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Add/Edit --}}
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:key="modal-{{ $isEdit ? 'edit' : 'add' }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        @if($isEdit)
                        <i class="bi bi-pencil-square"></i> Edit Configuration
                        @else
                        <i class="bi bi-plus-circle"></i> Add Configuration
                        @endif
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>

                <form wire:submit.prevent="{{ $isEdit ? 'updateConfiguration' : 'saveConfiguration' }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Key</label>
                            <input type="text" wire:model="key"
                                class="form-control @error('key') is-invalid @enderror"
                                placeholder="Enter configuration key">
                            @error('key')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Value</label>
                            <input type="text" wire:model="value"
                                class="form-control @error('value') is-invalid @enderror"
                                placeholder="Enter configuration value">
                            @error('value')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-secondary shadow-sm" wire:click="closeModal" wire:loading.attr="disabled">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success shadow-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="bi bi-check-circle"></i>
                                @if($isEdit)
                                Update Configuration
                                @else
                                Save Configuration
                                @endif
                            </span>
                            <span wire:loading>
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Staff Permission Modal --}}
    @if($showPermissionModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:key="permission-modal-{{ $selectedStaffId }}">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-shield-lock"></i> Manage Permissions - {{ $selectedStaffName }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closePermissionModal"></button>
                </div>

                <div class="modal-body" style="max-height: 60vh;">
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <p class="text-muted mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Select the permissions you want to grant to this staff member.
                        </p>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-success" wire:click="selectAllPermissions">
                                <i class="bi bi-check-all"></i> Select All
                            </button>
                            <button type="button" class="btn btn-outline-danger" wire:click="clearAllPermissions">
                                <i class="bi bi-x-circle"></i> Clear All
                            </button>
                        </div>
                    </div>

                    @foreach($permissionCategories as $category => $items)
                    <div class="card mb-3 border">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-folder2-open me-2"></i>{{ $loop->iteration }}. {{ $category }}
                            </h6>
                        </div>
                        <div class="card-body py-2">
                            @foreach($items as $item)
                                @if(isset($availablePermissions[$item['key']]))
                                {{-- Level 0: Parent Menu --}}
                                <div class="py-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               id="perm-{{ $item['key'] }}"
                                               wire:click="togglePermission('{{ $item['key'] }}')"
                                               @if(in_array($item['key'], $staffPermissions)) checked @endif>
                                        <label class="form-check-label fw-semibold" for="perm-{{ $item['key'] }}">
                                            {{ $loop->parent->iteration }}.{{ $loop->iteration }}. {{ $availablePermissions[$item['key']] }}
                                        </label>
                                    </div>

                                    @if(isset($item['children']))
                                        @foreach($item['children'] as $child)
                                            @if(isset($availablePermissions[$child['key']]))
                                            {{-- Level 1: Sub-Page --}}
                                            <div class="ms-4 py-1 border-start border-2 border-primary border-opacity-25 ps-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="perm-{{ $child['key'] }}"
                                                           wire:click="togglePermission('{{ $child['key'] }}')"
                                                           @if(in_array($child['key'], $staffPermissions)) checked @endif>
                                                    <label class="form-check-label fw-medium" for="perm-{{ $child['key'] }}">
                                                        {{ $loop->parent->parent->iteration }}.{{ $loop->parent->iteration }}.{{ $loop->iteration }}. {{ $availablePermissions[$child['key']] }}
                                                    </label>
                                                </div>

                                                @if(isset($child['children']))
                                                <div class="ms-4 border-start border-1 border-secondary border-opacity-25 ps-3">
                                                    <div class="row">
                                                        @foreach($child['children'] as $action)
                                                            @if(isset($availablePermissions[$action['key']]))
                                                            {{-- Level 2: Action --}}
                                                            <div class="col-md-6 py-1">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox"
                                                                           id="perm-{{ $action['key'] }}"
                                                                           wire:click="togglePermission('{{ $action['key'] }}')"
                                                                           @if(in_array($action['key'], $staffPermissions)) checked @endif>
                                                                    <label class="form-check-label small" for="perm-{{ $action['key'] }}">
                                                                        {{ $loop->parent->parent->parent->iteration }}.{{ $loop->parent->parent->iteration }}.{{ $loop->parent->iteration }}.{{ $loop->iteration }}. {{ $availablePermissions[$action['key']] }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endforeach

                    <div class="alert alert-info border-0 shadow-sm">
                        <i class="bi bi-lightbulb me-2"></i>
                        <strong>Note:</strong> Changes will take effect immediately after saving. Staff members will need to refresh their page to see the updated permissions.
                    </div>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary shadow-sm" wire:click="closePermissionModal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success shadow-sm" wire:click="savePermissions">
                        <span wire:loading.remove wire:target="savePermissions">
                            <i class="bi bi-check-circle"></i> Save Permissions
                        </span>
                        <span wire:loading wire:target="savePermissions">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Expense Modal --}}
    @if($showExpenseModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:key="expense-modal-{{ $isEditExpense ? 'edit' : 'add' }}">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header bg-warning text-white rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        @if($isEditExpense)
                        <i class="bi bi-pencil-square"></i> Edit Expense
                        @else
                        <i class="bi bi-plus-circle"></i> Add New Expense
                        @endif
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeExpenseModal"></button>
                </div>

                <form wire:submit.prevent="{{ $isEditExpense ? 'updateExpense' : 'saveExpense' }}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                                <select wire:model.live="expenseCategory"
                                    class="form-select @error('expenseCategory') is-invalid @enderror">
                                    <option value="">Select Category</option>
                                    @foreach($expenseCategories as $category)
                                        <option value="{{ $category }}">{{ $category }}</option>
                                    @endforeach
                                </select>
                                @error('expenseCategory')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Expense Type <span class="text-danger">*</span></label>
                                <select wire:model="expenseType"
                                    class="form-select @error('expenseType') is-invalid @enderror"
                                    {{ empty($expenseCategory) ? 'disabled' : '' }}>
                                    <option value="">Select Type</option>
                                    @foreach($expenseTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('expenseType')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if(empty($expenseCategory))
                                    <small class="text-muted">Please select a category first</small>
                                @endif
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" wire:model="expenseAmount"
                                        class="form-control @error('expenseAmount') is-invalid @enderror"
                                        placeholder="0">
                                    @error('expenseAmount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                                <input type="date" wire:model="expenseDate"
                                    class="form-control @error('expenseDate') is-invalid @enderror">
                                @error('expenseDate')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select wire:model="expenseStatus"
                                class="form-select @error('expenseStatus') is-invalid @enderror">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            @error('expenseStatus')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea wire:model="expenseDescription"
                                class="form-control @error('expenseDescription') is-invalid @enderror"
                                rows="3"
                                placeholder="Enter expense description or notes..."></textarea>
                            @error('expenseDescription')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-secondary shadow-sm" wire:click="closeExpenseModal" wire:loading.attr="disabled">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-warning text-white shadow-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="bi bi-check-circle"></i>
                                @if($isEditExpense)
                                Update Expense
                                @else
                                Save Expense
                                @endif
                            </span>
                            <span wire:loading>
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Expense Category Modal --}}
    @if($showCategoryModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:key="category-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header bg-info text-white rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle"></i> Add Expense Category/Type
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeCategoryModal"></button>
                </div>

                <form wire:submit.prevent="saveCategoryType">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Expense Category <span class="text-danger">*</span></label>
                            <select wire:model="newExpenseCategory"
                                class="form-select @error('newExpenseCategory') is-invalid @enderror">
                                <option value="">Select Existing Category</option>
                                <option value="Monthly Expenses">Monthly Expenses</option>
                                <option value="Daily Expenses">Daily Expenses</option>
                                <option value="__new__">+ Create New Category</option>
                            </select>
                            @error('newExpenseCategory')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($newExpenseCategory === '__new__')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Category Name <span class="text-danger">*</span></label>
                            <input type="text" wire:model="customExpenseCategory"
                                class="form-control @error('customExpenseCategory') is-invalid @enderror"
                                placeholder="e.g., Annual Expenses, Weekly Expenses">
                            @error('customExpenseCategory')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Expense Type <span class="text-danger">*</span></label>
                            <input type="text" wire:model="newExpenseType"
                                class="form-control @error('newExpenseType') is-invalid @enderror"
                                placeholder="e.g., Snacks, Electricity Bill, Rent">
                            @error('newExpenseType')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Enter the type of expense for the selected category</small>
                        </div>

                        <div class="alert alert-warning border-0 shadow-sm">
                            <i class="bi bi-lightbulb me-2"></i>
                            <strong>Tip:</strong> Category groups types together (e.g., "Monthly Expenses" can have types like "Rent", "Electricity Bill").
                        </div>
                    </div>

                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-secondary shadow-sm" wire:click="closeCategoryModal" wire:loading.attr="disabled">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-info text-white shadow-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="bi bi-check-circle"></i> Save Category/Type
                            </span>
                            <span wire:loading>
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Holiday & Poya Day Modal --}}
    @if($showHolidayModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:key="holiday-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header bg-danger text-white rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        @if($isEditHoliday)
                        <i class="bi bi-pencil-square"></i> Edit Holiday / Poya Day
                        @else
                        <i class="bi bi-plus-circle"></i> Add Holiday / Poya Day
                        @endif
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeHolidayModal"></button>
                </div>

                <form wire:submit.prevent="saveHoliday">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Holiday Date <span class="text-danger">*</span></label>
                            <input type="date" wire:model="holidayDate"
                                class="form-control @error('holidayDate') is-invalid @enderror">
                            @error('holidayDate')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description / Holiday Name</label>
                            <input type="text" wire:model="holidayDescription"
                                class="form-control @error('holidayDescription') is-invalid @enderror"
                                placeholder="e.g., Duruthu Full Moon Poya Day, Bank Holiday, Christmas...">
                            @error('holidayDescription')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="p-3 bg-light rounded-3 border mb-2">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" role="switch" id="isBlockedForCheque"
                                    wire:model="isBlockedForCheque">
                                <label class="form-check-label fw-bold text-dark" for="isBlockedForCheque">
                                    <i class="bi bi-shield-x text-danger me-1"></i> Block Cheque Realization / Deposits
                                </label>
                            </div>
                            <small class="text-muted d-block ps-4">
                                When enabled, users will NOT be allowed to issue or realize cheques on this date across the entire system.
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-secondary shadow-sm" wire:click="closeHolidayModal" wire:loading.attr="disabled">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-danger text-white shadow-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveHoliday">
                                <i class="bi bi-check-circle"></i>
                                @if($isEditHoliday)
                                Update Holiday
                                @else
                                Save Holiday
                                @endif
                            </span>
                            <span wire:loading wire:target="saveHoliday">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

</div>

@push('styles')
<style>
    .list-group-item {
        background-color: #fff;
        transition: all 0.2s ease-in-out;
        border: 1px solid #dee2e6;
    }

    .list-group-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
    }

    .modal.fade.show {
        display: block !important;
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }

    .table th,
    .table td {
        vertical-align: middle;
    }

    .table-bordered {
        border-color: #dee2e6;
    }

    .accordion-button:not(.collapsed) {
        background-color: #fff;
        color: #000;
        box-shadow: none;
    }

    .accordion-button:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .accordion-body {
        padding: 1.5rem;
    }

    /* Calendar Grid Styles */
    .calendar-grid-container {
        background-color: #fff;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
    }

    .calendar-grid-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .calendar-header-cell {
        padding: 0.75rem 0.5rem;
        font-weight: 700;
        font-size: 0.85rem;
        text-align: center;
        text-transform: uppercase;
        color: #495057;
        border-right: 1px solid #dee2e6;
    }

    .calendar-header-cell:last-child {
        border-right: none;
    }

    .calendar-cell {
        min-height: 110px;
        padding: 0.5rem;
        border-right: 1px solid #dee2e6;
        border-bottom: 1px solid #dee2e6;
        background-color: #fff;
        position: relative;
        transition: background-color 0.15s ease-in-out;
    }

    .calendar-cell:nth-child(7n) {
        border-right: none;
    }

    .calendar-cell-muted {
        background-color: #fafafa;
    }

    .calendar-cell:hover:not(.calendar-cell-muted) {
        background-color: #f8faff;
    }

    .calendar-cell.is-today {
        background-color: #f0f7ff;
        box-shadow: inset 0 0 0 2px #0d6efd;
    }

    .calendar-cell.has-holiday {
        background-color: #fff9f9;
    }

    .day-number {
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 24px;
    }

    .quick-add-btn {
        opacity: 0;
        transition: opacity 0.15s ease-in-out;
    }

    .calendar-cell:hover .quick-add-btn {
        opacity: 1;
    }

    .holiday-card {
        background-color: #fee2e2;
        border: 1px solid #fca5a5;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .holiday-card:hover {
        transform: scale(1.02);
        box-shadow: 0 0.125rem 0.25rem rgba(220, 53, 69, 0.2);
    }

    .btn-xs {
        padding: 0.1rem 0.25rem;
        font-size: 0.75rem;
    }

    @media (max-width: 768px) {
        .calendar-cell {
            min-height: 80px;
            padding: 0.25rem;
        }
        .calendar-header-cell {
            font-size: 0.75rem;
            padding: 0.5rem 0.2rem;
        }
    }
</style>
@endpush

@push('scripts')
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // SweetAlert for delete confirmation (System Configurations)
    window.addEventListener('swal:confirm-delete', event => {
        Swal.fire({
            title: 'Are you sure?',
            text: "This configuration will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('deleteConfirmed', {
                    id: event.detail.id
                });
            }
        });
    });

    // SweetAlert for delete confirmation (Expenses)
    window.addEventListener('swal:confirm-delete-expense', event => {
        Swal.fire({
            title: 'Are you sure?',
            text: "This expense will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('deleteExpenseConfirmed', {
                    id: event.detail.id
                });
            }
        });
    });

    // SweetAlert for delete confirmation (Expense Category/Type)
    window.addEventListener('swal:confirm-delete-category-type', event => {
        Swal.fire({
            title: 'Are you sure?',
            text: "This expense category/type will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('deleteCategoryTypeConfirmed', {
                    id: event.detail.id
                });
            }
        });
    });

    // SweetAlert for delete confirmation (Holidays / Poya Days)
    window.addEventListener('swal:confirm-delete-holiday', event => {
        Swal.fire({
            title: 'Are you sure?',
            text: "This holiday / poya day restriction will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('deleteHolidayConfirmed', {
                    id: event.detail.id
                });
            }
        });
    });
</script>
@endpush
