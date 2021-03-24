<?php

namespace Granam\Tests\GpWebPay\Codes;

use Granam\GpWebPay\Codes\PayMethodCodes;

class PayMethodCodesTest extends CodesTest
{
    /**
     * @test
     */
    public function I_can_get_all_pay_method_codes_at_once()
    {
        $reflectionClass = new \ReflectionClass(PayMethodCodes::class);
        $constantValues = array_values($reflectionClass->getConstants());
        ksort($constantValues);
        $payMethodCodes = PayMethodCodes::getPayMethodCodes();
        ksort($payMethodCodes);
        self::assertSame($constantValues, $payMethodCodes);
    }

    /**
     * @test
     * @param string $code
     * @param bool $expectedResult
     * @dataProvider provideCodeAndIfIsPayMethodCode
     */
    public function I_can_ask_if_a_code_is_pay_method_code(string $code, bool $expectedResult)
    {
        self::assertSame($expectedResult, PayMethodCodes::isSupportedPaymentMethod($code));
    }

    public function provideCodeAndIfIsPayMethodCode()
    {
        $values = [];
        foreach (PayMethodCodes::getPayMethodCodes() as $payMethodCode) {
            $values[] = [$payMethodCode, true];
        }
        $values[] = ['cash', false];

        return $values;
    }
}
