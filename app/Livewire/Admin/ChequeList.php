<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\Holiday;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Livewire\Concerns\WithDynamicLayout;
use App\Livewire\Concerns\HandlesChequeUploads;

#[Title('Cheque List')]
class ChequeList extends Component
{
    use WithDynamicLayout;
    use WithFileUploads;
    use HandlesChequeUploads;

    use WithPagination;
    public $perPage = 30;
    public $search = '';
    public $statusFilter = 'all';
    public $dateFrom = '';
    public $dateTo = '';

    // Edit modal
    public $showEditModal = false;
    public $editId = null;
    public $editChequeNumber = '';
    public $editBankName = '';
    public $editChequeDate = '';
    public $editChequeAmount = '';
    public $editChequePhoto = null;
    public $editChequePhotoUrl = null;

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function getChequesProperty()
    {
        // Show pending cheques first, then others by cheque_date desc
        $query = Cheque::with('customer')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END ASC")
            ->orderBy('cheque_date', 'desc');

        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('cheque_number', 'like', $term)
                    ->orWhere('bank_name', 'like', $term)
                    ->orWhereHas('customer', function ($cq) use ($term) {
                        $cq->where('name', 'like', $term)
                            ->orWhere('phone', 'like', $term);
                    });
            });
        }
        // Apply status filter if set
        if (!empty($this->statusFilter) && $this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }
        // Apply date range filter
        if (!empty($this->dateFrom)) {
            $query->whereDate('cheque_date', '>=', $this->dateFrom);
        }
        if (!empty($this->dateTo)) {
            $query->whereDate('cheque_date', '<=', $this->dateTo);
        }

        if ($this->perPage === 'all') {
            $totalRows = (clone $query)->count();
            return $query->paginate($totalRows > 0 ? $totalRows : 1);
        }

        return $query->paginate((int) $this->perPage);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
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

    public function clearDateFilter()
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function getPendingCountProperty()
    {
        return Cheque::where('status', 'pending')->count();
    }

    public function getPendingAmountProperty()
    {
        return Cheque::where('status', 'pending')->sum('cheque_amount');
    }

    public function getCompleteCountProperty()
    {
        return Cheque::where('status', 'complete')->count();
    }

    public function getCompleteAmountProperty()
    {
        return Cheque::where('status', 'complete')->sum('cheque_amount');
    }

    public function getOverdueCountProperty()
    {
        return Cheque::where('status', 'overdue')->count();
    }

    public function getOverdueAmountProperty()
    {
        return Cheque::where('status', 'overdue')->sum('cheque_amount');
    }

    public function openEditModal($id)
    {
        $cheque = Cheque::find($id);
        if (!$cheque) {
            $this->dispatch('toast', type: 'error', message: 'Cheque not found!');
            return;
        }
        $this->editId = $id;
        $this->editChequeNumber = $cheque->cheque_number;
        $this->editBankName = $cheque->bank_name;
        $this->editChequeDate = $cheque->cheque_date;
        $this->editChequeAmount = $cheque->cheque_amount;
        $this->editChequePhoto = null;
        $this->editChequePhotoUrl = $cheque->cheque_photo_url;
        $this->showEditModal = true;
    }

    public function updatedEditChequeDate($value)
    {
        if (!empty($value) && Holiday::isHoliday($value)) {
            $reason = Holiday::getHolidayReason($value);
            $this->addError('editChequeDate', "Warning: {$value} is marked as a Holiday / Poya Day ({$reason}). Cheques cannot be dated on this day.");
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
            'editChequePhoto'  => 'nullable|image|mimes:jpg,jpeg,png',
        ], [
            'editChequeNumber.required' => 'Cheque number is required.',
            'editBankName.required'     => 'Bank name is required.',
            'editChequeDate.required'   => 'Cheque date is required.',
            'editChequePhoto.image'     => 'Cheque photo must be an image file.',
            'editChequePhoto.mimes'     => 'Cheque photo must be JPG, JPEG or PNG.',
            'editChequePhoto.max'       => 'Cheque photo size may not be greater than 5MB.',
        ]);

        if (Holiday::isHoliday($this->editChequeDate)) {
            $reason = Holiday::getHolidayReason($this->editChequeDate);
            $this->addError('editChequeDate', "The selected date ({$this->editChequeDate}) is marked as a Holiday / Poya Day ({$reason}). Cheque realization is blocked on this date.");
            return;
        }

        try {
            $cheque = Cheque::find($this->editId);
            if (!$cheque) {
                $this->dispatch('toast', type: 'error', message: 'Cheque not found!');
                return;
            }

            $photoUrl = $cheque->cheque_photo_url;
            if ($this->editChequePhoto) {
                // Delete old photo file from storage if it exists
                if ($cheque->cheque_photo_url && !str_starts_with($cheque->cheque_photo_url, 'http')) {
                    Storage::disk('public')->delete($cheque->cheque_photo_url);
                }
                $photoUrl = $this->uploadChequePhotoToStorage($this->editChequePhoto, $this->editChequeNumber, $this->editChequeDate);
            }

            $cheque->update([
                'cheque_number' => $this->editChequeNumber,
                'bank_name'     => $this->editBankName,
                'cheque_date'   => $this->editChequeDate,
                'cheque_photo_url' => $photoUrl,
            ]);

            $this->showEditModal = false;
            $this->dispatch('toast', type: 'success', message: 'Cheque updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating cheque: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Failed to update cheque!');
        }
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editId = null;
        $this->editChequeNumber = '';
        $this->editBankName = '';
        $this->editChequeDate = '';
        $this->editChequeAmount = 0;
        $this->editChequePhoto = null;
        $this->editChequePhotoUrl = null;
    }

    public function confirmComplete($id)
    {
        $this->js("
            if (confirm('Mark this cheque as complete?')) {
                \$wire.completeCheque({$id});
            }
        ");
    }

    public function confirmReturn($id)
    {
        $this->js("
            if (confirm('Return this cheque?')) {
                \$wire.returnCheque({$id});
            }
        ");
    }

    public function completeCheque($id)
    {
        try {
            $cheque = Cheque::find($id);
            if (!$cheque) {
                $this->dispatch('toast', type: 'error', message: 'Cheque not found!');
                return;
            }
            $cheque->status = 'complete';
            $cheque->save();
            $this->dispatch('toast', type: 'success', message: 'Cheque marked as complete successfully!');
        } catch (\Exception $e) {
            Log::error("Error completing cheque: " . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Failed to mark cheque as complete!');
        }
    }

    public function returnCheque($id)
    {
        try {
            $cheque = Cheque::find($id);
            if (!$cheque) {
                $this->dispatch('toast', type: 'error', message: 'Cheque not found!');
                return;
            }
            $cheque->status = 'return';
            $cheque->save();
            $this->dispatch('toast', type: 'success', message: 'Cheque returned successfully!');
        } catch (\Exception $e) {
            Log::error("Error returning cheque: " . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Failed to return cheque!');
        }
    }

    // Export cheques to CSV
    public function exportCSV()
    {
        $query = Cheque::with('customer')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END ASC")
            ->orderBy('cheque_date', 'desc');

        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('cheque_number', 'like', $term)
                    ->orWhere('bank_name', 'like', $term)
                    ->orWhereHas('customer', function ($cq) use ($term) {
                        $cq->where('name', 'like', $term)
                            ->orWhere('phone', 'like', $term);
                    });
            });
        }
        
        if (!empty($this->statusFilter) && $this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }
        
        if (!empty($this->dateFrom)) {
            $query->whereDate('cheque_date', '>=', $this->dateFrom);
        }
        if (!empty($this->dateTo)) {
            $query->whereDate('cheque_date', '<=', $this->dateTo);
        }

        $cheques = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=cheques_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Customer', 'Cheque No', 'Bank', 'Amount', 'Date', 'Status'];

        $callback = function() use($cheques, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($cheques as $cheque) {
                fputcsv($file, [
                    $cheque->customer->name ?? 'N/A',
                    $cheque->cheque_number,
                    $cheque->bank_name,
                    number_format($cheque->cheque_amount, 2, '.', ''),
                    $cheque->cheque_date ? date('d/m/Y', strtotime($cheque->cheque_date)) : '-',
                    ucfirst($cheque->status)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return view('livewire.admin.cheque-list', [
            'cheques' => $this->cheques,
            'pendingCount' => $this->pendingCount,
            'completeCount' => $this->completeCount,
            'overdueCount' => $this->overdueCount,
            'pendingAmount' => $this->pendingAmount,
            'completeAmount' => $this->completeAmount,
            'overdueAmount' => $this->overdueAmount,
        ])->layout($this->layout);
    }
}
