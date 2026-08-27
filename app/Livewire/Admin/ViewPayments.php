<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Payment;
use App\Models\Cheque;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title('View Payments')]
class ViewPayments extends Component
{
    use WithDynamicLayout;

    use WithPagination;
    
    public $search = '';
    public $perPage = 30;
    public $selectedPayment = null;
    public $filters = [
        'status' => '',
        'paymentMethod' => '',
        'startDate' => '',
        'endDate' => '',
    ];
    
    public function viewPaymentDetails($paymentId)
    {
        try {
            $this->selectedPayment = Payment::with([
                'sale',
                'sale.customer',
                'sale.user',
                'sale.items',
                'sale.items.product',
                'cheques',
                'allocations.sale',
                'allocations.sale.customer'
            ])->findOrFail($paymentId);
            
            // Dispatch event to open modal
            $this->dispatch('open-payment-modal');
            
        } catch (\Exception $e) {
            $this->dispatch('showToast', [
                'type' => 'error',
                'message' => 'Error loading payment: ' . $e->getMessage()
            ]);
        }
    }
    
    public function resetFilters()
    {
        $this->filters = [
            'status' => '',
            'paymentMethod' => '',
            'startDate' => '',
            'endDate' => '',
        ];
        $this->search = '';
        $this->resetPage();
    }
    
    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    public function updatedPerPage()
    {
        $this->resetPage();
    }
    
    public function updatedFilters()
    {
        $this->resetPage();
    }
    
    public function render()
    {
        $query = Payment::query()
            ->with(['sale', 'sale.customer', 'sale.user', 'customer', 'cheques', 'allocations.sale'])
            ->when($this->search, function($q) {
                $q->where(function($query) {
                    $query->whereHas('sale', function($sq) {
                        $sq->where('invoice_number', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('customer', function($cq) {
                        $cq->where('name', 'like', "%{$this->search}%")
                           ->orWhere('phone', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('sale.customer', function($cq) {
                        $cq->where('name', 'like', "%{$this->search}%")
                           ->orWhere('phone', 'like', "%{$this->search}%");
                    });
                });
            })
            ->when($this->filters['status'], function($q) {
                return $q->where('status', $this->filters['status']);
            })
            ->when($this->filters['paymentMethod'], function($q) {
                return $q->where('payment_method', $this->filters['paymentMethod']);
            })
            ->when($this->filters['startDate'], function($q) {
                return $q->where(function($query) {
                    $query->whereDate('payment_date', '>=', $this->filters['startDate'])
                          ->orWhere(function($sub) {
                              $sub->whereNull('payment_date')
                                  ->whereDate('created_at', '>=', $this->filters['startDate']);
                          });
                });
            })
            ->when($this->filters['endDate'], function($q) {
                return $q->where(function($query) {
                    $query->whereDate('payment_date', '<=', $this->filters['endDate'])
                          ->orWhere(function($sub) {
                              $sub->whereNull('payment_date')
                                  ->whereDate('created_at', '<=', $this->filters['endDate']);
                          });
                });
            });
            
        $payments = $query->orderBy('created_at', 'desc')->paginate($this->perPage);
        
        // Base query for stats matching current filters/search
        $statsQuery = Payment::query()
            ->when($this->search, function($q) {
                $q->where(function($query) {
                    $query->whereHas('sale', function($sq) {
                        $sq->where('invoice_number', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('customer', function($cq) {
                        $cq->where('name', 'like', "%{$this->search}%")
                           ->orWhere('phone', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('sale.customer', function($cq) {
                        $cq->where('name', 'like', "%{$this->search}%")
                           ->orWhere('phone', 'like', "%{$this->search}%");
                    });
                });
            })
            ->when($this->filters['status'], function($q) {
                return $q->where('status', $this->filters['status']);
            })
            ->when($this->filters['paymentMethod'], function($q) {
                return $q->where('payment_method', $this->filters['paymentMethod']);
            })
            ->when($this->filters['startDate'], function($q) {
                return $q->where(function($query) {
                    $query->whereDate('payment_date', '>=', $this->filters['startDate'])
                          ->orWhere(function($sub) {
                              $sub->whereNull('payment_date')
                                  ->whereDate('created_at', '>=', $this->filters['startDate']);
                          });
                });
            })
            ->when($this->filters['endDate'], function($q) {
                return $q->where(function($query) {
                    $query->whereDate('payment_date', '<=', $this->filters['endDate'])
                          ->orWhere(function($sub) {
                              $sub->whereNull('payment_date')
                                  ->whereDate('created_at', '<=', $this->filters['endDate']);
                          });
                });
            });

        // 1. Total Net Received Payments (excluding pending/rejected payments, and pending/returned/cancelled cheques)
        $totalPayments = (clone $statsQuery)->whereNotIn('status', ['rejected', 'pending'])->sum('amount')
            - Cheque::whereIn('payment_id', (clone $statsQuery)->select('id'))
                ->whereIn('status', ['return', 'cancelled', 'pending'])
                ->sum('cheque_amount');

        // 2. Pending Payments (pending approval, or cheque payments where cheque is pending)
        $pendingPayments = (clone $statsQuery)->where('status', 'pending')->where('payment_method', '!=', 'cheque')->sum('amount')
            + Cheque::whereIn('payment_id', (clone $statsQuery)->select('id'))
                ->where('status', 'pending')
                ->sum('cheque_amount');

        // 3. Returned Cheques
        $returnedChequesTotal = Cheque::whereIn('payment_id', (clone $statsQuery)->select('id'))
            ->where('status', 'return')
            ->sum('cheque_amount');

        // 4. Remaining Cheque Balance
        $returnChequesQuery = Cheque::whereIn('payment_id', (clone $statsQuery)->select('id'))
            ->where('status', 'return');
            
        $chequeIds = $returnChequesQuery->pluck('id')->toArray();
        $remainingChequeBalance = 0;
        
        if (!empty($chequeIds)) {
            $notes = array_map(function($id) {
                return 'Settlement for returned cheque ID: ' . $id;
            }, $chequeIds);
            
            $settlements = \App\Models\Payment::whereIn('notes', $notes)->get();

            $paymentsByChequeId = [];
            foreach ($settlements as $settlement) {
                if (preg_match('/Settlement for returned cheque ID: (\d+)/', $settlement->notes, $matches)) {
                    $id = $matches[1];
                    $paymentsByChequeId[$id] = ($paymentsByChequeId[$id] ?? 0) + $settlement->amount;
                }
            }

            foreach ($returnChequesQuery->get() as $cheque) {
                $paid = $paymentsByChequeId[$cheque->id] ?? 0;
                $remaining = max(0, $cheque->cheque_amount - $paid);
                $remainingChequeBalance += $remaining;
            }
        }
        
        return view('livewire.admin.view-payments', [
            'payments' => $payments,
            'totalPayments' => $totalPayments,
            'pendingPayments' => $pendingPayments,
            'returnedChequesTotal' => $returnedChequesTotal,
            'remainingChequeBalance' => $remainingChequeBalance,
        ])->layout($this->layout);
    }

    public function exportCSV()
    {
        $query = Payment::query()
            ->with(['sale', 'sale.customer', 'sale.user', 'customer', 'cheques', 'allocations.sale'])
            ->when($this->search, function($q) {
                $q->where(function($query) {
                    $query->whereHas('sale', function($sq) {
                        $sq->where('invoice_number', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('customer', function($cq) {
                        $cq->where('name', 'like', "%{$this->search}%")
                           ->orWhere('phone', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('sale.customer', function($cq) {
                        $cq->where('name', 'like', "%{$this->search}%")
                           ->orWhere('phone', 'like', "%{$this->search}%");
                    });
                });
            })
            ->when($this->filters['status'], function($q) {
                return $q->where('status', $this->filters['status']);
            })
            ->when($this->filters['paymentMethod'], function($q) {
                return $q->where('payment_method', $this->filters['paymentMethod']);
            })
            ->when($this->filters['startDate'], function($q) {
                return $q->where(function($query) {
                    $query->whereDate('payment_date', '>=', $this->filters['startDate'])
                          ->orWhere(function($sub) {
                              $sub->whereNull('payment_date')
                                  ->whereDate('created_at', '>=', $this->filters['startDate']);
                          });
                });
            })
            ->when($this->filters['endDate'], function($q) {
                return $q->where(function($query) {
                    $query->whereDate('payment_date', '<=', $this->filters['endDate'])
                          ->orWhere(function($sub) {
                              $sub->whereNull('payment_date')
                                  ->whereDate('created_at', '<=', $this->filters['endDate']);
                          });
                });
            });

        $payments = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=payments_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Invoice(s)', 'Customer Name', 'Customer Phone', 'Amount', 'Payment Method', 'Payment Status', 'Date'];

        $callback = function() use($payments, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($payments as $payment) {
                // Invoice resolution
                if ($payment->sale) {
                    $invoice = $payment->sale->invoice_number;
                } elseif ($payment->allocations->count() > 0) {
                    $invoice = $payment->allocations->map(fn($a) => $a->sale?->invoice_number)->filter()->implode(', ');
                } else {
                    $invoice = 'General Payment';
                }

                // Customer resolution
                $customer = $payment->customer ?? $payment->sale?->customer ?? ($payment->allocations->first()?->sale?->customer);
                $customerName = $customer ? $customer->name : 'Walk-in Customer';
                $customerPhone = $customer ? $customer->phone : '';

                // Payment Status resolution
                $cheque = $payment->cheques->first();
                if ($cheque && $cheque->status === 'return') {
                    $status = 'Cheque Returned';
                } elseif ($cheque && $cheque->status === 'cancelled') {
                    $status = 'Cheque Cancelled';
                } else {
                    $status = $payment->status ? ucfirst($payment->status) : ($payment->is_completed ? 'Paid' : 'Scheduled');
                }

                // Date resolution
                $date = $payment->payment_date ? $payment->payment_date->format('d/m/Y h:i A') : 
                       ($payment->due_date ? 'Due: '.$payment->due_date->format('d/m/Y') : 'N/A');

                // Staff resolution
                $staff = $payment->sale?->user?->name ?? 'N/A';

                fputcsv($file, [
                    $invoice,
                    $customerName,
                    $customerPhone,
                    number_format($payment->amount, 2, '.', ''),
                    ucfirst($payment->payment_method),
                    $status,
                    $date,
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, 'payments_' . now()->format('YmdHis') . '.csv', $headers);
    }
}
