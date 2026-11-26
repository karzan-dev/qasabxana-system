<?php

namespace Tests\Unit;

use App\Services\ScaleLabel;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ScaleLabelTest extends TestCase
{
    public function test_default_label_and_decimal_boundaries(): void
    {
        $layout = ScaleLabel::layout([]);
        $code = ScaleLabel::encode('PLU001', '0.525', $layout);
        $this->assertSame('100000100525'.ScaleLabel::checkDigit('100000100525'), $code);
        $this->assertSame(6, ScaleLabel::checkDigit('100000100525'));
        $this->assertSame('1', ScaleLabel::parse($code, $layout)['plu']);
        foreach (['0.005', '0.525', '99.999'] as $weight) {
            $this->assertSame((float) $weight, ScaleLabel::parse(ScaleLabel::encode('00001', $weight, $layout), $layout)['value']);
        }
        $price = ScaleLabel::layout(['scale_label_value_type' => 'price', 'scale_label_value_decimals' => '0']);
        $this->assertEquals(99999, ScaleLabel::parse(ScaleLabel::encode('001', '99999', $price), $price)['value']);
    }

    public function test_rongta_six_digit_plu_label(): void
    {
        $code = '1000077001305';
        $layout = ScaleLabel::layoutFor($code, []);
        $this->assertSame($code, ScaleLabel::encode('000077', '0.130', $layout));
        $label = ScaleLabel::parse($code, $layout);
        $this->assertSame('1', $label['prefix']);
        $this->assertSame('000077', $label['product_code']);
        $this->assertSame('000077', $label['normalized_plu']);
        $this->assertSame(130, $label['encoded']);
        $this->assertSame(0.13, $label['value']);
        $this->assertSame('5', $label['checksum']);
    }

    #[DataProvider('invalidLabels')]
    public function test_rejects_invalid_labels(string $body, string $error, bool $validDigit): void
    {
        $code = strlen($body) === 12
            ? $body.(($validDigit ? 0 : 1) + ScaleLabel::checkDigit($body)) % 10
            : $body;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($error);
        ScaleLabel::parse($code, ScaleLabel::layout([]));
    }

    public static function invalidLabels(): array
    {
        return [
            ['2005', 'scaleUnsupported', false],
            ['Code128-not-digits', 'scaleUnsupported', false],
            ['200000100525', 'scaleChecksum', false],
            ['210000100525', 'scaleUnknownPrefix', true],
            ['100000100000', 'scaleZeroValue', true],
        ];
    }

    public function test_solver_keeps_all_candidates_and_uses_the_second_nonround_label(): void
    {
        $layout = ScaleLabel::layout(['scale_label_prefixes' => '27', 'scale_label_plu_length' => '5']);
        $first = ['code' => ScaleLabel::encode('1', '0.527', $layout), 'plu' => 'PLU001', 'weight' => '0.527'];
        $second = ['code' => ScaleLabel::encode('12345', '1.237', $layout), 'plu' => '12345', 'weight' => '1.237'];
        $this->assertGreaterThan(1, count(ScaleLabel::solve([$first])));
        $this->assertSame([$layout], ScaleLabel::solve([$first, $second]));
        $this->assertSame([], ScaleLabel::solve([$first, [...$second, 'weight' => '1.238']]));

        // A zero at the prefix boundary can leave two legitimate layouts indistinguishable.
        $default = ScaleLabel::layout(['scale_label_prefixes' => '20', 'scale_label_plu_length' => '5']);
        $ambiguous = [
            [...$first, 'code' => ScaleLabel::encode('1', '0.527', $default)],
            [...$second, 'code' => ScaleLabel::encode('12345', '1.237', $default)],
        ];
        $this->assertGreaterThan(1, count(ScaleLabel::solve($ambiguous)));
    }

    public function test_additional_price_layout_and_overlapping_prefixes(): void
    {
        $format = [
            'prefix' => '27', 'barcodeLength' => 13, 'productCodeStart' => 7,
            'productCodeLength' => 5, 'weightStart' => 2, 'weightLength' => 5,
            'priceDivisor' => 100, 'pluLength' => 6, 'validateChecksum' => true, 'barcodeType' => 'price',
        ];
        $body = '271234500009';
        $code = $body.ScaleLabel::checkDigit($body);
        $layout = ScaleLabel::layoutFor($code, ['scale_label_formats' => json_encode([$format])]);
        $label = ScaleLabel::parse($code, $layout);
        $this->assertSame('000009', $label['normalized_plu']);
        $this->assertSame(123.45, $label['value']);
        $this->assertSame('price', $label['type']);

        $this->expectExceptionMessage('scaleInvalidLayout');
        ScaleLabel::layouts(['scale_label_formats' => json_encode([$format, [...$format, 'prefix' => '2']])]);
    }
}
