<?php

namespace App\Models\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = [
        'source_stock_item_id',
        'destination_stock_item_id',
        'quantity',
        'status',
        'shipped_at',
        'received_at',
        'notes',
        'user_id'
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function sourceItem()
    {
        return $this->belongsTo(StockItem::class, 'source_stock_item_id');
    }

    public function destinationItem()
    {
        return $this->belongsTo(StockItem::class, 'destination_stock_item_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
