<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Customer;
use App\Models\ProductDetail;
use App\Models\Sale;
use App\Models\ProductStock;
use App\Models\ReturnsProduct;
use App\Models\ManualSaleReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title("Product Return")]
class ReturnProduct extends Component
{
    use WithDynamicLayout;

    // Mode: 'system' (from DB invoice) or 'manual' (external / non-DB invoice)
    public $returnMode = 'system';

    // ==========================================
    // System Return Properties
    // ==========================================
    public $searchCustomer = '';
    public $customers = [];
    public $selectedCustomer = null;

    public $customerInvoices = [];
    public $selectedInvoice = null;
    public $selectedInvoices = [];

    public $invoiceProducts = [];
    public $returnItems = [];
    public $totalReturnValue = 0;
    public $overallDiscountPerItem = 0;

    public $showInvoiceModal = false;
    public $invoiceModalData = null;

    public $showReturnSection = false;
    public $searchReturnProduct = '';
    public $availableProducts = [];
    public $invoiceProductsForSearch = [];
    public $selectedProducts = [];

    public $previousReturns = []; // Track previously returned items

    // ==========================================
    // Manual / External Return Properties
    // ==========================================
    public $manualInvoiceNumber = '';
    public $manualInvoiceDate = '';
    public $manualCustomerSearch = '';
    public $manualCustomers = [];
    public $selectedManualCustomer = null;
    public $manualCustomerName = '';
    public $manualCustomerPhone = '';

    public $manualProductSearch = '';
    public $manualProductSearchResults = [];
    public $manualReturnItems = [];
    public $manualTotalReturnValue = 0;
    public $manualNotes = '';

    public function mount()
    {
        $this->manualInvoiceDate = now()->format('Y-m-d');
    }

    /** 🔄 Switch between System and Manual Return modes */
    public function setReturnMode($mode)
    {
        $this->returnMode = $mode;
    }

    // ==========================================
    // SYSTEM RETURN METHODS
    // ==========================================

    /** 🔍 Search Customer or Invoice */
    public function updatedSearchCustomer()
    {
        if (strlen($this->searchCustomer) > 2) {
            $this->customers = Customer::query()
                ->where('name', 'like', '%' . $this->searchCustomer . '%')
                ->orWhere('phone', 'like', '%' . $this->searchCustomer . '%')
                ->orWhere('email', 'like', '%' . $this->searchCustomer . '%')
                ->limit(10)
                ->get();

            $this->customerInvoices = Sale::where('invoice_number', 'like', '%' . $this->searchCustomer . '%')
                ->latest()
                ->limit(5)
                ->get();
        } else {
            $this->customers = [];
            $this->customerInvoices = [];
        }
    }

    /** 👤 Select Customer */
    public function selectCustomer($customerId)
    {
        $this->selectedCustomer = Customer::find($customerId);
        $this->searchCustomer = '';
        $this->customers = [];

        $this->resetReturnData();
        $this->loadCustomerInvoices();
    }

    /** 🧾 Load Selected Customer's Invoices */
    public function loadCustomerInvoices()
    {
        if (!$this->selectedCustomer) {
            $this->customerInvoices = [];
            return;
        }

        $this->customerInvoices = Sale::where('customer_id', $this->selectedCustomer->id)
            ->latest()
            ->limit(5)
            ->get();
    }

    /** 🎯 Simple Invoice Selection for Return */
    public function selectInvoiceForReturn($invoiceId)
    {
        $this->resetReturnData();

        $this->selectedInvoice = Sale::with(['items.product', 'customer'])->find($invoiceId);
        $this->selectedInvoices = [$invoiceId];
        $this->showReturnSection = true;

        if ($this->selectedInvoice && $this->selectedInvoice->customer) {
            $this->selectedCustomer = $this->selectedInvoice->customer;
        }

        if ($this->selectedInvoice) {
            // Calculate overall discount per item
            $this->calculateOverallDiscountPerItem();

            // Load previous returns for this invoice
            $this->loadPreviousReturns();

            // Build return items with remaining quantities
            foreach ($this->selectedInvoice->items as $item) {
                if (!$item->product) continue;
                $alreadyReturned = $this->getAlreadyReturnedQuantity($item->product->id);
                $remainingQty = $item->quantity - $alreadyReturned;

                if ($remainingQty > 0) {
                    // Apply unit discount first
                    $unitDiscount = $item->discount_per_unit ?? 0;

                    // Apply proportional overall discount per item
                    $proportionalOverallDiscount = $this->overallDiscountPerItem;

                    // Total discount per unit is unit discount + proportional overall discount
                    $totalDiscountPerUnit = $unitDiscount + $proportionalOverallDiscount;

                    // Net price after all discounts
                    $netUnitPrice = $item->unit_price - $totalDiscountPerUnit;

                    $this->returnItems[] = [
                        'product_id' => $item->product->id,
                        'name' => $item->product->name,
                        'unit_price' => $item->unit_price,
                        'discount_per_unit' => $unitDiscount,
                        'overall_discount_per_unit' => $proportionalOverallDiscount,
                        'total_discount_per_unit' => $totalDiscountPerUnit,
                        'net_unit_price' => $netUnitPrice,
                        'original_qty' => $item->quantity,
                        'already_returned' => $alreadyReturned,
                        'max_qty' => $remainingQty,
                        'return_qty' => 0,
                        'return_condition' => 'usable',
                    ];
                }
            }
        }

        $this->loadInvoiceProductsForSearch();
        $this->searchCustomer = '';
    }

    /** 📊 Calculate Overall Discount Per Item */
    private function calculateOverallDiscountPerItem()
    {
        if (!$this->selectedInvoice) {
            $this->overallDiscountPerItem = 0;
            return;
        }

        $totalQuantity = $this->selectedInvoice->items->sum('quantity');
        $totalDiscountAmount = $this->selectedInvoice->discount_amount ?? 0;

        // Calculate total unit discounts from all sale items
        $totalUnitDiscounts = $this->selectedInvoice->items->sum(function ($item) {
            return ($item->discount_per_unit ?? 0) * $item->quantity;
        });

        // Calculate remaining overall discount after unit discounts
        $remainingOverallDiscount = $totalDiscountAmount - $totalUnitDiscounts;

        // Distribute remaining overall discount per item
        $this->overallDiscountPerItem = $totalQuantity > 0 ? ($remainingOverallDiscount / $totalQuantity) : 0;
    }

    /** 📜 Load Previous Returns */
    private function loadPreviousReturns()
    {
        if (!$this->selectedInvoice) {
            $this->previousReturns = [];
            return;
        }

        $this->previousReturns = ReturnsProduct::where('sale_id', $this->selectedInvoice->id)
            ->with('product')
            ->get()
            ->groupBy('product_id')
            ->map(function ($returns) {
                return [
                    'product_name' => $returns->first()->product->name ?? 'Unknown',
                    'total_returned' => $returns->sum('return_quantity'),
                    'total_amount' => $returns->sum('total_amount'),
                    'conditions' => $returns->pluck('return_condition')
                        ->filter()
                        ->map(fn($condition) => str_replace('_', ' ', $condition))
                        ->unique()
                        ->values()
                        ->toArray(),
                    'returns' => $returns->map(function ($return) {
                        $condition = $return->return_condition;
                        if (!$condition && $return->notes) {
                            if (str_contains($return->notes, '(company_fault)')) {
                                $condition = 'company_fault';
                            } elseif (str_contains($return->notes, '(damage)')) {
                                $condition = 'damage';
                            } else {
                                $condition = 'usable';
                            }
                        }

                        return [
                            'quantity' => $return->return_quantity,
                            'amount' => $return->total_amount,
                            'condition' => $condition ?: 'usable',
                            'date' => $return->created_at->format('Y-m-d H:i'),
                        ];
                    })->toArray()
                ];
            })
            ->toArray();
    }

    /** 🔢 Get Already Returned Quantity */
    private function getAlreadyReturnedQuantity($productId)
    {
        if (!$this->selectedInvoice) return 0;

        return ReturnsProduct::where('sale_id', $this->selectedInvoice->id)
            ->where('product_id', $productId)
            ->sum('return_quantity');
    }

    /** 👁️ View Invoice Details in Modal */
    public function viewInvoice($invoiceId)
    {
        $invoice = Sale::with(['items.product', 'customer'])->find($invoiceId);

        if ($invoice) {
            $totalDiscountAmount = $invoice->discount_amount ?? 0;
            $totalQty = $invoice->items->sum('quantity');

            // Calculate total unit discounts
            $totalUnitDiscounts = $invoice->items->sum(function ($item) {
                return ($item->discount_per_unit ?? 0) * $item->quantity;
            });

            // Calculate remaining overall discount per item
            $remainingOverallDiscount = $totalDiscountAmount - $totalUnitDiscounts;
            $overallDiscountPerItem = $totalQty > 0 ? ($remainingOverallDiscount / $totalQty) : 0;

            $this->invoiceModalData = [
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer ? $invoice->customer->name : 'N/A',
                'date' => $invoice->created_at->format('Y-m-d H:i:s'),
                'total_amount' => $invoice->total_amount,
                'overall_discount' => $totalDiscountAmount,
                'items' => $invoice->items->map(function ($item) use ($overallDiscountPerItem) {
                    $itemDiscount = $item->discount_per_unit ?? 0;
                    $totalDiscountPerUnit = $itemDiscount + $overallDiscountPerItem;
                    $netPrice = $item->unit_price - $totalDiscountPerUnit;

                    return [
                        'product_name' => $item->product ? $item->product->name : 'Item',
                        'product_code' => $item->product ? $item->product->code : '-',
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'item_discount' => $itemDiscount,
                        'overall_discount' => $overallDiscountPerItem,
                        'net_price' => $netPrice,
                        'total' => $item->quantity * $netPrice,
                    ];
                })->toArray()
            ];
            $this->showInvoiceModal = true;
            $this->dispatch('show-invoice-modal');
        }
    }

    /** ❌ Close Invoice Modal */
    public function closeInvoiceModal()
    {
        $this->showInvoiceModal = false;
        $this->invoiceModalData = null;
    }

    /** 📦 Load Products from Selected Invoice for Search */
    private function loadInvoiceProductsForSearch()
    {
        if (empty($this->selectedInvoices)) {
            $this->invoiceProductsForSearch = [];
            return;
        }

        $allProducts = collect();

        foreach ($this->selectedInvoices as $invoiceId) {
            $invoice = Sale::with(['items.product.price'])->find($invoiceId);
            if ($invoice) {
                $products = $invoice->items->map(function ($item) use ($invoice) {
                    if (!$item->product) return null;
                    $alreadyReturned = $this->getAlreadyReturnedQuantity($item->product->id);
                    $remainingQty = $item->quantity - $alreadyReturned;

                    return [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'code' => $item->product->code,
                        'image' => $item->product->image,
                        'selling_price' => $item->unit_price,
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'max_qty' => $remainingQty,
                    ];
                })->filter();
                $allProducts = $allProducts->merge($products);
            }
        }

        $this->invoiceProductsForSearch = $allProducts->unique('id')->values()->toArray();
    }

    /** ❌ Remove Product from Return Cart */
    public function removeFromReturn($index)
    {
        unset($this->returnItems[$index]);
        $this->returnItems = array_values($this->returnItems);
        $this->calculateTotalReturnValue();
    }

    /** 🧹 Clear Cart */
    public function clearReturnCart()
    {
        $this->returnItems = [];
        $this->totalReturnValue = 0;
    }

    /** ♻️ Auto-update total when quantities change */
    public function updatedReturnItems()
    {
        $this->calculateTotalReturnValue();
    }

    /** 💰 Calculate Total Return Value */
    private function calculateTotalReturnValue()
    {
        $this->totalReturnValue = collect($this->returnItems)->sum(
            fn($item) => ((float)($item['return_qty'] ?? 0)) * ((float)($item['net_unit_price'] ?? 0))
        );
    }

    /** ✅ Validate before showing confirmation */
    public function processReturn()
    {
        $this->calculateTotalReturnValue();

        if (empty($this->returnItems) || !$this->selectedInvoice) {
            $this->js("Swal.fire('Error!', 'Please select items for return.', 'error')");
            return;
        }

        $hasReturnItems = false;
        foreach ($this->returnItems as $item) {
            if ($item['return_qty'] < 0) {
                $this->js("Swal.fire('Error!', 'Return quantity cannot be negative for " . $item['name'] . "', 'error')");
                return;
            }

            $condition = $item['return_condition'] ?? 'usable';
            if (!in_array($condition, ['usable', 'damage', 'company_fault'])) {
                $this->js("Swal.fire('Error!', 'Please select a valid return condition for " . $item['name'] . "', 'error')");
                return;
            }

            if (isset($item['return_qty']) && $item['return_qty'] > 0) {
                if ($item['return_qty'] > $item['max_qty']) {
                    $this->js("Swal.fire('Error!', 'Invalid return quantity for " . $item['name'] . ". Maximum available: " . $item['max_qty'] . "', 'error')");
                    return;
                }
                $hasReturnItems = true;
            }
        }

        if (!$hasReturnItems) {
            $this->dispatch('alert', ['message' => 'Please enter at least one return quantity.']);
            return;
        }

        $this->dispatch('show-return-modal');
    }

    /** 💾 Confirm Return & Save to Database */
    public function confirmReturn()
    {
        $this->calculateTotalReturnValue();

        if (empty($this->returnItems) || !$this->selectedCustomer || !$this->selectedInvoice) return;

        $itemsToReturn = array_filter($this->returnItems, function ($item) {
            return isset($item['return_qty']) && $item['return_qty'] > 0;
        });

        if (empty($itemsToReturn)) {
            $this->dispatch('alert', ['message' => 'No valid return quantities entered.']);
            return;
        }

        DB::transaction(function () use ($itemsToReturn) {
            foreach ($itemsToReturn as $item) {
                $saleItem = \App\Models\SaleItem::where('sale_id', $this->selectedInvoice->id)
                    ->where('product_id', $item['product_id'])
                    ->first();
                $costPrice = $saleItem ? $saleItem->cost_price : 0;

                ReturnsProduct::create([
                    'sale_id' => $this->selectedInvoice->id,
                    'product_id' => $item['product_id'],
                    'return_quantity' => $item['return_qty'],
                    'selling_price' => $item['net_unit_price'],
                    'cost_price' => $costPrice,
                    'total_amount' => $item['return_qty'] * $item['net_unit_price'],
                    'return_condition' => $item['return_condition'] ?? 'usable',
                    'notes' => 'Customer return processed via system (' . ($item['return_condition'] ?? 'usable') . ')',
                ]);

                $this->updateProductStock($item['product_id'], $item['return_qty'], $item['return_condition'] ?? 'usable');
            }
        });

        $this->clearReturnCart();
        $this->dispatch('alert', ['message' => 'Return processed successfully!']);
        $this->dispatch('close-return-modal');
        $this->dispatch('reload-page');
    }

    /** 📈 Update Product Stock for System Returns */
    private function updateProductStock($productId, $quantity, $returnCondition = 'usable')
    {
        $stock = ProductStock::where('product_id', $productId)->first();

        if ($stock) {
            if (in_array($returnCondition, ['damage', 'company_fault'])) {
                $stock->damage_stock += $quantity;
            } else {
                $stock->available_stock += $quantity;
            }

            // Decrease sold_count since units are returned
            if ($stock->sold_count >= $quantity) {
                $stock->sold_count -= $quantity;
            } else {
                $stock->sold_count = 0;
            }
            $stock->save();
        } else {
            ProductStock::create([
                'product_id' => $productId,
                'available_stock' => in_array($returnCondition, ['damage', 'company_fault']) ? 0 : $quantity,
                'damage_stock' => in_array($returnCondition, ['damage', 'company_fault']) ? $quantity : 0,
                'total_stock' => $quantity,
                'sold_count' => 0,
                'restocked_quantity' => 0,
            ]);
        }
    }

    /** 🔄 Reset System Return Data */
    private function resetReturnData()
    {
        $this->selectedInvoice = null;
        $this->selectedInvoices = [];
        $this->invoiceProducts = [];
        $this->returnItems = [];
        $this->selectedProducts = [];
        $this->showReturnSection = false;
        $this->searchReturnProduct = '';
        $this->availableProducts = [];
        $this->invoiceProductsForSearch = [];
        $this->totalReturnValue = 0;
        $this->overallDiscountPerItem = 0;
        $this->previousReturns = [];
    }

    // ==========================================
    // MANUAL / EXTERNAL RETURN METHODS
    // ==========================================

    /** 🔍 Search Customer for Manual Return */
    public function updatedManualCustomerSearch()
    {
        if (strlen($this->manualCustomerSearch) > 1) {
            $this->manualCustomers = Customer::query()
                ->where('name', 'like', '%' . $this->manualCustomerSearch . '%')
                ->orWhere('phone', 'like', '%' . $this->manualCustomerSearch . '%')
                ->orWhere('email', 'like', '%' . $this->manualCustomerSearch . '%')
                ->limit(8)
                ->get();
        } else {
            $this->manualCustomers = [];
        }
    }

    /** 👤 Select Customer for Manual Return */
    public function selectManualCustomer($customerId)
    {
        $customer = Customer::find($customerId);
        if ($customer) {
            $this->selectedManualCustomer = $customer;
            $this->manualCustomerName = $customer->name;
            $this->manualCustomerPhone = $customer->phone ?? '';
            $this->manualCustomerSearch = '';
            $this->manualCustomers = [];
        }
    }

    /** ❌ Clear Selected Customer for Manual Return */
    public function clearManualCustomer()
    {
        $this->selectedManualCustomer = null;
        $this->manualCustomerName = '';
        $this->manualCustomerPhone = '';
        $this->manualCustomerSearch = '';
        $this->manualCustomers = [];
    }

    /** 🔍 Search Product to add to Manual Return */
    public function updatedManualProductSearch()
    {
        if (strlen($this->manualProductSearch) > 1) {
            $this->manualProductSearchResults = ProductDetail::with(['price', 'stock'])
                ->where('status', 1)
                ->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->manualProductSearch . '%')
                        ->orWhere('code', 'like', '%' . $this->manualProductSearch . '%')
                        ->orWhere('barcode', 'like', '%' . $this->manualProductSearch . '%');
                })
                ->limit(10)
                ->get();
        } else {
            $this->manualProductSearchResults = [];
        }
    }

    /** ➕ Add Product to Manual Return Cart */
    public function addManualProduct($productId)
    {
        $product = ProductDetail::with(['price', 'stock'])->find($productId);
        if (!$product) return;

        // Check if already in cart
        $existsIndex = null;
        foreach ($this->manualReturnItems as $index => $item) {
            if ($item['product_id'] == $productId) {
                $existsIndex = $index;
                break;
            }
        }

        if ($existsIndex !== null) {
            $this->manualReturnItems[$existsIndex]['return_qty'] += 1;
            $this->manualReturnItems[$existsIndex]['total_amount'] =
                $this->manualReturnItems[$existsIndex]['return_qty'] * $this->manualReturnItems[$existsIndex]['unit_price'];
        } else {
            $sellingPrice = $product->price ? (float)$product->price->selling_price : 0;
            $costPrice = $product->price ? (float)$product->price->supplier_price : 0;
            $currentStock = $product->stock ? (float)$product->stock->available_stock : 0;

            $this->manualReturnItems[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'image' => $product->image,
                'available_stock' => $currentStock,
                'unit_price' => $sellingPrice,
                'cost_price' => $costPrice,
                'return_qty' => 1,
                'return_condition' => 'usable',
                'notes' => '',
                'total_amount' => $sellingPrice * 1,
            ];
        }

        $this->manualProductSearch = '';
        $this->manualProductSearchResults = [];
        $this->calculateManualTotalReturnValue();
    }

    /** ❌ Remove Item from Manual Return Cart */
    public function removeManualReturnItem($index)
    {
        unset($this->manualReturnItems[$index]);
        $this->manualReturnItems = array_values($this->manualReturnItems);
        $this->calculateManualTotalReturnValue();
    }

    /** 🔄 Auto-update totals when Manual Return quantities or prices change */
    public function updatedManualReturnItems()
    {
        foreach ($this->manualReturnItems as $index => $item) {
            $qty = max(0, (float)($item['return_qty'] ?? 0));
            $price = max(0, (float)($item['unit_price'] ?? 0));
            $this->manualReturnItems[$index]['total_amount'] = $qty * $price;
        }
        $this->calculateManualTotalReturnValue();
    }

    /** 💰 Calculate Total Manual Return Value */
    private function calculateManualTotalReturnValue()
    {
        $this->manualTotalReturnValue = collect($this->manualReturnItems)->sum(function ($item) {
            return ((float)($item['return_qty'] ?? 0)) * ((float)($item['unit_price'] ?? 0));
        });
    }

    /** 🧹 Clear Manual Return Cart */
    public function clearManualReturnCart()
    {
        $this->manualReturnItems = [];
        $this->manualTotalReturnValue = 0;
        $this->manualInvoiceNumber = '';
        $this->manualInvoiceDate = now()->format('Y-m-d');
        $this->clearManualCustomer();
        $this->manualNotes = '';
    }

    /** ✅ Validate and prompt confirmation for Manual Return */
    public function processManualReturn()
    {
        $this->calculateManualTotalReturnValue();

        if (empty(trim($this->manualInvoiceNumber))) {
            $this->js("Swal.fire('Error!', 'Please enter the invoice number for this external sale return.', 'error')");
            return;
        }

        if (empty($this->manualInvoiceDate)) {
            $this->js("Swal.fire('Error!', 'Please select the invoice date.', 'error')");
            return;
        }

        if (empty($this->selectedManualCustomer) && empty(trim($this->manualCustomerName))) {
            $this->js("Swal.fire('Error!', 'Please select a customer or type the customer name.', 'error')");
            return;
        }

        if (empty($this->manualReturnItems)) {
            $this->js("Swal.fire('Error!', 'Please add at least one product to return.', 'error')");
            return;
        }

        foreach ($this->manualReturnItems as $item) {
            if (!isset($item['return_qty']) || (float)$item['return_qty'] <= 0) {
                $this->js("Swal.fire('Error!', 'Return quantity must be greater than 0 for " . addslashes($item['name']) . "', 'error')");
                return;
            }

            if (!isset($item['unit_price']) || (float)$item['unit_price'] < 0) {
                $this->js("Swal.fire('Error!', 'Unit price cannot be negative for " . addslashes($item['name']) . "', 'error')");
                return;
            }

            $condition = $item['return_condition'] ?? 'usable';
            if (!in_array($condition, ['usable', 'damage', 'company_fault'])) {
                $this->js("Swal.fire('Error!', 'Please select a valid condition for " . addslashes($item['name']) . "', 'error')");
                return;
            }
        }

        $this->dispatch('show-manual-return-modal');
    }

    /** 💾 Confirm and save Manual Return to separate database table */
    public function confirmManualReturn()
    {
        $this->calculateManualTotalReturnValue();

        if (empty($this->manualReturnItems) || empty(trim($this->manualInvoiceNumber))) {
            return;
        }

        $custName = $this->selectedManualCustomer ? $this->selectedManualCustomer->name : ($this->manualCustomerName ?: 'Walk-in Customer');
        $custId = $this->selectedManualCustomer ? $this->selectedManualCustomer->id : null;
        $invNumber = trim($this->manualInvoiceNumber);
        $invDate = $this->manualInvoiceDate ?: now()->toDateString();
        $generalNotes = $this->manualNotes;

        DB::transaction(function () use ($custName, $custId, $invNumber, $invDate, $generalNotes) {
            foreach ($this->manualReturnItems as $item) {
                $qty = (float)$item['return_qty'];
                $unitPrice = (float)$item['unit_price'];
                $costPrice = (float)($item['cost_price'] ?? 0);
                $condition = $item['return_condition'] ?? 'usable';
                $itemNotes = !empty($item['notes']) ? $item['notes'] : $generalNotes;

                ManualSaleReturn::create([
                    'invoice_number' => $invNumber,
                    'invoice_date' => $invDate,
                    'customer_id' => $custId,
                    'customer_name' => $custName,
                    'product_id' => $item['product_id'],
                    'return_quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'cost_price' => $costPrice,
                    'total_amount' => $qty * $unitPrice,
                    'return_condition' => $condition,
                    'notes' => $itemNotes ?: 'Manual/External sale return',
                    'created_by' => auth()->id(),
                ]);

                // Restock product stock in database
                $this->updateManualProductStock($item['product_id'], $qty, $condition);
            }
        });

        $this->clearManualReturnCart();
        $this->dispatch('close-manual-return-modal');
        $this->dispatch('alert', ['message' => 'Manual sale return processed successfully and inventory restocked!']);
    }

    /** 📈 Update Product Stock for Manual / External Returns */
    private function updateManualProductStock($productId, $quantity, $returnCondition = 'usable')
    {
        $stock = ProductStock::where('product_id', $productId)->first();

        if ($stock) {
            if (in_array($returnCondition, ['damage', 'company_fault'])) {
                $stock->damage_stock += $quantity;
            } else {
                $stock->available_stock += $quantity;
            }
            $stock->total_stock = ($stock->available_stock ?? 0) + ($stock->damage_stock ?? 0);
            $stock->restocked_quantity = ($stock->restocked_quantity ?? 0) + $quantity;
            $stock->save();
        } else {
            ProductStock::create([
                'product_id' => $productId,
                'available_stock' => in_array($returnCondition, ['damage', 'company_fault']) ? 0 : $quantity,
                'damage_stock' => in_array($returnCondition, ['damage', 'company_fault']) ? $quantity : 0,
                'total_stock' => $quantity,
                'sold_count' => 0,
                'restocked_quantity' => $quantity,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.return-product')->layout($this->layout);
    }
}
