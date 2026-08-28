<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-journal-check text-success me-2"></i> Cheque Management
            </h3>
            <p class="text-muted mb-0">View and manage all customer cheques</p>
        </div>

    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-5">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start border-warning border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Pending Cheques</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">Total: Rs. {{ number_format($pendingAmount ?? 0, 2) }}</div>
                            <div class="text-xs text-muted mt-1">{{ $pendingCount ?? 0 }} Cheques</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-clock-history fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start border-success border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Completed Cheques</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">Total: Rs. {{ number_format($completeAmount ?? 0, 2) }}</div>
                            <div class="text-xs text-muted mt-1">{{ $completeCount ?? 0 }} Cheques</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-check2-circle fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start border-danger border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Overdue Cheques</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">Total: Rs. {{ number_format($overdueAmount ?? 0, 2) }}</div>
                            <div class="text-xs text-muted mt-1">{{ $overdueCount ?? 0 }} Cheques</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-exclamation-triangle fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cheque Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-list-ul text-primary me-2"></i> Cheque List
                </h5>
                <span class="badge bg-primary">{{ $cheques->total() ?? 0 }} records</span>
            </div>

            {{-- Search --}}
            <div class="flex-grow-1" style="max-width: 280px;">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" wire:model.live="search"
                        placeholder="Search cheque / customer...">
                </div>
            </div>

            {{-- Date Range Filter --}}
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <label class="text-sm text-muted fw-medium mb-0">Date:</label>
                <input type="date" class="form-control form-control-sm" wire:model.live="dateFrom"
                    style="width: 145px;" title="From date">
                <span class="text-muted">–</span>
                <input type="date" class="form-control form-control-sm" wire:model.live="dateTo"
                    style="width: 145px;" title="To date">
                @if($dateFrom || $dateTo)
                <button class="btn btn-sm btn-outline-secondary" wire:click="clearDateFilter" title="Clear date filter">
                    <i class="bi bi-x-lg"></i>
                </button>
                @endif
            </div>

            {{-- Status & Per Page --}}
            <div class="d-flex align-items-center gap-2">
                <label class="text-sm text-muted fw-medium">Filter</label>
                <select wire:model.live="statusFilter" class="form-select form-select-sm" style="width: 130px;">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="complete">Complete</option>
                    <option value="overdue">Overdue</option>
                    <option value="return">Return</option>
                </select>
                <button wire:click="exportCSV" class="btn btn-sm btn-outline-success me-2">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
                </button>
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
            </div>
        </div>
        <div class="card-body p-0 overflow-auto">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">
                                <input type="checkbox" class="form-check-input" id="selectAll" onclick="toggleAllRows(this)">
                            </th>
                            <th class="ps-4">Cheque No</th>
                            <th>Customer</th>
                            <th class="text-center">Bank</th>
                            <th class="text-center">Amount</th>
                            <th class="text-center">Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Photo</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cheques as $cheque)
                        <tr wire:key="cheque-{{ $cheque->id }}" class="table-row" data-id="{{ $cheque->id }}">
                            <td class="ps-4" onclick="event.stopPropagation();">
                                <input type="checkbox" class="form-check-input row-checkbox" onchange="toggleRowHighlight(this)">
                            </td>
                            <td class="ps-4">{{ $cheque->cheque_number }}</td>
                            <td>{{ $cheque->customer->name ?? '-' }}</td>
                            <td class="text-center">{{ $cheque->bank_name }}</td>
                            <td class="text-center">Rs.{{ number_format($cheque->cheque_amount, 2) }}</td>
                            <td class="text-center">
                                {{ $cheque->cheque_date ? date('d-m-Y', strtotime($cheque->cheque_date)) : '-' }}
                                @if($cheque->cheque_date && \App\Models\Holiday::isHoliday($cheque->cheque_date))
                                    <br>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle py-0 px-1" style="font-size: 0.7rem;" title="Holiday / Poya Day: {{ \App\Models\Holiday::getHolidayReason($cheque->cheque_date) }}">
                                        <i class="bi bi-moon-stars"></i> Holiday
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $cheque->status == 'pending' ? 'warning' : ($cheque->status == 'complete' ? 'success' : ($cheque->status == 'return' ? 'danger' : 'secondary')) }}">
                                    {{ ucfirst($cheque->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($cheque->cheque_photo_url)
                                <a href="{{ $this->resolveChequePhotoUrl($cheque->cheque_photo_url) }}" target="_blank" class="btn btn-sm btn-outline-info p-1 py-0" title="View Photo">
                                    <i class="bi bi-image"></i>
                                </a>
                                @else
                                <span class="text-muted small">None</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @if($cheque->status == 'pending' || $cheque->status == 'overdue')
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <i class="bi bi-gear-fill"></i> Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if(auth()->user()->hasPermission('menu_banks_cheque_edit'))
                                        <!-- Edit Cheque -->
                                        <li>
                                            <button class="dropdown-item"
                                                wire:click="openEditModal({{ $cheque->id }})">
                                                <i class="bi bi-pencil-square text-primary me-2"></i>
                                                Edit
                                            </button>
                                        </li>
                                        @endif
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        @if(auth()->user()->hasPermission('menu_banks_cheque_complete'))
                                        <!-- Mark as Complete -->
                                        <li>
                                            <button class="dropdown-item"
                                                wire:click="confirmComplete({{ $cheque->id }})">
                                                <i class="bi bi-check2-circle text-success me-2"></i>
                                                Complete
                                            </button>
                                        </li>
                                        @endif
                                        @if(auth()->user()->hasPermission('menu_banks_cheque_return'))
                                        <!-- Return Cheque -->
                                        <li>
                                            <button class="dropdown-item"
                                                wire:click="confirmReturn({{ $cheque->id }})">
                                                <i class="bi bi-arrow-counterclockwise text-danger me-2"></i>
                                                Return
                                            </button>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-x-circle display-4 d-block mb-2"></i>
                                No cheques found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($cheques->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-center">
                    {{ $cheques->links('livewire.custom-pagination') }}
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Edit Cheque Modal --}}
    @if($showEditModal)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);" wire:ignore.self x-data="{ isUploadingPhoto: false }" @photo-uploaded.window="isUploadingPhoto = false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square text-primary me-2"></i> Edit Cheque
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeEditModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Amount (read-only) --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cheque Amount <span class="text-muted">(not editable)</span></label>
                        <input type="text" class="form-control bg-light" value="Rs. {{ number_format($editChequeAmount, 2) }}" readonly disabled>
                    </div>

                    {{-- Cheque Number --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cheque Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('editChequeNumber') is-invalid @enderror"
                            wire:model.live="editChequeNumber" placeholder="Enter cheque number">
                        @error('editChequeNumber')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Bank Name --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bank Name <span class="text-danger">*</span></label>
                        <select class="form-select @error('editBankName') is-invalid @enderror" wire:model.live="editBankName">
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
                        @error('editBankName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Cheque Date --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cheque Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('editChequeDate') is-invalid @enderror"
                            wire:model.live="editChequeDate">
                        @error('editChequeDate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($editChequeDate && \App\Models\Holiday::isHoliday($editChequeDate))
                            <div class="alert alert-danger py-1 px-2 mt-2 mb-0 small border-0 shadow-sm">
                                <i class="bi bi-shield-x me-1"></i>
                                <strong>Holiday / Poya Day:</strong> {{ \App\Models\Holiday::getHolidayReason($editChequeDate) }}. Cheque realization is blocked on this date.
                            </div>
                        @endif
                    </div>

                    {{-- Cheque Photo --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cheque Photo</label>

                        @if($editChequePhotoUrl)
                        <div class="mb-2">
                            <span class="text-xs text-muted d-block mb-1">Current Photo:</span>
                            <div class="position-relative d-inline-block">
                                @php
                                $directUrl = $this->resolveChequePhotoPreviewUrl($editChequePhotoUrl);
                                @endphp
                                <img src="{{ $directUrl }}" class="img-thumbnail" style="max-height: 150px; cursor: pointer;"
                                    onclick="window.open('{{ $this->resolveChequePhotoUrl($editChequePhotoUrl) }}', '_blank')">
                            </div>
                        </div>
                        @endif

                        <div class="input-group">
                            <input type="file" id="editChequePhotoInput" class="form-control @error('editChequePhoto') is-invalid @enderror"
                                accept="image/*" @change="isUploadingPhoto = true; compressAndUpload($event.target, 'editChequePhoto')">
                        </div>
                        @error('editChequePhoto')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                        <div wire:loading wire:target="editChequePhoto" class="mt-1">
                            <div class="spinner-border spinner-border-sm text-primary me-1"></div>
                            <small class="text-primary italic">Uploading...</small>
                        </div>

                        @if ($editChequePhoto)
                        <div class="mt-2">
                            <span class="text-xs text-success d-block mb-1">New Photo Preview:</span>
                            <img src="{{ $editChequePhoto->temporaryUrl() }}" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeEditModal">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="updateCheque" x-bind:disabled="isUploadingPhoto" wire:loading.attr="disabled" wire:target="updateCheque">
                        <span wire:loading wire:target="updateCheque" class="spinner-border spinner-border-sm me-1"></span>
                        <span x-show="isUploadingPhoto" class="spinner-border spinner-border-sm me-1" style="display: none;"></span>
                        <i class="bi bi-check2 me-1" wire:loading.remove wire:target="updateCheque" x-show="!isUploadingPhoto"></i>
                        <span x-show="!isUploadingPhoto" wire:loading.remove wire:target="updateCheque">Save Changes</span>
                        <span x-show="isUploadingPhoto" style="display: none;">Uploading Photo...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
    /* Row selection highlight - Multiple specificity levels */
    /* Row selection highlight - Multiple specificity levels, high-contrast for dark backgrounds */
    tr.selected-row,
    table tbody tr.selected-row,
    table.table tbody tr.selected-row,
    .table tbody tr.selected-row {
        background-color: #223046 !important;
        /* dark blue-gray */
        color: #fff !important;
    }

    tr.selected-row td,
    table tbody tr.selected-row td,
    table.table tbody tr.selected-row td {
        background-color: #223046 !important;
        color: #fff !important;
        --bs-table-accent-bg: #223046 !important;
        --bs-table-bg: #223046 !important;
    }

    tr.selected-row td .text-muted,
    table tbody tr.selected-row td .text-muted {
        color: #e0e0e0 !important;
    }

    table tbody tr.selected-row:hover,
    table.table tbody tr.selected-row:hover {
        background-color: #2d3a4a !important;
        /* slightly lighter on hover */
        color: #fff !important;
    }

    table tbody tr.selected-row:hover td,
    table.table tbody tr.selected-row:hover td {
        background-color: #2d3a4a !important;
        color: #fff !important;
        --bs-table-accent-bg: #2d3a4a !important;
        --bs-table-bg: #2d3a4a !important;
    }
</style>
@endpush

@push('scripts')
<script>
    // Row selection functions
    function toggleRowHighlight(checkbox) {
        const row = checkbox.closest('tr');
        if (checkbox.checked) {
            row.classList.add('selected-row');
            row.style.backgroundColor = '#d4e6f1';
        } else {
            row.classList.remove('selected-row');
            row.style.backgroundColor = '';
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

    // Toast notifications
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

    // Client-side image compression before Livewire upload
    function compressAndUpload(input, propertyName) {
        const file = input.files[0];
        if (!file) return;

        // Only compress if file is an image and larger than 900KB
        const isJpeg = file.type === 'image/jpeg' || file.type === 'image/jpg';
        const needsCompression = file.size > 900 * 1024; // 900KB threshold

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

                    // Scale down if very large (max 1920px on longest side)
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

                    // Compress to JPEG with 0.7 quality (~70%)
                    canvas.toBlob(function(blob) {
                        if (blob) {
                            const compressedFile = new File([blob], file.name, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            // Upload via Livewire's upload API
                            @this.upload(propertyName, compressedFile, successCb, errorCb, function() {});
                        }
                    }, 'image/jpeg', 0.7);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            // For small files or PNGs, upload directly via Livewire
            @this.upload(propertyName, file, successCb, errorCb, function() {});
        }
    }
</script>
@endpush