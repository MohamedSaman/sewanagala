<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryPayment extends Model
{
    use HasFactory;

    protected $table = 'salary_payments';

    protected $fillable = [
        'salary_id',
        'user_id',
        'salary_month',
        'amount',
        'payment_date',
        'payment_method',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'salary_month' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function salary(): BelongsTo
    {
        return $this->belongsTo(Salary::class, 'salary_id', 'salary_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
