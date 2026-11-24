<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class ScaleProducts
{
    public static function activeCodes(): Collection
    {
        return Product::where('status', 'ACTIVE')->whereNotNull('scale_product_code')
            ->get(['id', 'name_ku', 'scale_product_code'])
            ->filter(fn (Product $product) => ScaleLabel::normalizePlu($product->scale_product_code) !== null)
            ->groupBy(fn (Product $product) => ScaleLabel::normalizePlu($product->scale_product_code));
    }

    public static function unique(?string $code, ?int $except = null): void
    {
        $plu = ScaleLabel::normalizePlu($code);
        if ($plu !== null && self::activeCodes()->get($plu, collect())->contains(fn (Product $product) => $product->id !== $except)) {
            throw ValidationException::withMessages(['scale_product_code' => ['scaleDuplicatePlu']]);
        }
    }

    public static function resolve(string $plu): Product
    {
        $matching = self::activeCodes()->get($plu, collect());
        if ($matching->count() !== 1) {
            throw new InvalidArgumentException($matching->isEmpty() ? 'scalePluNotFound' : 'scaleDuplicatePlu');
        }
        $product = Product::with(['category', 'unit'])->where('status', 'ACTIVE')->find($matching->first()->id);
        if (! $product) {
            throw new InvalidArgumentException('scalePluNotFound');
        }
        if (! self::isWeighted($product)) {
            throw new InvalidArgumentException('scaleNotWeighted');
        }

        return $product;
    }

    public static function isWeighted(Product $product): bool
    {
        return $product->is_weighted || in_array(strtolower((string) $product->unit?->short_code), ['kg', 'g'], true);
    }

    public static function weight(array $label, Product $product): float
    {
        $price = (float) $product->price_per_kg;
        if ($label['type'] === 'price' && $price <= 0) {
            throw new InvalidArgumentException('scaleMissingPrice');
        }
        $weight = round($label['type'] === 'weight' ? $label['value'] : $label['value'] / $price, 3);
        if ($weight <= 0) {
            throw new InvalidArgumentException('scaleZeroValue');
        }

        return $weight;
    }
}
