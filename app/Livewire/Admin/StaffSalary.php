<?php

namespace App\Livewire\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Salary;
use App\Models\SalaryPayment;
use App\Models\POSSession;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use App\Livewire\Concerns\WithDynamicLayout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

#[Title('Staff Salary Management')]
class StaffSalary extends Component
{
    use WithPagination, WithDynamicLayout;

    #[Url(history: true)]
    public $search = '';

    #[Url(history: true)]
    public $selectedMonth = '';

    // Payment Modal Properties
    public $showPayModal = false;
    public $payStaffId = null;
    public $payStaffName = '';
    public $payBasicSalary = 0;
    public $payPrevAdjustment = 0;
    public $payNetPayable = 0;
    public $payAlreadyPaid = 0;
    public $payRemaining = 0;
    public $payStatus = '';

    // Payment Form Fields
    public $paymentAmount = '';
    public $paymentDate = '';
    public $paymentMethod = 'cash';
    public $paymentNotes = '';
    public $payMonthStart = '';
    public $payMonthEnd = '';

    // View Modal Properties
    public $showHistoryModal = false;
    public $historyStaffId = null;
    public $historyStaffName = '';
    public $viewMonth = '';
    public $activeViewTab = 'month_details'; // 'month_details' or 'all_months'
    public $historyStaffData = [];
    public $historyPayments = [];
    public $historyMonthList = [];

    // Delete Payment
    public $deletePaymentId = null;
    public $showDeletePaymentModal = false;

    public function mount()
    {
        $currentMonth = date('Y-m');
        if (empty($this->selectedMonth) || $this->selectedMonth > $currentMonth) {
            $this->selectedMonth = $currentMonth;
        }
        $this->paymentDate = date('Y-m-d');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedMonth()
    {
        $currentMonth = date('Y-m');
        if ($this->selectedMonth > $currentMonth) {
            $this->selectedMonth = $currentMonth;
            $this->js("Swal.fire('Notice', 'Future months cannot be selected.', 'info')");
        }
        $this->resetPage();
    }

    public function previousMonth()
    {
        $current = Carbon::createFromFormat('Y-m', $this->selectedMonth ?: date('Y-m'));
        $this->selectedMonth = $current->subMonth()->format('Y-m');
        $this->resetPage();
    }

    public function currentMonth()
    {
        $this->selectedMonth = date('Y-m');
        $this->resetPage();
    }

    public function nextMonth()
    {
        $currentMonth = date('Y-m');
        $current = Carbon::createFromFormat('Y-m', $this->selectedMonth ?: $currentMonth);
        $next = $current->addMonth()->format('Y-m');

        if ($next <= $currentMonth) {
            $this->selectedMonth = $next;
            $this->resetPage();
        }
    }

    /**
     * Calculate salary details for a specific staff member and month.
     * Incorporates month-to-month adjustment carryover for overpayments.
     */
    public function getStaffSalaryCalculation(User $staff, string $monthStr): array
    {
        $basicSalary = (float) ($staff->userDetail->basic_salary ?? 0);
        $targetCarbon = Carbon::createFromFormat('Y-m', $monthStr);
        $monthStart = $targetCarbon->copy()->startOfMonth()->toDateString();
        $monthEnd = $targetCarbon->copy()->endOfMonth()->toDateString();
        $prevMonthStr = $targetCarbon->copy()->subMonth()->format('Y-m');

        // Calculate previous month's overpayment adjustment (negative balance)
        $prevAdjustment = $this->calculatePreviousAdjustment($staff, $prevMonthStr);

        // Net payable for this month (basic salary minus any previous overpayment deduction)
        $netPayable = max(0, $basicSalary + $prevAdjustment);

        // Sum of payments already recorded for this month (strictly within this month)
        $paidAmount = (float) SalaryPayment::where('user_id', $staff->id)
            ->where('payment_date', '>=', $monthStart)
            ->where('payment_date', '<=', $monthEnd)
            ->sum('amount');

        // Remaining amount or extra amount
        // If paidAmount > netPayable, remaining is negative (extra amount)
        $remainingAmount = $netPayable - $paidAmount;

        // Status calculation
        if ($paidAmount <= 0) {
            $status = 'pending';
        } elseif ($paidAmount < $netPayable) {
            $status = 'partial';
        } elseif (abs($paidAmount - $netPayable) < 0.01) {
            $status = 'paid';
        } else {
            $status = 'overpaid';
        }

        return [
            'staff_id' => $staff->id,
            'name' => $staff->name,
            'email' => $staff->email,
            'contact' => $staff->contact,
            'basic_salary' => $basicSalary,
            'previous_adjustment' => $prevAdjustment,
            'net_payable' => $netPayable,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'extra_amount' => $remainingAmount < 0 ? abs($remainingAmount) : 0,
            'status' => $status,
            'salary_month' => $monthStart,
            'month_formatted' => $targetCarbon->format('F Y'),
        ];
    }

    /**
     * Recursively or step-wise determine previous month's overpayment adjustment.
     * If previous month received more than its net payable, extra amount is deducted as negative adjustment.
     */
    protected function calculatePreviousAdjustment(User $staff, string $prevMonthStr): float
    {
        // Check if there are any payments or salary records in the previous month
        $prevCarbon = Carbon::createFromFormat('Y-m', $prevMonthStr);
        $prevMonthStart = $prevCarbon->copy()->startOfMonth()->toDateString();
        $prevMonthEnd = $prevCarbon->copy()->endOfMonth()->toDateString();

        $prevPaymentsExist = SalaryPayment::where('user_id', $staff->id)
            ->where('payment_date', '>=', $prevMonthStart)
            ->where('payment_date', '<=', $prevMonthEnd)
            ->exists();

        $prevSalaryRecord = Salary::where('user_id', $staff->id)
            ->where('salary_month', $prevMonthStart)
            ->first();

        if (!$prevPaymentsExist && !$prevSalaryRecord) {
            return 0.00;
        }

        $basicSalary = (float) ($staff->userDetail->basic_salary ?? 0);

        // Previous month's adjustment brought forward from month before that (if any)
        $monthBeforePrevStr = $prevCarbon->copy()->subMonth()->format('Y-m');
        $monthBeforePrevStart = Carbon::createFromFormat('Y-m', $monthBeforePrevStr)->startOfMonth()->toDateString();

        $prevPrevAdjustment = 0.00;
        $prevPrevRecord = Salary::where('user_id', $staff->id)
            ->where('salary_month', $monthBeforePrevStart)
            ->first();

        if ($prevPrevRecord && $prevPrevRecord->remaining_amount < 0) {
            $prevPrevAdjustment = (float) $prevPrevRecord->remaining_amount;
        }

        $prevNetPayable = max(0, $basicSalary + $prevPrevAdjustment);

        $prevPaidAmount = (float) SalaryPayment::where('user_id', $staff->id)
            ->where('payment_date', '>=', $prevMonthStart)
            ->where('payment_date', '<=', $prevMonthEnd)
            ->sum('amount');

        if ($prevSalaryRecord && $prevPaidAmount == 0 && $prevSalaryRecord->paid_amount > 0) {
            $prevPaidAmount = (float) $prevSalaryRecord->paid_amount;
        }

        if ($prevPaidAmount > $prevNetPayable) {
            // Extra amount recorded as negative adjustment (e.g. -10,000)
            return -($prevPaidAmount - $prevNetPayable);
        }

        return 0.00;
    }

    /**
     * Open Pay Salary Modal
     */
    public function openPayModal($staffId)
    {
        $staff = User::with('userDetail')->find($staffId);
        if (!$staff) {
            $this->js("Swal.fire('Error!', 'Staff not found.', 'error')");
            return;
        }

        $calc = $this->getStaffSalaryCalculation($staff, $this->selectedMonth);
        $targetCarbon = Carbon::createFromFormat('Y-m', $this->selectedMonth);
        $monthStart = $targetCarbon->copy()->startOfMonth()->toDateString();
        $monthEnd = min(date('Y-m-d'), $targetCarbon->copy()->endOfMonth()->toDateString());
        $this->payMonthStart = $monthStart;
        $this->payMonthEnd = $monthEnd;

        $this->payStaffId = $staff->id;
        $this->payStaffName = $staff->name;
        $this->payBasicSalary = $calc['basic_salary'];
        $this->payPrevAdjustment = $calc['previous_adjustment'];
        $this->payNetPayable = $calc['net_payable'];
        $this->payAlreadyPaid = $calc['paid_amount'];
        $this->payRemaining = $calc['remaining_amount'];
        $this->payStatus = $calc['status'];

        // Default payment amount to remaining balance if > 0, else 0
        $this->paymentAmount = $calc['remaining_amount'] > 0 ? $calc['remaining_amount'] : '';

        // Default payment date to current date if within month, else month end
        $currentDate = date('Y-m-d');
        if ($currentDate >= $this->payMonthStart && $currentDate <= $this->payMonthEnd) {
            $this->paymentDate = $currentDate;
        } else {
            $this->paymentDate = $this->payMonthEnd;
        }

        $this->paymentMethod = 'cash';
        $this->paymentNotes = '';

        $this->resetErrorBag();
        $this->showPayModal = true;
    }
    public function closePayModal()
    {
        $this->showPayModal = false;
        $this->payStaffId = null;
        $this->paymentAmount = '';
        $this->paymentNotes = '';
        $this->resetErrorBag();
    }

    public function fillRemainingAmount()
    {
        if ($this->payRemaining > 0) {
            $this->paymentAmount = $this->payRemaining;
        }
    }

    /**
     * Record Salary Payment
     */
    public function recordPayment()
    {
        $targetCarbon = Carbon::createFromFormat('Y-m', $this->selectedMonth);
        $monthStart = $targetCarbon->copy()->startOfMonth()->toDateString();
        $monthEnd = min(date('Y-m-d'), $targetCarbon->copy()->endOfMonth()->toDateString());

        $this->validate([
            'payStaffId' => 'required|exists:users,id',
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentDate' => "required|date|after_or_equal:{$monthStart}|before_or_equal:{$monthEnd}",
            'paymentMethod' => 'required|in:cash,bank_transfer,cheque,other',
            'paymentNotes' => 'nullable|string|max:500',
        ], [
            'paymentDate.after_or_equal' => 'Payment date cannot be in a previous month. Please select a date within ' . $targetCarbon->format('F Y') . '.',
            'paymentDate.before_or_equal' => 'Payment date cannot be in the future.',
        ]);

        try {
            DB::beginTransaction();

            $monthDate = Carbon::parse($this->paymentDate)->startOfMonth()->toDateString();
            $staff = User::with('userDetail')->findOrFail($this->payStaffId);

            // 1. Create or get Salary master record
            $salary = Salary::firstOrCreate(
                [
                    'user_id' => $staff->id,
                    'salary_month' => $monthDate,
                ],
                [
                    'salary_type' => 'monthly',
                    'basic_salary' => $staff->userDetail->basic_salary ?? 0,
                    'previous_adjustment' => $this->payPrevAdjustment,
                    'net_salary' => $this->payNetPayable,
                    'paid_amount' => 0,
                    'remaining_amount' => $this->payNetPayable,
                    'payment_status' => 'pending',
                ]
            );

            // 2. Insert Payment record
            SalaryPayment::create([
                'salary_id' => $salary->salary_id,
                'user_id' => $staff->id,
                'salary_month' => $monthDate,
                'amount' => (float) $this->paymentAmount,
                'payment_date' => $this->paymentDate,
                'payment_method' => $this->paymentMethod,
                'notes' => $this->paymentNotes,
                'created_by' => Auth::id(),
            ]);

            // If payment was made using cash, deduct from cash_in_hands and update today's POS session
            if ($this->paymentMethod === 'cash') {
                $payAmount = (float) $this->paymentAmount;

                // 1. Deduct from cash_in_hands table (both keys)
                foreach (['cash in hand', 'cash_amount'] as $key) {
                    $cashRec = DB::table('cash_in_hands')->where('key', $key)->first();
                    if ($cashRec) {
                        DB::table('cash_in_hands')->where('key', $key)->update([
                            'value' => $cashRec->value - $payAmount,
                            'updated_at' => now(),
                        ]);
                    }
                }

                // 2. If payment date is today, update active POS session
                if (Carbon::parse($this->paymentDate)->toDateString() === now()->toDateString()) {
                    $posSession = POSSession::getTodaySession(Auth::id());
                    if (!$posSession) {
                        $posSession = POSSession::where('session_date', now()->toDateString())
                            ->where('status', 'open')
                            ->first();
                    }
                    if (!$posSession) {
                        $posSession = POSSession::openSession(Auth::id(), 0);
                    }
                    if ($posSession) {
                        $posSession->salary_payment = ($posSession->salary_payment ?? 0) + $payAmount;
                        $posSession->save();
                        $posSession->calculateDifference();
                    }
                }
            }

            // 3. Sync and recalculate salary master record
            $this->syncSalaryMasterRecord($staff, $this->selectedMonth);

            DB::commit();

            $formattedAmount = number_format((float) $this->paymentAmount, 2);
            $this->js("Swal.fire('Success!', 'Salary payment of Rs. {$formattedAmount} recorded successfully.', 'success')");
            $this->closePayModal();

            if ($this->showHistoryModal && $this->historyStaffId) {
                $histStaff = User::with('userDetail')->find($this->historyStaffId);
                if ($histStaff) {
                    $this->loadStaffViewMonthData($histStaff, $this->viewMonth ?: $this->selectedMonth);
                    $this->loadStaffMonthList($histStaff);
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            $this->js("Swal.fire('Error!', '" . addslashes($e->getMessage()) . "', 'error')");
        }
    }

    /**
     * Synchronize Salary master record for reporting and persistence.
     */
    protected function syncSalaryMasterRecord(User $staff, string $monthStr)
    {
        $calc = $this->getStaffSalaryCalculation($staff, $monthStr);

        Salary::updateOrCreate(
            [
                'user_id' => $staff->id,
                'salary_month' => $calc['salary_month'],
            ],
            [
                'salary_type' => 'monthly',
                'basic_salary' => $calc['basic_salary'],
                'previous_adjustment' => $calc['previous_adjustment'],
                'net_salary' => $calc['net_payable'],
                'paid_amount' => $calc['paid_amount'],
                'remaining_amount' => $calc['remaining_amount'],
                'payment_status' => $calc['status'],
            ]
        );
    }

    /**
     * Open Payment History & Payslip Modal
     */
    public function openHistoryModal($staffId, $monthStr = null)
    {
        $staff = User::with('userDetail')->find($staffId);
        if (!$staff) {
            $this->js("Swal.fire('Error!', 'Staff not found.', 'error')");
            return;
        }

        $currentMonth = date('Y-m');
        $targetMonth = $monthStr ?: ($this->selectedMonth ?: $currentMonth);
        if ($targetMonth > $currentMonth) {
            $targetMonth = $currentMonth;
        }

        $this->historyStaffId = $staff->id;
        $this->historyStaffName = $staff->name;
        $this->viewMonth = $targetMonth;
        $this->activeViewTab = 'month_details';

        $this->loadStaffViewMonthData($staff, $targetMonth);
        $this->loadStaffMonthList($staff);

        $this->showHistoryModal = true;
    }

    public function selectViewMonth($monthStr)
    {
        $currentMonth = date('Y-m');
        if ($monthStr > $currentMonth) {
            $monthStr = $currentMonth;
        }

        $this->viewMonth = $monthStr;
        $this->activeViewTab = 'month_details';

        $staff = User::with('userDetail')->find($this->historyStaffId);
        if ($staff) {
            $this->loadStaffViewMonthData($staff, $monthStr);
        }
    }

    public function updatedViewMonth()
    {
        $currentMonth = date('Y-m');
        if ($this->viewMonth > $currentMonth) {
            $this->viewMonth = $currentMonth;
            $this->js("Swal.fire('Notice', 'Future months cannot be selected.', 'info')");
        }

        $staff = User::with('userDetail')->find($this->historyStaffId);
        if ($staff) {
            $this->loadStaffViewMonthData($staff, $this->viewMonth);
        }
    }

    public function previousViewMonth()
    {
        $current = Carbon::createFromFormat('Y-m', $this->viewMonth ?: date('Y-m'));
        $this->selectViewMonth($current->subMonth()->format('Y-m'));
    }

    public function nextViewMonth()
    {
        $currentMonth = date('Y-m');
        $current = Carbon::createFromFormat('Y-m', $this->viewMonth ?: $currentMonth);
        $next = $current->addMonth()->format('Y-m');
        if ($next <= $currentMonth) {
            $this->selectViewMonth($next);
        }
    }

    protected function loadStaffViewMonthData(User $staff, string $monthStr)
    {
        $targetCarbon = Carbon::createFromFormat('Y-m', $monthStr);
        $monthStart = $targetCarbon->copy()->startOfMonth()->toDateString();
        $monthEnd = $targetCarbon->copy()->endOfMonth()->toDateString();

        $this->historyStaffData = $this->getStaffSalaryCalculation($staff, $monthStr);

        // Strict boundary: Only show payment transactions dated within the selected month (never show previous month payments)
        $this->historyPayments = SalaryPayment::with('createdBy')
            ->where('user_id', $staff->id)
            ->where('payment_date', '>=', $monthStart)
            ->where('payment_date', '<=', $monthEnd)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get()
            ->toArray();
    }

    protected function loadStaffMonthList(User $staff)
    {
        $months = [];
        $current = Carbon::now()->startOfMonth();

        // Generate past 12 months up to current month (no future months)
        for ($i = 0; $i < 12; $i++) {
            $mCarbon = $current->copy()->subMonths($i);
            $mStr = $mCarbon->format('Y-m');
            $calc = $this->getStaffSalaryCalculation($staff, $mStr);
            $months[] = [
                'month_key' => $mStr,
                'month_name' => $mCarbon->format('F Y'),
                'basic_salary' => $calc['basic_salary'],
                'previous_adjustment' => $calc['previous_adjustment'],
                'net_payable' => $calc['net_payable'],
                'paid_amount' => $calc['paid_amount'],
                'remaining_amount' => $calc['remaining_amount'],
                'status' => $calc['status'],
                'payment_count' => SalaryPayment::where('user_id', $staff->id)
                    ->where('payment_date', '>=', $mCarbon->copy()->startOfMonth()->toDateString())
                    ->where('payment_date', '<=', $mCarbon->copy()->endOfMonth()->toDateString())
                    ->count(),
            ];
        }

        $this->historyMonthList = $months;
    }

    public function payFromViewModal($staffId, $monthStr)
    {
        $this->closeHistoryModal();
        $this->selectedMonth = $monthStr;
        $this->openPayModal($staffId);
    }

    public function closeHistoryModal()
    {
        $this->showHistoryModal = false;
        $this->historyStaffId = null;
        $this->historyPayments = [];
        $this->historyMonthList = [];
    }

    /**
     * Delete payment confirmation
     */
    public function confirmDeletePayment($paymentId)
    {
        $this->deletePaymentId = $paymentId;
        $this->showDeletePaymentModal = true;
    }

    public function cancelDeletePayment()
    {
        $this->deletePaymentId = null;
        $this->showDeletePaymentModal = false;
    }

    public function deletePayment()
    {
        try {
            $payment = SalaryPayment::find($this->deletePaymentId);
            if ($payment) {
                $staff = User::with('userDetail')->find($payment->user_id);
                $wasCash = $payment->payment_method === 'cash';
                $deletedAmount = (float) $payment->amount;
                $deletedDate = Carbon::parse($payment->payment_date)->toDateString();

                $payment->delete();

                // If deleted payment was cash, restore cash in hand and POSSession
                if ($wasCash) {
                    foreach (['cash in hand', 'cash_amount'] as $key) {
                        $cashRec = DB::table('cash_in_hands')->where('key', $key)->first();
                        if ($cashRec) {
                            DB::table('cash_in_hands')->where('key', $key)->update([
                                'value' => $cashRec->value + $deletedAmount,
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    if ($deletedDate === now()->toDateString()) {
                        $posSession = POSSession::where('session_date', now()->toDateString())->first();
                        if ($posSession) {
                            $posSession->salary_payment = max(0, ($posSession->salary_payment ?? 0) - $deletedAmount);
                            $posSession->save();
                            $posSession->calculateDifference();
                        }
                    }
                }

                if ($staff) {
                    $this->syncSalaryMasterRecord($staff, $this->viewMonth ?: $this->selectedMonth);
                }

                $this->js("Swal.fire('Deleted!', 'Payment record deleted successfully.', 'success')");
                $this->cancelDeletePayment();

                // Refresh history modal if open
                if ($this->showHistoryModal && $this->historyStaffId) {
                    $staff = User::with('userDetail')->find($this->historyStaffId);
                    if ($staff) {
                        $this->loadStaffViewMonthData($staff, $this->viewMonth ?: date('Y-m'));
                        $this->loadStaffMonthList($staff);
                    }
                }
            }
        } catch (Exception $e) {
            $this->js("Swal.fire('Error!', '" . addslashes($e->getMessage()) . "', 'error')");
        }
    }

    public function render()
    {
        $currentMonthStr = date('Y-m');
        $monthStr = $this->selectedMonth ?: $currentMonthStr;
        if ($monthStr > $currentMonthStr) {
            $monthStr = $currentMonthStr;
            $this->selectedMonth = $currentMonthStr;
        }
        $monthCarbon = Carbon::createFromFormat('Y-m', $monthStr);
        $monthFormatted = $monthCarbon->format('F Y');

        // Staff query
        $staffQuery = User::where('role', 'staff')
            ->with('userDetail')
            ->when(!empty($this->search), function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('contact', 'like', $term);
                });
            })
            ->orderBy('name', 'asc');

        $staffMembers = $staffQuery->paginate(15);

        // Compute salary details for each staff in the current page
        $salaryList = [];
        $totalMonthlyPayroll = 0;
        $totalPaidThisMonth = 0;
        $totalRemainingDue = 0;

        foreach ($staffMembers as $staff) {
            $calc = $this->getStaffSalaryCalculation($staff, $monthStr);
            $salaryList[] = $calc;
        }

        // Summary KPI figures for all active staff
        $allStaff = User::where('role', 'staff')->with('userDetail')->get();
        foreach ($allStaff as $st) {
            $stCalc = $this->getStaffSalaryCalculation($st, $monthStr);
            $totalMonthlyPayroll += $stCalc['basic_salary'];
            $totalPaidThisMonth += $stCalc['paid_amount'];
            if ($stCalc['remaining_amount'] > 0) {
                $totalRemainingDue += $stCalc['remaining_amount'];
            }
        }

        return view('livewire.admin.staff-salary', [
            'staffMembers' => $staffMembers,
            'salaryList' => $salaryList,
            'monthFormatted' => $monthFormatted,
            'totalStaffCount' => $allStaff->count(),
            'totalMonthlyPayroll' => $totalMonthlyPayroll,
            'totalPaidThisMonth' => $totalPaidThisMonth,
            'totalRemainingDue' => $totalRemainingDue,
        ])->layout($this->layout);
    }
}
