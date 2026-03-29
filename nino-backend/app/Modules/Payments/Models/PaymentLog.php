<?php

namespace App\Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Orders\Models\Order;

class PaymentLog extends Model
{
    protected $fillable = [
        'payment_transaction_id',
        'order_id',
        'gateway',
        'direction',
        'event_type',
        'status_before',
        'status_after',
        'is_successful',
        'gateway_reference',
        'request_payload',
        'response_payload',
        'error_message',
        'error_code',
        'ip_address',
        'user_agent',
        'amount',
        'currency',
        'response_time_ms',
    ];

    protected $casts = [
        'is_successful' => 'boolean',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'amount' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
