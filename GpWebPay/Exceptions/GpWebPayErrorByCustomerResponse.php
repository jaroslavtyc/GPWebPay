<?php declare(strict_types=1);

namespace Granam\GpWebPay\Exceptions;

use Granam\GpWebPay\Codes\PrCodes;
use Granam\GpWebPay\Codes\SrCodes;

/**
 * Tag class marking an exception caused by invalid action or provided data by customer itself.
 * Localized message of this should be shown directly to the customer.
 */
class GpWebPayErrorByCustomerResponse extends GpWebPayErrorResponse
{
    /**
     * @param int $prCode
     * @param int $srCode
     * @param string|null $resultText
     * @param null $exceptionCode
     * @param \Exception|null $previousException
     */
    public function __construct(
        int $prCode,
        int $srCode,
        string $resultText = null,
        $exceptionCode = null, // intentionally without scalar type hint
        \Exception $previousException = null
    )
    {
        if (!static::isErrorCausedByCustomer($prCode, $srCode)) {
            \trigger_error("PR code {$prCode} and SR code {$srCode} do not mean a customer error", E_USER_WARNING);
        }
        parent::__construct($prCode, $srCode, $resultText, $exceptionCode, $previousException);
    }

    /**
     * @param int $prCode
     * @param int $srCode
     * @return bool
     */
    public static function isErrorCausedByCustomer(int $prCode, int $srCode): bool
    {
        return PrCodes::isErrorForCustomer($prCode) && SrCodes::isErrorForCustomer($srCode);
    }
}
