<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Unit;
use App\Services\ProductExpiryService;
use App\Services\ScaleLabel;
use App\Services\ScaleProducts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'unit'])->where('status', 'ACTIVE');
        $expiryFilter = $request->query('expiry_status');
        if ($expiryFilter !== null) {
            $request->validate([
                'expiry_status' => 'in:all,expired,today,urgent,within_3,within_7,within_15,within_30,no_expiry',
            ]);
            if ($expiryFilter !== 'all') {
                app(ProductExpiryService::class)->applyFilter($query, $expiryFilter);
            }
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ku', 'like', "%{$s}%")
                    ->orWhere('barcode', 'like', "%{$s}%")
                    ->orWhere('scale_product_code', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%");
            });
        }

        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->lowStock();
            } elseif ($request->stock_status === 'out') {
                $query->outOfStock();
            } elseif ($request->stock_status === 'expiring') {
                $query->expiringSoon(app(ProductExpiryService::class)->settings()['warning_days']);
            }
        }

        $products = $query->latest('id')->get()->map(fn (Product $p) => $this->payload($p));

        return response()->json(['success' => true, 'data' => $products]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'barcode' => 'required|string|unique:products,barcode',
            'name_ku' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'is_weighted' => 'nullable|boolean',
            'price_per_kg' => 'nullable|numeric|min:0',
            'tare_weight' => 'nullable|numeric|min:0',
            'scale_product_code' => 'nullable|string|max:50',
            'expiry_date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'description' => 'nullable|string',
        ], [
            'barcode.unique' => 'ئەم بارکۆدە پێشتر بەکار هاتووە.',
        ]);

        $this->applyWeightedDefaults($validated);

        $image = null;
        try {
            $product = DB::transaction(function () use ($validated, $request, &$image) {
                $this->validatePlu($validated['scale_product_code'] ?? null);
                if ($request->hasFile('image')) {
                    $image = $request->file('image')->store('products', 'public');
                    $validated['image'] = $image;
                }
                $validated['sku'] = 'SKU-TEMP-'.Str::uuid();
                $product = Product::create($validated);
                $product->update(['sku' => 'SKU-'.$product->id]);

                return $product;
            });
        } catch (Throwable $error) {
            if ($image) {
                Storage::disk('public')->delete($image);
            }
            throw $error;
        }

        ActivityLog::log('CREATE', 'PRODUCT', $product->id, "Added product {$product->name_ku} ({$product->barcode})");

        return response()->json(['success' => true, 'data' => $product], 201);
    }

    public function show($id)
    {
        $product = Product::with(['category', 'unit'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product->setAttribute(
                'image',
                $product->image ? asset('storage/'.ltrim($product->image, '/')) : null,
            ),
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'barcode' => "required|string|unique:products,barcode,{$id}",
            'name_ku' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'is_weighted' => 'nullable|boolean',
            'price_per_kg' => 'nullable|numeric|min:0',
            'tare_weight' => 'nullable|numeric|min:0',
            'scale_product_code' => 'nullable|string|max:50',
            'expiry_date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_image' => 'nullable|boolean',
            'description' => 'nullable|string',
        ], [
            'barcode.unique' => 'ئەم بارکۆدە پێشتر بەکار هاتووە.',
        ]);

        $this->applyWeightedDefaults($validated);

        $oldImage = $product->image;
        $removeOldImage = (bool) ($validated['remove_image'] ?? false);
        unset($validated['remove_image']);
        $newImage = $request->hasFile('image')
            ? $request->file('image')->store('products', 'public')
            : null;
        if ($newImage) {
            $validated['image'] = $newImage;
        } elseif ($removeOldImage) {
            $validated['image'] = null;
        }

        try {
            DB::transaction(function () use ($product, $validated) {
                if ($product->status === 'ACTIVE' && array_key_exists('scale_product_code', $validated)
                    && ScaleLabel::normalizePlu($validated['scale_product_code']) !== ScaleLabel::normalizePlu($product->scale_product_code)) {
                    $this->validatePlu($validated['scale_product_code'], $product->id);
                }
                $product->update($validated);
            });
        } catch (Throwable $error) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }
            throw $error;
        }

        if (($newImage || $removeOldImage) && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        ActivityLog::log('UPDATE', 'PRODUCT', $product->id, "Updated product {$product->name_ku} ({$product->barcode})");

        return response()->json([
            'success' => true,
            'data' => $product->fresh()->setAttribute(
                'image',
                $product->image ? asset('storage/'.ltrim($product->image, '/')) : null,
            ),
        ]);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->update(['status' => 'INACTIVE']);

        ActivityLog::log('DELETE', 'PRODUCT', $product->id, "Archived product {$product->name_ku}");

        return response()->json(['success' => true, 'message' => 'Product removed']);
    }

    public function findByBarcode(Request $request, string $barcode)
    {
        $active = Product::with(['category', 'unit'])->where('status', 'ACTIVE');
        $product = (clone $active)->where('barcode', $barcode)->first();
        if ($product) {
            return response()->json(['success' => true, 'data' => $this->payload($product)]);
        }

        $settings = $request->boolean('scale_label')
            ? Setting::whereIn('key_name', array_keys(ScaleLabel::DEFAULTS))->pluck('key_value', 'key_name')->all() + ScaleLabel::DEFAULTS
            : ScaleLabel::DEFAULTS;
        if ($settings['scale_label_enabled'] === '1') {
            try {
                $label = ScaleLabel::parse($barcode, ScaleLabel::layout($settings));
                $product = ScaleProducts::resolve($label['plu']);
                $weight = ScaleProducts::weight($label, $product);
            } catch (InvalidArgumentException $error) {
                // Keep direct short PLU lookups available alongside the label scanner.
                if ($error->getMessage() === 'scaleUnsupported' && strlen($barcode) <= 9) {
                    $product = (clone $active)->where('scale_product_code', $barcode)->first();
                    if ($product) {
                        return response()->json(['success' => true, 'data' => $this->payload($product)]);
                    }
                }

                return response()->json(['success' => false, 'message' => $error->getMessage()], 422);
            }

            return response()->json(['success' => true, 'data' => [
                ...$this->payload($product), 'scale_label' => ['weight_kg' => $weight],
            ]]);
        }

        $product = $active->where('scale_product_code', $barcode)->first();
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => $request->boolean('scale_label') ? 'productNotFound' : 'Product not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->payload($product),
        ]);
    }

    public function scaleCsv(): StreamedResponse
    {
        $layout = ScaleLabel::layout(Setting::whereIn('key_name', array_keys(ScaleLabel::DEFAULTS))->pluck('key_value', 'key_name')->all());

        return response()->streamDownload(function () use ($layout) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['PLU', 'Name', 'Price per kg', 'Barcode prefix'], ',', '"', '');
            Product::with('unit')->where('status', 'ACTIVE')->whereNotNull('scale_product_code')->chunkById(250, function ($products) use ($output, $layout) {
                foreach ($products as $product) {
                    $plu = ScaleLabel::normalizePlu($product->scale_product_code);
                    if ($plu === null || ! ScaleProducts::isWeighted($product)) {
                        continue;
                    }
                    $name = $product->name_ku;
                    if (preg_match('/^\s*[=+\-@]|^[\t\r\n]/', $name)) {
                        $name = "'".$name;
                    }
                    $price = (float) $product->price_per_kg ?: (float) $product->selling_price * ($product->unit?->short_code === 'g' ? 1000 : 1);
                    fputcsv($output, [$plu, $name, number_format($price, 2, '.', ''), $layout['prefixes'][0]], ',', '"', '');
                }
            });
            fclose($output);
        }, 'scale-products.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validatePlu(?string $code, ?int $except = null): void
    {
        if (ScaleLabel::normalizePlu($code) !== null) {
            // Serialize PLU changes without imposing a unique constraint on existing duplicates.
            Setting::where('key_name', 'scale_label_enabled')->lockForUpdate()->first();
            ScaleProducts::unique($code, $except);
        }
    }

    /** Numbers stay numbers: model decimal casts would serialize them as strings. */
    private function payload(Product $p): array
    {
        return [
            'id' => $p->id,
            'barcode' => $p->barcode,
            'sku' => $p->sku,
            'scale_product_code' => $p->scale_product_code,
            'name_ku' => $p->name_ku,
            'category_id' => $p->category_id,
            'unit_id' => $p->unit_id,
            'is_weighted' => (bool) $p->is_weighted,
            'purchase_price' => (float) $p->purchase_price,
            'selling_price' => (float) $p->selling_price,
            'price_per_kg' => $p->price_per_kg === null ? null : (float) $p->price_per_kg,
            'tare_weight' => (float) $p->tare_weight,
            'wholesale_price' => (float) $p->wholesale_price,
            'quantity' => (float) $p->quantity,
            'min_stock' => (float) $p->min_stock,
            'expiry_date' => $p->expiry_date ? $p->expiry_date->format('Y-m-d') : null,
            'image' => $p->image ? asset('storage/'.ltrim($p->image, '/')) : null,
            'description' => $p->description,
            'status' => $p->status,
            'category_ku' => $p->category ? $p->category->name_ku : '',
            'category_en' => $p->category ? $p->category->name_en : '',
            'category_ar' => $p->category ? $p->category->name_ar : '',
            'unit_ku' => $p->unit ? $p->unit->name_ku : '',
            'unit_en' => $p->unit ? $p->unit->name_en : '',
            'unit_ar' => $p->unit ? $p->unit->name_ar : '',
            'unit_code' => $p->unit ? $p->unit->short_code : '',
        ];
    }

    /** Keep the weighted flag consistent with the selected unit. */
    private function applyWeightedDefaults(array &$validated): void
    {
        $unit = Unit::find((int) ($validated['unit_id'] ?? 0));
        $unitIsWeight = $unit !== null && in_array(strtolower((string) $unit->short_code), ['kg', 'g'], true);
        $explicitlyWeighted = ! empty($validated['is_weighted']);
        $validated['is_weighted'] = $unitIsWeight || $explicitlyWeighted;

        if ($validated['is_weighted'] && empty($validated['price_per_kg'])) {
            $validated['price_per_kg'] = $validated['selling_price'] * (strtolower((string) $unit?->short_code) === 'g' ? 1000 : 1);
        }

        if (! $validated['is_weighted']) {
            $validated['price_per_kg'] = null;
        }
    }
}
