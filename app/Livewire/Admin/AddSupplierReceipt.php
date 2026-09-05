<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductSupplier;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\PurchasePaymentAllocation;
use App\Models\POSSession;
use App\Models\SupplierCheque;
use App\Models\Holiday;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title("Add Supplier Receipt")]
class AddSupplierReceipt extends Component
{
    use WithDynamicLayout;
    use WithPagination;

    public $search = '';
    public $selectedSupplier = null;
    public $supplierOrders = [];
    public $selectedOrders = [];
    public $supplierGivenCheques = [];
    public $totalDueAmount = 0;
    public $totalPaymentAmount = '';
    public $remainingAmount = 0;
    public $showPaymentModal = false;
    public $showReceiptModal = false;
    public $lastPayment = null;
    public $selectedOrderForView = null;
    public $showOrderDetailsModal = false;

    // Overpayment tracking
    public $supplierOverpayment = 0;
    public $useOverpayment = false;
    public $overpaymentToApply = '';

    // Multi-Payment Rows
    public $paymentRows = [];
    public $paymentDate = '';
    public $paymentNotes = '';

    // Allocations
    public $allocations = [];

    // Quick Create Supplier Modal
    public $showCreateSupplierModal = false;
    public $newSupplierName = '';
    public $newSupplierBusinessName = '';
    public $newSupplierPhone = '';
    public $newSupplierContact = '';
    public $newSupplierEmail = '';
    public $newSupplierAddress = '';
    public $newSupplierOpeningBalance = 0;
    public $newSupplierNotes = '';

    public function mount()
    {
        $this->paymentDate = now()->format('Y-m-d');
        $this->totalPaymentAmount = '';
        $this->addPaymentRow();
    }

    public function updatedSearch($value)
    {
        $this->resetPage();
        $this->selectedSupplier = null;
        $this->supplierOrders = [];
        $this->resetPaymentData();
    }

    public function updatedTotalPaymentAmount()
    {
        $amount = (float)$this->totalPaymentAmount;
        $maxPayment = $this->totalDueAmount - (float)$this->overpaymentToApply;
        if ($amount > $maxPayment) {
            $this->totalPaymentAmount = $maxPayment;
        }
        if ($this->totalPaymentAmount !== '' && $amount < 0) {
            $this->totalPaymentAmount = '';
        }

        $this->calculateRemainingAmount();
        $this->autoAllocatePayment();

        // If only one payment row exists, update its amount to match total
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

    public function selectSupplier($supplierId)
    {
        $this->selectedSupplier = ProductSupplier::find($supplierId);
        $this->loadSupplierOrders();
        $this->loadSupplierCheques();
        
        $this->selectedOrders = collect($this->supplierOrders)->pluck('id')->toArray();
        if ((float) ($this->selectedSupplier?->balance_total ?? 0) > 0) {
            array_unshift($this->selectedOrders, 'opening');
        }
        
        $this->calculateTotalDue();
        $this->totalPaymentAmount = '';
        $this->initializeAllocations();
        
        // Load supplier overpayment
        $this->supplierOverpayment = $this->selectedSupplier ? $this->selectedSupplier->getAvailableOverpayment() : 0;
        $this->useOverpayment = false;
        $this->overpaymentToApply = '';
    }

    public function clearSelectedSupplier()
    {
        $this->selectedSupplier = null;
        $this->supplierOrders = [];
        $this->selectedOrders = [];
        $this->supplierGivenCheques = [];
        $this->allocations = [];
        $this->totalDueAmount = 0;
        $this->totalPaymentAmount = '';
        $this->remainingAmount = 0;
        $this->supplierOverpayment = 0;
        $this->useOverpayment = false;
        $this->overpaymentToApply = '';
        $this->resetPaymentData();
    }

    public function loadSupplierCheques()
    {
        if (!$this->selectedSupplier) {
            $this->supplierGivenCheques = [];
            return;
        }

        $this->supplierGivenCheques = SupplierCheque::where('supplier_id', $this->selectedSupplier->id)
            ->orderBy('cheque_date', 'desc')
            ->get();
    }

    private function loadSupplierOrders()
    {
        if (!$this->selectedSupplier) return;

        $orders = PurchaseOrder::where('supplier_id', $this->selectedSupplier->id)
            ->where('due_amount', '>', 0)
            ->orderBy('order_date', 'asc')
            ->get();

        $this->supplierOrders = $orders;
    }

    public function toggleOrderSelection($orderId)
    {
        if (in_array($orderId, $this->selectedOrders)) {
            $this->selectedOrders = array_values(array_diff($this->selectedOrders, [$orderId]));
        } else {
            $this->selectedOrders[] = $orderId;
        }
        
        $this->calculateTotalDue();
        $this->totalPaymentAmount = '';
        $this->remainingAmount = $this->totalDueAmount;
        $this->initializeAllocations();
    }

    public function selectAllOrders()
    {
        $this->selectedOrders = collect($this->supplierOrders)->pluck('id')->toArray();
        if ((float) ($this->selectedSupplier?->balance_total ?? 0) > 0) {
            array_unshift($this->selectedOrders, 'opening');
        }
        $this->calculateTotalDue();
        $this->totalPaymentAmount = '';
        $this->remainingAmount = $this->totalDueAmount;
        $this->initializeAllocations();
    }

    public function clearOrderSelection()
    {
        $this->selectedOrders = [];
        $this->calculateTotalDue();
        $this->totalPaymentAmount = '';
        $this->remainingAmount = $this->totalDueAmount;
        $this->allocations = [];
    }

    private function calculateTotalDue()
    {
        $ordersDue = collect($this->supplierOrders)
            ->whereIn('id', $this->selectedOrders)
            ->sum('due_amount');
        
        $openingDue = in_array('opening', $this->selectedOrders) ? (float) ($this->selectedSupplier?->balance_total ?? 0) : 0;
        $this->totalDueAmount = $openingDue + $ordersDue;
        $this->remainingAmount = $this->totalDueAmount;
        
        // Reset overpayment application when orders change
        $this->useOverpayment = false;
        $this->overpaymentToApply = '';
    }

    public function toggleOverpayment()
    {
        $this->useOverpayment = !$this->useOverpayment;
        
        if ($this->useOverpayment && $this->supplierOverpayment > 0) {
            // Apply overpayment (max of available overpayment or total due)
            $this->overpaymentToApply = min($this->supplierOverpayment, $this->totalDueAmount);
        } else {
            $this->overpaymentToApply = '';
        }
        
        $this->calculateRemainingAmount();
    }

    public function updatedOverpaymentToApply()
    {
        $amount = (float)$this->overpaymentToApply;
        if ($amount > $this->supplierOverpayment) {
            $this->overpaymentToApply = $this->supplierOverpayment;
        }
        if ($amount > $this->totalDueAmount) {
            $this->overpaymentToApply = $this->totalDueAmount;
        }
        if ($this->overpaymentToApply !== '' && $amount < 0) {
            $this->overpaymentToApply = '';
        }
        
        $this->calculateRemainingAmount();
    }

    private function calculateRemainingAmount()
    {
        $this->remainingAmount = $this->totalDueAmount - (float)$this->totalPaymentAmount - (float)$this->overpaymentToApply;
        if ($this->remainingAmount < 0) {
            $this->remainingAmount = 0;
        }
    }

    private function initializeAllocations()
    {
        $this->allocations = [];

        if (in_array('opening', $this->selectedOrders)) {
            $this->allocations['opening'] = [
                'order_code' => 'Opening Balance',
                'due_amount' => (float) ($this->selectedSupplier?->balance_total ?? 0),
                'payment_amount' => 0,
                'is_fully_paid' => false,
            ];
        }

        foreach ($this->supplierOrders as $order) {
            if (in_array($order->id, $this->selectedOrders)) {
                $this->allocations[$order->id] = [
                    'order_code' => $order->order_code,
                    'due_amount' => (float) $order->due_amount,
                    'payment_amount' => 0,
                    'is_fully_paid' => false
                ];
            }
        }
    }

    private function autoAllocatePayment()
    {
        // Total payment includes both cash/cheque/bank payment and overpayment credit
        $remainingPayment = (float)$this->totalPaymentAmount + (float)$this->overpaymentToApply;

        if (in_array('opening', $this->selectedOrders)) {
            $openingDue = (float) ($this->selectedSupplier?->balance_total ?? 0);
            $openingPayment = min($remainingPayment, $openingDue);
            if (isset($this->allocations['opening'])) {
                $this->allocations['opening']['payment_amount'] = $openingPayment;
                $this->allocations['opening']['is_fully_paid'] = $openingPayment >= $openingDue;
            }
            $remainingPayment -= $openingPayment;
        }

        foreach ($this->supplierOrders as $order) {
            $orderId = $order->id;
            
            // Only allocate to selected orders
            if (!in_array($orderId, $this->selectedOrders)) {
                continue;
            }

            $dueAmount = (float) $order->due_amount;

            if ($remainingPayment <= 0) {
                $this->allocations[$orderId]['payment_amount'] = 0;
                $this->allocations[$orderId]['is_fully_paid'] = false;
            } elseif ($remainingPayment >= $dueAmount) {
                $this->allocations[$orderId]['payment_amount'] = $dueAmount;
                $this->allocations[$orderId]['is_fully_paid'] = true;
                $remainingPayment -= $dueAmount;
            } else {
                $this->allocations[$orderId]['payment_amount'] = $remainingPayment;
                $this->allocations[$orderId]['is_fully_paid'] = false;
                $remainingPayment = 0;
            }
        }
    }

    public function openPaymentModal()
    {
        if (empty($this->selectedOrders)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Please select at least one order or opening balance to make a payment.'
            ]);
            return;
        }

        if ((float)$this->totalPaymentAmount <= 0 && (float)$this->overpaymentToApply <= 0) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Please enter a payment amount or apply overpayment credit.'
            ]);
            return;
        }

        $totalPaymentWithOverpayment = (float)$this->totalPaymentAmount + (float)$this->overpaymentToApply;
        if ($totalPaymentWithOverpayment > $this->totalDueAmount) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Total payment amount cannot exceed total due amount.'
            ]);
            return;
        }

        $this->autoAllocatePayment();

        if (count($this->paymentRows) === 1) {
            $this->paymentRows[0]['amount'] = $this->totalPaymentAmount;
        }

        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function closeReceiptModal()
    {
        $this->showReceiptModal = false;
        $this->lastPayment = null;
        $this->clearSelectedSupplier();
    }

    public function closeOrderDetailsModal()
    {
        $this->showOrderDetailsModal = false;
        $this->selectedOrderForView = null;
    }

    public function viewOrderDetails($orderId)
    {
        $this->selectedOrderForView = PurchaseOrder::with(['supplier', 'items.product', 'returns.product'])
            ->find($orderId);
        $this->showOrderDetailsModal = true;
    }

    private function resetPaymentData()
    {
        $this->paymentRows = [];
        $this->paymentDate = now()->format('Y-m-d');
        $this->paymentNotes = '';
        $this->totalPaymentAmount = '';
        $this->addPaymentRow();
    }

    public function processPayment()
    {
        $overpaymentUsed = (float)$this->overpaymentToApply;

        if ((float)$this->totalPaymentAmount > 0) {
            $this->validate([
                'paymentDate' => 'required|date',
                'paymentRows.*.method' => 'required|in:cash,cheque,bank_transfer',
                'paymentRows.*.amount' => 'required|numeric|min:0.01',
                'paymentRows.*.cheque_number' => 'required_if:paymentRows.*.method,cheque',
                'paymentRows.*.bank_name' => 'required_if:paymentRows.*.method,cheque,bank_transfer',
                'paymentRows.*.cheque_date' => 'required_if:paymentRows.*.method,cheque|date',
                'totalPaymentAmount' => 'required|numeric|min:0.01',
            ], [
                'paymentRows.*.method.required' => 'Payment method is required.',
                'paymentRows.*.amount.required' => 'Amount is required for all methods.',
                'paymentRows.*.cheque_number.required_if' => 'Cheque number is required for cheques.',
                'paymentRows.*.bank_name.required_if' => 'Bank name is required.',
                'paymentRows.*.cheque_date.required_if' => 'Cheque date is required.',
            ]);

            $totalRowsAmount = collect($this->paymentRows)->sum('amount');
            if (abs((float)$totalRowsAmount - (float)$this->totalPaymentAmount) > 0.01) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'The sum of payment method amounts (Rs. ' . number_format($totalRowsAmount, 2) . ') must equal the Total Payment Amount (Rs. ' . number_format((float)$this->totalPaymentAmount, 2) . ').'
                ]);
                return;
            }

            // Check for duplicate cheque numbers and holidays
            foreach ($this->paymentRows as $index => $row) {
                if ($row['method'] === 'cheque' && !empty($row['cheque_date'])) {
                    if (Holiday::isHoliday($row['cheque_date'])) {
                        $reason = Holiday::getHolidayReason($row['cheque_date']);
                        $this->addError("paymentRows.{$index}.cheque_date", "The selected date is marked as a Holiday / Poya Day ({$reason}). Cheques cannot be dated on this day.");
                        $this->dispatch('show-toast', [
                            'type' => 'error',
                            'message' => "Selected cheque date ({$row['cheque_date']}) is a Holiday / Poya Day ({$reason})."
                        ]);
                        return;
                    }

                    $existsInCheques = SupplierCheque::where('cheque_number', $row['cheque_number'])
                        ->where('bank_name', $row['bank_name'])
                        ->exists();
                    $existsInPayments = PurchasePayment::where('payment_method', 'cheque')
                        ->where('cheque_number', $row['cheque_number'])
                        ->where('bank_name', $row['bank_name'])
                        ->exists();

                    if ($existsInCheques || $existsInPayments) {
                        $this->addError("paymentRows.{$index}.cheque_number", "Cheque number {$row['cheque_number']} for {$row['bank_name']} is already in use.");
                        $this->dispatch('show-toast', [
                            'type' => 'error',
                            'message' => "Cheque number {$row['cheque_number']} for {$row['bank_name']} is already registered."
                        ]);
                        return;
                    }
                }
            }
        }

        $totalPaymentWithOverpayment = (float)$this->totalPaymentAmount + $overpaymentUsed;
        if ($totalPaymentWithOverpayment > $this->totalDueAmount) {
            $this->addError('totalPaymentAmount', 'Total payment amount cannot exceed total due.');
            return;
        }

        DB::beginTransaction();
        try {
            $groupReference = 'SUP-RCPT-' . now()->format('YmdHis') . '-' . rand(10, 99);
            $createdPayments = [];
            $totalCashAmount = 0;

            if ((float)$this->totalPaymentAmount > 0) {
                foreach ($this->paymentRows as $row) {
                    if ($row['amount'] <= 0) continue;

                    $paymentRecord = [
                        'supplier_id' => $this->selectedSupplier->id,
                        'amount' => (float)$row['amount'],
                        'payment_method' => $row['method'],
                        'payment_reference' => $groupReference,
                        'payment_date' => $this->paymentDate,
                        'notes' => $this->paymentNotes . ($overpaymentUsed > 0 ? " (Overpayment credit applied: " . number_format($overpaymentUsed, 2) . ")" : ""),
                        'status' => $row['method'] === 'cash' ? 'paid' : 'pending',
                        'is_completed' => $row['method'] === 'cash' ? 1 : 0,
                        'overpayment_used' => 0,
                    ];

                    if ($row['method'] === 'cheque') {
                        $paymentRecord = array_merge($paymentRecord, [
                            'cheque_number' => $row['cheque_number'],
                            'bank_name' => $row['bank_name'],
                            'cheque_date' => $row['cheque_date'],
                            'cheque_status' => 'pending',
                        ]);
                    }

                    if ($row['method'] === 'bank_transfer') {
                        $paymentRecord = array_merge($paymentRecord, [
                            'bank_name' => $row['bank_name'],
                            'bank_transaction' => $row['transfer_reference'] ?? null,
                        ]);
                    }

                    $payment = PurchasePayment::create($paymentRecord);
                    $createdPayments[] = $payment;

                    if ($row['method'] === 'cheque') {
                        SupplierCheque::create([
                            'cheque_number' => $row['cheque_number'],
                            'cheque_date' => $row['cheque_date'],
                            'bank_name' => $row['bank_name'],
                            'amount' => (float)$row['amount'],
                            'supplier_id' => $this->selectedSupplier->id,
                            'payee_name' => $this->selectedSupplier->name,
                            'purchase_payment_id' => $payment->id,
                            'status' => 'pending',
                            'notes' => $this->paymentNotes ?? null,
                            'created_by' => auth()->id(),
                        ]);
                    }

                    if ($row['method'] === 'cash') {
                        $totalCashAmount += (float)$row['amount'];
                    }
                }
            } else {
                // Overpayment-only payment
                $payment = PurchasePayment::create([
                    'supplier_id' => $this->selectedSupplier->id,
                    'amount' => 0,
                    'payment_method' => 'overpayment_credit',
                    'payment_reference' => $groupReference,
                    'payment_date' => $this->paymentDate,
                    'notes' => "Payment made using overpayment credit: " . number_format($overpaymentUsed, 2),
                    'status' => 'paid',
                    'is_completed' => 1,
                    'overpayment_used' => $overpaymentUsed,
                ]);
                $createdPayments[] = $payment;
            }

            // Allocate to opening balance and orders
            $primaryPaymentId = $createdPayments[0]->id;

            foreach ($this->allocations as $orderId => $allocation) {
                if ($allocation['payment_amount'] <= 0) continue;

                if ($orderId === 'opening') {
                    // Deduct from supplier balance_total
                    $this->selectedSupplier->balance_total = max(0, (float)$this->selectedSupplier->balance_total - $allocation['payment_amount']);
                    $this->selectedSupplier->save();
                    continue;
                }

                PurchasePaymentAllocation::create([
                    'purchase_payment_id' => $primaryPaymentId,
                    'purchase_order_id' => $orderId,
                    'allocated_amount' => $allocation['payment_amount'],
                ]);

                $order = PurchaseOrder::find($orderId);
                if ($order) {
                    $order->due_amount -= $allocation['payment_amount'];
                    $order->due_amount = max(0, round((float)$order->due_amount, 2));
                    $order->save();
                }
            }

            // Deduct overpayment from supplier if used
            if ($overpaymentUsed > 0) {
                $this->selectedSupplier->useOverpayment($overpaymentUsed);
            }

            // Update POSSession for cash payments
            if ($totalCashAmount > 0) {
                $activeSession = POSSession::where('user_id', auth()->id())
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($activeSession) {
                    $activeSession->supplier_payment += $totalCashAmount;
                    $activeSession->save();
                }
            }

            DB::commit();

            $this->loadSupplierCheques();

            // Set lastPayment for receipt modal
            $this->lastPayment = PurchasePayment::with(['supplier', 'allocations.order'])
                ->find($primaryPaymentId);
            $this->lastPayment->overpayment_applied = $overpaymentUsed;
            $this->lastPayment->grouped_payments = $createdPayments;

            $this->showPaymentModal = false;
            $this->showReceiptModal = true;

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Supplier payment of Rs. ' . number_format($totalPaymentWithOverpayment, 2) . ' processed successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Supplier payment processing error: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Failed to save payment: ' . $e->getMessage()
            ]);
        }
    }

    public function printReceipt()
    {
        $this->dispatch('print-receipt', ['paymentId' => $this->lastPayment->id]);
    }

    public function downloadReceipt()
    {
        if (!$this->lastPayment) {
            return;
        }

        // Load the payment with relationships for PDF
        $payment = PurchasePayment::with(['supplier', 'allocations.order'])
            ->find($this->lastPayment->id);

        $pdf = Pdf::loadView('components.payment-receipt', ['payment' => $payment]);
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'payment-receipt-' . $payment->id . '.pdf');
    }

    public function openCreateSupplierModal()
    {
        $this->resetNewSupplierForm();
        $this->showCreateSupplierModal = true;
    }

    public function closeCreateSupplierModal()
    {
        $this->showCreateSupplierModal = false;
        $this->resetNewSupplierForm();
    }

    public function resetNewSupplierForm()
    {
        $this->newSupplierName = '';
        $this->newSupplierBusinessName = '';
        $this->newSupplierPhone = '';
        $this->newSupplierContact = '';
        $this->newSupplierEmail = '';
        $this->newSupplierAddress = '';
        $this->newSupplierOpeningBalance = 0;
        $this->newSupplierNotes = '';
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function saveNewSupplier()
    {
        $this->validate([
            'newSupplierName' => 'required|string|max:255',
            'newSupplierBusinessName' => 'nullable|string|max:255',
            'newSupplierPhone' => 'nullable|string|max:20',
            'newSupplierContact' => 'nullable|string|max:20',
            'newSupplierEmail' => 'nullable|email|max:255',
            'newSupplierAddress' => 'nullable|string|max:255',
            'newSupplierOpeningBalance' => 'nullable|numeric|min:0',
            'newSupplierNotes' => 'nullable|string|max:500',
        ], [
            'newSupplierName.required' => 'Supplier name is required.',
        ]);

        $supplier = ProductSupplier::create([
            'name' => $this->newSupplierName,
            'businessname' => $this->newSupplierBusinessName,
            'phone' => $this->newSupplierPhone,
            'contact' => $this->newSupplierContact ?: $this->newSupplierPhone,
            'email' => $this->newSupplierEmail,
            'address' => $this->newSupplierAddress,
            'balance_total' => (float) $this->newSupplierOpeningBalance,
            'status' => 'active',
            'notes' => $this->newSupplierNotes,
        ]);

        $this->closeCreateSupplierModal();
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => "Supplier '{$supplier->name}' created successfully!"
        ]);

        // Automatically select the newly created supplier
        $this->selectSupplier($supplier->id);
    }

    public function getSuppliersProperty()
    {
        return ProductSupplier::with(['orders' => function ($query) {
            $query->where('due_amount', '>', 0);
        }])
            ->where(function ($query) {
                $query->where('balance_total', '>', 0)
                      ->orWhereHas('orders', function ($q) {
                          $q->where('due_amount', '>', 0);
                      });
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('phone', 'like', "%{$this->search}%")
                      ->orWhere('contact', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%")
                      ->orWhere('businessname', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.admin.add-supplier-receipt', [
            'suppliers' => $this->suppliers
        ])->layout($this->layout);
    }
}
