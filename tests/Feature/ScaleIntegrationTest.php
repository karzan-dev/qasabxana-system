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
        $this->assertSame('0', Setting::get('scale_label_enabled'));
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
        $layout = ScaleLabel::layout(['scale_label_prefixes' => '27']);
        $samples = [
            ['code' => ScaleLabel::encode('1', '0.527', $layout), 'plu' => '1', 'weight' => '0.527'],
            ['code' => ScaleLabel::encode('12345', '1.237', $layout), 'plu' => '12345', 'weight' => '1.237'],
        ];
        $this->postJson('/api/settings/scale/learn', ['samples' => [$samples[0]], 'confirm' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('samples');
        $this->postJson('/api/settings/scale/learn', ['samples' => $samples])
            ->assertOk()->assertJsonCount(1, 'data.layouts')->assertJsonPath('data.can_save', true);
        $this->assertSame('20', Setting::get('scale_label_prefixes'));
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

    public function test_pos_label_exact_barcode_priority_and_ambiguous_or_inactive_codes(): void
    {
        $layout = ScaleLabel::layout([]);
        $label = ScaleLabel::encode('00001', '0.525', $layout);
        $weighted = $this->product('PLU001');
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
        $exact = $this->product('PLU2', ['barcode' => $label, 'is_weighted' => false], 'pcs');
        $this->scan($label)->assertOk()->assertJsonPath('data.id', $exact->id)->assertJsonMissingPath('data.scale_label');
        $this->scan(ScaleLabel::encode('2', '0.525', $layout))->assertUnprocessable()->assertJsonPath('message', 'scaleNotWeighted');
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

    private function scan(string $code): \Illuminate\Testing\TestResponse
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
