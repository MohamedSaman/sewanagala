<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\Component;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\POSSession;
use Illuminate\Support\Facades\Log;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title("Expenses")]
class Expenses extends Component
{
    use WithDynamicLayout, WithPagination;

    // Filters
    public $fromDateFilter = '';
    public $toDateFilter = '';
    public $perPage = 30;

    // Totals
    public $todayTotal = 0;
    public $monthTotal = 0;
    public $overallTotal = 0;
    public $dailyFilteredTotal = 0;
    public $monthlyFilteredTotal = 0;

    // Form inputs for creating
    public $category, $amount, $date, $status, $description;

    // Form inputs for editing
    public $expenseId;
    public $edit_category, $edit_amount, $edit_date, $edit_status, $edit_description, $edit_expense_type;

    // Delete confirmation
    public $expenseToDelete;

    // Modal states
    public $showEditDailyModal = false;
    public $showEditMonthlyModal = false;
    public $showDeleteModal = false;
    public $showViewModal = false;
    public $viewExpense = null;

    public function mount()
    {
        $this->fromDateFilter = Carbon::today()->format('Y-m-d');
        $this->toDateFilter = Carbon::today()->format('Y-m-d');
        $this->date = Carbon::today()->format('Y-m-d');
        $this->loadExpenses();
        $this->loadCategories();
    }

    public function loadCategories()
    {
        // No longer fetching from DB because category is now a text input.
    }

    public function updated($propertyName)
    {
        if ($propertyName === 'fromDateFilter' || $propertyName === 'toDateFilter') {
            $this->resetPage('dailyPage');
            $this->resetPage('monthlyPage');
            $this->loadExpenses();
        }
    }

    public function clearDateFilters()
    {
        $this->fromDateFilter = '';
        $this->toDateFilter = '';
        $this->resetPage('dailyPage');
        $this->resetPage('monthlyPage');
        $this->loadExpenses();
    }

    public function updatedPerPage()
    {
        $this->resetPage('dailyPage');
        $this->resetPage('monthlyPage');
    }

    public function loadExpenses()
    {
        // Totals
        $this->todayTotal = Expense::whereDate('date', Carbon::today())->sum('amount');
        $this->monthTotal = Expense::whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->sum('amount');

        $query = Expense::query();

        if ($this->fromDateFilter) {
            $query->whereDate('date', '>=', $this->fromDateFilter);
        }

        if ($this->toDateFilter) {
            $query->whereDate('date', '<=', $this->toDateFilter);
        }

        $this->overallTotal = $query->sum('amount');
    }

    public function saveDailyExpense()
    {
        $this->validate([
            'date' => 'required|date',
            'category' => 'required',
            'amount' => 'required|numeric|min:0',
        ]);

        Expense::create([
            'category' => $this->category,
            'amount' => $this->amount,
            'description' => $this->description,
            'date' => $this->date,
            'expense_type' => 'daily',
        ]);

        // Update cash in hands - subtract expense amount
        $cashInHandRecord = DB::table('cash_in_hands')->where('key', 'cash_amount')->first();

        if ($cashInHandRecord) {
            DB::table('cash_in_hands')
                ->where('key', 'cash_amount')
                ->update([
                    'value' => $cashInHandRecord->value - $this->amount,
                    'updated_at' => now()
                ]);
        }

        // Update today's POS session expenses only when expense date is today
        try {
            if ($this->date && Carbon::parse($this->date)->toDateString() === Carbon::today()->toDateString()) {
                $session = POSSession::getTodaySession(Auth::id());
                if (! $session) {
                    // create an open session with zero opening cash so expense is tracked
                    $session = POSSession::openSession(Auth::id(), 0);
                }

                $session->expenses = ($session->expenses ?? 0) + $this->amount;
                $session->save();
                // Recalculate expected cash / difference
                $session->calculateDifference();
            }
        } catch (\Exception $e) {
            Log::error('Failed to update POS session after daily expense: ' . $e->getMessage());
        }

        $this->reset(['category', 'amount', 'description']);
        $this->date = Carbon::today()->format('Y-m-d');
        $this->loadExpenses();
        $this->js("swal.fire('Success!', 'Daily expense added successfully.', 'success')");
        $this->dispatch('close-modal', 'addDailyExpenseModal');
        $this->dispatch('refreshPage');
    }

    public function saveMonthlyExpense()
    {
        $this->validate([
            'date' => 'required|date',
            'category' => 'required',
            'amount' => 'required|numeric|min:0',
        ]);

        Expense::create([
            'date' => $this->date,
            'category' => $this->category,
            'amount' => $this->amount,
            'status' => 'Paid',
            'description' => $this->description,
            'expense_type' => 'monthly',
        ]);

        // Update cash in hands - subtract expense amount
        $cashInHandRecord = DB::table('cash_in_hands')->where('key', 'cash_amount')->first();

        if ($cashInHandRecord) {
            DB::table('cash_in_hands')
                ->where('key', 'cash_amount')
                ->update([
                    'value' => $cashInHandRecord->value - $this->amount,
                    'updated_at' => now()
                ]);
        }

        // If the monthly expense is for today, update today's POS session totals
        try {
            if ($this->date && Carbon::parse($this->date)->toDateString() === Carbon::today()->toDateString()) {
                $session = POSSession::getTodaySession(Auth::id());
                if (! $session) {
                    $session = POSSession::openSession(Auth::id(), 0);
                }

                $session->expenses = ($session->expenses ?? 0) + $this->amount;
                $session->save();
                $session->calculateDifference();
            }
        } catch (\Exception $e) {
            Log::error('Failed to update POS session after monthly expense: ' . $e->getMessage());
        }

        $this->reset(['date', 'category', 'amount', 'status', 'description']);
        $this->loadExpenses();
        $this->js("swal.fire('Success!', 'Monthly expense added successfully.', 'success')");
        $this->dispatch('close-modal', 'addMonthlyExpenseModal');
        $this->dispatch('refreshPage');
    }

    public function confirmDelete($id)
    {
        $this->expenseToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteExpense()
    {
        if ($this->expenseToDelete) {
            Expense::findOrFail($this->expenseToDelete)->delete();
            $this->loadExpenses();
            $this->js("swal.fire('Deleted!', 'Expense has been deleted.', 'success')");
            $this->showDeleteModal = false;
            $this->expenseToDelete = null;
            $this->dispatch('refreshPage');
        }
    }

    public function editExpense($id)
    {
        $expense = Expense::findOrFail($id);

        $this->expenseId = $expense->id;
        $this->edit_category = $expense->category;
        $this->edit_description = $expense->description;
        $this->edit_amount = $expense->amount;
        $this->edit_date = $expense->date ? $expense->date->format('Y-m-d') : '';
        $this->edit_status = $expense->status;
        $this->edit_expense_type = $expense->expense_type;

        // Open modal based on expense type
        if ($expense->expense_type === 'daily') {
            $this->showEditDailyModal = true;
        } else {
            $this->showEditMonthlyModal = true;
        }
    }

    public function viewExpense($id)
    {
        $this->viewExpense = Expense::findOrFail($id);
        $this->showViewModal = true;
    }

    public function updateExpense()
    {
        $this->validate([
            'edit_category' => 'required|string',
            'edit_amount' => 'required|numeric|min:0',
        ]);

        $expense = Expense::findOrFail($this->expenseId);

        $updateData = [
            'category' => $this->edit_category,
            'description' => $this->edit_description,
            'amount' => $this->edit_amount,
        ];

        if ($expense->expense_type === 'monthly') {
            $this->validate(['edit_date' => 'required|date']);
            $updateData['date'] = $this->edit_date;
            $updateData['status'] = 'Paid';
        } else {
            // For daily, use today's date
            $updateData['date'] = now();
        }

        $expense->update($updateData);

        // Close the modals
        $this->showEditDailyModal = false;
        $this->showEditMonthlyModal = false;

        $this->resetEditFields();
        $this->loadExpenses();
        $this->js("swal.fire('Success!', 'Expense updated successfully.', 'success')");
        $this->dispatch('refreshPage');
    }

    public function resetEditFields()
    {
        $this->reset([
            'expenseId',
            'edit_category',
            'edit_amount',
            'edit_date',
            'edit_status',
            'edit_description',
            'edit_expense_type'
        ]);
        $this->resetErrorBag();
    }

    public function resetFields()
    {
        $this->reset(['category', 'amount', 'status', 'description']);
        $this->date = Carbon::today()->format('Y-m-d');
        $this->resetErrorBag();
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewExpense = null;
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->expenseToDelete = null;
    }

    public function closeEditDailyModal()
    {
        $this->showEditDailyModal = false;
        $this->resetEditFields();
    }

    public function closeEditMonthlyModal()
    {
        $this->showEditMonthlyModal = false;
        $this->resetEditFields();
    }

    public function render()
    {
        $dailyQuery = Expense::where('expense_type', 'daily');
        $monthlyQuery = Expense::where('expense_type', 'monthly');

        if ($this->fromDateFilter) {
            $dailyQuery->whereDate('date', '>=', $this->fromDateFilter);
            $monthlyQuery->whereDate('date', '>=', $this->fromDateFilter);
        }

        if ($this->toDateFilter) {
            $dailyQuery->whereDate('date', '<=', $this->toDateFilter);
            $monthlyQuery->whereDate('date', '<=', $this->toDateFilter);
        }

        $dailyQuery = $dailyQuery->latest();
        $monthlyQuery = $monthlyQuery->latest();

        $this->dailyFilteredTotal = (clone $dailyQuery)->sum('amount');
        $this->monthlyFilteredTotal = (clone $monthlyQuery)->sum('amount');

        if ($this->perPage === 'all') {
            $dailyTotal = (clone $dailyQuery)->count();
            $monthlyTotal = (clone $monthlyQuery)->count();
            $dailyExpenses = $dailyQuery->paginate($dailyTotal > 0 ? $dailyTotal : 1, ['*'], 'dailyPage');
            $monthlyExpenses = $monthlyQuery->paginate($monthlyTotal > 0 ? $monthlyTotal : 1, ['*'], 'monthlyPage');
        } else {
            $dailyExpenses = $dailyQuery->paginate((int) $this->perPage, ['*'], 'dailyPage');
            $monthlyExpenses = $monthlyQuery->paginate((int) $this->perPage, ['*'], 'monthlyPage');
        }

        return view('livewire.admin.expenses', [
            'dailyExpenses' => $dailyExpenses,
            'monthlyExpenses' => $monthlyExpenses,
        ])->layout($this->layout);
    }

    public function exportCSV()
    {
        $query = Expense::query();

        if ($this->fromDateFilter) {
            $query->whereDate('date', '>=', $this->fromDateFilter);
        }
        if ($this->toDateFilter) {
            $query->whereDate('date', '<=', $this->toDateFilter);
        }

        $expenses = $query->latest('date')->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=expenses_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Date', 'Category', 'Description', 'Amount', 'Type'];

        $callback = function() use($expenses, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($expenses as $expense) {
                fputcsv($file, [
                    $expense->date ? \Carbon\Carbon::parse($expense->date)->format('d/m/Y') : '',
                    $expense->category,
                    $expense->description,
                    number_format($expense->amount, 2, '.', ''),
                    ucfirst($expense->expense_type),
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, 'expenses_' . now()->format('YmdHis') . '.csv', $headers);
    }
}
