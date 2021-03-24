<?php declare(strict_types=1);

namespace Granam\GpWebPay\Codes;

class RequestPayloadKeys extends RequestDigestKeys
{
    const DIGEST = 'DIGEST';
    const LANG = 'LANG';

    /**
     * @return array|\string[]
     */
    public static function getRequestPayloadKeys()
    {
        $keys = parent::getDigestKeys();
        $keys[] = self::DIGEST;
        $keys[] = self::LANG;

        return $keys;
    }
}
