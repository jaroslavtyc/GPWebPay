<?php declare(strict_types=1);

namespace Granam\GpWebPay\Codes;

use Granam\Strict\Object\StrictObject;

class PayMethodCodes extends StrictObject implements Codes
{
    const CRD = 'CRD'; // payment card
    const MCM = 'MCM'; // MasterCard Mobile
    const MPS = 'MPS'; // MasterPass
    const BTNCS = 'BTNCS'; // PLATBA 24 Česká spořitelna

    /**
     * @return array|string[]
     */
    public static function getPayMethodCodes(): array
    {
        return [
            self::CRD,
            self::MCM,
            self::MPS,
            self::BTNCS,
        ];
    }

    /**
     * @param string $payMethod
     * @return bool
     */
    public static function isSupportedPaymentMethod(string $payMethod): bool
    {
        return \in_array($payMethod, self::getPayMethodCodes(), true);
    }
}
