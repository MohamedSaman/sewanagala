<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'invoice_number',
        'customer_id',
        'customer_type',
        'subtotal',
        'discount_amount',
        'total_amount',
        'payment_type',
        'payment_status',
        'status',
        'notes',
        'due_amount',
        'due_date',
        'user_id',
        'sale_type',
        'created_at',
        'updated_at',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Generate unique sale ID
    public static function generateSaleId($forDate = null)
    {
        $prefix = 'SALE-';
        $date = ($forDate ? Carbon::parse($forDate) : now())->format('Ymd');
        $lastSale = self::where('sale_id', 'like', "{$prefix}{$date}%")
            ->orderBy('sale_id', 'desc')
            ->first();

        $nextNumber = 1;

        if ($lastSale) {
            $parts = explode('-', $lastSale->sale_id);
            $lastNumber = intval(end($parts));
            $nextNumber = $lastNumber + 1;
        }

        return $prefix . $date . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    // Generate unique invoice numbers
    public static function generateInvoiceNumber($forDate = null)
    {
        $prefix = 'INV-';
        $date = ($forDate ? Carbon::parse($forDate) : now())->format('Ymd');
        $lastNumber = self::whereNotNull('invoice_number')
            ->where('invoice_number', 'like', "{$prefix}%")
            ->pluck('invoice_number')
            ->map(function ($invoiceNumber) use ($prefix) {
                $suffix = substr($invoiceNumber, strrpos($invoiceNumber, '-') + 1);
                return is_numeric($suffix) ? (int) $suffix : 0;
            })
            ->max() ?? 0;

        $nextNumber = $lastNumber + 1;

        return $prefix . $date . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function returns()
    {
        return $this->hasMany(ReturnsProduct::class, 'sale_id');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
