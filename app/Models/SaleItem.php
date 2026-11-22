<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'gross_weight',
        'tare_weight',
        'net_weight',
        'weight_unit',
        'scale_source',
        'price_per_kg',
        'unit_price',
        'purchase_cost',
        'discount',
        'total',
        'profit',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'gross_weight' => 'decimal:3',
        'tare_weight' => 'decimal:3',
        'net_weight' => 'decimal:3',
        'price_per_kg' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'purchase_cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
