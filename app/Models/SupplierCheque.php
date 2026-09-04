<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SupplierCheque extends Model
{
    use HasFactory;

    protected $fillable = [
        'cheque_number',
        'cheque_date',
        'bank_name',
        'amount',
        'supplier_id',
        'payee_name',
        'purchase_payment_id',
        'status',
        'cheque_photo_url',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'cheque_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(ProductSupplier::class, 'supplier_id');
    }

    public function purchasePayment()
    {
        return $this->belongsTo(PurchasePayment::class, 'purchase_payment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Day name accessor (e.g. Tuesday)
     */
    public function getDayNameAttribute(): string
    {
        return $this->cheque_date ? Carbon::parse($this->cheque_date)->format('l') : '';
    }

    /**
     * Display Payee Name (either supplier relation name or custom payee_name)
     */
    public function getDisplayPayeeAttribute(): string
    {
        return $this->supplier?->name ?? $this->payee_name ?? '-';
    }

    /**
     * Check if cheque falls on a registered holiday
     */
    public function getIsHolidayAttribute(): bool
    {
        if (!$this->cheque_date) {
            return false;
        }
        return Holiday::isHoliday($this->cheque_date->format('Y-m-d'));
    }

    /**
     * Get holiday reason if applicable
     */
    public function getHolidayReasonAttribute(): ?string
    {
        if (!$this->cheque_date) {
            return null;
        }
        return Holiday::getHolidayReason($this->cheque_date->format('Y-m-d'));
    }
}
