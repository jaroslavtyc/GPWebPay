<?php

namespace Granam\Tests\GpWebPay;

use Granam\GpWebPay\PayResponse;
use Granam\TestWithMockery\TestWithMockery;

abstract class PayResponseTest extends TestWithMockery
{
    /**
     * @test
     */
    public function I_can_use_it_as_pay_response()
    {
        $sutClass = self::getSutClass();
        self::assertTrue(
            is_a($sutClass, PayResponse::class, true),
            $sutClass . ' should implement ' . PayResponse::class
        );
    }
}
