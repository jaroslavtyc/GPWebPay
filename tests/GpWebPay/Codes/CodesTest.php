<?php

namespace Granam\Tests\GpWebPay\Codes;

use Granam\GpWebPay\Codes\Codes;
use Granam\TestWithMockery\TestWithMockery;

abstract class CodesTest extends TestWithMockery
{
    /**
     * @test
     */
    public function I_can_use_it_as_codes()
    {
        $sutClass = self::getSutClass();
        self::assertTrue(is_a($sutClass, Codes::class, true), $sutClass . ' should implement interface ' . Codes::class);
    }
}
