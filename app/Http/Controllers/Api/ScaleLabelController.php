<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Setting;
use App\Services\ScaleLabel;
use App\Services\ScaleProducts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ScaleLabelController extends Controller
{
    public const CHECKS = ['printed', 'learned', 'half', 'oneQuarter', 'thirdProduct', 'priceMatches', 'saleReturned'];

    public function index(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => [
            'duplicates' => ScaleProducts::activeCodes()->filter(fn ($products) => $products->count() > 1)
                ->map(fn ($products, $plu) => ['plu' => (string) $plu, 'products' => $products->values()])->values(),
            'products' => $request->user()->isAdmin() ? Product::with('unit')->where('status', 'ACTIVE')->whereNotNull('scale_product_code')->get()
                ->filter(fn (Product $product) => ScaleProducts::isWeighted($product) && ScaleLabel::normalizePlu($product->scale_product_code) !== null)
                ->map(fn (Product $product) => $product->only(['id', 'name_ku', 'scale_product_code']))->values() : [],
        ]]);
    }

    public function learn(Request $request): JsonResponse
    {
        $input = $request->validate([
            'samples' => 'required|array|min:1|max:8',
            'samples.*.code' => 'required|string|max:64',
            'samples.*.plu' => 'required|string|max:24|regex:/^(?:PLU)?[0-9]+$/i',
            'samples.*.weight' => 'nullable|string|max:16|regex:/^[0-9]+(?:[.,][0-9]+)?$/',
            'samples.*.price' => 'nullable|string|max:16|regex:/^[0-9]+(?:[.,][0-9]+)?$/',
            'confirm' => 'sometimes|boolean',
        ]);
        $samples = $input['samples'];
        foreach ($samples as $sample) {
            if (empty($sample['weight']) && empty($sample['price'])) {
                throw ValidationException::withMessages(['samples' => ['scaleSampleValueRequired']]);
            }
        }

        try {
            $matches = ScaleLabel::solve($samples);
        } catch (InvalidArgumentException $error) {
            throw ValidationException::withMessages(['samples' => [$error->getMessage()]]);
        }

        $differentWeights = collect($samples)->filter(fn ($sample) => ! empty($sample['weight']))
            ->map(fn ($sample) => (float) str_replace(',', '.', $sample['weight']))->unique()->count() >= 2;
        $canSave = count($matches) === 1 && $differentWeights;

        if ($request->boolean('confirm')) {
            if (! $canSave) {
                throw ValidationException::withMessages(['samples' => ['scaleCannotConfirm']]);
            }
            $layout = $matches[0];
            DB::transaction(function () use ($layout) {
                foreach ([
                    'scale_label_prefixes' => implode(',', $layout['prefixes']),
                    'scale_label_plu_length' => $layout['plu_length'],
                    'scale_label_value_length' => $layout['value_length'],
                    'scale_label_value_type' => $layout['type'],
                    'scale_label_value_decimals' => $layout['decimals'],
                ] as $key => $value) {
                    Setting::updateOrCreate(['key_name' => $key], ['key_value' => (string) $value, 'group_name' => 'scale']);
                }
                self::clearChecklist();
                ActivityLog::log('UPDATE', 'SETTING', 0, 'Confirmed EAN-13 scale layout from label samples');
            });
        }

        return response()->json(['success' => true, 'data' => ['layouts' => $matches, 'can_save' => $canSave]]);
    }

    public function preview(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $input = $request->validate([
            'product_id' => 'required|integer',
            'weight' => 'required|numeric|min:0.001|max:999999',
            'code' => 'nullable|string|max:64',
        ]);
        $product = Product::with('unit')->where('status', 'ACTIVE')->findOrFail($input['product_id']);
        $layout = ScaleLabel::layout(Setting::whereIn('key_name', array_keys(ScaleLabel::DEFAULTS))->pluck('key_value', 'key_name')->all());
        try {
            if (! ScaleProducts::isWeighted($product) || ScaleLabel::normalizePlu($product->scale_product_code) === null) {
                throw new InvalidArgumentException('scaleNotWeighted');
            }
            $value = (float) $input['weight'];
            if ($layout['type'] === 'price') {
                if ((float) $product->price_per_kg <= 0) {
                    throw new InvalidArgumentException('scaleMissingPrice');
                }
                $value *= (float) $product->price_per_kg;
            }
            $code = $input['code'] ?? ScaleLabel::encode($product->scale_product_code, number_format($value, $layout['decimals'], '.', ''), $layout);
            $parsed = ScaleLabel::parse($code, $layout);
            $resolved = ScaleProducts::resolve($parsed['plu']);
            if ((int) $resolved->id !== (int) $product->id) {
                throw new InvalidArgumentException('scalePluNotFound');
            }
            $weight = ScaleProducts::weight($parsed, $resolved);
        } catch (InvalidArgumentException $error) {
            throw ValidationException::withMessages(['code' => [$error->getMessage()]]);
        }

        return response()->json(['success' => true, 'data' => [
            'code' => $code, ...$parsed, 'weight_kg' => $weight, 'name' => $resolved->name_ku,
        ]]);
    }

    public function checklist(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $items = $request->validate([
            'items' => 'present|array|max:'.count(self::CHECKS),
            'items.*' => 'required|string|distinct|in:'.implode(',', self::CHECKS),
        ])['items'];
        $verified = count($items) === count(self::CHECKS);
        $state = [
            'items' => $items,
            'verified' => $verified,
            'by' => $request->user()->username,
            'at' => now()->toIso8601String(),
        ];
        DB::transaction(function () use ($state) {
            Setting::updateOrCreate(['key_name' => 'scale_hardware_checklist'], ['key_value' => json_encode($state, JSON_THROW_ON_ERROR), 'group_name' => 'scale']);
            ActivityLog::log('UPDATE', 'SETTING', 0, $state['verified'] ? 'Marked all scale hardware checks complete' : 'Updated scale hardware checklist');
        });

        return response()->json(['success' => true, 'data' => $state]);
    }

    public static function clearChecklist(): void
    {
        Setting::where('key_name', 'scale_hardware_checklist')->delete();
    }
}
