<?php

namespace App\Livewire\Admin;

use App\Models\Cheque;
use App\Models\Expense;
use App\Models\Investor;
use App\Models\InvestorTransaction;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Investor Ledger')]
class InvestorLedger extends Component
{
    use \App\Livewire\Concerns\WithDynamicLayout;
    public Investor $investor;

    public array $rows = [];

    public array $descriptionSuggestions = [];

    public array $monthOptions = [];

    // Payment method modal properties
    public bool $showPaymentModal = false;

    public ?int $pendingPaymentIndex = null;
    public bool $isPendingInflow = false;

    public string $selectedPaymentMethod = 'cash';

    public string $chequeSearch = '';

    public ?int $selectedChequeId = null;

    public array $availableCheques = [];
    public bool $showChequeDetail = false;
    public array $chequeDetail = [];

    // Delete confirmation properties
    public bool $showDeleteModal = false;
    public ?int $indexToDelete = null;

    public function mount(Investor $investor): void
    {
        $user = auth()->user();
        if ($user && $user->role === 'staff') {
            if (!\App\Models\StaffPermission::hasPermission($user->id, 'view_investor_' . $investor->id)) {
                abort(403, 'Unauthorized access to this investor\'s ledger.');
            }
        }
        
        $this->investor = $investor;
        $this->monthOptions = $this->buildMonthOptions();
        $this->descriptionSuggestions = $this->buildDescriptionSuggestions();
        $this->loadRows();
    }

    protected function newRow(): array
    {
        return [
            'id' => null,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => '',
            'inflow' => '',
            'outflow' => '',
            'outflow_month' => '',
            'profit_start_date' => '',
            'profit_end_date' => '',
            'payment_method' => '',
            'cheque_id' => null,
            'cheque_number' => '',
            'cheque_bank' => '',
            'cheque_date' => '',
            'cheque_amount' => '',
            'cheque_customer' => '',
        ];
    }

    protected function buildMonthOptions(): array
    {
        $months = [];

        // Show last 24 months, excluding current month
        for ($i = 1; $i <= 24; $i++) {
            $date = now()->startOfMonth()->subMonths($i);
            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->format('F Y'),
            ];
        }

        return $months;
    }

    protected function buildDescriptionSuggestions(): array
    {
        $base = [
            'Investment',
            'Profit Margin',
            'Refund',
            'Expense',
            'Withdrawal',
        ];

        $history = $this->investor->transactions()
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->select('description')
            ->distinct()
            ->orderBy('description')
            ->pluck('description')
            ->map(fn($item) => trim((string) $item))
            ->filter()
            ->values()
            ->toArray();

        return array_values(array_unique(array_merge($base, $history)));
    }

    protected function isProfitMarginDescription(string $description): bool
    {
        $desc = strtolower($description);
        // Only return true for the base "Profit Margin" entry, NOT withdrawals
        return str_contains($desc, 'profit margin') && !str_contains($desc, '(withdrawal)');
    }

    protected function extractProfitRangeFromReference(?string $reference): ?array
    {
        if (! $reference) {
            return null;
        }

        if (str_starts_with($reference, 'profit-range:')) {
            if (preg_match('/^profit-range:(\d{4}-\d{2}-\d{2}):(\d{4}-\d{2}-\d{2})$/', $reference, $matches)) {
                return [
                    'start' => $matches[1],
                    'end' => $matches[2],
                ];
            }

            return null;
        }

        if (str_starts_with($reference, 'profit-month:')) {
            $month = substr($reference, strlen('profit-month:'));

            if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
                return null;
            }

            $date = Carbon::createFromFormat('Y-m', $month);

            return [
                'start' => $date->copy()->startOfMonth()->toDateString(),
                'end' => $date->copy()->endOfMonth()->toDateString(),
            ];
        }

        return null;
    }

    protected function formatProfitRangeLabel(string $startDate, string $endDate): string
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->isSameMonth($end)) {
            return $start->format('d') . ' to ' . $end->format('d M Y');
        }

        return $start->format('d M Y') . ' to ' . $end->format('d M Y');
    }

    protected function parseProfitRangeValues(array $row): ?array
    {
        $startDate = trim((string) ($row['profit_start_date'] ?? ''));
        $endDate = trim((string) ($row['profit_end_date'] ?? ''));

        if ($startDate === '' || $endDate === '') {
            return null;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            return null;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->greaterThan($end)) {
            return null;
        }

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ];
    }

    protected function rangesOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        $rangeAStart = Carbon::parse($startA)->startOfDay();
        $rangeAEnd = Carbon::parse($endA)->endOfDay();
        $rangeBStart = Carbon::parse($startB)->startOfDay();
        $rangeBEnd = Carbon::parse($endB)->endOfDay();

        return $rangeAStart->lessThanOrEqualTo($rangeBEnd) && $rangeAEnd->greaterThanOrEqualTo($rangeBStart);
    }

    public function getUsedProfitRanges(?int $excludeIndex = null): array
    {
        $dbRanges = $this->investor->transactions()
            ->where('reference', 'like', 'profit-%')
            ->select('id', 'reference')
            ->get()
            ->map(function ($transaction) {
                $range = $this->extractProfitRangeFromReference($transaction->reference);

                if (! $range) {
                    return null;
                }

                return [
                    'id' => $transaction->id,
                    'start' => $range['start'],
                    'end' => $range['end'],
                ];
            })
            ->filter()
            ->toArray();

        $uiRanges = [];
        foreach ($this->rows as $index => $row) {
            if ($excludeIndex !== null && $index === $excludeIndex) {
                continue;
            }
            $range = $this->parseProfitRangeValues($row);
            if ($range !== null) {
                $uiRanges[] = $range;
            }
        }

        return array_merge($dbRanges, $uiRanges);
    }

    protected function calculateProfitMarginOutflow(string $startDate, string $endDate): float
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            return 0;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // POS gross sales for the selected date range
        $salesTotal = (float) Sale::query()
            ->where('sale_type', 'pos')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');

        // Deduct returns for POS sales in this date range
        $returnsTotal = (float) DB::table('returns_products')
            ->join('sales', 'returns_products.sale_id', '=', 'sales.id')
            ->where('sales.sale_type', 'pos')
            ->whereBetween('sales.created_at', [$start, $end])
            ->sum('returns_products.total_amount');

        // Net sales after returns
        $netSalesTotal = $salesTotal - $returnsTotal;

        // GROSS COGS for all POS sold items in the selected range
        $grossCOGS = (float) DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->where('s.sale_type', 'pos')
            ->whereBetween('s.created_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(si.quantity * si.cost_price), 0) as total_cost')
            ->value('total_cost');

        // Returns COGS — reverse the cost for returned items (MUST subtract like ProfitLoss.php does)
        $returnsCOGS = (float) DB::table('returns_products as rp')
            ->join('sales as s', 's.id', '=', 'rp.sale_id')
            ->where('s.sale_type', 'pos')
            ->whereBetween('s.created_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(rp.return_quantity * rp.cost_price), 0) as total_cost')
            ->value('total_cost');

        // Net COGS = Gross COGS - Returns COGS (same logic as ProfitLoss.php)
        $netCOGS = $grossCOGS - $returnsCOGS;

        // Expenses for the selected date range
        $expenses = (float) Expense::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        // Net profit = net POS sales − net COGS − expenses
        $saleRevenue        = $netSalesTotal - $netCOGS - $expenses;
        $investorShareRatio = ((float) $this->investor->profit_share_percentage) / 100;
        $outflow            = $saleRevenue * $investorShareRatio;

        return round(max($outflow, 0), 2);
    }

    protected function applyRowRules(int $index): void
    {
        if (! isset($this->rows[$index])) {
            return;
        }

        $description = trim((string) ($this->rows[$index]['description'] ?? ''));
        $isProfitMargin = $this->isProfitMarginDescription($description);

        if ($isProfitMargin) {
            $range = $this->parseProfitRangeValues($this->rows[$index]);
            if ($range !== null) {
                $this->rows[$index]['inflow'] = (string) $this->calculateProfitMarginOutflow($range['start'], $range['end']);
            } else {
                $this->rows[$index]['inflow'] = '';
            }
        } else {
            $this->rows[$index]['outflow_month'] = '';
            $this->rows[$index]['profit_start_date'] = '';
            $this->rows[$index]['profit_end_date'] = '';
        }
    }

    /**
     * Livewire lifecycle hook — fires after any rows.*.field is updated via wire:model.live.
     * This is the reliable way to react to changes: the new value is already in $this->rows
     * when this runs, unlike wire:change which could fire before the model is synced.
     */
    public function updatedRows($value, $name): void
    {
        // $name is like "0.description" or "2.profit_start_date"
        $parts = explode('.', (string) $name);
        if (count($parts) >= 1 && is_numeric($parts[0])) {
            $index = (int) $parts[0];
            $field = $parts[1] ?? '';

            if ($field === 'description' || $field === 'profit_start_date' || $field === 'profit_end_date') {
                $this->applyRowRules($index);
            }
        }
    }

    // Keep these for backward compatibility (if called from elsewhere)
    public function onDescriptionChanged(int $index): void
    {
        $this->applyRowRules($index);
    }

    public function onOutflowMonthChanged(int $index): void
    {
        $this->applyRowRules($index);
    }

    protected function loadRows(): void
    {
        $transactions = $this->investor->transactions()
            ->with('cheque')
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $this->rows = $transactions->map(function ($item) {
            $range = $this->extractProfitRangeFromReference($item->reference);

            return [
                'id' => $item->id,
                'transaction_date' => $item->transaction_date ? Carbon::parse($item->transaction_date)->format('Y-m-d') : now()->format('Y-m-d'),
                'description' => (string) ($item->description ?? ''),
                'inflow' => $item->type === 'inflow' ? (string) $item->amount : '',
                'outflow' => $item->type === 'outflow' ? (string) $item->amount : '',
                'outflow_month' => '',
                'profit_start_date' => $range['start'] ?? '',
                'profit_end_date' => $range['end'] ?? '',
                'payment_method' => (string) ($item->payment_method ?? 'cash'),
                'cheque_id' => $item->cheque_id,
                'cheque_number' => $item->cheque ? $item->cheque->cheque_number : '',
                'cheque_bank' => $item->cheque ? $item->cheque->bank_name : '',
                'cheque_date' => $item->cheque && $item->cheque->cheque_date ? Carbon::parse($item->cheque->cheque_date)->format('d M Y') : '',
                'cheque_amount' => $item->cheque ? (string) $item->cheque->cheque_amount : '',
                'cheque_customer' => $item->cheque && $item->cheque->customer ? $item->cheque->customer->name : '',
            ];
        })->values()->toArray();

        $this->ensureTrailingBlankRow();
        $this->monthOptions = $this->buildMonthOptions();
    }

    public function addRow(): void
    {
        if (!auth()->user()->hasPermission('menu_profit_share_edit')) {
            return;
        }
        $this->rows[] = $this->newRow();
    }

    protected function isRowBlank(array $row): bool
    {
        $description = trim((string) ($row['description'] ?? ''));
        $inflow = is_numeric($row['inflow'] ?? null) ? (float) $row['inflow'] : 0;
        $outflow = is_numeric($row['outflow'] ?? null) ? (float) $row['outflow'] : 0;
        $profitStartDate = trim((string) ($row['profit_start_date'] ?? ''));
        $profitEndDate = trim((string) ($row['profit_end_date'] ?? ''));

        return $description === '' && $inflow <= 0 && $outflow <= 0 && $profitStartDate === '' && $profitEndDate === '';
    }

    protected function ensureTrailingBlankRow(): void
    {
        $normalizedRows = [];

        foreach ($this->rows as $row) {
            if (! $this->isRowBlank($row)) {
                $normalizedRows[] = $row;
            }
        }

        $normalizedRows[] = $this->newRow();
        $this->rows = array_values($normalizedRows);
    }


    public function confirmDelete(int $index): void
    {
        $this->indexToDelete = $index;
        $this->showDeleteModal = true;
    }

    public function deleteRow(): void
    {
        if (!auth()->user()->hasPermission('menu_profit_share_delete')) {
            $this->showDeleteModal = false;
            return;
        }
        if ($this->indexToDelete === null || ! isset($this->rows[$this->indexToDelete])) {
            $this->showDeleteModal = false;
            return;
        }

        $index = $this->indexToDelete;
        $row = $this->rows[$index];

        if (! empty($row['id'])) {
            $transaction = $this->investor->transactions()->find($row['id']);

            if ($transaction) {
                // Return cheque status to pending if it's being deleted
                if ($transaction->cheque_id) {
                    Cheque::where('id', $transaction->cheque_id)->update(['status' => 'pending']);
                }

                $transaction->delete();
            }
            session()->flash('success', 'Line deleted successfully.');
        } else {
            // Unsaved row
            session()->flash('success', 'Draft line removed.');
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        $this->resetErrorBag();
        $this->ensureTrailingBlankRow();
        $this->monthOptions = $this->buildMonthOptions();

        $this->showDeleteModal = false;
        $this->indexToDelete = null;
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    // ─── Payment Method Modal ─────────────────────────────────────────

    /**
     * Called when user clicks on inflow/outflow cell or blurs input.
     * Open modal to select payment method.
     */
    public function requestPaymentSave(int $index): void
    {
        if (!auth()->user()->hasPermission('menu_profit_share_edit')) {
            return;
        }
        if (! isset($this->rows[$index])) {
            return;
        }

        $outflow = is_numeric($this->rows[$index]['outflow'] ?? null) ? (float) $this->rows[$index]['outflow'] : 0;
        $inflow = is_numeric($this->rows[$index]['inflow'] ?? null) ? (float) $this->rows[$index]['inflow'] : 0;
        $description = trim((string) ($this->rows[$index]['description'] ?? ''));

        // If blank row, skip
        if ($description === '' && $outflow <= 0 && $inflow <= 0) {
            return;
        }

        // ONLY ask for payment method if there is ONLY an outflow (pure withdrawal).
        // For inflows (e.g., adding capital, profit margin), automatically save!
        // For rows with both inflow and outflow, also just save (don't ask for payment)
        if ($outflow > 0 && $inflow <= 0) {
            $this->pendingPaymentIndex = $index;
            $this->isPendingInflow = false;
            $this->selectedPaymentMethod = $this->rows[$index]['payment_method'] ?: 'cash';
            $this->chequeSearch = '';
            $this->selectedChequeId = $this->rows[$index]['cheque_id'];

            $this->loadAvailableCheques();

            $this->showPaymentModal = true;
            return;
        }

        // Otherwise save normally
        $this->saveRow($index);
    }

    public function loadAvailableCheques(): void
    {
        $query = Cheque::query()
            ->with('customer')
            ->whereIn('status', ['pending', 'complete'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END ASC")
            ->orderBy('id', 'desc');

        if ($this->chequeSearch !== '') {
            $query->where(function ($q) {
                $q->where('cheque_number', 'like', '%' . $this->chequeSearch . '%')
                    ->orWhere('bank_name', 'like', '%' . $this->chequeSearch . '%')
                    ->orWhereHas('customer', function ($customerQuery) {
                        $customerQuery->where('name', 'like', '%' . $this->chequeSearch . '%');
                    });
            });
        }

        $this->availableCheques = $query->limit(20)->get()->map(function ($cheque) {
            return [
                'id' => $cheque->id,
                'cheque_number' => $cheque->cheque_number,
                'bank_name' => $cheque->bank_name,
                'cheque_amount' => (float) $cheque->cheque_amount,
                'cheque_date' => $cheque->cheque_date ? Carbon::parse($cheque->cheque_date)->format('d M Y') : '',
                'customer_name' => $cheque->customer ? $cheque->customer->name : 'N/A',
            ];
        })->toArray();
    }

    public function updatedChequeSearch(): void
    {
        $this->loadAvailableCheques();
    }

    public function selectCheque(int $chequeId): void
    {
        $this->selectedChequeId = $chequeId;

        // Automatically update the outflow amount in the UI when a cheque is selected
        if (!$this->isPendingInflow && $this->pendingPaymentIndex !== null && isset($this->rows[$this->pendingPaymentIndex])) {
            $cheque = Cheque::find($chequeId);
            if ($cheque) {
                $this->rows[$this->pendingPaymentIndex]['outflow'] = (string) $cheque->cheque_amount;
                $cheque->status = 'pending';
                $cheque->save();
            }
        }
    }

    public function confirmPaymentMethod(): void
    {
        if (!auth()->user()->hasPermission('menu_profit_share_edit')) {
            $this->closePaymentModal();
            return;
        }
        if ($this->pendingPaymentIndex === null) {
            return;
        }

        $index = $this->pendingPaymentIndex;

        if (! isset($this->rows[$index])) {
            $this->closePaymentModal();
            return;
        }

        // Validate cheque selection if cheque method chosen, and ONLY if it's an outflow
        if (!$this->isPendingInflow && $this->selectedPaymentMethod === 'cheque' && ! $this->selectedChequeId) {
            $this->addError('chequeSelection', 'Please select a cheque.');
            return;
        }

        // Set payment method on row
        $this->rows[$index]['payment_method'] = $this->selectedPaymentMethod;

        if (!$this->isPendingInflow && $this->selectedPaymentMethod === 'cheque' && $this->selectedChequeId) {
            $this->rows[$index]['cheque_id'] = $this->selectedChequeId;
            $cheque = Cheque::find($this->selectedChequeId);
            $this->rows[$index]['cheque_number'] = $cheque ? $cheque->cheque_number : '';
            if ($cheque) {
                // Automatically set the row outflow amount to the exact cheque amount
                $this->rows[$index]['outflow'] = (string) $cheque->cheque_amount;
            }
        } else {
            $this->rows[$index]['cheque_id'] = null;
            $this->rows[$index]['cheque_number'] = '';
        }

        // Save the row
        $this->saveRow($index);

        // If cheque was selected for outflow, keep it reserved as pending
        if (!$this->isPendingInflow && $this->selectedPaymentMethod === 'cheque' && $this->selectedChequeId) {
            Cheque::where('id', $this->selectedChequeId)->update(['status' => 'pending']);
        }

        $this->closePaymentModal();
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->pendingPaymentIndex = null;
        $this->isPendingInflow = false;
        $this->selectedPaymentMethod = 'cash';
        $this->chequeSearch = '';
        $this->selectedChequeId = null;
        $this->availableCheques = [];
        $this->resetErrorBag('chequeSelection');
    }

    // ─── Cheque Detail Viewer ─────────────────────────────────────────

    public function viewChequeDetails(int $index): void
    {
        if (! isset($this->rows[$index])) {
            return;
        }

        $row = $this->rows[$index];
        $chequeId = $row['cheque_id'] ?? null;

        if (! $chequeId) {
            return;
        }

        $cheque = Cheque::with('customer')->find($chequeId);

        if (! $cheque) {
            return;
        }

        $this->chequeDetail = [
            'cheque_number' => $cheque->cheque_number,
            'bank_name' => $cheque->bank_name,
            'cheque_date' => $cheque->cheque_date ? Carbon::parse($cheque->cheque_date)->format('d M Y') : 'N/A',
            'cheque_amount' => number_format((float) $cheque->cheque_amount, 2),
            'status' => ucfirst($cheque->status),
            'customer_name' => $cheque->customer ? $cheque->customer->name : 'N/A',
            'description' => $row['description'] ?? '',
        ];

        $this->showChequeDetail = true;
    }

    public function closeChequeDetailModal(): void
    {
        $this->showChequeDetail = false;
        $this->chequeDetail = [];
    }

    // ─── Validation & Save ────────────────────────────────────────────

    protected function validateRow(int $index): ?array
    {
        if (! isset($this->rows[$index])) {
            return null;
        }

        $this->applyRowRules($index);

        $row = $this->rows[$index];
        $description = trim((string) ($row['description'] ?? ''));
        $isProfitMargin = $this->isProfitMarginDescription($description);
        $inflow = is_numeric($row['inflow'] ?? null) ? (float) $row['inflow'] : 0;
        $outflow = is_numeric($row['outflow'] ?? null) ? (float) $row['outflow'] : 0;
        $profitStartDate = trim((string) ($row['profit_start_date'] ?? ''));
        $profitEndDate = trim((string) ($row['profit_end_date'] ?? ''));

        if ($description === '' && $inflow <= 0 && $outflow <= 0) {
            return null;
        }

        if ($description === '') {
            $this->addError("rows.$index.description", 'Description is required.');
        }

        if ($inflow < 0) {
            $this->addError("rows.$index.inflow", 'Inflow must be 0 or higher.');
        }

        if ($outflow < 0) {
            $this->addError("rows.$index.outflow", 'Outflow must be 0 or higher.');
        }

        // if both are filled and it's NOT a profit margin, we don't allow it in the basic view
        if ($inflow > 0 && $outflow > 0 && !$isProfitMargin) {
            $this->addError("rows.$index.inflow", 'Use either inflow or outflow in one line.');
            $this->addError("rows.$index.outflow", 'Use either inflow or outflow in one line.');
        }

        if ($inflow <= 0 && $outflow <= 0 && !$isProfitMargin) {
            $this->addError("rows.$index.inflow", 'Enter inflow or outflow amount.');
        }

        if ($isProfitMargin) {
            $yesterday = now()->subDay()->startOfDay();

            if ($profitStartDate === '') {
                $this->addError("rows.$index.profit_start_date", 'Select a start date for Profit Margin.');
            }

            if ($profitEndDate === '') {
                $this->addError("rows.$index.profit_end_date", 'Select an end date for Profit Margin.');
            }

            if ($profitStartDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $profitStartDate)) {
                if (Carbon::parse($profitStartDate)->startOfDay()->greaterThan($yesterday)) {
                    $this->addError("rows.$index.profit_start_date", 'Start date cannot be today or a future date.');
                }
            }

            if ($profitEndDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $profitEndDate)) {
                if (Carbon::parse($profitEndDate)->startOfDay()->greaterThan($yesterday)) {
                    $this->addError("rows.$index.profit_end_date", 'End date cannot be today or a future date.');
                }
            }

            if ($profitStartDate !== '' && $profitEndDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $profitStartDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $profitEndDate)) {
                if (Carbon::parse($profitStartDate)->startOfDay()->greaterThan(Carbon::parse($profitEndDate)->startOfDay())) {
                    $this->addError("rows.$index.profit_end_date", 'End date must be the same as or after the start date.');
                }
            }

            $currentRange = $this->parseProfitRangeValues($row);

            if ($currentRange === null) {
                $this->addError("rows.$index.profit_start_date", 'Select a valid date range for Profit Margin.');
            } else {
                $reference = 'profit-range:' . $currentRange['start'] . ':' . $currentRange['end'];

                $existingRanges = $this->investor->transactions()
                    ->where('reference', 'like', 'profit-%')
                    ->get(['id', 'reference']);

                foreach ($existingRanges as $transaction) {
                    if (! empty($row['id']) && (int) $transaction->id === (int) $row['id']) {
                        continue;
                    }

                    $existingRange = $this->extractProfitRangeFromReference($transaction->reference);
                    if ($existingRange === null) {
                        continue;
                    }

                    if ($this->rangesOverlap($currentRange['start'], $currentRange['end'], $existingRange['start'], $existingRange['end'])) {
                        $this->dispatch('toast', type: 'error', message: 'This date range overlaps with an existing Profit Margin entry.');
                        $this->addError("rows.$index.profit_start_date", 'This date range overlaps with an existing Profit Margin entry.');
                        $this->addError("rows.$index.profit_end_date", 'This date range overlaps with an existing Profit Margin entry.');
                        break;
                    }
                }

                if (! empty($this->getErrorBag()->getMessages())) {
                    return null;
                }

                foreach ($this->rows as $rIndex => $r) {
                    if ($rIndex === $index) {
                        continue;
                    }

                    $isOtherProfit = $this->isProfitMarginDescription(trim((string) ($r['description'] ?? '')));
                    if (! $isOtherProfit) {
                        continue;
                    }

                    $otherRange = $this->parseProfitRangeValues($r);
                    if ($otherRange === null) {
                        continue;
                    }

                    if ($this->rangesOverlap($currentRange['start'], $currentRange['end'], $otherRange['start'], $otherRange['end'])) {
                        $this->dispatch('toast', type: 'error', message: 'This date range overlaps with another row.');
                        $this->addError("rows.$index.profit_start_date", 'This date range overlaps with another row.');
                        $this->addError("rows.$index.profit_end_date", 'This date range overlaps with another row.');
                        break;
                    }
                }
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return null;
        }

        $rowDate = trim((string) ($row['transaction_date'] ?? ''));
        $isNewRow = empty($row['id']);

        // Always use today's date for transaction_date unless explicitly set
        // For profit margins, the date is always today for new rows
        $transactionDate = now()->toDateString();

        // For existing non-profit rows, use the user-selected date if provided
        if (!$isNewRow && !$isProfitMargin && $rowDate !== '') {
            $transactionDate = $rowDate;
        }

        return [
            'description' => $description,
            'is_profit_margin' => $isProfitMargin,
            'inflow' => $inflow,
            'outflow' => $outflow,
            'reference' => $isProfitMargin && $currentRange !== null ? 'profit-range:' . $currentRange['start'] . ':' . $currentRange['end'] : null,
            'transaction_date' => $transactionDate,
            'payment_method' => $row['payment_method'] ?: 'cash',
            'cheque_id' => $row['cheque_id'] ?? null,
        ];
    }

    public function saveRow(int $index): void
    {
        if (!auth()->user()->hasPermission('menu_profit_share_edit')) {
            return;
        }
        $this->resetErrorBag();
        $validated = $this->validateRow($index);

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        if ($validated === null) {
            return;
        }

        $row = $this->rows[$index];

        $savingInflow = $validated['inflow'] > 0 || $validated['is_profit_margin'];
        $savingOutflow = $validated['outflow'] > 0;

        if ($savingInflow && $savingOutflow) {
            // BOTH exist! Save the Inflow first under the main row
            $inflowData = [
                'type' => 'inflow',
                'amount' => $validated['inflow'],
                'transaction_date' => $validated['transaction_date'],
                'reference' => $validated['reference'],
                'description' => $validated['description'],
                'payment_method' => $validated['is_profit_margin'] ? 'cash' : $validated['payment_method'],
                'cheque_id' => null,
            ];

            if (! empty($row['id'])) {
                $this->investor->transactions()->where('id', $row['id'])->update($inflowData);
            } else {
                InvestorTransaction::create(['investor_id' => $this->investor->id] + $inflowData);
            }

            // Then create a brand NEW Outflow record 
            InvestorTransaction::create([
                'investor_id' => $this->investor->id,
                'type' => 'outflow',
                'amount' => $validated['outflow'],
                'transaction_date' => $validated['transaction_date'],
                'reference' => null,
                'description' => $validated['description'] . ' (Withdrawal)',
                'payment_method' => $validated['payment_method'],
                'cheque_id' => $validated['cheque_id'],
            ]);

            // Reload UI to show both fully split rows correctly
            $this->loadRows();
            session()->flash('success', 'Line split into Inflow & Outflow successfully.');
            return;
        }

        // Single save path
        $type = $savingInflow ? 'inflow' : 'outflow';
        $amount = $savingInflow ? $validated['inflow'] : $validated['outflow'];

        $transactionData = [
            'type' => $type,
            'amount' => $amount,
            'transaction_date' => $validated['transaction_date'],
            'reference' => $savingInflow ? $validated['reference'] : null,
            'description' => $validated['description'],
            'payment_method' => $validated['payment_method'],
            'cheque_id' => $type === 'outflow' ? $validated['cheque_id'] : null,
        ];

        if (! empty($row['id'])) {
            $transaction = $this->investor->transactions()->findOrFail($row['id']);
            $transaction->update($transactionData);
        } else {
            $transaction = InvestorTransaction::create(['investor_id' => $this->investor->id] + $transactionData);
            $this->rows[$index]['id'] = $transaction->id;
        }

        $this->ensureTrailingBlankRow();
        $this->monthOptions = $this->buildMonthOptions();

        session()->flash('success', 'Line saved successfully.');
    }

    public function saveRows(): void
    {
        if (!auth()->user()->hasPermission('menu_profit_share_edit')) {
            return;
        }
        $this->resetErrorBag();

        // Check if any pure withdrawal rows are missing payment method
        foreach (array_keys($this->rows) as $index) {
            $row = $this->rows[$index];
            $outflow = is_numeric($row['outflow'] ?? null) ? (float) $row['outflow'] : 0;
            $inflow = is_numeric($row['inflow'] ?? null) ? (float) $row['inflow'] : 0;
            $description = trim((string) ($row['description'] ?? ''));
            $paymentMethod = trim((string) ($row['payment_method'] ?? ''));

            if ($outflow > 0 && $inflow <= 0 && $description !== '' && $paymentMethod === '') {
                // Open payment modal for this withdrawal row
                $this->pendingPaymentIndex = $index;
                $this->isPendingInflow = false;
                $this->chequeSearch = '';
                $this->selectedChequeId = null;
                $this->loadAvailableCheques();
                $this->showPaymentModal = true;
                return;
            }
        }

        $needsReload = false;

        foreach (array_keys($this->rows) as $index) {
            $validated = $this->validateRow($index);

            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }

            if ($validated === null) {
                continue;
            }

            $row = $this->rows[$index];

            $savingInflow = $validated['inflow'] > 0 || $validated['is_profit_margin'];
            $savingOutflow = $validated['outflow'] > 0;

            if ($savingInflow && $savingOutflow) {
                // BOTH exist! Save the Inflow first under the main row
                $inflowData = [
                    'type' => 'inflow',
                    'amount' => $validated['inflow'],
                    'transaction_date' => $validated['transaction_date'],
                    'reference' => $validated['reference'],
                    'description' => $validated['description'],
                    'payment_method' => $validated['is_profit_margin'] ? 'cash' : $validated['payment_method'],
                    'cheque_id' => null,
                ];

                if (! empty($row['id'])) {
                    $this->investor->transactions()->where('id', $row['id'])->update($inflowData);
                } else {
                    InvestorTransaction::create(['investor_id' => $this->investor->id] + $inflowData);
                }

                // Then create a brand NEW Outflow record 
                InvestorTransaction::create([
                    'investor_id' => $this->investor->id,
                    'type' => 'outflow',
                    'amount' => $validated['outflow'],
                    'transaction_date' => $validated['transaction_date'],
                    'reference' => null,
                    'description' => $validated['description'] . ' (Withdrawal)',
                    'payment_method' => $validated['payment_method'],
                    'cheque_id' => $validated['cheque_id'],
                ]);

                $needsReload = true;
                continue;
            }

            // Single save path
            $type = $savingInflow ? 'inflow' : 'outflow';
            $amount = $savingInflow ? $validated['inflow'] : $validated['outflow'];

            $transactionData = [
                'type' => $type,
                'amount' => $amount,
                'transaction_date' => $validated['transaction_date'],
                'reference' => $savingInflow ? $validated['reference'] : null,
                'description' => $validated['description'],
                'payment_method' => $validated['payment_method'],
                'cheque_id' => $type === 'outflow' ? $validated['cheque_id'] : null,
            ];

            if (! empty($row['id'])) {
                $transaction = $this->investor->transactions()->findOrFail($row['id']);
                $transaction->update($transactionData);
            } else {
                $transaction = InvestorTransaction::create(['investor_id' => $this->investor->id] + $transactionData);
                $this->rows[$index]['id'] = $transaction->id;
            }
        }

        if ($needsReload) {
            $this->loadRows();
        } else {
            $this->ensureTrailingBlankRow();
        }

        session()->flash('success', 'All lines saved successfully.');
    }

    public function getTotalsProperty(): array
    {
        $row = $this->investor->transactions()
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'inflow' THEN amount ELSE 0 END), 0) as total_inflow")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'outflow' THEN amount ELSE 0 END), 0) as total_outflow")
            ->first();

        $inflow = (float) ($row->total_inflow ?? 0);
        $outflow = (float) ($row->total_outflow ?? 0);

        return [
            'inflow' => $inflow,
            'outflow' => $outflow,
            'balance' => $inflow - $outflow,
        ];
    }

    public function render()
    {
        return view('livewire.admin.investor-ledger', [
            'totals' => $this->totals,
            'descriptionSuggestions' => $this->descriptionSuggestions,
        ])->layout($this->getLayoutProperty());
    }
}
