<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\PurchasePayment;
use App\Models\Salary;
use App\Models\ProductPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;
use Illuminate\Support\Facades\Log;

#[Title("Profit & Loss Statement")]
class ProfitLoss extends Component
{
    use WithDynamicLayout;

    // Date filters
    public $startDate;
    public $endDate;

    // Summary data
    public $totalRevenue = 0;
    public $totalCOGS = 0;
    public $grossProfit = 0;
    public $grossProfitPercentage = 0;

    public $totalExpenses = 0;
    public $totalSalaries = 0;
    public $totalOutgoing = 0;

    public $totalReturns = 0; // Total return amount (selling price)
    public $totalReturnsCOGS = 0; // COGS for returned products
    public $returnImpact = 0; // Net loss from returns (Selling Price - COGS)

    public $netProfit = 0;
    public $netProfitPercentage = 0;

    // Detailed data
    public $revenueBreakdown = [];
    public $expenseBreakdown = [];
    public $paymentBreakdown = [];
    public $salaryBreakdown = [];
    public $allOutgoingBreakdown = [];

    // Monthly data for trends
    public $monthlyTrends = [];
    public $categoryWiseExpenses = [];
    public $paymentMethodWiseRevenue = [];

    // Summary totals by category
    public $expenseByCategoryTotals = [];
    public $incomeTotals = [];
    public $outgoingTotals = [];

    public function mount()
    {
        // Initialize with empty dates - shows overall data
        $this->startDate = null;
        $this->endDate = null;

        $this->loadData();
    }

    public function updatedStartDate()
    {
        $this->loadData();
    }

    public function updatedEndDate()
    {
        $this->loadData();
    }

    public function resetFilters()
    {
        $this->startDate = null;
        $this->endDate = null;
        $this->loadData();
    }

    public function loadData()
    {
        try {
            $this->calculateRevenue();
            $this->calculateCOGS();
            $this->calculateExpenses();
            $this->calculateSalaries();
            $this->calculatePaymentsByCategory();
            $this->calculateAllOutgoing();
            $this->calculateMonthlyTrends();
            $this->calculateNetProfit();
        } catch (\Exception $e) {
            Log::error('P&L Calculation Error: ' . $e->getMessage());
        }
    }

    /**
     * Calculate Total Revenue from Sales (Sale Amount - COGS - Returns)
     */
    private function calculateRevenue()
    {
        // Only count POS sales (sale_type = 'pos') — user does not use admin sales
        $query = Sale::where('status', 'confirm')
                     ->where('sale_type', 'pos');

        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        // Get total sales amount (Gross Revenue)
        $totalSalesAmount = $query->sum('total_amount');

        $sales = $query->get();

        $this->revenueBreakdown = [];
        $this->paymentMethodWiseRevenue = [];

        foreach ($sales as $sale) {
            // Breakdown by sale type
            $saleType = $sale->sale_type ?? 'Regular';
            if (!isset($this->revenueBreakdown[$saleType])) {
                $this->revenueBreakdown[$saleType] = 0;
            }
            $this->revenueBreakdown[$saleType] += $sale->total_amount;
        }

        // Calculate COGS from items in POS sales only
        $cogsQuery = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'confirm')
            ->where('sales.sale_type', 'pos')
            ->select(DB::raw('SUM(sale_items.cost_price * sale_items.quantity) as total_cost'));

        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $cogsQuery->whereBetween('sales.created_at', [$start, $end]);
        }

        $cogsResult = $cogsQuery->first();
        $this->totalCOGS = $cogsResult ? ($cogsResult->total_cost ?? 0) : 0;

        // Calculate Returns Amount — POS sales only
        $returnsQuery = DB::table('returns_products')
            ->join('sales', 'returns_products.sale_id', '=', 'sales.id')
            ->where('sales.status', 'confirm')
            ->where('sales.sale_type', 'pos')
            ->select(DB::raw('SUM(returns_products.total_amount) as total_returns'));

        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $returnsQuery->whereBetween('sales.created_at', [$start, $end]);
        }

        $returnsResult = $returnsQuery->first();
        $this->totalReturns = $returnsResult ? ($returnsResult->total_returns ?? 0) : 0;

        // Calculate COGS for returned products — POS sales only
        $returnsCOGSQuery = DB::table('returns_products')
            ->join('sales', 'returns_products.sale_id', '=', 'sales.id')
            ->where('sales.status', 'confirm')
            ->where('sales.sale_type', 'pos')
            ->select(DB::raw('SUM(returns_products.cost_price * returns_products.return_quantity) as total_cost'));

        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $returnsCOGSQuery->whereBetween('sales.created_at', [$start, $end]);
        }

        $returnsCOGSResult = $returnsCOGSQuery->first();
        $this->totalReturnsCOGS = $returnsCOGSResult ? ($returnsCOGSResult->total_cost ?? 0) : 0;

        // Calculate Return Impact (Net loss from returns)
        // Return Impact = Return Selling Price - Return COGS
        $this->returnImpact = $this->totalReturns - $this->totalReturnsCOGS;

        // Log for debugging
        Log::info('Revenue Calculation', [
            'total_sales' => $totalSalesAmount,
            'total_cogs' => $this->totalCOGS,
            'total_returns' => $this->totalReturns,
            'returns_cogs' => $this->totalReturnsCOGS
        ]);

        // ===========================================================
        // FINANCIAL SUMMARY (correct chain — matches Analytics page)
        // ===========================================================
        // grossSalesAmount  = sum of all confirmed sales = what Analytics shows
        // netSalesAmount    = gross sales - returns      = net revenue
        // netCOGS           = total COGS - returned COGS = net cost of goods
        // totalRevenue      = grossSalesAmount (shown on Revenue card, matches Analytics)
        // grossProfit       = netSalesAmount - netCOGS
        // netProfit         = grossProfit - operating expenses
        // ===========================================================

        $netSalesAmount = (float) $totalSalesAmount - (float) $this->totalReturns;
        $netCOGS        = (float) $this->totalCOGS  - (float) $this->totalReturnsCOGS;

        // Revenue card = GROSS sales (same number as Analytics page)
        $this->totalRevenue = (float) $totalSalesAmount;

        // Store net COGS for use in calculateCOGS()
        $this->totalCOGS = $netCOGS;

        // Pre-calculate grossProfit so calculateCOGS() can pick it up
        $grossProfitCalc = $netSalesAmount - $netCOGS;

        // Store breakdown for the blade table
        $this->incomeTotals['Gross Sales Revenue'] = (float) $totalSalesAmount;
        $this->incomeTotals['Total Returns']       = (float) $this->totalReturns;
        $this->incomeTotals['Net Sales Revenue']   = $netSalesAmount;
        $this->incomeTotals['Total COGS']          = $netCOGS;
        $this->incomeTotals['Gross Profit']        = $grossProfitCalc;
        $this->incomeTotals['Total Sales Revenue'] = (float) $totalSalesAmount; // kept for % calc
    }

    /**
     * Calculate Cost of Goods Sold and Gross Profit
     * grossProfit = Net Revenue (after returns) - Net COGS
     */
    private function calculateCOGS()
    {
        // Gross Profit = Net Sales (after returns) - Net COGS
        $this->grossProfit = (float) ($this->incomeTotals['Gross Profit'] ?? 0);

        // Percentage based on gross sales (to match Analytics denominator)
        $grossSales = (float) ($this->incomeTotals['Gross Sales Revenue'] ?? $this->totalRevenue);
        $this->grossProfitPercentage = $grossSales > 0
            ? round(($this->grossProfit / $grossSales) * 100, 2)
            : 0;

        // Payment method breakdown
        $paymentQuery = \App\Models\Payment::query();
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end   = Carbon::parse($this->endDate)->endOfDay();
            $paymentQuery->whereBetween('payment_date', [$start, $end]);
        }
        $paymentMethods = $paymentQuery
            ->groupBy('payment_method')
            ->selectRaw('payment_method, SUM(amount) as total')
            ->get();
        foreach ($paymentMethods as $method) {
            $this->paymentMethodWiseRevenue[$method->payment_method ?? 'Unknown'] = $method->total ?? 0;
        }
    }

    /**
     * Calculate Operating Expenses
     */
    private function calculateExpenses()
    {
        // Get all expenses grouped by category
        $expenseQuery = Expense::query();
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $expenseQuery->whereBetween('created_at', [$start, $end]);
        }

        $expenses = $expenseQuery
            ->groupBy('category')
            ->selectRaw('category, SUM(amount) as total_amount, COUNT(*) as count')
            ->get();

        $this->expenseBreakdown = [];
        $this->totalExpenses = 0;
        $this->expenseByCategoryTotals = [];

        foreach ($expenses as $expense) {
            $category = $expense->category ?? 'Uncategorized';
            $this->expenseBreakdown[$category] = [
                'amount' => $expense->total_amount,
                'count' => $expense->count,
            ];
            $this->expenseByCategoryTotals[$category] = $expense->total_amount;
            $this->totalExpenses += $expense->total_amount;
        }

        $this->outgoingTotals['Operating Expenses'] = $this->totalExpenses;
    }

    /**
     * Calculate Salaries (removed - not included in P&L)
     */
    private function calculateSalaries()
    {
        // Salaries removed from P&L calculation
        $this->totalSalaries = 0;
        $this->salaryBreakdown = [];
        $this->outgoingTotals['Salaries & Staff'] = 0;
    }

    /**
     * Calculate all outgoing payments
     */
    private function calculateAllOutgoing()
    {
        $this->allOutgoingBreakdown = [];
        $this->totalOutgoing = 0;

        // Operating Expenses only
        if ($this->totalExpenses > 0) {
            $this->allOutgoingBreakdown['Operating Expenses'] = $this->totalExpenses;
            $this->totalOutgoing += $this->totalExpenses;
        }

        // COGS
        if ($this->totalCOGS > 0) {
            $this->allOutgoingBreakdown['Cost of Goods Sold'] = $this->totalCOGS;
            $this->totalOutgoing += $this->totalCOGS;
        }
    }

    /**
     * Calculate payment methods breakdown
     */
    private function calculatePaymentsByCategory()
    {
        // Payment breakdown is already calculated in calculateRevenue()
        // This method is kept for consistency
    }

    /**
     * Calculate monthly trends
     */
    private function calculateMonthlyTrends()
    {
        // If dates are set, use them; otherwise use all data
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfMonth();
            $end = Carbon::parse($this->endDate)->endOfMonth();
        } else {
            // Get the earliest date from database
            $earliestSale = Sale::orderBy('created_at')->first();
            $start = $earliestSale ? $earliestSale->created_at->copy()->startOfMonth() : now()->startOfMonth();
            $end = now();
        }

        $this->monthlyTrends = [];

        $current = $start->copy();
        while ($current <= $end) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $monthSalesAmount = Sale::where('status', 'confirm')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('total_amount');

            // Calculate COGS for this month (only confirmed sales)
            $monthCOGSValue = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.status', 'confirm')
                ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
                ->selectRaw('SUM(sale_items.cost_price * sale_items.quantity) as total')
                ->first()
                ->total ?? 0;

            // Monthly Revenue (Gross Profit) = Sale Amount - COGS
            $monthRevenue = $monthSalesAmount - $monthCOGSValue;

            $monthExpenses = Expense::whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $monthProfit = $monthRevenue - $monthExpenses;

            $this->monthlyTrends[] = [
                'month' => $current->format('M Y'),
                'revenue' => $monthRevenue,
                'expenses' => $monthExpenses,
                'cogs' => $monthCOGSValue,
                'profit' => $monthProfit,
            ];

            $current->addMonth();
        }
    }

    /**
     * Calculate Net Profit = Gross Profit - Operating Expenses
     * NOT totalRevenue - expenses (that would ignore COGS)
     */
    private function calculateNetProfit()
    {
        $this->netProfit = $this->grossProfit - $this->totalExpenses;

        $grossSales = (float) ($this->incomeTotals['Gross Sales Revenue'] ?? $this->totalRevenue);
        $this->netProfitPercentage = $grossSales > 0
            ? round(($this->netProfit / $grossSales) * 100, 2)
            : 0;
    }

    /**
     * Format currency values
     */
    public function formatCurrency($value)
    {
        return number_format($value, 2);
    }

    /**
     * Export to PDF (optional - requires barryvdh/laravel-dompdf)
     */
    public function exportPDF()
    {
        $data = [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'totalRevenue' => $this->totalRevenue,
            'totalCOGS' => $this->totalCOGS,
            'grossProfit' => $this->grossProfit,
            'grossProfitPercentage' => $this->grossProfitPercentage,
            'totalExpenses' => $this->totalExpenses,
            'totalSalaries' => $this->totalSalaries,
            'totalReturns' => $this->totalReturns,
            'totalReturnsCOGS' => $this->totalReturnsCOGS,
            'returnImpact' => $this->returnImpact,
            'netProfit' => $this->netProfit,
            'netProfitPercentage' => $this->netProfitPercentage,
            'revenueBreakdown' => $this->revenueBreakdown,
            'expenseBreakdown' => $this->expenseBreakdown,
            'monthlyTrends' => $this->monthlyTrends,
            'incomeTotals' => $this->incomeTotals,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.profit-loss-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'profit-loss-statement-' . now()->format('Y-m-d') . '.pdf';

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->output();
            },
            $filename
        );
    }

    public function render()
    {
        return view('livewire.admin.profit-loss')->layout($this->layout);
    }

    public function exportCSV()
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=profit_loss_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['PROFIT & LOSS STATEMENT']);
            fputcsv($file, ['Generated Date', now()->format('d/m/Y H:i')]);
            if ($this->startDate || $this->endDate) {
                fputcsv($file, ['Period', ($this->startDate ?? 'All') . ' to ' . ($this->endDate ?? 'All')]);
            } else {
                fputcsv($file, ['Period', 'Overall (All Data)']);
            }
            fputcsv($file, []);

            // 1. REVENUE
            fputcsv($file, ['REVENUE']);
            foreach ($this->revenueBreakdown as $source => $amount) {
                fputcsv($file, ['  ' . $source, number_format($amount, 2, '.', '')]);
            }
            fputcsv($file, ['  Less: Returns', number_format($this->totalReturns, 2, '.', '')]);
            fputcsv($file, ['Total Net Revenue', number_format($this->totalRevenue, 2, '.', '')]);
            fputcsv($file, []);

            // 2. COST OF GOODS SOLD
            fputcsv($file, ['COST OF GOODS SOLD']);
            fputcsv($file, ['  COGS', number_format($this->totalCOGS, 2, '.', '')]);
            fputcsv($file, ['  Less: Returned COGS', number_format($this->totalReturnsCOGS, 2, '.', '')]);
            fputcsv($file, ['Total Cost of Goods Sold', number_format($this->totalCOGS - $this->totalReturnsCOGS, 2, '.', '')]);
            fputcsv($file, []);

            // 3. GROSS PROFIT
            fputcsv($file, ['GROSS PROFIT', number_format($this->grossProfit, 2, '.', '')]);
            fputcsv($file, ['Gross Profit margin (%)', number_format($this->grossProfitPercentage, 2, '.', '')]);
            fputcsv($file, []);

            // 4. EXPENSES
            fputcsv($file, ['EXPENSES & OUTGOINGS']);
            foreach ($this->categoryWiseExpenses as $cat => $amount) {
                fputcsv($file, ['  ' . $cat, number_format($amount, 2, '.', '')]);
            }
            fputcsv($file, ['  Salaries', number_format($this->totalSalaries, 2, '.', '')]);
            fputcsv($file, ['Total Expenses & Outgoings', number_format($this->totalExpenses + $this->totalSalaries, 2, '.', '')]);
            fputcsv($file, []);

            // 5. NET PROFIT
            fputcsv($file, ['NET PROFIT', number_format($this->netProfit, 2, '.', '')]);
            fputcsv($file, ['Net Profit margin (%)', number_format($this->netProfitPercentage, 2, '.', '')]);

            fclose($file);
        };

        return response()->streamDownload($callback, 'profit_loss_' . now()->format('YmdHis') . '.csv', $headers);
    }
}
