<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductSupplier;
use App\Models\PurchasePayment;
use App\Models\PurchaseOrder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title("List Supplier Receipt")]
class ListSupplierReceipt extends Component
{
    use WithDynamicLayout;

    use WithPagination;

    public $perPage = 30;
    public $showPaymentModal = false;
    public $selectedSupplier = null;
    public $payments = [];
    public $searchOrderNumber = '';
    public $searchedOrder = null;
    public $orderPayments = [];
    public $fromDateFilter = '';
    public $toDateFilter = '';

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedFromDateFilter()
    {
        $this->resetPage();
    }

    public function updatedToDateFilter()
    {
        $this->resetPage();
    }

    public function clearDateFilters()
    {
        $this->fromDateFilter = '';
        $this->toDateFilter = '';
        $this->resetPage();
    }

    public function getSuppliersProperty()
    {
        // Get suppliers with total paid and receipt count (sum from purchase_payments table)
        $query = ProductSupplier::select(
            'product_suppliers.id',
            'product_suppliers.name',
            'product_suppliers.address',
            'product_suppliers.created_at',
            'product_suppliers.updated_at'
        )
            ->selectRaw('COALESCE(SUM(purchase_payments.amount),0) as total_paid')
            ->selectRaw('COUNT(purchase_payments.id) as receipts_count')
            ->leftJoin('purchase_payments', function ($join) {
                $join->on('purchase_payments.supplier_id', '=', 'product_suppliers.id');
                if ($this->fromDateFilter) {
                    $join->whereDate('purchase_payments.payment_date', '>=', $this->fromDateFilter);
                }
                if ($this->toDateFilter) {
                    $join->whereDate('purchase_payments.payment_date', '<=', $this->toDateFilter);
                }
            })
            ->groupBy(
                'product_suppliers.id',
                'product_suppliers.name',
                'product_suppliers.address',
                'product_suppliers.created_at',
                'product_suppliers.updated_at'
            )
            ->having('total_paid', '>', 0)
            ->orderByDesc('total_paid');

        if ($this->perPage === 'all') {
            $totalRows = (clone $query)->count();
            return $query->paginate($totalRows > 0 ? $totalRows : 1);
        }

        return $query->paginate((int) $this->perPage);
    }

    public function showSupplierPayments($supplierId)
    {
        $this->selectedSupplier = ProductSupplier::find($supplierId);
        $this->payments = PurchasePayment::with(['allocations', 'allocations.order'])
            ->where('supplier_id', $supplierId)
            ->orderByDesc('payment_date')
            ->get();
            
        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->selectedSupplier = null;
        $this->payments = [];
    }

    public function updatedSearchOrderNumber()
    {
        if (trim($this->searchOrderNumber) === '') {
            $this->searchedOrder = null;
            $this->orderPayments = [];
            return;
        }

        $this->searchedOrder = PurchaseOrder::with('supplier')
            ->where('order_code', 'LIKE', '%' . $this->searchOrderNumber . '%')
            ->first();

        if ($this->searchedOrder) {
            $this->orderPayments = PurchasePayment::with(['allocations.order'])
                ->whereHas('allocations', function ($q) {
                    $q->where('purchase_order_id', $this->searchedOrder->id);
                })
                ->orderByDesc('payment_date')
                ->get();
        } else {
            $this->orderPayments = [];
        }
    }

    public function clearSearch()
    {
        $this->searchOrderNumber = '';
        $this->searchedOrder = null;
        $this->orderPayments = [];
    }

    public function render()
    {
        $groupedPayments = collect($this->payments)->groupBy(function($p) {
            return $p->payment_reference ?: 'single_'.$p->id;
        });

        return view('livewire.admin.list-supplier-receipt', [
            'suppliers' => $this->suppliers,
            'showPaymentModal' => $this->showPaymentModal,
            'selectedSupplier' => $this->selectedSupplier,
            'groupedPayments' => $groupedPayments,
        ])->layout($this->layout);
    }

    public function exportCSV()
    {
        $query = ProductSupplier::select(
            'product_suppliers.id',
            'product_suppliers.name',
            'product_suppliers.address',
            'product_suppliers.created_at',
            'product_suppliers.updated_at'
        )
            ->selectRaw('COALESCE(SUM(purchase_payments.amount),0) as total_paid')
            ->selectRaw('COUNT(purchase_payments.id) as receipts_count')
            ->leftJoin('purchase_payments', function ($join) {
                $join->on('purchase_payments.supplier_id', '=', 'product_suppliers.id');
                if ($this->fromDateFilter) {
                    $join->whereDate('purchase_payments.payment_date', '>=', $this->fromDateFilter);
                }
                if ($this->toDateFilter) {
                    $join->whereDate('purchase_payments.payment_date', '<=', $this->toDateFilter);
                }
            })
            ->groupBy(
                'product_suppliers.id',
                'product_suppliers.name',
                'product_suppliers.address',
                'product_suppliers.created_at',
                'product_suppliers.updated_at'
            )
            ->having('total_paid', '>', 0)
            ->orderByDesc('total_paid');

        $suppliers = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=supplier_receipts_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Supplier Name', 'Address', 'Receipts Count', 'Total Paid'];

        $callback = function() use($suppliers, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($suppliers as $sup) {
                fputcsv($file, [
                    $sup->name,
                    $sup->address ?? '',
                    $sup->receipts_count,
                    number_format($sup->total_paid, 2, '.', ''),
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, 'supplier_receipts_' . now()->format('YmdHis') . '.csv', $headers);
    }
}
