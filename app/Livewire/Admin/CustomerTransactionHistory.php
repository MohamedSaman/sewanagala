<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\ReturnsProduct;
use Livewire\WithFileUploads;
use App\Livewire\Concerns\HandlesChequeUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerTransactionHistory extends Component
{
    use WithFileUploads;
    use HandlesChequeUploads;

    public Customer $customer;

    // Payment Modal State
    public $showPaymentModal = false;
    public $paymentRows = [];
    public $paymentDate = '';
    public $paymentNotes = '';
    public $totalDueAmount = 0;
    public $remainingAmount = 0;

    public function mount(Customer $customer)
    {
        $this->customer = $customer;
        $this->paymentDate = now()->format('Y-m-d');
        $this->addPaymentRow();
    }

    public function addPaymentRow()
    {
        $this->paymentRows[] = [
            'method' => 'cash',
            'amount' => 0,
            'cheque_number' => '',
            'bank_name' => '',
            'cheque_date' => now()->format('Y-m-d'),
            'cheque_photo' => null,
            'transfer_reference' => '',
            'transfer_date' => now()->format('Y-m-d'),
        ];
    }

    public function removePaymentRow($index)
    {
        if (count($this->paymentRows) > 1) {
            unset($this->paymentRows[$index]);
            $this->paymentRows = array_values($this->paymentRows);
        }
    }

    public function openPaymentModal()
    {
        // Calculate due amount based on unpaid sales minus returns
        $salesDue = Sale::where('customer_id', $this->customer->id)
            ->whereIn('payment_status', ['pending', 'partial'])
            ->get();
            
        $dueTotal = (float) $this->customer->opening_balance;
        foreach ($salesDue as $sale) {
            $returnAmount = ReturnsProduct::where('sale_id', $sale->id)->sum('total_amount');
            $dueTotal += max(0, $sale->due_amount - $returnAmount);
        }
        
        $this->totalDueAmount = $dueTotal;
        $this->paymentRows = [];
        $this->addPaymentRow();
        $this->paymentDate = now()->format('Y-m-d');
        $this->paymentNotes = '';
        
        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
    }

    public function processPayment()
    {
        $totalPaymentAmount = collect($this->paymentRows)->sum('amount');
        
        if ($totalPaymentAmount <= 0) {
            $this->js("Swal.fire('Error!', 'Please enter an amount greater than zero.', 'error')");
            return;
        }

        // Validate rows
        foreach ($this->paymentRows as $index => $row) {
            if (empty($row['method'])) {
                $this->js("Swal.fire('Error!', 'Payment method is required.', 'error')");
                return;
            }
            if ($row['amount'] <= 0) {
                $this->js("Swal.fire('Error!', 'Amount must be greater than 0 for all payment rows.', 'error')");
                return;
            }
            if ($row['method'] === 'cheque') {
                if (empty($row['cheque_number'])) {
                    $this->js("Swal.fire('Error!', 'Cheque number is required.', 'error')");
                    return;
                }
                if (empty($row['bank_name'])) {
                    $this->js("Swal.fire('Error!', 'Bank name is required.', 'error')");
                    return;
                }
                if (empty($row['cheque_date'])) {
                    $this->js("Swal.fire('Error!', 'Cheque date is required.', 'error')");
                    return;
                }
            }
            if ($row['method'] === 'bank_transfer') {
                if (empty($row['bank_name'])) {
                    $this->js("Swal.fire('Error!', 'Bank name is required.', 'error')");
                    return;
                }
            }
        }

        try {
            DB::beginTransaction();

            $groupReference = 'RCPT-' . now()->format('YmdHis') . '-' . rand(10, 99);
            $totalProcessed = 0;
            $createdPayments = [];

            // 1. Create all Payment records first
            foreach ($this->paymentRows as $row) {
                if ($row['amount'] <= 0) continue;

                $paymentData = [
                    'customer_id' => $this->customer->id,
                    'amount' => $row['amount'],
                    'payment_method' => $row['method'],
                    'payment_reference' => $groupReference, // Shared reference for grouping
                    'payment_date' => $this->paymentDate ?: now()->format('Y-m-d'),
                    'notes' => $this->paymentNotes,
                    'status' => 'paid',
                    'is_completed' => 1,
                    'created_by' => auth()->id() ?: 1,
                ];

                if ($row['method'] === 'bank_transfer') {
                    $paymentData['bank_name'] = $row['bank_name'];
                    $paymentData['transfer_date'] = $row['transfer_date'] ?: now()->format('Y-m-d');
                    $paymentData['transfer_reference'] = $row['transfer_reference'];
                }

                if ($row['method'] === 'cheque') {
                    $paymentData['cheque_number'] = $row['cheque_number'];
                    $paymentData['bank_name'] = $row['bank_name'];
                    $paymentData['cheque_date'] = $row['cheque_date'];
                }

                $payment = Payment::create($paymentData);
                $createdPayments[] = $payment;

                if ($row['method'] === 'cheque') {
                    $photoUrl = null;
                    if (isset($row['cheque_photo']) && $row['cheque_photo']) {
                        $photoUrl = $this->uploadChequePhotoToStorage($row['cheque_photo'], $row['cheque_number'], $row['cheque_date']);
                    }

                    \App\Models\Cheque::create([
                        'payment_id' => $payment->id,
                        'cheque_number' => $row['cheque_number'],
                        'bank_name' => $row['bank_name'],
                        'cheque_date' => $row['cheque_date'],
                        'cheque_amount' => $row['amount'],
                        'cheque_photo_url' => $photoUrl,
                        'status' => 'pending',
                        'customer_id' => $this->customer->id,
                    ]);
                }

                if ($row['method'] === 'cash') {
                    // Update cash_amount record
                    $cashAmountRecord = DB::table('cash_in_hands')->where('key', 'cash_amount')->first();
                    if ($cashAmountRecord) {
                        DB::table('cash_in_hands')
                            ->where('key', 'cash_amount')
                            ->update([
                                'value' => (float)$cashAmountRecord->value + (float)$row['amount'],
                                'updated_at' => now()
                            ]);
                    } else {
                        DB::table('cash_in_hands')->insert([
                            'key' => 'cash_amount',
                            'value' => $row['amount'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            // 2. Allocate payments to invoices sequential (FIFO)
            $pendingSales = Sale::where('customer_id', $this->customer->id)
                ->where(function ($query) {
                    $query->where('payment_status', 'pending')
                        ->orWhere('payment_status', 'partial');
                })
                ->orderBy('created_at', 'asc')
                ->get();

            $salesList = [];
            foreach ($pendingSales as $sale) {
                // Calculate actual due amount by checking returns
                $returnAmount = ReturnsProduct::where('sale_id', $sale->id)->sum('total_amount');
                $adjustedDueAmount = max(0, $sale->due_amount - $returnAmount);
                if ($adjustedDueAmount > 0.01) {
                    $salesList[] = [
                        'id' => $sale->id,
                        'due_amount' => $adjustedDueAmount,
                    ];
                }
            }

            foreach ($createdPayments as $payment) {
                $remainingPaymentAmount = $payment->amount;

                // Apply payments to the customer's migrated opening balance first.
                $openingAllocation = min($remainingPaymentAmount, (float) $this->customer->opening_balance);
                if ($openingAllocation > 0) {
                    $this->customer->opening_balance = max(0, (float) $this->customer->opening_balance - $openingAllocation);
                    $this->customer->save();
                    $remainingPaymentAmount -= $openingAllocation;
                    $totalProcessed += $openingAllocation;
                }

                foreach ($salesList as &$saleItem) {
                    if ($remainingPaymentAmount <= 0) break;

                    $remainingNeeded = $saleItem['due_amount'];
                    if ($remainingNeeded <= 0) continue;

                    $allocationAmount = min($remainingPaymentAmount, $remainingNeeded);

                    // Create allocation link
                    DB::table('payment_allocations')->insert([
                        'payment_id' => $payment->id,
                        'sale_id' => $saleItem['id'],
                        'allocated_amount' => $allocationAmount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Update Sale model
                    $saleModel = Sale::find($saleItem['id']);
                    if ($saleModel) {
                        $saleModel->due_amount = max(0, $saleModel->due_amount - $allocationAmount);
                        $saleModel->payment_status = $saleModel->due_amount <= 0.01 ? 'paid' : 'partial';
                        $saleModel->save();
                    }

                    $remainingPaymentAmount -= $allocationAmount;
                    $saleItem['due_amount'] -= $allocationAmount;
                    $totalProcessed += $allocationAmount;
                }
            }

            DB::commit();

            $this->showPaymentModal = false;
            $this->js("Swal.fire('Success!', 'Payment of Rs." . number_format($totalPaymentAmount, 2) . " processed successfully!', 'success')");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment processing failed in CustomerTransactionHistory', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->js("Swal.fire('Error!', 'Failed to process payment: " . addslashes($e->getMessage()) . "', 'error')");
        }
    }

    public function render()
    {
        // 1. Get Sales (Increases Balance / Debit)
        $openingBalance = collect([[
            'type' => 'Opening Balance',
            'id' => null,
            'reference' => 'OPENING-BALANCE',
            'date' => $this->customer->created_at,
            'debit' => (float) $this->customer->opening_balance,
            'credit' => 0,
            'details' => 'Opening balance due',
            'cheque_count' => null,
            'due_days' => null,
        ]]);

        $sales = Sale::where('customer_id', $this->customer->id)
            ->get()
            ->map(function ($sale) {
                return [
                    'type' => 'Sale',
                    'id' => $sale->id,
                    'reference' => 'INV-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT),
                    'date' => $sale->created_at,
                    'debit' => $sale->total_amount,
                    'credit' => 0,
                    'details' => 'Sale Invoice: ' . ($sale->invoice_number ?? ''),
                    'cheque_count' => null,
                    'due_days' => $sale->due_date
                        ? (int) \Carbon\Carbon::parse($sale->created_at)->startOfDay()->diffInDays(\Carbon\Carbon::parse($sale->due_date)->startOfDay())
                        : null,
                ];
            });

        // 2. Get Payments (Decreases Balance / Credit)
        $payments = Payment::where(function ($query) {
            $query->where('customer_id', $this->customer->id)
                ->orWhereHas('sale', function ($q) {
                    $q->where('customer_id', $this->customer->id);
                });
        })
            ->withCount('cheques')
            ->get()
            ->map(function ($payment) {
                // Some live environments store payment_date as date-only, which renders 12:00 AM.
                // Prefer created_at when payment_date has no meaningful time component.
                $transactionDate = $payment->payment_date;
                if (
                    !$transactionDate ||
                    (
                        $payment->created_at &&
                        $transactionDate->format('H:i:s') === '00:00:00' &&
                        $payment->created_at->format('H:i:s') !== '00:00:00'
                    )
                ) {
                    $transactionDate = $payment->created_at;
                }

                return [
                    'type' => 'Payment',
                    'id' => $payment->id,
                    'reference' => $payment->payment_reference ?? 'PAY-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT),
                    'date' => $transactionDate,
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'details' => 'Payment via ' . ucfirst(str_replace('_', ' ', $payment->payment_method)),
                    'cheque_count' => $payment->payment_method === 'cheque' ? $payment->cheques_count : null,
                    'due_days' => null,
                ];
            });

        // 3. Get Returns (Decreases Balance / Credit)
        $returns = ReturnsProduct::whereHas('sale', function ($q) {
            $q->where('customer_id', $this->customer->id);
        })
            ->get()
            ->map(function ($return) {
                return [
                    'type' => 'Return',
                    'id' => $return->id,
                    'reference' => 'RET-' . str_pad($return->id, 5, '0', STR_PAD_LEFT),
                    'date' => $return->created_at,
                    'debit' => 0,
                    'credit' => $return->total_amount,
                    'details' => 'Product Return',
                    'cheque_count' => null,
                    'due_days' => null,
                ];
            });

        // 4. Get Returned Cheques (Increases Balance / Debit)
        $returnedCheques = \App\Models\Cheque::where('customer_id', $this->customer->id)
            ->whereIn('status', ['return', 'cancelled'])
            ->get()
            ->map(function ($cheque) {
                return [
                    'type' => 'Returned Cheque',
                    'id' => $cheque->id,
                    'reference' => 'CHQ-RET-' . str_pad($cheque->id, 5, '0', STR_PAD_LEFT),
                    'date' => $cheque->updated_at ?? $cheque->created_at,
                    'debit' => $cheque->cheque_amount,
                    'credit' => 0,
                    'details' => 'Returned Cheque: ' . $cheque->cheque_number . ' (' . $cheque->bank_name . ')',
                    'cheque_count' => 1,
                    'due_days' => null,
                ];
            });

        // Combine, sort by date ascending, and reset keys
        $transactions = collect()
            ->concat($openingBalance)
            ->concat($sales)
            ->concat($payments)
            ->concat($returns)
            ->concat($returnedCheques)
            ->sortBy(function ($transaction) {
                // Ensure strictly stable sorting if timestamps are exactly identical.
                // Sales (type A) come first, then returns (type B), then returned cheques (type C), then payments (type D).
                $typeOrder = ['Opening Balance' => 0, 'Sale' => 1, 'Return' => 2, 'Returned Cheque' => 3, 'Payment' => 4];
                $timestamp = \Carbon\Carbon::parse($transaction['date'])->timestamp;
                return $timestamp . '_' . $typeOrder[$transaction['type']] . '_' . $transaction['id'];
            })
            ->values();

        // Calculate running balance
        $balance = 0;
        $processedTransactions = $transactions->map(function ($transaction) use (&$balance) {
            $balance += $transaction['debit'];
            $balance -= $transaction['credit'];
            $transaction['cheque_count'] = $transaction['cheque_count'] ?? null;
            $transaction['due_days'] = $transaction['due_days'] ?? null;
            $transaction['balance'] = $balance;
            return $transaction;
        });

        // Return transactions in standard chronological order (oldest first, newest last)
        // This ensures chronological reading top-to-bottom makes logical sense to users.
        return view('livewire.admin.customer-transaction-history', [
            'transactions' => $processedTransactions
        ])->layout('components.layouts.admin');
    }
}
