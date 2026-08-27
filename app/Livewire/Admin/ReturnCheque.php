<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Cheque;
use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;
use App\Livewire\Concerns\HandlesChequeUploads;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

#[Title('Return Cheque')]
class ReturnCheque extends Component
{
    use WithDynamicLayout;
    use WithFileUploads;
    use HandlesChequeUploads;

    use WithPagination;

    public $paymentRows = [];
    public $totalReturnedAmount = 0;
    public $perPage = 30;
    public $fromDateFilter = '';
    public $toDateFilter = '';
    public $selectedChequeId = null;
    public $chequeIdToCancel = null;
    public $search = '';

    protected $rules = [
        'paymentRows.*.method' => 'required|in:cash,cheque,bank_transfer',
        'paymentRows.*.amount' => 'required|numeric|min:0.01',
        'paymentRows.*.cheque_number' => 'required_if:paymentRows.*.method,cheque',
        'paymentRows.*.bank_name' => 'required_if:paymentRows.*.method,cheque,bank_transfer',
        'paymentRows.*.cheque_date' => 'required_if:paymentRows.*.method,cheque|date',
    ];

    public function getChequesProperty()
    {
        // Only return and cancelled cheques
        $query = Cheque::with(['customer', 'payment.sale'])
            ->whereIn('status', ['return', 'cancelled']);

        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('cheque_number', 'like', $term)
                    ->orWhereHas('customer', function ($cq) use ($term) {
                        $cq->where('name', 'like', $term);
                    });
            });
        }

        if ($this->fromDateFilter) {
            $query->whereDate('cheque_date', '>=', $this->fromDateFilter);
        }

        if ($this->toDateFilter) {
            $query->whereDate('cheque_date', '<=', $this->toDateFilter);
        }

        $query->orderByRaw("CASE WHEN status = 'return' THEN 0 ELSE 1 END ASC")
            ->orderByDesc('cheque_date');

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

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedFromDateFilter()
    {
        $this->resetPage();
    }

    public function updatedToDateFilter()
    {
        $this->resetPage();
    }

    public function clearDateFilters()
    {
        $this->fromDateFilter = '';
        $this->toDateFilter = '';
        $this->resetPage();
    }

    public function getReturnCountProperty()
    {
        return Cheque::where('status', 'return')->count();
    }

    public function getReturnAmountProperty()
    {
        return Cheque::where('status', 'return')->sum('cheque_amount');
    }

    public function getCancelledCountProperty()
    {
        return Cheque::where('status', 'cancelled')->count();
    }

    public function getCancelledAmountProperty()
    {
        return Cheque::where('status', 'cancelled')->sum('cheque_amount');
    }

    public function getOverdueCountProperty()
    {
        return Cheque::where('status', 'overdue')->count();
    }

    public function getOverdueAmountProperty()
    {
        return Cheque::where('status', 'overdue')->sum('cheque_amount');
    }

    public function getRemainingReturnStatsProperty()
    {
        $returnCheques = Cheque::where('status', 'return')->get();
        $totalRemaining = 0;
        $count = 0;

        $chequeIds = $returnCheques->pluck('id')->toArray();
        if (!empty($chequeIds)) {
            $notes = array_map(function($id) {
                return 'Settlement for returned cheque ID: ' . $id;
            }, $chequeIds);
            
            $payments = \App\Models\Payment::whereIn('notes', $notes)->get();

            $paymentsByChequeId = [];
            foreach ($payments as $payment) {
                if (preg_match('/Settlement for returned cheque ID: (\d+)/', $payment->notes, $matches)) {
                    $id = $matches[1];
                    $paymentsByChequeId[$id] = ($paymentsByChequeId[$id] ?? 0) + $payment->amount;
                }
            }

            foreach ($returnCheques as $cheque) {
                $paid = $paymentsByChequeId[$cheque->id] ?? 0;
                $remaining = max(0, $cheque->cheque_amount - $paid);
                if ($remaining > 0) {
                    $totalRemaining += $remaining;
                    $count++;
                }
            }
        }

        return ['amount' => $totalRemaining, 'count' => $count];
    }

    public function setSelectedCheque($id)
    {
        $this->selectedChequeId = $id;

        // Pre-fill the cheque amount from the selected cheque
        $cheque = Cheque::find($id);
        if ($cheque) {
            // Calculate how much has already been paid for this returned cheque
            $alreadyPaid = \App\Models\Payment::where('notes', 'Settlement for returned cheque ID: ' . $id)->sum('amount');
            
            $this->totalReturnedAmount = $cheque->cheque_amount - $alreadyPaid;
            $this->paymentRows = [
                [
                    'method' => 'cash',
                    'amount' => $this->totalReturnedAmount,
                    'cheque_number' => '',
                    'bank_name' => '',
                    'cheque_date' => now()->format('Y-m-d'),
                    'cheque_photo' => null,
                    'transfer_reference' => ''
                ]
            ];
        }
    }

    public function addPaymentRow()
    {
        $enteredAmount = collect($this->paymentRows)->sum(fn($row) => floatval($row['amount'] ?? 0));
        $remaining = max(0, $this->totalReturnedAmount - $enteredAmount);

        $this->paymentRows[] = [
            'method' => 'cash',
            'amount' => $remaining > 0 ? $remaining : 0,
            'cheque_number' => '',
            'bank_name' => '',
            'cheque_date' => now()->format('Y-m-d'),
            'cheque_photo' => null,
            'transfer_reference' => ''
        ];
    }

    public function removePaymentRow($index)
    {
        if (count($this->paymentRows) > 1) {
            unset($this->paymentRows[$index]);
            $this->paymentRows = array_values($this->paymentRows);
        }
    }

    public function rechequeSubmit()
    {
        $this->validate();

        $oldCheque = Cheque::with('payment.sale')->find($this->selectedChequeId);
        if (!$oldCheque) {
            $this->js("Swal.fire('Error!', 'Cheque not found.', 'error');");
            return;
        }

        $totalPaid = collect($this->paymentRows)->sum(fn($row) => floatval($row['amount'] ?? 0));

        try {
            DB::beginTransaction();

            // Check if this payment completes the cheque settlement
            $alreadyPaid = \App\Models\Payment::where('notes', 'Settlement for returned cheque ID: ' . $this->selectedChequeId)->sum('amount');
            $remainingBeforeThis = $oldCheque->cheque_amount - $alreadyPaid;

            if ($totalPaid >= $remainingBeforeThis - 0.01) {
                // Mark old cheque as cancelled ONLY if fully paid
                $oldCheque->status = 'cancelled';
                $oldCheque->save();
            }

            $sale = $oldCheque->payment ? $oldCheque->payment->sale : null;


            foreach ($this->paymentRows as $row) {
                if ($row['amount'] <= 0) continue;

                $payment = \App\Models\Payment::create([
                    'customer_id' => $oldCheque->customer_id,
                    'sale_id' => $sale ? $sale->id : null,
                    'amount' => $row['amount'],
                    'payment_method' => $row['method'],
                    'payment_date' => now(),
                    'is_completed' => true,
                    'status' => 'paid',
                    'payment_reference' => strtoupper($row['method']) . '-' . now()->format('YmdHis') . '-' . rand(10, 99),
                    'bank_name' => $row['method'] !== 'cash' ? $row['bank_name'] : null,
                    'cheque_number' => $row['method'] === 'cheque' ? $row['cheque_number'] : null,
                    'cheque_date' => $row['method'] === 'cheque' ? $row['cheque_date'] : null,
                    'transfer_reference' => $row['method'] === 'bank_transfer' ? ($row['transfer_reference'] ?? null) : null,
                    'transfer_date' => $row['method'] === 'bank_transfer' ? now() : null,
                    'notes' => 'Settlement for returned cheque ID: ' . $this->selectedChequeId,
                ]);

                if ($row['method'] === 'cheque') {
                    $photoUrl = null;
                    if (isset($row['cheque_photo']) && $row['cheque_photo']) {
                        $photoUrl = $this->uploadChequePhotoToStorage($row['cheque_photo'], $row['cheque_number'], $row['cheque_date']);
                    }

                    Cheque::create([
                        'cheque_number' => $row['cheque_number'],
                        'bank_name' => $row['bank_name'],
                        'cheque_date' => $row['cheque_date'],
                        'cheque_amount' => $row['amount'],
                        'cheque_photo_url' => $photoUrl,
                        'status' => 'pending',
                        'customer_id' => $oldCheque->customer_id,
                        'payment_id' => $payment->id,
                    ]);
                }

                if ($row['method'] === 'cash') {
                    $this->updateCashInHands((float)$row['amount']);
                }
            }

            DB::commit();

            // Reset form
            $this->resetForm();

            // Close modal and show success message
            $this->js("bootstrap.Modal.getInstance(document.getElementById('rechequeModal')).hide();");
            $this->js("Swal.fire('Success!', 'Payments processed and old cheque cancelled successfully.', 'success');");
            $this->js("location.reload();");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->js("Swal.fire('Error!', 'Failed to process payments: " . addslashes($e->getMessage()) . "', 'error');");
        }
    }

    private function updateCashInHands($amount)
    {
        $keys = ['cash_amount', 'cash in hand'];
        foreach ($keys as $key) {
            $record = DB::table('cash_in_hands')->where('key', $key)->first();
            if ($record) {
                DB::table('cash_in_hands')
                    ->where('key', $key)
                    ->update([
                        'value' => (float)$record->value + (float)$amount,
                        'updated_at' => now()
                    ]);
            } else {
                DB::table('cash_in_hands')->insert([
                    'key' => $key,
                    'value' => $amount,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    private function resetForm()
    {
        $this->selectedChequeId = null;
        $this->paymentRows = [];
        $this->totalReturnedAmount = 0;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function triggerCancelCheque($id)
    {
        $this->chequeIdToCancel = $id;
        $this->dispatch('show-cancel-cheque-modal');
    }

    public function cancelChequeConfirmed()
    {
        if ($this->chequeIdToCancel) {
            $cheque = Cheque::find($this->chequeIdToCancel);
            if ($cheque) {
                $cheque->status = 'cancelled';
                $cheque->save();
                
                // Show success SweetAlert and reload
                $this->js("Swal.fire('Success!', 'Cheque has been cancelled. No due amount exists for it now.', 'success').then(() => { location.reload(); });");
            }
            $this->chequeIdToCancel = null;
        }
    }

    public function render()
    {
        return view('livewire.admin.return-cheque', [
            'cheques' => $this->cheques,
            'returnCount' => $this->returnCount,
            'cancelledCount' => $this->cancelledCount,
            'overdueCount' => $this->overdueCount,
            'returnAmount' => $this->returnAmount,
            'cancelledAmount' => $this->cancelledAmount,
            'overdueAmount' => $this->overdueAmount,
            'remainingReturnStats' => $this->remainingReturnStats,
        ])->layout($this->layout);
    }

    public function exportCSV()
    {
        $query = Cheque::with(['customer', 'payment.sale'])
            ->whereIn('status', ['return', 'cancelled'])
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('cheque_number', 'like', $term)
                        ->orWhereHas('customer', function ($cq) use ($term) {
                            $cq->where('name', 'like', $term);
                        });
                });
            })
            ->when($this->fromDateFilter, function ($q) {
                $q->whereDate('cheque_date', '>=', $this->fromDateFilter);
            })
            ->when($this->toDateFilter, function ($q) {
                $q->whereDate('cheque_date', '<=', $this->toDateFilter);
            })
            ->orderByRaw("CASE WHEN status = 'return' THEN 0 ELSE 1 END ASC")
            ->orderByDesc('cheque_date');

        $cheques = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=returned_cheques_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Cheque Number', 'Cheque Date', 'Customer', 'Bank', 'Cheque Amount', 'Status'];

        $callback = function() use($cheques, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($cheques as $cheque) {
                fputcsv($file, [
                    $cheque->cheque_number,
                    $cheque->cheque_date ? \Carbon\Carbon::parse($cheque->cheque_date)->format('d/m/Y') : '',
                    $cheque->customer->name ?? 'N/A',
                    $cheque->bank_name ?? 'N/A',
                    number_format($cheque->cheque_amount, 2, '.', ''),
                    $cheque->status === 'return' ? 'Returned' : 'Cancelled',
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, 'returned_cheques_' . now()->format('YmdHis') . '.csv', $headers);
    }
}
