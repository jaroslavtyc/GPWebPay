<?php declare(strict_types=1);

namespace Granam\GpWebPay\Codes;

use Granam\Strict\Object\StrictObject;

class ResponseDigestKeys extends StrictObject implements Codes
{
    const OPERATION = RequestDigestKeys::OPERATION;
    const ORDERNUMBER = RequestDigestKeys::ORDERNUMBER;
    const MERORDERNUM = RequestDigestKeys::MERORDERNUM;
    const MD = RequestDigestKeys::MD;
    const PRCODE = 'PRCODE';
    const SRCODE = 'SRCODE';
    const RESULTTEXT = 'RESULTTEXT';
    const USERPARAM1 = 'USERPARAM1';
    const ADDINFO = 'ADDINFO';

    /**
     * @return array|string[]
     */
    public static function getResponseDigestKeys()
    {
        return [
            self::OPERATION,
            self::ORDERNUMBER,
            self::MERORDERNUM,
            self::MD,
            self::PRCODE,
            self::SRCODE,
            self::RESULTTEXT,
            self::USERPARAM1,
            self::ADDINFO,
        ];
    }
}
