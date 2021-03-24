<?php declare(strict_types=1);

namespace Granam\GpWebPay\Codes;

class ResponsePayloadKeys extends ResponseDigestKeys
{
    const DIGEST = RequestPayloadKeys::DIGEST;
    const DIGEST1 = 'DIGEST1';

    /**
     * @return array|\string[]
     */
    public static function getResponsePayloadKeys()
    {
        $keys = parent::getResponseDigestKeys();
        $keys[] = self::DIGEST;
        $keys[] = self::DIGEST1;

        return $keys;
    }
}
