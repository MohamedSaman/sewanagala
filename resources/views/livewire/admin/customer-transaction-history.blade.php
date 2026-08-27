<div>
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="bi bi-clock-history text-primary me-2"></i> Transaction History
                <span class="text-muted fs-6 ms-2">- {{ $customer->name ?? '' }}</span>
            </h4>
            <a href="{{ route((auth()->user()->role === 'admin' ? 'admin.' : 'staff.') . 'manage-customer') }}" wire:navigate class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Customers
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card shadow-sm border-0 border-start border-4 border-info h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-1" style="font-size:0.8rem;">Total Debits</h6>
                    <h4 class="mb-0 fw-bold">Rs.{{ number_format($transactions->sum('debit'), 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-1" style="font-size:0.8rem;">Total Payments</h6>
                    <h4 class="mb-0 fw-bold">Rs.{{ number_format($transactions->where('type', 'Payment')->sum('credit'), 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card shadow-sm border-0 border-start border-4 border-warning h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-1" style="font-size:0.8rem;">Total Returns</h6>
                    <h4 class="mb-0 fw-bold">Rs.{{ number_format($transactions->where('type', 'Return')->sum('credit'), 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            @php
            $currentBalance = $transactions->last() ? $transactions->last()['balance'] : 0;
            @endphp
            <div class="card shadow-sm border-0 border-start border-4 {{ $currentBalance > 0 ? 'border-danger' : 'border-primary' }} h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-1" style="font-size:0.8rem;">Closing Balance</h6>
                    <h4 class="mb-0 fw-bold text-{{ $currentBalance > 0 ? 'danger' : 'success' }}">
                        Rs.{{ number_format($currentBalance, 2) }}
                        <small class="fs-6">{{ $currentBalance > 0 ? '(Due)' : ($currentBalance < 0 ? '(Advance)' : '') }}</small>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Excel-like Data Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered mb-0 align-middle" style="font-size: 0.9rem;">
                    <thead class="table-dark">
                        <tr>
                            <th class="py-3 px-3 text-nowrap" style="width: 15%">Date & Time</th>
                            <th class="py-3 px-3">Type</th>
                            <th class="py-3 px-3">Reference / Details</th>
                            <th class="py-3 px-3 text-center" style="width: 10%">Cheque Count</th>
                            <th class="py-3 px-3 text-center" style="width: 8%">Due Days</th>
                            <th class="py-3 px-3 text-end" style="width: 15%">Debit <small class="text-white-50">(Rs.)</small></th>
                            <th class="py-3 px-3 text-end" style="width: 15%">Credit <small class="text-white-50">(Rs.)</small></th>
                            <th class="py-3 px-3 text-end" style="width: 15%">Balance <small class="text-white-50">(Rs.)</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td class="px-3 text-nowrap text-muted">
                                {{ \Carbon\Carbon::parse($transaction['date'])->format('d/m/Y h:i A') }}
                            </td>
                            <td class="px-3">
                                @if($transaction['type'] == 'Opening Balance')
                                <span class="badge bg-danger w-100">Opening Due</span>
                                @elseif($transaction['type'] == 'Sale')
                                <span class="badge bg-info text-dark w-100">Sale</span>
                                @elseif($transaction['type'] == 'Payment')
                                <span class="badge bg-success w-100">Payment</span>
                                @elseif($transaction['type'] == 'Return')
                                <span class="badge bg-warning text-dark w-100">Return</span>
                                @elseif($transaction['type'] == 'Returned Cheque')
                                <span class="badge bg-danger w-100">Return Chq</span>
                                @endif
                            </td>
                            <td class="px-3">
                                <span class="fw-bold text-dark">{{ $transaction['reference'] }}</span><br>
                                <span class="text-muted" style="font-size:0.8rem;">{{ $transaction['details'] }}</span>
                            </td>
                            <td class="px-3 text-center">
                                @if(!is_null($transaction['cheque_count']))
                                <span class="fw-bold text-dark">{{ $transaction['cheque_count'] }}</span>
                                @else
                                -
                                @endif
                            </td>
                            <td class="px-3 text-center">
                                @if(!is_null($transaction['due_days'] ?? null))
                                <span class="badge bg-warning text-dark">{{ $transaction['due_days'] }} days</span>
                                @else
                                -
                                @endif
                            </td>
                            <td class="px-3 text-end text-danger fw-semibold">
                                {{ $transaction['debit'] > 0 ? number_format($transaction['debit'], 2) : '-' }}
                            </td>
                            <td class="px-3 text-end text-success fw-semibold">
                                {{ $transaction['credit'] > 0 ? number_format($transaction['credit'], 2) : '-' }}
                            </td>
                            <td class="px-3 text-end fw-bold {{ $transaction['balance'] > 0 ? 'text-danger' : 'text-success' }}" style="background-color: rgba(0,0,0,0.02)">
                                {{ number_format($transaction['balance'], 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3 text-black-50"></i>
                                <h5>No transactions found</h5>
                                <p class="mb-0">There are no sales, payments, or returns for this customer yet.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold border-top-2">
                        <tr>
                            <td colspan="5" class="text-end py-3">Totals:</td>
                            <td class="text-end text-danger py-3">Rs.{{ number_format($transactions->sum('debit'), 2) }}</td>
                            <td class="text-end text-success py-3">Rs.{{ number_format($transactions->sum('credit'), 2) }}</td>
                            <td class="text-end py-3 text-{{ $currentBalance > 0 ? 'danger' : 'success' }}">Rs.{{ number_format($currentBalance, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Card Footer containing the + Add Payment Button -->
            <div class="card-footer bg-white border-top-0 py-3">
                <button class="btn btn-success d-flex align-items-center shadow-sm" wire:click="openPaymentModal" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); border: none; font-weight: 500;">
                    <i class="bi bi-plus-lg me-2"></i> Add Payment
                </button>
            </div>
        </div>
    </div>

    {{-- Payment Modal --}}
    @if($showPaymentModal)
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
                    {{-- Customer Info & Summary --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">CUSTOMER INFORMATION</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>Name:</strong></td>
                                    <td>{{ $customer->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td>{{ $customer->phone ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td>{{ ucfirst($customer->type ?? 'N/A') }}</td>
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
                                    <td class="text-end text-success fw-bold">Rs.{{ number_format(collect($paymentRows)->sum('amount'), 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Remaining Due:</strong></td>
                                    <td class="text-end text-danger">Rs.{{ number_format(max(0, $totalDueAmount - collect($paymentRows)->sum('amount')), 2) }}</td>
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
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-borderless align-middle mb-0">
                                    <thead class="bg-white text-muted small text-uppercase fw-bold">
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
                                                            <input type="text" wire:model.blur="paymentRows.{{ $index }}.cheque_number" class="form-control form-control-sm border-0 shadow-sm" placeholder="Cheque No">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <select wire:model.blur="paymentRows.{{ $index }}.bank_name" class="form-select form-select-sm border-0 shadow-sm">
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
                                                            <input type="date" wire:model.blur="paymentRows.{{ $index }}.cheque_date" class="form-control form-control-sm border-0 shadow-sm">
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
                                                            <select wire:model.blur="paymentRows.{{ $index }}.bank_name" class="form-select form-select-sm border-0 shadow-sm">
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
                                                            <input type="text" wire:model.blur="paymentRows.{{ $index }}.transfer_reference" class="form-control form-control-sm border-0 shadow-sm" placeholder="Ref No">
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

                    {{-- Actions and Total --}}
                    <div class="row mt-4">
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-warning rounded-pill px-4 py-2 fw-bold text-white shadow-sm" 
                                    wire:click="addPaymentRow" 
                                    style="background: linear-gradient(135deg, #16285A 0%, #E65F1E 100%); border: none;">
                                <i class="bi bi-plus-circle me-1"></i> Add Another Payment
                            </button>
                            <div class="d-flex align-items-center">
                                <span class="h5 mb-0 me-3 fw-bold text-dark">Total: <span class="text-success">Rs.{{ number_format(collect($paymentRows)->sum('amount'), 2) }}</span></span>
                                <button type="button" class="btn btn-success px-4 py-2 fw-bold shadow-sm" 
                                        wire:click="processPayment"
                                        style="background: linear-gradient(135deg, #198754 0%, #157347 100%); border: none;">
                                    <i class="bi bi-check-circle me-1"></i> Save Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
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
