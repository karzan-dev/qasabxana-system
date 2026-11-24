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
        $this->assertSame('200000100525'.ScaleLabel::checkDigit('200000100525'), $code);
        $this->assertSame(5, ScaleLabel::checkDigit('200000100525'));
        $this->assertSame('1', ScaleLabel::parse($code, $layout)['plu']);
        foreach (['0.005', '0.525', '99.999'] as $weight) {
            $this->assertSame((float) $weight, ScaleLabel::parse(ScaleLabel::encode('00001', $weight, $layout), $layout)['value']);
        }
        $price = ScaleLabel::layout(['scale_label_value_type' => 'price', 'scale_label_value_decimals' => '0']);
        $this->assertEquals(99999, ScaleLabel::parse(ScaleLabel::encode('001', '99999', $price), $price)['value']);
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
            ['200000100000', 'scaleZeroValue', true],
        ];
    }

    public function test_solver_keeps_all_candidates_and_uses_the_second_nonround_label(): void
    {
        $layout = ScaleLabel::layout(['scale_label_prefixes' => '27']);
        $first = ['code' => ScaleLabel::encode('1', '0.527', $layout), 'plu' => 'PLU001', 'weight' => '0.527'];
        $second = ['code' => ScaleLabel::encode('12345', '1.237', $layout), 'plu' => '12345', 'weight' => '1.237'];
        $this->assertGreaterThan(1, count(ScaleLabel::solve([$first])));
        $this->assertSame([$layout], ScaleLabel::solve([$first, $second]));
        $this->assertSame([], ScaleLabel::solve([$first, [...$second, 'weight' => '1.238']]));

        // A zero at the prefix boundary can leave two legitimate layouts indistinguishable.
        $default = ScaleLabel::layout([]);
        $ambiguous = [
            [...$first, 'code' => ScaleLabel::encode('1', '0.527', $default)],
            [...$second, 'code' => ScaleLabel::encode('12345', '1.237', $default)],
        ];
        $this->assertGreaterThan(1, count(ScaleLabel::solve($ambiguous)));
    }
}
