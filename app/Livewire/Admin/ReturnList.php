<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ReturnsProduct;
use App\Models\ManualSaleReturn;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Livewire\Concerns\WithDynamicLayout;
use Livewire\WithPagination;

#[Title("Product Return")]
class ReturnList extends Component
{
    use WithDynamicLayout, WithPagination;

    public $returnSource = 'system'; // 'system' or 'manual'
    public $returnsCount = 0;
    public $returnSearch = '';
    public $selectedReturn = null;
    public $isManualSelected = false;
    public $showReceiptModal = false;
    public $currentReturnId = null;
    public $perPage = 30;
    public $fromDateFilter = '';
    public $toDateFilter = '';

    public function mount()
    {
        $this->loadReturns();
    }

    public function setReturnSource($source)
    {
        $this->returnSource = $source;
        $this->resetPage();
        $this->loadReturns();
    }

    protected function loadReturns()
    {
        if ($this->returnSource === 'manual') {
            $query = ManualSaleReturn::with(['customer', 'product']);
            if (!empty($this->returnSearch)) {
                $search = '%' . $this->returnSearch . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', $search)
                      ->orWhere('customer_name', 'like', $search)
                      ->orWhereHas('product', function ($pq) use ($search) {
                          $pq->where('name', 'like', $search)
                              ->orWhere('code', 'like', $search);
                      });
                });
            }
        } else {
            $query = ReturnsProduct::with(['sale', 'product']);
            if (!empty($this->returnSearch)) {
                $search = '%' . $this->returnSearch . '%';
                $query->where(function ($q) use ($search) {
                    $q->whereHas('sale', function ($sq) use ($search) {
                        $sq->where('invoice_number', 'like', $search);
                    })->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', $search)
                            ->orWhere('code', 'like', $search);
                    });
                });
            }
        }

        if ($this->fromDateFilter) {
            $query->whereDate('created_at', '>=', $this->fromDateFilter);
        }

        if ($this->toDateFilter) {
            $query->whereDate('created_at', '<=', $this->toDateFilter);
        }

        $this->returnsCount = $query->count();
    }

    public function updatedReturnSearch()
    {
        $this->resetPage();
        $this->loadReturns();
    }

    public function updatedFromDateFilter()
    {
        $this->resetPage();
        $this->loadReturns();
    }

    public function updatedToDateFilter()
    {
        $this->resetPage();
        $this->loadReturns();
    }

    public function clearDateFilters()
    {
        $this->fromDateFilter = '';
        $this->toDateFilter = '';
        $this->resetPage();
        $this->loadReturns();
    }

    public function showReturnDetails($id, $isManual = false)
    {
        $this->isManualSelected = $isManual;
        if ($isManual) {
            $this->selectedReturn = ManualSaleReturn::with(['customer', 'product'])->find($id);
        } else {
            $this->selectedReturn = ReturnsProduct::with(['sale', 'product'])->find($id);
        }
        $this->dispatch('showModal', 'returnDetailsModal');
    }

    public function showReceipt($returnId, $isManual = false)
    {
        $this->isManualSelected = $isManual;
        if ($isManual) {
            $this->selectedReturn = ManualSaleReturn::with(['customer', 'product'])->find($returnId);
        } else {
            $this->selectedReturn = ReturnsProduct::with(['sale.customer', 'product'])->find($returnId);
        }
        $this->currentReturnId = $returnId;
        $this->showReceiptModal = true;
        $this->dispatch('showModal', 'receiptModal');
    }

    public function deleteReturn($returnId, $isManual = false)
    {
        if (!auth()->user()->hasPermission('menu_return_customer_delete')) {
            return;
        }
        $this->isManualSelected = $isManual;
        if ($isManual) {
            $this->selectedReturn = ManualSaleReturn::find($returnId);
        } else {
            $this->selectedReturn = ReturnsProduct::find($returnId);
        }
        $this->currentReturnId = $returnId;
        $this->dispatch('showModal', 'deleteReturnModal');
    }

    public function confirmDeleteReturn()
    {
        if (!auth()->user()->hasPermission('menu_return_customer_delete')) {
            return;
        }
        try {
            if ($this->selectedReturn) {
                // Restore stock before deleting return record
                $this->restoreStock($this->selectedReturn, $this->isManualSelected);

                $this->selectedReturn->delete();
                $this->loadReturns();
                $this->resetPage();

                $this->dispatch('hideModal', 'deleteReturnModal');
                $this->dispatch('showToast', ['type' => 'success', 'message' => 'Return record deleted successfully!']);
            }
        } catch (\Exception $e) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Error deleting return: ' . $e->getMessage()]);
        }
    }

    private function restoreStock($return, $isManual = false)
    {
        $productStock = \App\Models\ProductStock::where('product_id', $return->product_id)->first();

        if ($productStock) {
            $qty = $return->return_quantity;
            $condition = $return->return_condition ?? 'usable';

            if (in_array($condition, ['damage', 'company_fault'])) {
                $productStock->damage_stock = max(0, $productStock->damage_stock - $qty);
            } else {
                $productStock->available_stock = max(0, $productStock->available_stock - $qty);
            }

            if (!$isManual) {
                if ($productStock->sold_count >= $qty) {
                    $productStock->sold_count += $qty;
                }
            } else {
                $productStock->total_stock = max(0, ($productStock->available_stock ?? 0) + ($productStock->damage_stock ?? 0));
                $productStock->restocked_quantity = max(0, ($productStock->restocked_quantity ?? 0) - $qty);
            }

            $productStock->save();
        }
    }

    public function closeModal()
    {
        $this->selectedReturn = null;
        $this->currentReturnId = null;
        $this->showReceiptModal = false;
        $this->dispatch('hideModal', 'returnDetailsModal');
        $this->dispatch('hideModal', 'deleteReturnModal');
        $this->dispatch('hideModal', 'receiptModal');
    }

    public function render()
    {
        if ($this->returnSource === 'manual') {
            $query = ManualSaleReturn::with(['customer', 'product'])->orderByDesc('created_at');

            if (!empty($this->returnSearch)) {
                $search = '%' . $this->returnSearch . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', $search)
                      ->orWhere('customer_name', 'like', $search)
                      ->orWhereHas('product', function ($pq) use ($search) {
                          $pq->where('name', 'like', $search)
                              ->orWhere('code', 'like', $search);
                      });
                });
            }
        } else {
            $query = ReturnsProduct::with(['sale.customer', 'product'])->orderByDesc('created_at');

            if (!empty($this->returnSearch)) {
                $search = '%' . $this->returnSearch . '%';
                $query->where(function ($q) use ($search) {
                    $q->whereHas('sale', function ($sq) use ($search) {
                        $sq->where('invoice_number', 'like', $search);
                    })->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', $search)
                            ->orWhere('code', 'like', $search);
                    });
                });
            }
        }

        if ($this->fromDateFilter) {
            $query->whereDate('created_at', '>=', $this->fromDateFilter);
        }

        if ($this->toDateFilter) {
            $query->whereDate('created_at', '<=', $this->toDateFilter);
        }

        if ($this->perPage === 'all') {
            $totalRows = (clone $query)->count();
            $returns = $query->paginate($totalRows > 0 ? $totalRows : 1);
        } else {
            $returns = $query->paginate((int) $this->perPage);
        }

        return view('livewire.admin.return-list', [
            'returns' => $returns,
            'selectedReturn' => $this->selectedReturn,
            'currentReturnId' => $this->currentReturnId,
            'isManualSelected' => $this->isManualSelected,
        ])->layout($this->layout);
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function exportCSV()
    {
        if ($this->returnSource === 'manual') {
            $query = ManualSaleReturn::with(['customer', 'product'])
                ->when($this->returnSearch, function ($q) {
                    $search = '%' . $this->returnSearch . '%';
                    $q->where(function ($sub) use ($search) {
                        $sub->where('invoice_number', 'like', $search)
                            ->orWhere('customer_name', 'like', $search)
                            ->orWhereHas('product', function ($pq) use ($search) {
                                $pq->where('name', 'like', $search)
                                    ->orWhere('code', 'like', $search);
                            });
                    });
                })
                ->when($this->fromDateFilter, function ($q) {
                    $q->whereDate('created_at', '>=', $this->fromDateFilter);
                })
                ->when($this->toDateFilter, function ($q) {
                    $q->whereDate('created_at', '<=', $this->toDateFilter);
                })
                ->orderByDesc('created_at');

            $returns = $query->get();

            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=manual_returns_" . now()->format('YmdHis') . ".csv",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $columns = ['Invoice Number', 'Customer', 'Product', 'Condition', 'Return Qty', 'Unit Price', 'Total', 'Date'];

            $callback = function() use($returns, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);

                foreach ($returns as $ret) {
                    fputcsv($file, [
                        $ret->invoice_number,
                        $ret->customer_name ?? ($ret->customer ? $ret->customer->name : 'N/A'),
                        $ret->product ? $ret->product->name : 'N/A',
                        ucfirst($ret->return_condition ?? 'usable'),
                        $ret->return_quantity,
                        number_format($ret->unit_price, 2, '.', ''),
                        number_format($ret->total_amount, 2, '.', ''),
                        $ret->created_at ? $ret->created_at->format('d/m/Y') : '',
                    ]);
                }

                fclose($file);
            };

            return response()->streamDownload($callback, 'manual_returns_' . now()->format('YmdHis') . '.csv', $headers);
        } else {
            $query = ReturnsProduct::with(['sale', 'product'])
                ->when($this->returnSearch, function ($q) {
                    $search = '%' . $this->returnSearch . '%';
                    $q->where(function ($sub) use ($search) {
                        $sub->whereHas('sale', function ($sq) use ($search) {
                            $sq->where('invoice_number', 'like', $search);
                        })->orWhereHas('product', function ($pq) use ($search) {
                            $pq->where('name', 'like', $search)
                                ->orWhere('code', 'like', $search);
                        });
                    });
                })
                ->when($this->fromDateFilter, function ($q) {
                    $q->whereDate('created_at', '>=', $this->fromDateFilter);
                })
                ->when($this->toDateFilter, function ($q) {
                    $q->whereDate('created_at', '<=', $this->toDateFilter);
                })
                ->orderByDesc('created_at');

            $returns = $query->get();

            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=returns_" . now()->format('YmdHis') . ".csv",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $columns = ['Invoice Number', 'Product', 'Condition', 'Return Qty', 'Unit Price', 'Total', 'Date'];

            $callback = function() use($returns, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);

                foreach ($returns as $ret) {
                    fputcsv($file, [
                        $ret->sale ? $ret->sale->invoice_number : 'N/A',
                        $ret->product ? $ret->product->name : 'N/A',
                        ucfirst($ret->return_condition ?? 'usable'),
                        $ret->return_quantity,
                        number_format($ret->selling_price, 2, '.', ''),
                        number_format($ret->total_amount, 2, '.', ''),
                        $ret->created_at ? $ret->created_at->format('d/m/Y') : '',
                    ]);
                }

                fclose($file);
            };

            return response()->streamDownload($callback, 'returns_' . now()->format('YmdHis') . '.csv', $headers);
        }
    }
}
