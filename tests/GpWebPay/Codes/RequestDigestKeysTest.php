<?php

namespace Granam\Tests\GpWebPay\Codes;

use Granam\GpWebPay\Codes\RequestDigestKeys;

class RequestDigestKeysTest extends CodesTest
{
    /**
     * @test
     */
    public function I_can_get_list_of_all_digest_keys()
    {
        $reflectionClass = new \ReflectionClass(RequestDigestKeys::class);
        $constantValues = array_values($reflectionClass->getConstants());
        sort($constantValues);
        $digestKeys = RequestDigestKeys::getDigestKeys();
        sort($digestKeys);
        self::assertSame($constantValues, $digestKeys);
    }
}
