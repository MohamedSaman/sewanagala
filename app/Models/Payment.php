<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'amount',
        'payment_method',
        'payment_reference',
        'card_number',
        'bank_name',
        'cheque_number',
        'cheque_date',
        'cheque_photo_url',
        'is_completed',
        'payment_date',
        'due_date',
        'due_payment_method',
        'due_payment_attachment',
        'status',
        'customer_id',
        'transfer_date',
        'transfer_reference',
        'notes',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'due_date' => 'date',
        'cheque_date' => 'date',
        'is_completed' => 'boolean',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cheques()
    {
        return $this->hasMany(Cheque::class);
    }

    public function getStatusBadgeAttribute()
    {
        if ($this->status === null) {
            return '<span class="badge bg-secondary">Pending Payment</span>';
        }

        return match ($this->status) {
            'pending' => '<span class="badge bg-warning">Pending Approval</span>',
            'approved' => '<span class="badge bg-success">Approved</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
            'paid' => '<span class="badge bg-success">Paid</span>',
            default => '<span class="badge bg-secondary">Unknown</span>',
        };
    }
    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
