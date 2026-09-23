<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title('List Customer Receipt')]
class ListCustomerReceipt extends Component
{
    use WithDynamicLayout;

    use WithPagination;

    public $perPage = 30;
    public $showPaymentModal = false;
    public $selectedCustomer = null;
    public $payments = [];
    public $fromDateFilter = '';
    public $toDateFilter = '';
    
    public $showDeleteModal = false;
    public $paymentReferenceToDelete = null;

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

    public function getCustomersProperty()
    {
        // Get customers with total paid and receipt count (sum from payments table)
        $query = Customer::select(
            'customers.id',
            'customers.name',
            'customers.email',
            'customers.address',
            'customers.created_at',
            'customers.updated_at'
        )
            ->selectRaw('(COALESCE(SUM(payments.amount),0) - (SELECT COALESCE(SUM(cheque_amount),0) FROM cheques WHERE cheques.customer_id = customers.id AND cheques.status IN (\'return\', \'cancelled\'))) as total_paid')
            ->selectRaw('COUNT(payments.id) as receipts_count')
            ->leftJoin('payments', function ($join) {
                $join->on('payments.customer_id', '=', 'customers.id');
                if ($this->fromDateFilter) {
                    $join->whereDate('payments.payment_date', '>=', $this->fromDateFilter);
                }
                if ($this->toDateFilter) {
                    $join->whereDate('payments.payment_date', '<=', $this->toDateFilter);
                }
            })
            ->groupBy(
                'customers.id',
                'customers.name',
                'customers.email',
                'customers.address',
                'customers.created_at',
                'customers.updated_at'
            )
            ->having('total_paid', '>', 0)
            ->orderByDesc('total_paid');

        if ($this->perPage === 'all') {
            $totalRows = (clone $query)->count();
            return $query->paginate($totalRows > 0 ? $totalRows : 1);
        }

        return $query->paginate((int) $this->perPage);
    }

    public function showCustomerPayments($customerId)
    {
        $this->selectedCustomer = Customer::find($customerId);
        $this->payments = Payment::with(['allocations', 'allocations.sale', 'cheques'])
            ->where('customer_id', $customerId)
            ->orderByDesc('payment_date')
            ->get();
            
        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->selectedCustomer = null;
        $this->payments = [];
    }

    public function downloadReceipt($reference)
    {
        if (\Illuminate\Support\Str::startsWith($reference, 'single_')) {
            $paymentId = str_replace('single_', '', $reference);
            $payments = Payment::with(['customer', 'allocations.sale', 'cheques'])->where('id', $paymentId)->get();
        } else {
            $payments = Payment::with(['customer', 'allocations.sale', 'cheques'])->where('payment_reference', $reference)->get();
        }

        if ($payments->isEmpty()) abort(404);
        
        $customer = $payments->first()->customer;
        
        $allocations = [];
        $allAllocations = $payments->pluck('allocations')->flatten();
        foreach ($allAllocations as $alloc) {
            $sale = $alloc->sale;
            $returnAmount = \App\Models\ReturnsProduct::where('sale_id', $sale->id)->sum('total_amount');
            $allocations[] = (object) [
                'invoice_number' => $sale ? $sale->invoice_number : 'N/A',
                'total_amount' => $sale ? $sale->total_amount : 0,
                'return_amount' => $returnAmount,
                'adjusted_total' => $sale ? ($sale->total_amount - $returnAmount) : 0,
                'allocated_amount' => $alloc->allocated_amount,
            ];
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.receipts.payment-receipt', [
            'payment' => $payments->first(),
            'customer' => $customer,
            'payments' => $payments,
            'total_amount_paid' => $payments->sum('amount'),
            'allocations' => collect($allocations),
            'received_by' => auth()->user()->name ?? 'Admin',
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'receipt_' . $reference . '.pdf');
    }

    public function confirmDeletePayment($reference)
    {
        $this->paymentReferenceToDelete = $reference;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->paymentReferenceToDelete = null;
    }

    public function deletePayment()
    {
        if (!$this->paymentReferenceToDelete) {
            return;
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                if (\Illuminate\Support\Str::startsWith($this->paymentReferenceToDelete, 'single_')) {
                    $paymentId = str_replace('single_', '', $this->paymentReferenceToDelete);
                    $payments = Payment::with(['allocations.sale', 'cheques'])->where('id', $paymentId)->get();
                } else {
                    $payments = Payment::with(['allocations.sale', 'cheques'])->where('payment_reference', $this->paymentReferenceToDelete)->get();
                }

                if ($payments->isEmpty()) {
                    throw new \Exception('Payment not found.');
                }

                foreach ($payments as $payment) {
                    $allocatedSum = 0;
                    // Reverse allocations
                    foreach ($payment->allocations as $allocation) {
                        $sale = $allocation->sale;
                        if ($sale) {
                            $sale->due_amount += $allocation->allocated_amount;
                            
                            if ($sale->due_amount >= $sale->total_amount) {
                                $sale->payment_status = 'pending';
                            } elseif ($sale->due_amount > 0) {
                                $sale->payment_status = 'partial';
                            } else {
                                $sale->payment_status = 'paid';
                            }
                            
                            $sale->save();
                        }
                        $allocatedSum += $allocation->allocated_amount;
                        $allocation->delete();
                    }

                    // Restore Opening Balance
                    $unallocated = $payment->amount - $allocatedSum;
                    if ($unallocated > 0 && $payment->customer_id) {
                        $customer = \App\Models\Customer::find($payment->customer_id);
                        if ($customer) {
                            $customer->opening_balance += $unallocated;
                            $customer->save();
                        }
                    }

                    // Restore Overpaid Amount if payment method was overpaid_amount
                    if ($payment->payment_method === 'overpaid_amount' && $payment->customer_id) {
                        $customer = \App\Models\Customer::find($payment->customer_id);
                        if ($customer) {
                            $customer->overpaid_amount += $payment->amount;
                            $customer->save();
                        }
                    }

                    // Deduct from Cash in Hand if it was cash
                    if ($payment->payment_method === 'cash') {
                        $cashRecord = \Illuminate\Support\Facades\DB::table('cash_in_hands')->where('key', 'cash_amount')->first();
                        if ($cashRecord) {
                            \Illuminate\Support\Facades\DB::table('cash_in_hands')->where('key', 'cash_amount')->update([
                                'value' => (float)$cashRecord->value - (float)$payment->amount,
                                'updated_at' => now()
                            ]);
                        }
                    }

                    // Delete cheques
                    foreach ($payment->cheques as $cheque) {
                        $cheque->delete();
                    }

                    // Delete the payment record
                    $payment->delete();
                }
            });

            $this->dispatch('show-success', 'Payment deleted and reversed successfully!');
            $this->closeDeleteModal();

            // Refresh the payments list for the modal
            if ($this->selectedCustomer) {
                $this->showCustomerPayments($this->selectedCustomer->id);
            }

        } catch (\Exception $e) {
            $this->dispatch('show-error', 'Failed to delete payment: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $groupedPayments = collect($this->payments)->groupBy(function($p) {
            return $p->payment_reference ?: 'single_'.$p->id;
        });

        return view('livewire.admin.list-customer-receipt', [
            'customers' => $this->customers,
            'showPaymentModal' => $this->showPaymentModal,
            'selectedCustomer' => $this->selectedCustomer,
            'groupedPayments' => $groupedPayments,
        ])->layout($this->layout);
    }

    public function exportCSV()
    {
        $query = Customer::select(
            'customers.id',
            'customers.name',
            'customers.email',
            'customers.address',
            'customers.created_at',
            'customers.updated_at'
        )
            ->selectRaw('(COALESCE(SUM(payments.amount),0) - (SELECT COALESCE(SUM(cheque_amount),0) FROM cheques WHERE cheques.customer_id = customers.id AND cheques.status IN (\'return\', \'cancelled\'))) as total_paid')
            ->selectRaw('COUNT(payments.id) as receipts_count')
            ->leftJoin('payments', function ($join) {
                $join->on('payments.customer_id', '=', 'customers.id');
                if ($this->fromDateFilter) {
                    $join->whereDate('payments.payment_date', '>=', $this->fromDateFilter);
                }
                if ($this->toDateFilter) {
                    $join->whereDate('payments.payment_date', '<=', $this->toDateFilter);
                }
            })
            ->groupBy(
                'customers.id',
                'customers.name',
                'customers.email',
                'customers.address',
                'customers.created_at',
                'customers.updated_at'
            )
            ->having('total_paid', '>', 0)
            ->orderByDesc('total_paid');

        $customers = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=customer_receipts_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Customer Name', 'Email', 'Address', 'Receipts Count', 'Total Paid'];

        $callback = function() use($customers, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($customers as $cust) {
                fputcsv($file, [
                    $cust->name,
                    $cust->email ?? '',
                    $cust->address ?? '',
                    $cust->receipts_count,
                    number_format($cust->total_paid, 2, '.', ''),
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, 'customer_receipts_' . now()->format('YmdHis') . '.csv', $headers);
    }
}
