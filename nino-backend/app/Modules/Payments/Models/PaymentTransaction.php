<?php

namespace App\Modules\Payments\Models;

/**
 * Canonical payment transaction model alias for the Payments module.
 *
 * The actual ledger schema lives in the Finance module, but gateway adapters
 * and payment callbacks still reference the Payments namespace. This alias
 * keeps that boundary stable while both modules share one real model contract.
 */
class PaymentTransaction extends \App\Modules\Finance\Models\PaymentTransaction
{
    protected $fillable = [
        'reference',
        'order_id',
        'customer_id',
        'type',
        'status',
        'payment_method',
        'gateway',
        'gateway_transaction_id',
        'gateway_reference',
        'amount',
        'fee_amount',
        'net_amount',
        'currency',
        'cod_status',
        'cod_collected_at',
        'cod_deposited_at',
        'cod_collected_by',
        'cod_collected_amount',
        'cod_notes',
        'order_subtotal',
        'order_discount',
        'order_shipping',
        'order_tax',
        'order_total',
        'metadata',
        'payload',
        'failure_reason',
        'error_code',
        'error_message',
        'processed_by',
    ];
}
