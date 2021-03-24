<?php declare(strict_types=1);

namespace Granam\GpWebPay\Codes;

use Alcohol\ISO4217;
use Granam\GpWebPay\Exceptions\UnknownCurrency;
use Granam\Strict\Object\StrictObject;

/**
 * Currency codes are in (number) format ISO 4217:2001, see GP_webpay_HTTP_EN.pdf / GP_webpay_HTTP.pdf
 */
class CurrencyCodes extends StrictObject implements Codes
{
    /**
     * @var ISO4217
     */
    private $iso4217;

    /**
     * @param ISO4217 $iso4217
     */
    public function __construct(ISO4217 $iso4217)
    {
        $this->iso4217 = $iso4217;
    }

    /**
     * @param int $numericCode
     * @return bool
     */
    public function isCurrencyNumericCode(int $numericCode): bool
    {
        $formattedCode = $this->formatNumericCode($numericCode);
        foreach ($this->iso4217->getAll() as $currency) {
            if ($currency['numeric'] === $formattedCode) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $numericCode
     * @return string
     */
    private function formatNumericCode(int $numericCode): string
    {
        return \sprintf("%'03d", $numericCode);
    }

    /**
     * @param int $numericCode
     * @return int
     * @throws \Granam\GpWebPay\Exceptions\UnknownCurrency
     */
    public function getCurrencyPrecision(int $numericCode): int
    {
        $formattedCode = $this->formatNumericCode($numericCode);
        foreach ($this->iso4217->getAll() as $currency) {
            if ($currency['numeric'] === $formattedCode) {
                return (int)$currency['exp'];
            }
        }

        throw new UnknownCurrency("Given currency of numeric code {$numericCode} is not known");
    }

    /**
     * @param string $stringCurrencyCode
     * @return int
     * @throws \Granam\GpWebPay\Exceptions\UnknownCurrency
     */
    public function getCurrencyNumericCode(string $stringCurrencyCode): int
    {
        $unifiedCurrencyCode = strtoupper(trim($stringCurrencyCode));
        foreach ($this->iso4217->getAll() as $currency) {
            if ($currency['alpha3'] === $unifiedCurrencyCode) {
                return (int)$currency['numeric'];
            }
        }
        throw new UnknownCurrency("Given currency code {$stringCurrencyCode} does not match any known currency");
    }
}
