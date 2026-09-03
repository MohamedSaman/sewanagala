<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualSupplierReturn extends Model
{
    use HasFactory;

    protected $table = 'manual_supplier_returns';

    protected $fillable = [
        'reference_number',
        'return_date',
        'supplier_id',
        'supplier_name',
        'product_id',
        'return_quantity',
        'unit_price',
        'total_amount',
        'return_condition',
        'return_reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'return_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(ProductSupplier::class, 'supplier_id');
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
