<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class POSSession extends Model
{
    use HasFactory;

    protected $table = 'pos_sessions';

    protected $fillable = [
        'user_id',
        'session_date',
        'opening_cash',
        'closing_cash',
        'total_sales',
        'cash_sales',
        'cheque_payment',
        'credit_card_payment',
        'bank_transfer',
        'late_payment_bulk',
        'supplier_payment',
        'salary_payment',
        'refunds',
        'manual_returns',
        'expenses',
        'cash_deposit_bank',
        'expected_cash',
        'cash_difference',
        'notes',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'session_date' => 'date',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'cheque_payment' => 'decimal:2',
        'credit_card_payment' => 'decimal:2',
        'bank_transfer' => 'decimal:2',
        'late_payment_bulk' => 'decimal:2',
        'supplier_payment' => 'decimal:2',
        'salary_payment' => 'decimal:2',
        'refunds' => 'decimal:2',
        'manual_returns' => 'decimal:2',
        'expenses' => 'decimal:2',
        'cash_deposit_bank' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create today's open session for a user.
     * If another user already opened a session today, auto-inherit today's opening cash.
     */
    public static function getTodaySession($userId)
    {
        // 0. Auto-close any open sessions from previous days first
        self::autoClosePastSessions();

        // 1. Check for an existing open session for this user today
        $existingSession = self::where('user_id', $userId)
            ->whereDate('session_date', Carbon::today())
            ->where('status', 'open')
            ->first();

        if ($existingSession) {
            return $existingSession;
        }

        // 2. Check if ANY user already opened/created a session for today
        $todaySession = self::whereDate('session_date', Carbon::today())
            ->orderBy('id', 'asc')
            ->first();

        if ($todaySession && $todaySession->opening_cash !== null) {
            // Store opening cash was already set today by another user.
            // Auto-open session for this user using that opening cash without prompting.
            return self::create([
                'user_id' => $userId,
                'session_date' => now()->toDateString(),
                'opening_cash' => $todaySession->opening_cash,
                'status' => 'open',
                'notes' => 'Inherited store opening cash',
            ]);
        }

        return null;
    }

    /**
     * Auto-close any open sessions from previous days.
     * Calculates full expected cash as closing_cash and updates cash_in_hands.
     */
    public static function autoClosePastSessions()
    {
        $pastOpenSessions = self::where('status', 'open')
            ->whereDate('session_date', '<', Carbon::today())
            ->get();

        foreach ($pastOpenSessions as $session) {
            $session->autoClose();
        }
    }

    /**
     * Auto-close this session using current calculated cash as closing cash.
     */
    public function autoClose($notes = 'Auto-closed at midnight')
    {
        $expectedCash = $this->calculateExpectedCash();

        $existingClosedSession = self::where('user_id', $this->user_id)
            ->whereDate('session_date', $this->session_date)
            ->where('status', 'closed')
            ->where('id', '!=', $this->id)
            ->first();

        if ($existingClosedSession) {
            $existingClosedSession->update([
                'closing_cash' => $expectedCash,
                'expected_cash' => $expectedCash,
                'cash_difference' => 0,
                'notes' => $existingClosedSession->notes ? $existingClosedSession->notes . ' | ' . $notes : $notes,
            ]);
            $this->delete();
        } else {
            try {
                $this->update([
                    'closing_cash' => $expectedCash,
                    'expected_cash' => $expectedCash,
                    'cash_difference' => 0,
                    'status' => 'closed',
                    'closed_at' => now(),
                    'notes' => $this->notes ? $this->notes . ' | ' . $notes : $notes,
                ]);
            } catch (\Throwable $e) {
                $closed = self::where('user_id', $this->user_id)
                    ->whereDate('session_date', $this->session_date)
                    ->where('status', 'closed')
                    ->where('id', '!=', $this->id)
                    ->first();

                if ($closed) {
                    $closed->update([
                        'closing_cash' => $expectedCash,
                        'expected_cash' => $expectedCash,
                        'cash_difference' => 0,
                    ]);
                    $this->delete();
                } else {
                    throw $e;
                }
            }
        }

        // Update cash_in_hands table with the closing cash
        foreach (['cash in hand', 'cash_amount'] as $key) {
            \Illuminate\Support\Facades\DB::table('cash_in_hands')->updateOrInsert(
                ['key' => $key],
                ['value' => $expectedCash, 'updated_at' => now()]
            );
        }
    }

    /**
     * Create a new session
     */
    public static function openSession($userId, $openingCash, $notes = null)
    {
        // Auto-close past sessions first
        self::autoClosePastSessions();

        // Check if there's already an open session for this user today
        $existingSession = self::where('user_id', $userId)
            ->whereDate('session_date', Carbon::today())
            ->where('status', 'open')
            ->first();
        if ($existingSession) {
            return $existingSession;
        }

        return self::create([
            'user_id' => $userId,
            'session_date' => now()->toDateString(),
            'opening_cash' => $openingCash,
            'status' => 'open',
            'notes' => $notes,
        ]);
    }

    /**
     * Close the session
     */
    public function closeSession($closingCash = null, $notes = null)
    {
        $expectedCash = $this->calculateExpectedCash();
        $finalClosingCash = $closingCash !== null ? (float)$closingCash : $expectedCash;

        $existingClosedSession = self::where('user_id', $this->user_id)
            ->whereDate('session_date', $this->session_date)
            ->where('status', 'closed')
            ->where('id', '!=', $this->id)
            ->first();

        if ($existingClosedSession) {
            $existingClosedSession->update([
                'closing_cash' => $finalClosingCash,
                'expected_cash' => $expectedCash,
                'cash_difference' => $finalClosingCash - $expectedCash,
                'notes' => $notes ?: $existingClosedSession->notes,
            ]);
            $this->delete();
        } else {
            $this->update([
                'closing_cash' => $finalClosingCash,
                'status' => 'closed',
                'closed_at' => now(),
                'notes' => $notes,
            ]);
            $this->calculateDifference();
        }

        // Update cash_in_hands table with the closing cash
        foreach (['cash in hand', 'cash_amount'] as $key) {
            \Illuminate\Support\Facades\DB::table('cash_in_hands')->updateOrInsert(
                ['key' => $key],
                ['value' => $finalClosingCash, 'updated_at' => now()]
            );
        }
    }

    /**
     * Calculate expected cash and difference
     */
    public function calculateDifference()
    {
        $expectedCash = $this->calculateExpectedCash();
        $closingCash = $this->closing_cash !== null ? (float)$this->closing_cash : $expectedCash;
        $difference = $closingCash - $expectedCash;

        $this->update([
            'expected_cash' => $expectedCash,
            'cash_difference' => $difference,
        ]);
    }

    /**
     * Calculate current expected cash in hand for this session & update attributes
     */
    public function calculateExpectedCash()
    {
        $sessionDate = $this->session_date ? Carbon::parse($this->session_date)->toDateString() : now()->toDateString();

        // 1. Opening Cash
        $openingCash = (float)($this->opening_cash ?? 0);

        // 2. POS Cash Sales
        $posSalesToday = Sale::whereDate('created_at', $sessionDate)
            ->where('sale_type', 'pos')
            ->pluck('id');

        $cashSales = (float)Payment::whereIn('sale_id', $posSalesToday)
            ->where('payment_method', 'cash')
            ->whereDate('payment_date', $sessionDate)
            ->sum('amount');

        // 3. Late Cash Payments (bulk / admin cash payments)
        $lateCashPayments = (float)Payment::where(function ($query) use ($posSalesToday) {
            $query->whereNotIn('sale_id', $posSalesToday)
                ->orWhereNull('sale_id');
        })
            ->whereDate('payment_date', $sessionDate)
            ->where('payment_method', 'cash')
            ->where('is_completed', true)
            ->sum('amount');

        // 4. Expenses
        $expenses = (float)\Illuminate\Support\Facades\DB::table('expenses')
            ->whereDate('date', $sessionDate)
            ->sum('amount');

        // 5. Refunds (System Returns) - Cash refunds
        $refunds = (float)\Illuminate\Support\Facades\DB::table('returns_products')
            ->whereDate('created_at', $sessionDate)
            ->sum(\Illuminate\Support\Facades\DB::raw("CASE WHEN refund_cash_amount IS NOT NULL THEN refund_cash_amount WHEN refund_type IS NULL OR refund_type = 'cash' THEN total_amount ELSE 0 END"));

        // 6. Manual Returns - Cash refunds
        $manualReturns = (float)\Illuminate\Support\Facades\DB::table('manual_sale_returns')
            ->whereDate('created_at', $sessionDate)
            ->sum(\Illuminate\Support\Facades\DB::raw("CASE WHEN refund_cash_amount IS NOT NULL THEN refund_cash_amount WHEN refund_type IS NULL OR refund_type = 'cash' THEN total_amount ELSE 0 END"));

        // 7. Cash Deposit to Bank
        $cashDeposit = (float)\Illuminate\Support\Facades\DB::table('deposits')
            ->whereDate('date', $sessionDate)
            ->sum('amount');

        // 8. Supplier Payments (Cash)
        $supplierPayment = (float)\Illuminate\Support\Facades\DB::table('purchase_payments')
            ->whereDate('payment_date', $sessionDate)
            ->where('payment_method', 'cash')
            ->sum('amount');

        // 9. Salary Payments (Cash)
        $salaryPayment = (float)\Illuminate\Support\Facades\DB::table('salary_payments')
            ->whereDate('payment_date', $sessionDate)
            ->where('payment_method', 'cash')
            ->sum('amount');

        // Total POS Sales Amount (Cash + Cheque + Card + Bank)
        $totalSales = (float)Sale::whereDate('created_at', $sessionDate)
            ->where('sale_type', 'pos')
            ->sum('total_amount');

        $chequePayment = (float)Payment::whereIn('sale_id', $posSalesToday)
            ->where('payment_method', 'cheque')
            ->whereDate('payment_date', $sessionDate)
            ->sum('amount');

        $cardPayment = (float)Payment::whereIn('sale_id', $posSalesToday)
            ->where('payment_method', 'card')
            ->whereDate('payment_date', $sessionDate)
            ->sum('amount');

        $bankTransfer = (float)Payment::whereIn('sale_id', $posSalesToday)
            ->where('payment_method', 'bank_transfer')
            ->whereDate('payment_date', $sessionDate)
            ->sum('amount');

        // Update model properties
        $this->total_sales = $totalSales;
        $this->cash_sales = $cashSales;
        $this->cheque_payment = $chequePayment;
        $this->credit_card_payment = $cardPayment;
        $this->bank_transfer = $bankTransfer;
        $this->late_payment_bulk = $lateCashPayments;
        $this->expenses = $expenses;
        $this->refunds = $refunds;
        $this->manual_returns = $manualReturns;
        $this->cash_deposit_bank = $cashDeposit;
        $this->supplier_payment = $supplierPayment;
        $this->salary_payment = $salaryPayment;

        $expectedCash = $openingCash + $cashSales + $lateCashPayments - $expenses - $refunds - $manualReturns - $cashDeposit - $supplierPayment - $salaryPayment;

        $this->expected_cash = $expectedCash;
        $this->save();

        return $expectedCash;
    }

    /**
     * Update session totals from sales
     */
    public function updateFromSales()
    {
        return $this->calculateExpectedCash();
    }

    /**
     * Check if session is closed
     */
    public function isClosed()
    {
        return $this->status === 'closed';
    }

    /**
     * Check if session is open
     */
    public function isOpen()
    {
        return $this->status === 'open';
    }
}
