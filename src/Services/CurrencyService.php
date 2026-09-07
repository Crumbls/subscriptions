<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Services;

use NumberFormatter;
use ResourceBundle;

class CurrencyService
{
    public function getCurrencies(): array
    {
        return once(function () {
            $bundle = ResourceBundle::create('en', 'ICUDATA-curr');
            $currencies = $bundle['Currencies'];
            $activeCodes = $this->resolveActiveIsoCodes();

            $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

            $active = [];

            foreach ($currencies as $code => $data) {
                if (! preg_match('/^[A-Z]{3}$/', $code)) {
                    continue;
                }

                if ($code[0] === 'X') {
                    continue;
                }

                if ($activeCodes !== [] && ! isset($activeCodes[$code])) {
                    continue;
                }

                if ($formatter->formatCurrency(1, $code) === false) {
                    continue;
                }

                $symbol = $data[0];
                $name = $data[1];

                $active[$code] = [
                    'code' => $code,
                    'symbol' => $symbol,
                    'name' => $name,
                    'label' => "{$name} ({$symbol})",
                ];
            }

            return $active;
        });
    }

    private function resolveActiveIsoCodes(): array
    {
        $supplemental = ResourceBundle::create('supplementalData', 'ICUDATA', false);

        if ($supplemental === null) {
            return [];
        }

        $mapping = $supplemental->get('codeMappingsCurrency');

        if ($mapping === null) {
            return [];
        }

        $codes = [];

        foreach ($mapping as $entry) {
            $code = $entry->get(0);

            if (is_string($code)) {
                $codes[$code] = true;
            }
        }

        return $codes;
    }

    public function exists(string $code): bool
    {
        return isset($this->getCurrencies()[strtoupper($code)]);
    }

    public function symbol(string $code): string
    {
        $code = strtoupper($code);

        return $this->getCurrencies()[$code]['symbol'] ?? $code;
    }

    public function name(string $code): string
    {
        $code = strtoupper($code);

        return $this->getCurrencies()[$code]['name'] ?? $code;
    }

    public function decimals(string $code): int
    {
        static $cache = [];

        $code = strtoupper($code);

        return $cache[$code] ??= $this->resolveDecimals($code);
    }

    public function factor(string $code): int
    {
        return 10 ** $this->decimals($code);
    }

    public function step(string $code): float|int
    {
        $decimals = $this->decimals($code);

        return $decimals === 0 ? 1 : 1 / (10 ** $decimals);
    }

    public function toMinor(int|float|string|null $amount, string $code): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        $amount = is_string($amount) ? str_replace(',', '.', $amount) : $amount;

        return (int) round(((float) $amount) * $this->factor($code));
    }

    public function fromMinor(int|float|string|null $minor, string $code): string
    {
        $minor = (int) ($minor ?? 0);
        $decimals = $this->decimals($code);

        if ($decimals === 0) {
            return (string) $minor;
        }

        return number_format($minor / $this->factor($code), $decimals, '.', '');
    }

    public function format(int|float|string|null $minor, string $code, ?string $locale = null): string
    {
        $formatter = new NumberFormatter($locale ?? 'en_US', NumberFormatter::CURRENCY);

        return $formatter->formatCurrency((int) ($minor ?? 0) / $this->factor($code), strtoupper($code));
    }

    private function resolveDecimals(string $code): int
    {
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, $code);

        $digits = $formatter->getAttribute(NumberFormatter::FRACTION_DIGITS);

        return $digits === false ? 2 : max(0, (int) $digits);
    }
}
