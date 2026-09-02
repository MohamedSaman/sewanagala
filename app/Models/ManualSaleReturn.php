<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualSaleReturn extends Model
{
    use HasFactory;

    protected $table = 'manual_sale_returns';

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'customer_id',
        'customer_name',
        'product_id',
        'return_quantity',
        'unit_price',
        'cost_price',
        'total_amount',
        'return_condition',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'return_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductDetail::class, 'product_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
