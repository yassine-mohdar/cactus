<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'sale_price',
        'quantity',
        'image_path',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues()
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'product_variant_option_value',
            'product_variant_id',
            'option_value_id',
        );
    }

    public function hasSalePrice(): bool
    {
        return $this->sale_price !== null
            && ($this->price === null || (float) $this->sale_price < (float) $this->price);
    }

    public function effectivePrice(): ?float
    {
        $price = $this->hasSalePrice() ? $this->sale_price : $this->price;

        return $price !== null ? (float) $price : null;
    }

    public function optionSummary(): string
    {
        return $this->optionValues
            ->sortBy([
                fn (ProductOptionValue $value) => (int) ($value->option?->position ?? 0),
                fn (ProductOptionValue $value) => (string) ($value->option?->name ?? ''),
                fn (ProductOptionValue $value) => (string) $value->value,
            ])
            ->map(function (ProductOptionValue $value): string {
                $optionName = $value->option?->name ?? 'Option';

                return $optionName.': '.$value->value;
            })
            ->implode(' · ');
    }
}
