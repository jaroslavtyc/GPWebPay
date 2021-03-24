<?php

namespace Granam\Tests\GpWebPay;

use Granam\GpWebPay\PayRequest;
use Granam\TestWithMockery\TestWithMockery;

abstract class PayRequestTest extends TestWithMockery
{
    /**
     * @test
     */
    public function I_can_use_it_as_pay_request()
    {
        $sutClass = self::getSutClass();
        self::assertTrue(is_a($sutClass, PayRequest::class, true), $sutClass . ' should implement ' . PayRequest::class);
    }
}
