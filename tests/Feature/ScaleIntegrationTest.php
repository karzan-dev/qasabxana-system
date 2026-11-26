<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\User;
use App\Services\ScaleLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ScaleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signIn('ADMIN');
    }

    public function test_settings_validation_and_wizard_require_explicit_unambiguous_confirmation(): void
    {
        $this->assertSame('1', Setting::get('scale_label_enabled'));
        Setting::set('scale_label_enabled', '0');
        $settings = [
            'expiry_alerts_enabled' => '1', 'expiry_warning_days' => '7', 'expiry_urgent_days' => '3',
            'expiry_alerts_expired' => '1', 'expiry_alerts_bell' => '1', 'expiry_alerts_dashboard' => '1',
        ];
        foreach (['2,20', '200', 'AB'] as $invalid) {
            $this->postJson('/api/settings', $settings + ['scale_label_prefixes' => $invalid])
                ->assertUnprocessable()->assertJsonValidationErrors('scale_label_prefixes');
        }
        $this->postJson('/api/settings', $settings + ['scale_parity' => 'unsupported'])
            ->assertUnprocessable()->assertJsonValidationErrors('scale_parity');
        $layout = ScaleLabel::layout(['scale_label_prefixes' => '27', 'scale_label_plu_length' => '5']);
        $samples = [
            ['code' => ScaleLabel::encode('1', '0.527', $layout), 'plu' => '1', 'weight' => '0.527'],
            ['code' => ScaleLabel::encode('12345', '1.237', $layout), 'plu' => '12345', 'weight' => '1.237'],
        ];
        $this->postJson('/api/settings/scale/learn', ['samples' => [$samples[0]], 'confirm' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('samples');
        $this->postJson('/api/settings/scale/learn', ['samples' => $samples])
            ->assertOk()->assertJsonCount(1, 'data.layouts')->assertJsonPath('data.can_save', true);
        $this->assertSame('1', Setting::get('scale_label_prefixes'));
        $this->postJson('/api/settings/scale/learn', ['samples' => $samples, 'confirm' => true])->assertOk();
        $this->assertSame('27', Setting::get('scale_label_prefixes'));
        $this->assertSame('0', Setting::get('scale_label_enabled'));
        $this->postJson('/api/settings/scale/checklist', ['items' => ['printed']])->assertOk()->assertJsonPath('data.verified', false);
        $this->postJson('/api/settings/scale/checklist', ['items' => ['printed', 'learned', 'half', 'oneQuarter', 'thirdProduct', 'priceMatches', 'saleReturned']])
            ->assertOk()->assertJsonPath('data.verified', true)->assertJsonPath('data.by', 'admin-scale');
        $this->assertDatabaseHas('activity_logs', ['description' => 'Marked all scale hardware checks complete']);
        $this->signIn('CASHIER');
        $this->postJson('/api/settings/scale/learn', ['samples' => $samples])->assertForbidden();
        $this->postJson('/api/settings/scale/preview', [])->assertForbidden();
    }

    public function test_pos_label_conflicts_and_ambiguous_or_inactive_codes(): void
    {
        $layout = ScaleLabel::layout([]);
        $label = ScaleLabel::encode('00001', '0.525', $layout);
        $weighted = $this->product('PLU001');
        Setting::set('scale_label_enabled', '0');
        $this->scan($label)->assertNotFound();
        Setting::set('scale_label_enabled', '1');
        $this->scan($label)->assertOk()->assertJsonPath('data.id', $weighted->id)->assertJsonPath('data.scale_label.weight_kg', 0.525);
        $badDigit = (ScaleLabel::checkDigit(substr($label, 0, 12)) + 1) % 10;
        $this->scan(substr($label, 0, 12).$badDigit)->assertUnprocessable()->assertJsonPath('message', 'scaleChecksum');
        $this->scan(ScaleLabel::encode('99', '0.525', $layout))->assertUnprocessable()->assertJsonPath('message', 'scalePluNotFound');
        $inactive = $this->product('00001', ['status' => 'INACTIVE']);
        $this->scan($label)->assertOk()->assertJsonPath('data.id', $weighted->id);
        $inactive->update(['status' => 'ACTIVE']);
        $this->scan($label)->assertUnprocessable()->assertJsonPath('message', 'scaleDuplicatePlu');
        $this->getJson('/api/settings/scale')->assertOk()->assertJsonCount(1, 'data.duplicates');
        $inactive->update(['status' => 'INACTIVE']);
        $exact = $this->product('PLU2', ['barcode' => $label, 'is_weighted' => false], 'pcs');
        $this->scan($label)->assertUnprocessable()->assertJsonPath('message', 'scaleBarcodeConflict');
        $this->scan($exact->barcode.'x')->assertNotFound();
        $this->scan(ScaleLabel::encode('2', '0.525', $layout))->assertUnprocessable()->assertJsonPath('message', 'scaleNotWeighted');
    }

    public function test_rongta_sample_can_be_tested_sold_repeated_and_returned(): void
    {
        Setting::set('scale_label_prefixes', '10');
        Setting::set('scale_label_plu_length', '5');
        $code = '1000007001702';
        $product = $this->product('000007', ['quantity' => 50, 'barcode' => '0100000000007']);
        $this->scan($product->barcode)->assertOk()->assertJsonPath('data.id', $product->id)->assertJsonMissingPath('data.scale_label');
        $this->scan($code)->assertOk()->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.scale_label.product_code', '00007')
            ->assertJsonPath('data.scale_label.normalized_plu', '000007')
            ->assertJsonPath('data.scale_label.weight_kg', 0.17);
        $this->postJson('/api/settings/scale/test-barcode', ['code' => $code])->assertOk()
            ->assertJsonPath('data.weight_grams', 170)->assertJsonPath('data.total', 3400)
            ->assertJsonPath('data.checksum_valid', true);
        $this->assertDatabaseCount('sales', 0);
        $this->assertSame('50.000', $product->fresh()->quantity);
        $this->postJson('/api/cash-register/shifts/open', ['opening_balance' => '0.00'])->assertCreated();
        $item = ['product_id' => $product->id, 'quantity' => 0.170, 'unit_price' => 1, 'scale_barcodes' => [$code]];
        $saleInput = ['payment_method' => 'CASH', 'cash_received' => '3400.00', 'idempotency_key' => (string) Str::uuid()];
        $this->postJson('/api/pos/complete-sale', $saleInput + ['items' => [[...$item, 'quantity' => 1]]])
            ->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertSame('50.000', $product->fresh()->quantity);
        $sale = $this->postJson('/api/pos/complete-sale', $saleInput + ['items' => [$item]])
            ->assertOk()->assertJsonPath('data.items.0.total', 3400)
            ->assertJsonPath('data.items.0.scale_barcodes', [$code])->json('data.sale');
        $this->assertSame('49.830', $product->fresh()->quantity);
        $this->postJson('/api/pos/complete-sale', $saleInput + ['items' => [$item]])->assertOk();
        $this->assertSame('49.830', $product->fresh()->quantity);
        $this->postJson('/api/returns/process', [
            'sale_id' => $sale['id'], 'items' => [['product_id' => $product->id, 'quantity' => 0.170]],
            'idempotency_key' => (string) Str::uuid(),
        ])->assertOk();
        $this->assertSame('50.000', $product->fresh()->quantity);
        $this->postJson('/api/pos/complete-sale', [
            'payment_method' => 'CARD', 'idempotency_key' => (string) Str::uuid(),
            'items' => [[...$item, 'quantity' => 0.340, 'scale_barcodes' => [$code, $code]]],
        ])->assertOk()->assertJsonPath('data.items.0.total', 6800);
        $this->assertSame('49.660', $product->fresh()->quantity);
    }

    public function test_rongta_six_digit_plu_scan_and_checkout_use_the_label_weight_and_database_price(): void
    {
        $code = '1000077001305';
        $layout = ScaleLabel::layout([]);
        $ordinaryCode = ScaleLabel::encode('999999', '0.130', $layout);
        $ordinary = $this->product('78', ['barcode' => $ordinaryCode, 'is_weighted' => false], 'pcs');
        $this->scan($ordinaryCode)->assertOk()->assertJsonPath('data.id', $ordinary->id)
            ->assertJsonMissingPath('data.scale_label');
        $this->scan($code)->assertUnprocessable()->assertJsonPath('message', 'scalePluNotFound');
        $product = $this->product('000077', ['barcode' => $code, 'price_per_kg' => 40000, 'quantity' => 50]);
        $this->getJson('/api/settings/scale')->assertOk()
            ->assertJsonFragment(['scale_product_code' => '000077', 'price_per_kg' => 40000, 'scale_barcode_enabled' => true]);

        $this->scan('1000077001306')->assertUnprocessable()->assertJsonPath('message', 'scaleChecksum');
        $this->scan('100007700130')->assertUnprocessable()->assertJsonPath('message', 'scaleUnsupported');
        $this->scan($code)->assertOk()->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.scale_product_code', '000077')
            ->assertJsonPath('data.scale_label.product_code', '000077')
            ->assertJsonPath('data.scale_label.weight_kg', 0.13);
        $this->postJson('/api/settings/scale/test-barcode', ['code' => $code])->assertOk()
            ->assertJsonPath('data.weight_grams', 130)->assertJsonPath('data.price_per_kg', 40000)
            ->assertJsonPath('data.total', 5200);
        $this->postJson('/api/cash-register/shifts/open', ['opening_balance' => '0.00'])->assertCreated();
        $this->postJson('/api/pos/complete-sale', [
            'payment_method' => 'CARD', 'idempotency_key' => (string) Str::uuid(),
            'items' => [[
                'product_id' => $product->id, 'quantity' => 0.130, 'unit_price' => 1,
                'scale_barcodes' => [$code],
            ]],
        ])->assertOk()->assertJsonPath('data.items.0.total', 5200)
            ->assertJsonPath('data.items.0.scale_barcodes', [$code]);
        $this->assertSame('49.870', $product->fresh()->quantity);
    }

    public function test_preview_price_labels_product_validation_and_csv(): void
    {
        $product = $this->product('PLU001', ['name_ku' => '=test formula']);
        $preview = $this->postJson('/api/settings/scale/preview', ['product_id' => $product->id, 'weight' => '0.525'])
            ->assertOk()->assertJsonPath('data.weight_kg', 0.525)->json('data.code');
        $this->postJson('/api/settings/scale/preview', ['product_id' => $product->id, 'weight' => '0.525', 'code' => $preview])
            ->assertOk()->assertJsonPath('data.plu', '1');
        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(10, $product->fresh()->quantity);

        Setting::set('scale_label_enabled', '1');
        Setting::set('scale_label_value_type', 'price');
        Setting::set('scale_label_value_decimals', '0');
        $priceLabel = ScaleLabel::encode('1', '10500', ScaleLabel::layout([
            'scale_label_value_type' => 'price', 'scale_label_value_decimals' => '0',
        ]));
        $this->scan($priceLabel)->assertOk()->assertJsonPath('data.scale_label.weight_kg', 0.525);
        $product->update(['price_per_kg' => 0]);
        $this->scan($priceLabel)->assertUnprocessable()->assertJsonPath('message', 'scaleMissingPrice');
        $this->postJson('/api/products', [
            'barcode' => 'new', 'name_ku' => 'New product', 'category_id' => $product->category_id,
            'unit_id' => $product->unit_id, 'purchase_price' => 1, 'selling_price' => 2, 'scale_product_code' => '00001',
        ])->assertUnprocessable()->assertJsonValidationErrors('scale_product_code');
        $csv = $this->get('/api/products/scale-csv')->assertOk()->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBFPLU", $csv);
        $this->assertStringContainsString("1,\"'=test formula\"", $csv);
    }

    public function test_label_sales_use_server_prices_keep_the_source_and_restore_kg_and_gram_stock(): void
    {
        Setting::set('scale_label_enabled', '1');
        $this->postJson('/api/cash-register/shifts/open', ['opening_balance' => '0.00'])->assertCreated();
        foreach (['kg' => 10, 'g' => 10000] as $unit => $startingStock) {
            $plu = $unit === 'kg' ? '1' : '2';
            $product = $this->product($plu, ['quantity' => $startingStock], $unit);
            $weight = $this->scan(ScaleLabel::encode($plu, '0.525', ScaleLabel::layout([])))->assertOk()->json('data.scale_label.weight_kg');
            $item = ['product_id' => $product->id, 'quantity' => $weight, 'net_weight' => $weight, 'scale_source' => 'label', 'unit_price' => 1, 'price_per_kg' => 1];
            $this->postJson('/api/pos/complete-sale', [
                'items' => [[...$item, 'quantity' => 11, 'net_weight' => 11]], 'payment_method' => 'CARD', 'idempotency_key' => (string) Str::uuid(),
            ])->assertUnprocessable();
            $sale = $this->postJson('/api/pos/complete-sale', [
                'items' => [$item], 'payment_method' => 'CASH', 'cash_received' => '10500.00', 'idempotency_key' => (string) Str::uuid(),
            ])->assertOk()->assertJsonPath('data.items.0.scale_source', 'label')->json('data.sale');
            $this->assertEquals(10500, (float) $sale['total']);
            $quantity = $unit === 'kg' ? 0.525 : 525;
            $this->assertEquals($startingStock - $quantity, (float) $product->fresh()->quantity);
            $this->postJson('/api/returns/process', [
                'sale_id' => $sale['id'], 'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
                'idempotency_key' => (string) Str::uuid(),
            ])->assertOk();
            $this->assertEquals($startingStock, (float) $product->fresh()->quantity);
        }
    }

    private function scan(string $code): TestResponse
    {
        return $this->getJson('/api/products/barcode/'.$code.'?scale_label=1');
    }

    private function signIn(string $role): void
    {
        $group = Role::firstOrCreate(['name' => $role], ['display_name_ku' => $role, 'display_name_en' => $role, 'display_name_ar' => $role]);
        $this->actingAs(User::create([
            'username' => strtolower($role).'-scale', 'name' => $role, 'password' => 'Only-For-Tests-123',
            'role_id' => $group->id, 'status' => 'ACTIVE',
        ]), 'sanctum');
    }

    private function product(string $plu, array $attributes = [], string $type = 'kg'): Product
    {
        $category = Category::firstOrCreate(['name_en' => 'Scale test'], ['name_ku' => 'Scale test', 'name_ar' => 'Scale test']);
        $unit = Unit::firstOrCreate(['short_code' => $type], ['name_ku' => $type, 'name_en' => $type, 'name_ar' => $type]);

        return Product::create($attributes + [
            'barcode' => Str::uuid()->toString(), 'sku' => Str::uuid()->toString(), 'name_ku' => 'Scale product',
            'scale_product_code' => $plu, 'category_id' => $category->id, 'unit_id' => $unit->id,
            'purchase_price' => 5000, 'selling_price' => 20000, 'quantity' => 10, 'min_stock' => 1,
            'is_weighted' => true, 'price_per_kg' => 20000,
        ]);
    }
}
