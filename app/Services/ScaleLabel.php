<?php

namespace App\Services;

use InvalidArgumentException;

final class ScaleLabel
{
    public const DEFAULTS = [
        'scale_label_enabled' => '0',
        'scale_label_prefixes' => '20',
        'scale_label_plu_length' => '5',
        'scale_label_value_length' => '5',
        'scale_label_value_type' => 'weight',
        'scale_label_value_decimals' => '3',
        'scale_unitless_unit' => 'kg',
        'scale_data_bits' => '8',
        'scale_parity' => 'none',
        'scale_stop_bits' => '1',
    ];

    public static function layout(array $settings): array
    {
        $settings += self::DEFAULTS;

        return [
            'prefixes' => array_map('trim', explode(',', $settings['scale_label_prefixes'])),
            'plu_length' => (int) $settings['scale_label_plu_length'],
            'value_length' => (int) $settings['scale_label_value_length'],
            'type' => $settings['scale_label_value_type'],
            'decimals' => (int) $settings['scale_label_value_decimals'],
        ];
    }

    public static function validLayout(array $layout): bool
    {
        $width = strlen($layout['prefixes'][0] ?? '');
        if ($width < 1 || $width > 3) {
            return false;
        }

        foreach ($layout['prefixes'] as $prefix) {
            if (strlen($prefix) !== $width || ! preg_match('/^[0-9]{1,3}$/D', $prefix)) {
                return false;
            }
        }

        return in_array($layout['plu_length'], [4, 5, 6], true)
            && in_array($layout['value_length'], [4, 5, 6], true)
            && in_array($layout['type'], ['weight', 'price'], true)
            && in_array($layout['decimals'], [0, 1, 2, 3], true)
            && $width + $layout['plu_length'] + $layout['value_length'] === 12
            && count(array_unique($layout['prefixes'])) === count($layout['prefixes']);
    }

    public static function checkDigit(string $body): int
    {
        if (! preg_match('/^[0-9]{12}$/D', $body)) {
            throw new InvalidArgumentException('scaleUnsupported');
        }

        $sum = 0;
        for ($index = 0; $index < 12; $index++) {
            $sum += (int) $body[$index] * ($index % 2 === 0 ? 1 : 3);
        }

        return (10 - $sum % 10) % 10;
    }

    public static function validateBarcode(string $code): void
    {
        if (! preg_match('/^[0-9]{13}$/D', $code)) {
            throw new InvalidArgumentException('scaleUnsupported');
        }
        if (self::checkDigit(substr($code, 0, 12)) !== (int) $code[12]) {
            throw new InvalidArgumentException('scaleChecksum');
        }
    }

    public static function normalizePlu(?string $plu): ?string
    {
        if (! preg_match('/^(?:PLU)?([0-9]+)$/iD', trim($plu ?? ''), $match)) {
            return null;
        }

        return ltrim($match[1], '0') ?: '0';
    }

    public static function parse(string $code, array $layout): array
    {
        self::validateBarcode($code);
        if (! self::validLayout($layout)) {
            throw new InvalidArgumentException('scaleInvalidLayout');
        }

        $width = strlen($layout['prefixes'][0]);
        $prefix = substr($code, 0, $width);
        if (! in_array($prefix, $layout['prefixes'], true)) {
            throw new InvalidArgumentException('scaleUnknownPrefix');
        }

        $plu = self::normalizePlu(substr($code, $width, $layout['plu_length']));
        $encoded = (int) substr($code, $width + $layout['plu_length'], $layout['value_length']);
        if ($encoded === 0) {
            throw new InvalidArgumentException('scaleZeroValue');
        }

        return [
            'prefix' => $prefix, 'plu' => $plu, 'encoded' => $encoded,
            'type' => $layout['type'], 'value' => $encoded / 10 ** $layout['decimals'],
        ];
    }

    public static function encode(string $plu, string $value, array $layout): string
    {
        if (! self::validLayout($layout)) {
            throw new InvalidArgumentException('scaleInvalidLayout');
        }

        $plu = self::normalizePlu($plu);
        $encoded = self::scaled($value, $layout['decimals']);
        if ($plu === null || strlen($plu) > $layout['plu_length'] || ! $encoded || strlen((string) $encoded) > $layout['value_length']) {
            throw new InvalidArgumentException('scaleValueDoesNotFit');
        }

        $body = $layout['prefixes'][0].str_pad($plu, $layout['plu_length'], '0', STR_PAD_LEFT)
            .str_pad((string) $encoded, $layout['value_length'], '0', STR_PAD_LEFT);

        return $body.self::checkDigit($body);
    }

    public static function solve(array $samples): array
    {
        foreach ($samples as $sample) {
            self::validateBarcode($sample['code']);
        }
        if ($samples === []) {
            return [];
        }

        $matches = [];
        for ($width = 1; $width <= 3; $width++) {
            for ($pluLength = 4; $pluLength <= 6; $pluLength++) {
                $valueLength = 12 - $width - $pluLength;
                if ($valueLength < 4 || $valueLength > 6) {
                    continue;
                }

                $prefixes = array_values(array_unique(array_map(fn (array $sample) => substr($sample['code'], 0, $width), $samples)));
                foreach (['weight', 'price'] as $type) {
                    for ($decimals = 0; $decimals <= 3; $decimals++) {
                        $layout = ['prefixes' => $prefixes, 'plu_length' => $pluLength, 'value_length' => $valueLength, 'type' => $type, 'decimals' => $decimals];
                        foreach ($samples as $sample) {
                            try {
                                $parsed = self::parse($sample['code'], $layout);
                            } catch (InvalidArgumentException) {
                                continue 2;
                            }
                            if ($parsed['plu'] !== self::normalizePlu($sample['plu'])
                                || $parsed['encoded'] !== self::scaled($sample[$type] ?? '', $decimals)) {
                                continue 2;
                            }
                        }
                        $matches[] = $layout;
                    }
                }
            }
        }

        return $matches;
    }

    private static function scaled(string $value, int $decimals): ?int
    {
        if (! preg_match('/^([0-9]{1,9})(?:[.,]([0-9]{1,6}))?$/D', trim($value), $match)) {
            return null;
        }

        $fraction = $match[2] ?? '';
        if (trim(substr($fraction, $decimals), '0') !== '') {
            return null;
        }

        return (int) ($match[1].str_pad(substr($fraction, 0, $decimals), $decimals, '0'));
    }
}
