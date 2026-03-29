<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SHIPPED,
        self::STATUS_RECEIVED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'source_stock_item_id',
        'destination_stock_item_id',
        'quantity',
        'status',
        'shipped_at',
        'received_at',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
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
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isShipped(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    public function isReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
