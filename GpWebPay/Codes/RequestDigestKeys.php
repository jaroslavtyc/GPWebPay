<?php declare(strict_types=1);

namespace Granam\GpWebPay\Codes;

use Granam\Strict\Object\StrictObject;

class RequestDigestKeys extends StrictObject implements Codes
{
    // required
    const MERCHANTNUMBER = 'MERCHANTNUMBER';
    const OPERATION = 'OPERATION';
    const ORDERNUMBER = 'ORDERNUMBER';
    const AMOUNT = 'AMOUNT'; // integer price of an order in the lowest currency units (like cents)
    const CURRENCY = 'CURRENCY'; // numeric expression of a currency according to ISO 4217
    const DEPOSITFLAG = 'DEPOSITFLAG'; // if a customer card should be charged immediately
    // optional
    const MERORDERNUM = 'MERORDERNUM';
    const URL = 'URL';
    const DESCRIPTION = 'DESCRIPTION';
    const MD = 'MD';
    const PAYMETHOD = 'PAYMETHOD';
    const DISABLEPAYMETHOD = 'DISABLEPAYMETHOD';
    const PAYMETHODS = 'PAYMETHODS';
    const EMAIL = 'EMAIL';
    const REFERENCENUMBER = 'REFERENCENUMBER';
    const ADDINFO = 'ADDINFO';
    const USERPARAM1 = 'USERPARAM1';
    const FASTPAYID = 'FASTPAYID';

    /**
     * @return array|string[]
     */
    public static function getDigestKeys()
    {
        return [
            self::MERCHANTNUMBER,
            self::OPERATION,
            self::ORDERNUMBER,
            self::AMOUNT,
            self::CURRENCY,
            self::DEPOSITFLAG,
            self::MERORDERNUM,
            self::URL,
            self::DESCRIPTION,
            self::MD,
            self::PAYMETHOD,
            self::DISABLEPAYMETHOD,
            self::PAYMETHODS,
            self::EMAIL,
            self::REFERENCENUMBER,
            self::ADDINFO,
            self::USERPARAM1,
            self::FASTPAYID,
        ];
    }
}
