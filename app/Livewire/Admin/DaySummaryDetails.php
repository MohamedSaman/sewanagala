<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithDynamicLayout;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\POSSession;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\PurchasePayment;
use Illuminate\Support\Facades\DB;

#[Title('Day Summary Details')]

class DaySummaryDetails extends Component
{
    use WithPagination;
    use WithDynamicLayout;

    public $sessionId;
    public $session;
    public $sessionDate;

    // Summary data
    public $cashInHand = 0;
    public $cashSales = 0;
    public $lateCashPayments = 0;
    public $expenses = 0;
    public $returns = 0;
    public $manualReturns = 0;
    public $cashDeposit = 0;
    public $currentCash = 0;
    public $supplierPayment = 0;
    public $salaryPayment = 0;

    public function mount($sessionId)
    {
        $this->sessionId = $sessionId;
        $this->loadSession();
        $this->loadSummaryData();
    }

    public function loadSession()
    {
        $this->session = POSSession::with('user')->findOrFail($this->sessionId);
        $this->sessionDate = $this->session->session_date;
    }

    public function loadSummaryData()
    {
        $sessionDate = $this->session->session_date;

        // Cash in Hand (Opening Cash)
        $this->cashInHand = $this->session->opening_cash;

        // Cash Sales (POS)
        $this->cashSales = $this->session->cash_sales;

        // Late Cash Payments (payments made today for old sales or without sale_id)
        $posSalesToday = Sale::whereDate('created_at', $sessionDate)
            ->where('sale_type', 'pos')
            ->pluck('id');

        $this->lateCashPayments = Payment::where(function ($query) use ($posSalesToday) {
            $query->whereNotIn('sale_id', $posSalesToday)
                ->orWhereNull('sale_id');
        })
            ->whereDate('payment_date', $sessionDate)
            ->where('payment_method', 'cash')
            ->where('is_completed', true)
            ->sum('amount');
        $this->supplierPayment = PurchasePayment::whereDate('payment_date', $sessionDate)
            ->where('payment_method', 'cash')
            ->sum('amount');
        $this->salaryPayment = DB::table('salary_payments')
            ->whereDate('payment_date', $sessionDate)
            ->where('payment_method', 'cash')
            ->sum('amount');

        // Expenses
        $this->expenses = (float)($this->session->expenses ?? 0);

        // Returns/Refunds (Cash refunds only)
        $this->returns = (float)DB::table('returns_products')
            ->whereDate('created_at', $sessionDate)
            ->sum(DB::raw("CASE WHEN refund_cash_amount IS NOT NULL THEN refund_cash_amount WHEN refund_type IS NULL OR refund_type = 'cash' THEN total_amount ELSE 0 END"));

        // Manual Returns (Cash refunds only)
        $this->manualReturns = (float)DB::table('manual_sale_returns')
            ->whereDate('created_at', $sessionDate)
            ->sum(DB::raw("CASE WHEN refund_cash_amount IS NOT NULL THEN refund_cash_amount WHEN refund_type IS NULL OR refund_type = 'cash' THEN total_amount ELSE 0 END"));

        // Cash Deposit
        $this->cashDeposit = (float)($this->session->cash_deposit_bank ?? 0);

        // Current Cash / Closing Cash Amount
        if ($this->session->isClosed() && $this->session->closing_cash !== null) {
            $this->currentCash = (float)$this->session->closing_cash;
        } else {
            $this->currentCash = $this->cashInHand + $this->cashSales + $this->lateCashPayments - $this->expenses - $this->returns - $this->manualReturns - $this->cashDeposit - $this->supplierPayment - $this->salaryPayment;
        }
    }

    public function goBack()
    {
        return redirect()->route('admin.day-summary');
    }

    public function render()
    {
        // Get cash payments for this session date
        $posSalesToday = Sale::whereDate('created_at', $this->sessionDate)
            ->where('sale_type', 'pos')
            ->pluck('id');

        $cashPayments = Payment::with(['sale.customer'])
            ->whereIn('sale_id', $posSalesToday)
            ->where('payment_method', 'cash')
            ->whereDate('payment_date', $this->sessionDate)
            ->orderBy('payment_date', 'desc')
            ->paginate(15);

        return view('livewire.admin.day-summary-details', [
            'cashPayments' => $cashPayments
        ])->layout($this->layout);
    }
}
