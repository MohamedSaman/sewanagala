<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\SupplierCheque;
use App\Models\ProductSupplier;
use App\Models\Holiday;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Livewire\Concerns\WithDynamicLayout;
use App\Livewire\Concerns\HandlesChequeUploads;
use Carbon\Carbon;

#[Title('Supplier Cheque List')]
class SupplierChequeList extends Component
{
    use WithDynamicLayout;
    use WithFileUploads;
    use HandlesChequeUploads;
    use WithPagination;

    public $perPage = 30;
    public $search = '';
    public $statusFilter = 'all';
    public $supplierFilter = 'all';
    public $bankFilter = 'all';
    public $dateFrom = '';
    public $dateTo = '';
    public $viewMode = 'table'; // 'table' or 'sheet'

    // Add Cheque Modal
    public $showAddModal = false;
    public $newCheque = [
        'cheque_number' => '',
        'cheque_date' => '',
        'bank_name' => 'BOC',
        'amount' => '',
        'supplier_id' => '',
        'payee_name' => '',
        'notes' => '',
    ];
    public $newChequePhoto = null;

    // Edit Cheque Modal
    public $showEditModal = false;
    public $editId = null;
    public $editChequeNumber = '';
    public $editBankName = '';
    public $editChequeDate = '';
    public $editChequeAmount = '';
    public $editSupplierId = '';
    public $editPayeeName = '';
    public $editNotes = '';
    public $editChequePhoto = null;
    public $editChequePhotoUrl = null;

    public function mount()
    {
        $this->newCheque['cheque_date'] = now()->format('Y-m-d');
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedSupplierFilter()
    {
        $this->resetPage();
    }

    public function updatedBankFilter()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->supplierFilter = 'all';
        $this->bankFilter = 'all';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function setViewMode($mode)
    {
        $this->viewMode = in_array($mode, ['table', 'sheet']) ? $mode : 'table';
    }

    public function getSuppliersProperty()
    {
        return ProductSupplier::orderBy('name')->get(['id', 'name']);
    }

    public function getBankNamesProperty()
    {
        return SupplierCheque::select('bank_name')
            ->distinct()
            ->whereNotNull('bank_name')
            ->where('bank_name', '!=', '')
            ->orderBy('bank_name')
            ->pluck('bank_name');
    }

    protected function buildQuery()
    {
        $query = SupplierCheque::with(['supplier', 'purchasePayment']);

        // In sheet mode, order chronologically by cheque_date ascending (like the physical ledger)
        // In table mode, pending first then recent dates
        if ($this->viewMode === 'sheet') {
            $query->orderBy('cheque_date', 'asc')->orderBy('id', 'asc');
        } else {
            $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END ASC")
                ->orderBy('cheque_date', 'desc');
        }

        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('cheque_number', 'like', $term)
                    ->orWhere('bank_name', 'like', $term)
                    ->orWhere('payee_name', 'like', $term)
                    ->orWhereHas('supplier', function ($sq) use ($term) {
                        $sq->where('name', 'like', $term)
                            ->orWhere('phone', 'like', $term);
                    });
            });
        }

        if (!empty($this->statusFilter) && $this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if (!empty($this->supplierFilter) && $this->supplierFilter !== 'all') {
            $query->where('supplier_id', $this->supplierFilter);
        }

        if (!empty($this->bankFilter) && $this->bankFilter !== 'all') {
            $query->where('bank_name', $this->bankFilter);
        }

        if (!empty($this->dateFrom)) {
            $query->whereDate('cheque_date', '>=', $this->dateFrom);
        }
        if (!empty($this->dateTo)) {
            $query->whereDate('cheque_date', '<=', $this->dateTo);
        }

        return $query;
    }

    public function getChequesProperty()
    {
        $query = $this->buildQuery();

        if ($this->perPage === 'all') {
            $totalRows = (clone $query)->count();
            return $query->paginate($totalRows > 0 ? $totalRows : 1);
        }

        return $query->paginate((int) $this->perPage);
    }

    // Statistics Accessors
    public function getPendingCountProperty()
    {
        return SupplierCheque::where('status', 'pending')->count();
    }

    public function getPendingAmountProperty()
    {
        return SupplierCheque::where('status', 'pending')->sum('amount');
    }

    public function getCompleteCountProperty()
    {
        return SupplierCheque::where('status', 'complete')->count();
    }

    public function getCompleteAmountProperty()
    {
        return SupplierCheque::where('status', 'complete')->sum('amount');
    }

    public function getReturnCountProperty()
    {
        return SupplierCheque::where('status', 'return')->count();
    }

    public function getReturnAmountProperty()
    {
        return SupplierCheque::where('status', 'return')->sum('amount');
    }

    public function getTotalCountProperty()
    {
        return SupplierCheque::count();
    }

    public function getTotalAmountProperty()
    {
        return SupplierCheque::sum('amount');
    }

    // --- ADD MODAL ---
    public function openAddModal()
    {
        $this->newCheque = [
            'cheque_number' => '',
            'cheque_date' => now()->format('Y-m-d'),
            'bank_name' => 'BOC',
            'amount' => '',
            'supplier_id' => '',
            'payee_name' => '',
            'notes' => '',
        ];
        $this->newChequePhoto = null;
        $this->showAddModal = true;
        $this->resetValidation();
    }

    public function closeAddModal()
    {
        $this->showAddModal = false;
        $this->newChequePhoto = null;
        $this->resetValidation();
    }

    public function updatedNewChequeSupplierId($value)
    {
        if (!empty($value)) {
            $supplier = ProductSupplier::find($value);
            if ($supplier) {
                $this->newCheque['payee_name'] = $supplier->name;
            }
        }
    }

    public function saveNewCheque()
    {
        $this->validate([
            'newCheque.cheque_number' => 'required|string|max:100',
            'newCheque.cheque_date'   => 'required|date',
            'newCheque.bank_name'     => 'required|string|max:100',
            'newCheque.amount'        => 'required|numeric|min:0.01',
            'newCheque.supplier_id'   => 'nullable|exists:product_suppliers,id',
            'newCheque.payee_name'    => 'required_without:newCheque.supplier_id|nullable|string|max:255',
            'newChequePhoto'          => 'nullable|image|max:5120',
        ], [
            'newCheque.cheque_number.required' => 'Cheque number is required.',
            'newCheque.cheque_date.required'   => 'Cheque date is required.',
            'newCheque.bank_name.required'     => 'Bank name is required.',
            'newCheque.amount.required'        => 'Cheque amount is required.',
            'newCheque.payee_name.required_without' => 'Please select a supplier or enter a payee name.',
        ]);

        if (Holiday::isHoliday($this->newCheque['cheque_date'])) {
            $reason = Holiday::getHolidayReason($this->newCheque['cheque_date']);
            $this->addError('newCheque.cheque_date', "The selected date is marked as a Holiday / Poya Day ({$reason}). Cheques cannot be dated on this day.");
            return;
        }

        try {
            $photoUrl = null;
            if ($this->newChequePhoto) {
                $photoUrl = $this->uploadChequePhotoToStorage($this->newChequePhoto, $this->newCheque['cheque_number'], $this->newCheque['cheque_date']);
            }

            $payeeName = $this->newCheque['payee_name'];
            if (!empty($this->newCheque['supplier_id'])) {
                $supp = ProductSupplier::find($this->newCheque['supplier_id']);
                if ($supp) {
                    $payeeName = $supp->name;
                }
            }

            SupplierCheque::create([
                'cheque_number' => $this->newCheque['cheque_number'],
                'cheque_date'   => $this->newCheque['cheque_date'],
                'bank_name'     => $this->newCheque['bank_name'],
                'amount'        => $this->newCheque['amount'],
                'supplier_id'   => !empty($this->newCheque['supplier_id']) ? $this->newCheque['supplier_id'] : null,
                'payee_name'    => $payeeName,
                'status'        => 'pending',
                'cheque_photo_url' => $photoUrl,
                'notes'         => $this->newCheque['notes'],
                'created_by'    => auth()->id(),
            ]);

            $this->showAddModal = false;
            $this->dispatch('toast', type: 'success', message: 'Supplier cheque added successfully!');
        } catch (\Exception $e) {
            Log::error('Error adding supplier cheque: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Failed to add cheque: ' . $e->getMessage());
        }
    }

    // --- EDIT MODAL ---
    public function openEditModal($id)
    {
        $cheque = SupplierCheque::find($id);
        if (!$cheque) {
            $this->dispatch('toast', type: 'error', message: 'Cheque not found!');
            return;
        }

        $this->editId = $id;
        $this->editChequeNumber = $cheque->cheque_number;
        $this->editBankName = $cheque->bank_name;
        $this->editChequeDate = $cheque->cheque_date ? $cheque->cheque_date->format('Y-m-d') : '';
        $this->editChequeAmount = $cheque->amount;
        $this->editSupplierId = $cheque->supplier_id ?? '';
        $this->editPayeeName = $cheque->payee_name ?? ($cheque->supplier?->name ?? '');
        $this->editNotes = $cheque->notes ?? '';
        $this->editChequePhoto = null;
        $this->editChequePhotoUrl = $cheque->cheque_photo_url;
        $this->showEditModal = true;
        $this->resetValidation();
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editId = null;
        $this->editChequePhoto = null;
        $this->resetValidation();
    }

    public function updatedEditChequeDate($value)
    {
        if (!empty($value) && Holiday::isHoliday($value)) {
            $reason = Holiday::getHolidayReason($value);
            $this->addError('editChequeDate', "Warning: {$value} is marked as a Holiday / Poya Day ({$reason}).");
        } else {
            $this->resetErrorBag('editChequeDate');
        }
    }

    public function updateCheque()
    {
        $this->validate([
            'editChequeNumber' => 'required|string|max:100',
            'editBankName'     => 'required|string|max:100',
            'editChequeDate'   => 'required|date',
            'editChequeAmount' => 'required|numeric|min:0.01',
            'editChequePhoto'  => 'nullable|image|max:5120',
        ], [
            'editChequeNumber.required' => 'Cheque number is required.',
            'editBankName.required'     => 'Bank name is required.',
            'editChequeDate.required'   => 'Cheque date is required.',
            'editChequeAmount.required' => 'Cheque amount is required.',
        ]);

        if (Holiday::isHoliday($this->editChequeDate)) {
            $reason = Holiday::getHolidayReason($this->editChequeDate);
            $this->addError('editChequeDate', "The selected date ({$this->editChequeDate}) is marked as a Holiday / Poya Day ({$reason}).");
            return;
        }

        try {
            $cheque = SupplierCheque::find($this->editId);
            if (!$cheque) {
                $this->dispatch('toast', type: 'error', message: 'Cheque not found!');
                return;
            }

            $photoUrl = $cheque->cheque_photo_url;
            if ($this->editChequePhoto) {
                if ($cheque->cheque_photo_url && !str_starts_with($cheque->cheque_photo_url, 'http')) {
                    Storage::disk('public')->delete($cheque->cheque_photo_url);
                }
                $photoUrl = $this->uploadChequePhotoToStorage($this->editChequePhoto, $this->editChequeNumber, $this->editChequeDate);
            }

            $payeeName = $this->editPayeeName;
            if (!empty($this->editSupplierId)) {
                $supp = ProductSupplier::find($this->editSupplierId);
                if ($supp) {
                    $payeeName = $supp->name;
                }
            }

            $cheque->update([
                'cheque_number' => $this->editChequeNumber,
                'bank_name'     => $this->editBankName,
                'cheque_date'   => $this->editChequeDate,
                'amount'        => $this->editChequeAmount,
                'supplier_id'   => !empty($this->editSupplierId) ? $this->editSupplierId : null,
                'payee_name'    => $payeeName,
                'notes'         => $this->editNotes,
                'cheque_photo_url' => $photoUrl,
            ]);

            $this->showEditModal = false;
            $this->dispatch('toast', type: 'success', message: 'Cheque updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating supplier cheque: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Failed to update cheque!');
        }
    }

    // --- STATUS ACTIONS ---
    public function completeCheque($id)
    {
        try {
            $cheque = SupplierCheque::find($id);
            if (!$cheque) {
                $this->dispatch('toast', type: 'error', message: 'Cheque not found!');
                return;
            }
            $cheque->status = 'complete';
            $cheque->save();

            // Also update linked purchase payment if exists
            if ($cheque->purchase_payment_id) {
                $payment = $cheque->purchasePayment;
                if ($payment) {
                    $payment->cheque_status = 'complete';
                    $payment->status = 'paid';
                    $payment->is_completed = 1;
                    $payment->save();
                }
            }

            $this->dispatch('toast', type: 'success', message: 'Cheque marked as complete/cleared successfully!');
        } catch (\Exception $e) {
            Log::error('Error completing supplier cheque: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Failed to complete cheque!');
        }
    }

    public function returnCheque($id)
    {
        try {
            $cheque = SupplierCheque::find($id);
            if (!$cheque) {
                $this->dispatch('toast', type: 'error', message: 'Cheque not found!');
                return;
            }
            $cheque->status = 'return';
            $cheque->save();

            if ($cheque->purchase_payment_id) {
                $payment = $cheque->purchasePayment;
                if ($payment) {
                    $payment->cheque_status = 'returned';
                    $payment->save();
                }
            }

            $this->dispatch('toast', type: 'success', message: 'Cheque marked as returned successfully!');
        } catch (\Exception $e) {
            Log::error('Error returning supplier cheque: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Failed to return cheque!');
        }
    }

    public function cancelCheque($id)
    {
        try {
            $cheque = SupplierCheque::find($id);
            if (!$cheque) {
                $this->dispatch('toast', type: 'error', message: 'Cheque not found!');
                return;
            }
            $cheque->status = 'cancelled';
            $cheque->save();

            $this->dispatch('toast', type: 'success', message: 'Cheque marked as cancelled!');
        } catch (\Exception $e) {
            Log::error('Error cancelling supplier cheque: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Failed to cancel cheque!');
        }
    }

    public function deleteCheque($id)
    {
        try {
            $cheque = SupplierCheque::find($id);
            if (!$cheque) {
                $this->dispatch('toast', type: 'error', message: 'Cheque not found!');
                return;
            }

            if ($cheque->cheque_photo_url && !str_starts_with($cheque->cheque_photo_url, 'http')) {
                Storage::disk('public')->delete($cheque->cheque_photo_url);
            }

            $cheque->delete();
            $this->dispatch('toast', type: 'success', message: 'Supplier cheque deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting supplier cheque: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Failed to delete cheque!');
        }
    }

    // --- CSV EXPORT ---
    public function exportCSV()
    {
        $query = $this->buildQuery();
        $cheques = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=supplier_cheques_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Date', 'Day', 'Cheque No', 'Bank', 'Payee / Supplier', 'Amount', 'Status', 'Notes'];

        $callback = function() use ($cheques, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($cheques as $cheque) {
                fputcsv($file, [
                    $cheque->cheque_date ? $cheque->cheque_date->format('d/m/Y') : '-',
                    $cheque->day_name,
                    $cheque->cheque_number,
                    $cheque->bank_name,
                    $cheque->display_payee,
                    number_format($cheque->amount, 2, '.', ''),
                    ucfirst($cheque->status),
                    $cheque->notes ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return view('livewire.admin.supplier-cheque-list', [
            'cheques' => $this->cheques,
            'suppliers' => $this->suppliers,
            'bankNames' => $this->bankNames,
            'pendingCount' => $this->pendingCount,
            'pendingAmount' => $this->pendingAmount,
            'completeCount' => $this->completeCount,
            'completeAmount' => $this->completeAmount,
            'returnCount' => $this->returnCount,
            'returnAmount' => $this->returnAmount,
            'totalCount' => $this->totalCount,
            'totalAmount' => $this->totalAmount,
        ])->layout($this->layout);
    }
}
