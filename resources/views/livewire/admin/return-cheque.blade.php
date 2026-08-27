<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-arrow-counterclockwise text-danger me-2"></i> Returned & Cancelled Cheques
            </h3>
            <p class="text-muted mb-0">View and manage all returned and cancelled cheques</p>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-5">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-danger border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Returned Cheques</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">Total: Rs. {{ number_format($returnAmount ?? 0, 2) }}</div>
                            <div class="text-xs text-muted mt-1">{{ $returnCount ?? 0 }} Cheques</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-arrow-counterclockwise fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-primary border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Remaining Balance</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">Total: Rs. {{ number_format($remainingReturnStats['amount'] ?? 0, 2) }}</div>
                            <div class="text-xs text-muted mt-1">{{ $remainingReturnStats['count'] ?? 0 }} Cheques</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-wallet2 fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-secondary border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-secondary text-uppercase mb-1">Cancelled Cheques</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">Total: Rs. {{ number_format($cancelledAmount ?? 0, 2) }}</div>
                            <div class="text-xs text-muted mt-1">{{ $cancelledCount ?? 0 }} Cheques</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-x-circle fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-warning border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Overdue Cheques</div>
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
                    <i class="bi bi-list-ul text-danger me-2"></i> Returned & Cancelled Cheque List
                </h5>
                <span class="badge bg-danger">{{ $cheques->total() ?? 0 }} records</span>
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
                            <th class="ps-4">Cheque No</th>
                            <th>Customer</th>
                            <th class="text-center">Bank</th>
                            <th class="text-center">Amount</th>
                            <th class="text-center">Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cheques as $cheque)
                        <tr wire:key="cheque-{{ $cheque->id }}">
                            <td class="ps-4">{{ $cheque->cheque_number }}</td>
                            <td>{{ $cheque->customer->name ?? '-' }}</td>
                            <td class="text-center">{{ $cheque->bank_name }}</td>
                            <td class="text-center">Rs.{{ number_format($cheque->cheque_amount, 2) }}</td>
                            <td class="text-center">{{ $cheque->cheque_date ? date('M d, Y', strtotime($cheque->cheque_date)) : '-' }}</td>
                            <td class="text-center">
                                @php
                                    $paid = \App\Models\Payment::where('notes', 'Settlement for returned cheque ID: ' . $cheque->id)->sum('amount');
                                    $isPartial = $paid > 0 && $paid < $cheque->cheque_amount - 0.01;
                                @endphp
                                @if($isPartial)
                                    <span class="badge bg-warning text-dark">Partial Paid</span>
                                    <div class="small text-muted mt-1">Bal: Rs.{{ number_format($cheque->cheque_amount - $paid, 2) }}</div>
                                @else
                                    <span class="badge bg-{{ $cheque->status == 'return' ? 'danger' : ($cheque->status == 'cancelled' ? 'secondary' : 'warning') }}">
                                        {{ ucfirst($cheque->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @if($cheque->status == 'return')
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#rechequeModal" wire:click="setSelectedCheque({{ $cheque->id }})">
                                    <i class="bi bi-arrow-repeat"></i> Re-Cheque
                                </button>
                                <button class="btn btn-outline-danger btn-sm ms-1" wire:click="triggerCancelCheque({{ $cheque->id }})">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </button>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-x-circle display-4 d-block mb-2"></i>
                                No returned or cancelled cheques found.
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

    <!-- Re-Cheque Modal -->
    <div wire:ignore.self class="modal fade" id="rechequeModal" tabindex="-1" aria-labelledby="rechequeModalLabel" aria-hidden="true" x-data="{ isUploadingPhoto: false }" @photo-uploaded.window="isUploadingPhoto = false">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-gradient-primary text-white py-3">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-cash-stack me-2"></i> Settle Returned Cheque
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="rechequeSubmit">
                    <div class="modal-body p-4">
                        {{-- Summary Header --}}
                        <div class="alert alert-light border-0 shadow-sm mb-4 p-0" style="background: #f8f9fa; border-radius: 12px; overflow: hidden;">
                            <div class="row g-0 text-center">
                                <div class="col-md-4 border-end py-3 px-2">
                                    <span class="text-muted d-block text-uppercase small fw-bold">Original Amount</span>
                                    @php 
                                        $original = \App\Models\Cheque::find($selectedChequeId)?->cheque_amount ?? 0;
                                        $paid = \App\Models\Payment::where('notes', 'Settlement for returned cheque ID: ' . $selectedChequeId)->sum('amount');
                                    @endphp
                                    <span class="h5 fw-bold text-dark mb-0">Rs.{{ number_format($original, 2) }}</span>
                                </div>
                                <div class="col-md-4 border-end py-3 px-2">
                                    <span class="text-muted d-block text-uppercase small fw-bold">Already Paid</span>
                                    <span class="h5 fw-bold text-success mb-0">Rs.{{ number_format($paid, 2) }}</span>
                                </div>
                                <div class="col-md-4 py-3 px-2 bg-white">
                                    <span class="text-muted d-block text-uppercase small fw-bold">Remaining Balance</span>
                                    <span class="h5 fw-bold text-danger mb-0">Rs.{{ number_format($totalReturnedAmount, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Payment Rows --}}
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle">
                                <thead class="text-muted small text-uppercase fw-bold">
                                    <tr>
                                        <th style="width: 25%;">Method</th>
                                        <th style="width: 20%;">Amount</th>
                                        <th>Details</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentRows as $index => $row)
                                    <tr class="border-bottom border-light">
                                        <td>
                                            <select wire:model.live="paymentRows.{{ $index }}.method" class="form-select border-0 bg-light rounded-3" required>
                                                <option value="cash">Cash</option>
                                                <option value="cheque">Cheque</option>
                                                <option value="bank_transfer">Bank Transfer</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" wire:model.live="paymentRows.{{ $index }}.amount" class="form-control border-0 bg-light rounded-3 text-end fw-bold" placeholder="0.00" required>
                                        </td>
                                        <td>
                                            @if($row['method'] === 'cheque')
                                                <div class="row g-2">
                                                    <div class="col-md-6">
                                                        <input type="text" wire:model.defer="paymentRows.{{ $index }}.cheque_number" class="form-control form-control-sm border-0 bg-light" placeholder="Cheque No" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <select wire:model.defer="paymentRows.{{ $index }}.bank_name" class="form-select form-select-sm border-0 bg-light" required>
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
                                                    <div class="col-md-12 mt-2">
                                                        <input type="date" wire:model.defer="paymentRows.{{ $index }}.cheque_date" class="form-control form-control-sm border-0 bg-light" required>
                                                    </div>
                                                    <div class="col-md-12 mt-2">
                                                        <label class="form-label small fw-bold mb-1">Cheque Photo</label>
                                                        <input type="file" id="returnChequePhotoInput_{{ $index }}" class="form-control form-control-sm border-0 bg-light" accept="image/*" @change="isUploadingPhoto = true; compressAndUploadReturn($event.target, {{ $index }})">
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
                                                        <select wire:model.defer="paymentRows.{{ $index }}.bank_name" class="form-select form-select-sm border-0 bg-light" required>
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
                                                        <input type="text" wire:model.defer="paymentRows.{{ $index }}.transfer_reference" class="form-control form-control-sm border-0 bg-light" placeholder="Ref No">
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted small">Standard cash payment</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(count($paymentRows) > 1)
                                            <button type="button" wire:click="removePaymentRow({{ $index }})" class="btn btn-link text-danger p-0">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <button type="button" wire:click="addPaymentRow" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                <i class="bi bi-plus-circle me-1"></i> Add Another Payment
                            </button>
                            
                            @php $remaining = floatval($totalReturnedAmount) - collect($paymentRows)->sum(fn($row) => floatval($row['amount'] ?? 0)); @endphp
                            @if($remaining > 0.01)
                            <div class="text-warning small fw-bold">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Remaining: Rs.{{ number_format($remaining, 2) }}
                            </div>
                            @elseif($remaining < -0.01)
                            <div class="text-danger small fw-bold">
                                <i class="bi bi-exclamation-circle-fill me-1"></i> Excess: Rs.{{ number_format(abs($remaining), 2) }}
                            </div>
                            @else
                            <div class="text-success small fw-bold">
                                <i class="bi bi-check-circle-fill me-1"></i> Fully Paid
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 justify-content-between bg-light">
                        <button type="button" class="btn btn-light px-4 rounded-pill fw-bold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-5 rounded-pill fw-bold shadow-sm" x-bind:disabled="isUploadingPhoto" wire:loading.attr="disabled" wire:target="rechequeSubmit">
                            <span wire:loading.remove wire:target="rechequeSubmit" x-show="!isUploadingPhoto">
                                Confirm Settlement
                            </span>
                            <span wire:loading wire:target="rechequeSubmit">
                                <span class="spinner-border spinner-border-sm me-1"></span> Processing...
                            </span>
                            <span x-show="isUploadingPhoto" style="display: none;">
                                <span class="spinner-border spinner-border-sm me-1"></span> Uploading Photo...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .bg-gradient-primary {
            background: linear-gradient(45deg, #4e73df 0%, #224abe 100%);
        }
        .form-select, .form-control {
            transition: all 0.2s;
        }
        .form-select:focus, .form-control:focus {
            background-color: #fff !important;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }
    </style>

</div>

@push('scripts')
<script>
    window.addEventListener('show-cancel-cheque-modal', event => {
        Swal.fire({
            title: "Cancel Cheque?",
            text: "Are you sure you want to cancel this returned cheque? This will mark it as cancelled with no due amount.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, cancel it!"
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('cancelChequeConfirmed');
            }
        });
    });

    // Clean up any stuck modals on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
        document.body.style.removeProperty('overflow');
    });

    // Client-side image compression before Livewire upload
    function compressAndUploadReturn(input, rowIndex) {
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