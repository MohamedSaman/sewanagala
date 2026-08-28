<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Cheque;
use App\Models\Holiday;
use App\Models\ReturnsProduct;
use App\Livewire\Concerns\WithDynamicLayout;
use App\Livewire\Concerns\HandlesChequeUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Barryvdh\DomPDF\Facade\Pdf;


#[Title("Add Customer Receipt")]
class AddCustomerReceipt extends Component
{
    use WithDynamicLayout;
    use WithFileUploads;
    use HandlesChequeUploads;

    use WithPagination;

    public $search = '';
    public $selectedCustomer = null;
    public $customerSales = [];
    public $selectedInvoices = [];
    public $allocations = [];
    public $paymentRows = [];
    public $paymentDate = '';
    public $paymentNotes = '';
    public $totalDueAmount = 0;
    public $totalPaymentAmount = '';
    public $remainingAmount = 0;
    public $showPaymentModal = false;
    public $showViewModal = false;
    public $showReceiptModal = false;
    public $selectedSale = null;
    public $latestPayment = null;
    public $paymentSuccess = false;

    protected function rules()
    {
        return [
            'paymentRows.*.method' => 'required|in:cash,cheque,bank_transfer',
            'paymentRows.*.amount' => 'required|numeric|min:0.01',
            'paymentRows.*.cheque_number' => 'required_if:paymentRows.*.method,cheque',
            'paymentRows.*.bank_name' => 'required_if:paymentRows.*.method,cheque,bank_transfer',
            'paymentRows.*.cheque_date' => 'required_if:paymentRows.*.method,cheque|date',
            'totalPaymentAmount' => 'required|numeric|min:0.01',
        ];
    }

    protected $messages = [
        'totalPaymentAmount.required' => 'Payment amount is required.',
        'totalPaymentAmount.min' => 'Payment amount must be at least Rs. 0.01',
        'paymentRows.*.method.required' => 'Payment method is required.',
        'paymentRows.*.amount.required' => 'Amount is required for all methods.',
        'paymentRows.*.cheque_number.required_if' => 'Cheque number is required.',
        'paymentRows.*.bank_name.required_if' => 'Bank name is required.',
        'paymentRows.*.cheque_date.required_if' => 'Cheque date is required.',
    ];

    public function mount()
    {
        $this->totalPaymentAmount = '';
        $this->paymentDate = now()->format('Y-m-d');
        $this->addPaymentRow();
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->selectedCustomer = null;
        $this->customerSales = [];
        $this->resetPaymentData();
    }

    public function updatedTotalPaymentAmount()
    {
        $amount = (float)$this->totalPaymentAmount;
        if ($amount > $this->totalDueAmount) {
            $this->totalPaymentAmount = $this->totalDueAmount;
        }

        if ($this->totalPaymentAmount !== '' && $amount < 0) {
            $this->totalPaymentAmount = '';
        }

        $this->calculateRemainingAmount();
        $this->autoAllocatePayment();

        // If only one payment row exists, update its amount to match the total
        if (count($this->paymentRows) === 1) {
            $this->paymentRows[0]['amount'] = $this->totalPaymentAmount;
        }
    }

    public function addPaymentRow()
    {
        $enteredAmount = collect($this->paymentRows)->sum('amount');
        $remaining = max(0, (float)$this->totalPaymentAmount - $enteredAmount);

        $this->paymentRows[] = [
            'method' => 'cash',
            'amount' => $remaining > 0 ? $remaining : 0,
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

    public function updatedPaymentRows($value, $nestedKey)
    {
        if (str_ends_with($nestedKey, '.cheque_date') && !empty($value)) {
            if (Holiday::isHoliday($value)) {
                $reason = Holiday::getHolidayReason($value);
                $this->addError("paymentRows.{$nestedKey}", "Warning: {$value} is marked as a Holiday / Poya Day ({$reason}). Cheque realization is blocked on this date.");
            } else {
                $this->resetErrorBag("paymentRows.{$nestedKey}");
            }
        }
    }

    public function selectCustomer($customerId)
    {
        $this->selectedCustomer = Customer::find($customerId);
        $this->loadCustomerSales();
        $this->selectedInvoices = ((float) ($this->selectedCustomer->opening_balance ?? 0) > 0) ? ['opening'] : [];
        $this->totalPaymentAmount = '';
        $this->calculateTotalDue();
        $this->initializeAllocations();
    }

    public function clearSelectedCustomer()
    {
        $this->selectedCustomer = null;
        $this->customerSales = [];
        $this->selectedInvoices = [];
        $this->allocations = [];
        $this->totalDueAmount = 0;
        $this->totalPaymentAmount = '';
        $this->remainingAmount = 0;
        $this->resetPaymentData();
    }

    public function resetPaymentData()
    {
        $this->paymentRows = [];
        $this->paymentDate = now()->format('Y-m-d');
        $this->paymentNotes = '';
        $this->addPaymentRow();
    }

    /**
     * Toggle invoice selection
     */
    public function toggleInvoiceSelection($saleId)
    {
        if (in_array($saleId, $this->selectedInvoices)) {
            $this->selectedInvoices = array_values(array_diff($this->selectedInvoices, [$saleId]));
        } else {
            $this->selectedInvoices[] = $saleId;
        }

        $this->calculateTotalDue();
        $this->totalPaymentAmount = '';
        $this->remainingAmount = $this->totalDueAmount;
        $this->initializeAllocations();
    }

    /**
     * Select all invoices
     */
    public function selectAllInvoices()
    {
        $this->selectedInvoices = array_column($this->customerSales, 'id');
        if ((float) ($this->selectedCustomer->opening_balance ?? 0) > 0) {
            array_unshift($this->selectedInvoices, 'opening');
        }
        $this->calculateTotalDue();
        $this->totalPaymentAmount = '';
        $this->remainingAmount = $this->totalDueAmount;
        $this->initializeAllocations();
    }

    /**
     * Clear invoice selection
     */
    public function clearInvoiceSelection()
    {
        $this->selectedInvoices = [];
        $this->totalDueAmount = 0;
        $this->totalPaymentAmount = '';
        $this->remainingAmount = 0;
        $this->allocations = [];
    }

    /**
     * Calculate total return amount for a specific sale
     */
    private function calculateReturnAmount($saleId)
    {
        return ReturnsProduct::where('sale_id', $saleId)
            ->sum('total_amount');
    }

    /**
     * Load customer sales with return amounts calculated
     */
    private function loadCustomerSales()
    {
        if (!$this->selectedCustomer) return;

        $sales = Sale::with(['items', 'payments', 'returns'])
            ->where('customer_id', $this->selectedCustomer->id)
            ->where(function ($query) {
                $query->where('payment_status', 'pending')
                    ->orWhere('payment_status', 'partial');
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $this->customerSales = $sales->map(function ($sale) {
            $paidAmount = $sale->total_amount - $sale->due_amount;

            // Calculate total return amount for this sale
            $returnAmount = $this->calculateReturnAmount($sale->id);

            // Adjusted amounts after returns
            $adjustedTotalAmount = $sale->total_amount - $returnAmount;
            $adjustedDueAmount = max(0, $sale->due_amount - $returnAmount);

            // If adjusted due amount is 0 or negative, update the sale status
            if ($adjustedDueAmount <= 0.01) {
                $this->autoMarkSaleAsPaid($sale->id, $returnAmount);
            }

            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'sale_id' => $sale->sale_id,
                'sale_date' => $sale->created_at->format('d/m/Y'),
                'original_total_amount' => $sale->total_amount,
                'total_amount' => $adjustedTotalAmount,
                'original_due_amount' => $sale->due_amount,
                'due_amount' => $adjustedDueAmount,
                'return_amount' => $returnAmount,
                'paid_amount' => $paidAmount,
                'payment_status' => $adjustedDueAmount <= 0.01 ? 'paid' : $sale->payment_status,
                'items_count' => $sale->items->count(),
                'has_returns' => $returnAmount > 0,
            ];
        })->filter(function ($sale) {
            // Only show sales with due amount > 0 after returns
            return $sale['due_amount'] > 0.01;
        })->values()->toArray();

        $this->calculateTotalDue();
    }

    /**
     * Calculate total due amount for selected invoices
     */
    private function calculateTotalDue()
    {
        $this->totalDueAmount = (in_array('opening', $this->selectedInvoices) ? (float) ($this->selectedCustomer->opening_balance ?? 0) : 0)
            + collect($this->customerSales)
            ->whereIn('id', $this->selectedInvoices)
            ->sum('due_amount');
        $this->remainingAmount = $this->totalDueAmount;
    }

    private function calculateRemainingAmount()
    {
        $this->remainingAmount = $this->totalDueAmount - (float)$this->totalPaymentAmount;
    }

    private function initializeAllocations()
    {
        $this->allocations = [];

        if (in_array('opening', $this->selectedInvoices)) {
            $this->allocations['opening'] = [
                'sale_id' => 'opening',
                'invoice_number' => 'Opening Balance',
                'due_amount' => (float) ($this->selectedCustomer->opening_balance ?? 0),
                'payment_amount' => 0,
                'is_fully_paid' => false,
            ];
        }

        foreach ($this->customerSales as $sale) {
            if (in_array($sale['id'], $this->selectedInvoices)) {
                $this->allocations[$sale['id']] = [
                    'sale_id' => $sale['id'],
                    'invoice_number' => $sale['invoice_number'],
                    'due_amount' => $sale['due_amount'],
                    'payment_amount' => 0,
                    'is_fully_paid' => false
                ];
            }
        }
    }

    private function autoAllocatePayment()
    {
        $remainingPayment = (float)$this->totalPaymentAmount;

        if (in_array('opening', $this->selectedInvoices)) {
            $openingDue = (float) ($this->selectedCustomer->opening_balance ?? 0);
            $openingPayment = min($remainingPayment, $openingDue);
            $this->allocations['opening']['payment_amount'] = $openingPayment;
            $this->allocations['opening']['is_fully_paid'] = $openingPayment >= $openingDue;
            $remainingPayment -= $openingPayment;
        }

        foreach ($this->customerSales as $sale) {
            $saleId = $sale['id'];

            // Only allocate to selected invoices
            if (!in_array($saleId, $this->selectedInvoices)) {
                continue;
            }

            $dueAmount = $sale['due_amount'];

            if ($remainingPayment <= 0) {
                $this->allocations[$saleId]['payment_amount'] = 0;
                $this->allocations[$saleId]['is_fully_paid'] = false;
            } elseif ($remainingPayment >= $dueAmount) {
                $this->allocations[$saleId]['payment_amount'] = $dueAmount;
                $this->allocations[$saleId]['is_fully_paid'] = true;
                $remainingPayment -= $dueAmount;
            } else {
                $this->allocations[$saleId]['payment_amount'] = $remainingPayment;
                $this->allocations[$saleId]['is_fully_paid'] = false;
                $remainingPayment = 0;
            }
        }
    }

    public function openPaymentModal()
    {
        // Only allow opening if invoices are selected
        if (empty($this->selectedInvoices)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Please select at least one invoice to process payment.'
            ]);
            return;
        }

        // Validate payment amount
        if (!$this->totalPaymentAmount || $this->totalPaymentAmount <= 0) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Please enter a payment amount greater than zero.'
            ]);
            return;
        }

        if ($this->totalPaymentAmount > $this->totalDueAmount) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Payment amount cannot exceed total due amount.'
            ]);
            return;
        }

        // Allocate payment
        $this->autoAllocatePayment();

        // Update first payment row amount if it's the only one
        if (count($this->paymentRows) === 1) {
            $this->paymentRows[0]['amount'] = $this->totalPaymentAmount;
        }

        // Show modal
        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->paymentSuccess = false;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->selectedSale = null;
    }

    public function openReceiptModal()
    {
        $this->showReceiptModal = true;
    }

    public function closeReceiptModal()
    {
        $this->showReceiptModal = false;
        $this->latestPayment = null;

        // Reset everything
        $this->selectedCustomer = null;
        $this->customerSales = [];
        $this->selectedInvoices = [];
        $this->allocations = [];
        $this->totalDueAmount = 0;
        $this->totalPaymentAmount = '';
        $this->remainingAmount = 0;
        $this->resetPaymentData();
    }


    public function viewSale($saleId)
    {
        $this->selectedSale = Sale::with(['customer', 'items', 'payments', 'returns.product'])->find($saleId);

        // Calculate return amount for display
        if ($this->selectedSale) {
            $this->selectedSale->return_amount = $this->calculateReturnAmount($saleId);
            $this->selectedSale->adjusted_total = $this->selectedSale->total_amount - $this->selectedSale->return_amount;
            $this->selectedSale->adjusted_due = max(0, $this->selectedSale->due_amount - $this->selectedSale->return_amount);
        }

        $this->showViewModal = true;
    }

    /**
     * Automatically mark sale as paid if returns cover the full due amount
     */
    private function autoMarkSaleAsPaid($saleId, $returnAmount)
    {
        try {
            $sale = Sale::find($saleId);
            if ($sale && $sale->due_amount <= $returnAmount) {
                DB::beginTransaction();

                // Create a system payment record for the return adjustment
                $payment = Payment::create([
                    'customer_id' => $sale->customer_id,
                    'amount' => min($sale->due_amount, $returnAmount),
                    'payment_method' => 'return_adjustment',
                    'payment_reference' => 'AUTO-RETURN-' . $sale->invoice_number,
                    'payment_date' => now(),
                    'status' => 'paid',
                    'is_completed' => 1,
                    'notes' => 'Automatically adjusted due to product returns covering the full amount',
                    'created_by' => Auth::id() ?? 1,
                ]);

                // Create payment allocation
                DB::table('payment_allocations')->insert([
                    'payment_id' => $payment->id,
                    'sale_id' => $saleId,
                    'allocated_amount' => min($sale->due_amount, $returnAmount),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update sale
                $sale->due_amount = 0;
                $sale->payment_status = 'paid';
                $sale->save();

                DB::commit();

                Log::info('Sale automatically marked as paid due to returns', [
                    'sale_id' => $saleId,
                    'return_amount' => $returnAmount,
                    'payment_id' => $payment->id
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to auto-mark sale as paid', [
                'sale_id' => $saleId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function processPayment()
    {
        Log::info('Payment processing started', [
            'customer_id' => $this->selectedCustomer->id,
            'total_amount' => $this->totalPaymentAmount,
        ]);

        // Validate inputs
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            $firstError = collect($e->errors())->flatten()->first();
            $this->dispatch('show-toast', ['type' => 'error', 'message' => $firstError ?? 'Please fill all required fields correctly.']);
            return;
        }

        $totalRowsAmount = collect($this->paymentRows)->sum('amount');
        if (abs((float)$totalRowsAmount - (float)$this->totalPaymentAmount) > 0.01) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'The sum of payment method amounts (Rs. ' . number_format($totalRowsAmount, 2) . ') must equal the Total Payment Amount (Rs. ' . number_format((float)$this->totalPaymentAmount, 2) . ').'
            ]);
            return;
        }

        // Validate against blocked holidays / poya days
        foreach ($this->paymentRows as $index => $row) {
            if ($row['method'] === 'cheque' && !empty($row['cheque_date'])) {
                if (Holiday::isHoliday($row['cheque_date'])) {
                    $reason = Holiday::getHolidayReason($row['cheque_date']);
                    $this->addError("paymentRows.{$index}.cheque_date", "The selected date is marked as a Holiday / Poya Day ({$reason}).");
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => "Cheque date ({$row['cheque_date']}) is a Holiday/Poya Day ({$reason}). Cheques cannot be accepted on this date."
                    ]);
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
                    'customer_id' => $this->selectedCustomer->id,
                    'amount' => $row['amount'],
                    'payment_method' => $row['method'],
                    'payment_reference' => $groupReference, // Shared reference for grouping
                    'payment_date' => $this->paymentDate,
                    'notes' => $this->paymentNotes,
                    'status' => 'paid',
                    'is_completed' => 1,
                    'created_by' => Auth::id(),
                ];

                if ($row['method'] === 'bank_transfer') {
                    $paymentData['bank_name'] = $row['bank_name'];
                    $paymentData['transfer_date'] = $row['transfer_date'];
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

                    Cheque::create([
                        'payment_id' => $payment->id,
                        'cheque_number' => $row['cheque_number'],
                        'bank_name' => $row['bank_name'],
                        'cheque_date' => $row['cheque_date'],
                        'cheque_amount' => $row['amount'],
                        'cheque_photo_url' => $photoUrl,
                        'status' => 'pending',
                        'customer_id' => $this->selectedCustomer->id,
                    ]);
                }

                if ($row['method'] === 'cash') {
                    $this->updateCashInHands((float)$row['amount']);
                }
            }

            // 2. Allocate payments to invoices
            // We'll use a simple sequential allocation: take each created payment and apply its amount to pending allocations
            $currentAllocations = $this->allocations; // Local copy to track remaining needs
            
            foreach ($createdPayments as $payment) {
                $remainingPaymentAmount = $payment->amount;

                foreach ($currentAllocations as $saleId => &$allocation) {
                    if ($remainingPaymentAmount <= 0) break;

                    $remainingNeededForThisSale = $allocation['payment_amount'];
                    if ($remainingNeededForThisSale <= 0) continue;

                    $allocationAmount = min($remainingPaymentAmount, $remainingNeededForThisSale);

                    if ($saleId === 'opening') {
                        $this->selectedCustomer->opening_balance = max(0, (float) $this->selectedCustomer->opening_balance - $allocationAmount);
                        $this->selectedCustomer->save();
                        $remainingPaymentAmount -= $allocationAmount;
                        $allocation['payment_amount'] -= $allocationAmount;
                        $totalProcessed += $allocationAmount;
                        continue;
                    }

                    // Create allocation link
                    DB::table('payment_allocations')->insert([
                        'payment_id' => $payment->id,
                        'sale_id' => $saleId,
                        'allocated_amount' => $allocationAmount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Update Sale model
                    $saleModel = Sale::find($saleId);
                    if ($saleModel) {
                        $saleModel->due_amount = max(0, $saleModel->due_amount - $allocationAmount);
                        $saleModel->payment_status = $saleModel->due_amount <= 0.01 ? 'paid' : 'partial';
                        $saleModel->save();
                    }

                    $remainingPaymentAmount -= $allocationAmount;
                    $allocation['payment_amount'] -= $allocationAmount;
                    $totalProcessed += $allocationAmount;
                }
            }

            DB::commit();

            $this->latestPayment = $createdPayments[0]; // For receipt lookup

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "Payment of Rs." . number_format($totalProcessed, 2) . " processed successfully!"
            ]);

            // Open receipt modal
            $this->openReceiptModal();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Payment processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Failed to process payment. Please check your input and try again.'
            ]);
        }
    }

    public function downloadReceipt()
    {
        if (!$this->latestPayment) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'No payment receipt available to download.'
            ]);
            return;
        }

        try {
            // Find all payments in this group
            $payments = Payment::with(['cheques'])
                ->where('payment_reference', $this->latestPayment->payment_reference)
                ->get();

            if ($payments->isEmpty()) {
                $payments = collect([Payment::with(['cheques'])->find($this->latestPayment->id)]);
            }

            $paymentIds = $payments->pluck('id')->toArray();

            // Get allocations from all payments in this group
            $allocations = DB::table('payment_allocations')
                ->join('sales', 'payment_allocations.sale_id', '=', 'sales.id')
                ->whereIn('payment_allocations.payment_id', $paymentIds)
                ->select(
                    'sales.id as sale_id',
                    'sales.invoice_number',
                    'sales.total_amount',
                    DB::raw('SUM(payment_allocations.allocated_amount) as allocated_amount')
                )
                ->groupBy('sales.id', 'sales.invoice_number', 'sales.total_amount')
                ->get()
                ->map(function ($allocation) {
                    $returnAmount = $this->calculateReturnAmount($allocation->sale_id);
                    $allocation->return_amount = $returnAmount;
                    $allocation->adjusted_total = $allocation->total_amount - $returnAmount;
                    return $allocation;
                });

            $receiptData = [
                'payment' => $payments->first(), // Primary payment for header info
                'payments' => $payments, // All payments in the group
                'customer' => $this->selectedCustomer,
                'received_by' => Auth::user()->name,
                'payment_date' => $payments->first()->payment_date,
                'allocations' => $allocations,
                'total_amount_paid' => $payments->sum('amount'),
            ];

            $pdf = PDF::loadView('admin.receipts.payment-receipt', $receiptData);
            $pdf->setPaper('a4', 'portrait');

            $filename = 'payment-receipt-' . $groupReference = ($payments->first()->payment_reference ?? $payments->first()->id) . '-' . date('Y-m-d') . '.pdf';

            return response()->streamDownload(
                function () use ($pdf) {
                    echo $pdf->output();
                },
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Receipt download failed', [
                'error' => $e->getMessage(),
                'payment_id' => $this->latestPayment->id
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Failed to generate receipt: ' . $e->getMessage()
            ]);
        }
    }

    public function getCustomersProperty()
    {
        return Customer::with(['sales' => function ($query) {
            $query->whereIn('payment_status', ['pending', 'partial']);
        }])
            ->where(function ($query) {
                $query->where('opening_balance', '>', 0)
                    ->orWhereHas('sales', function ($salesQuery) {
                        $salesQuery->whereIn('payment_status', ['pending', 'partial'])
                            ->whereRaw('due_amount - COALESCE((SELECT SUM(total_amount) FROM returns_products WHERE returns_products.sale_id = sales.id), 0) > 0.01');
                    });
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.admin.add-customer-receipt', [
            'customers' => $this->customers
        ])->layout($this->layout);
    }

    private function updateCashInHands($amount)
    {
        // Update cash_amount record
        $cashAmountRecord = DB::table('cash_in_hands')->where('key', 'cash_amount')->first();

        if ($cashAmountRecord) {
            // Update existing record
            DB::table('cash_in_hands')
                ->where('key', 'cash_amount')
                ->update([
                    'value' => (float)$cashAmountRecord->value + (float)$amount,
                    'updated_at' => now()
                ]);
        } else {
            // Create new record
            DB::table('cash_in_hands')->insert([
                'key' => 'cash_amount',
                'value' => $amount,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    // uploadChequePhoto removed as it's now provided by HandlesChequeUploads trait
    }
}
