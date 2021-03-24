<?php

namespace Granam\Tests\GpWebPay;

use Granam\GpWebPay\CardPayRequestValues;
use Granam\GpWebPay\Codes\CurrencyCodes;
use Granam\GpWebPay\Codes\PayMethodCodes;
use Granam\GpWebPay\Codes\RequestDigestKeys;
use Granam\GpWebPay\Codes\RequestPayloadKeys;
use Granam\GpWebPay\Exceptions\UnsupportedPayMethod;
use Granam\GpWebPay\Exceptions\ValueTooLong;
use Granam\String\StringTools;
use Granam\TestWithMockery\TestWithMockery;

class CardPayRequestValuesTest extends TestWithMockery
{
    /**
     * @test
     * @dataProvider provideParameters
     * @param int $orderNumber
     * @param float $price
     * @param int $currencyNumericCode
     * @param bool $depositFlag
     * @param int|null $merchantOrderIdentification
     * @param string|null $description
     * @param string|null $merchantNote
     * @param string|null $fastPayId
     * @param string|null $payMethod
     * @param string|null $disabledPayMethod
     * @param array|null $payMethods
     * @param string|null $cardHolderEmail
     * @param string|null $referenceNumber
     * @param string|null $addInfo
     * @param string|null $lang
     */
    public function I_can_create_it_both_directly_and_from_array(
        int $orderNumber,
        float $price,
        int $currencyNumericCode,
        bool $depositFlag,
        int $merchantOrderIdentification = null,
        string $merchantNote = null,
        string $description = null,
        string $fastPayId = null,
        string $payMethod = null,
        string $disabledPayMethod = null,
        array $payMethods = null,
        string $cardHolderEmail = null,
        string $referenceNumber = null,
        string $addInfo = null,
        string $lang = null
    )
    {
        $currencyCodes = $this->createCurrencyCodes($currencyNumericCode, $currencyPrecision = 2);
        $arrayParameters = [
            RequestDigestKeys::ORDERNUMBER => $orderNumber,
            RequestDigestKeys::AMOUNT => $price,
            RequestDigestKeys::CURRENCY => $currencyNumericCode,
            RequestDigestKeys::DEPOSITFLAG => $depositFlag,
            // optional
            lcfirst(RequestDigestKeys::MERORDERNUM) => $merchantOrderIdentification,
            RequestDigestKeys::DESCRIPTION => $description,
            strtolower(RequestDigestKeys::MD) => $merchantNote,
            RequestDigestKeys::FASTPAYID => $fastPayId,
            RequestDigestKeys::PAYMETHOD => $payMethod,
            RequestDigestKeys::DISABLEPAYMETHOD => $disabledPayMethod,
            RequestDigestKeys::PAYMETHODS => $payMethods,
            RequestDigestKeys::EMAIL => $cardHolderEmail,
            RequestDigestKeys::REFERENCENUMBER => $referenceNumber,
            RequestDigestKeys::ADDINFO => $addInfo,
            RequestPayloadKeys::LANG => $lang,
        ];
        $arrayParameters = array_filter($arrayParameters, static function ($parameter) {
            return $parameter !== null || random_int(0, 1) > 1; // to cover all combinations
        });
        $fromArrayCardPayRequestValues = CardPayRequestValues::createFromArray($arrayParameters, $currencyCodes);

        $arrayParametersWithPrice = $arrayParameters;
        $arrayParametersWithPrice['price'] = $price; // alternative, more human-friendly name for this parameter
        unset($arrayParametersWithPrice[RequestDigestKeys::AMOUNT]);
        $fromArrayWithPriceCardPayRequestValues = CardPayRequestValues::createFromArray($arrayParametersWithPrice, $currencyCodes);
        self::assertEquals($fromArrayCardPayRequestValues, $fromArrayWithPriceCardPayRequestValues);

        $newCardPayRequestValues = new CardPayRequestValues(
            $currencyCodes,
            $orderNumber,
            $price,
            $currencyNumericCode,
            $depositFlag,
            $merchantOrderIdentification,
            $description,
            $merchantNote,
            $fastPayId,
            $payMethod,
            $disabledPayMethod,
            $payMethods,
            $cardHolderEmail,
            $referenceNumber,
            $addInfo,
            $lang
        );
        self::assertEquals($fromArrayCardPayRequestValues, $newCardPayRequestValues);

        $expectedValues = [];
        foreach ($arrayParameters as $key => $parameter) {
            $expectedValues[strtoupper($key)] = $parameter;
        }
        $expectedValues[CardPayRequestValues::PRICE_INDEX] = $expectedValues[RequestDigestKeys::AMOUNT];
        $reflection = new \ReflectionClass(CardPayRequestValues::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC ^ \ReflectionMethod::IS_STATIC);
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (strpos($methodName, 'get') !== 0) {
                continue; // we are interested in getters only
            }
            if ($methodName === 'getAmount') {
                self::assertSame((int)round($price * 100), $newCardPayRequestValues->getAmount());
                continue; // amount is calculated by the class
            }
            $propertyName = strtoupper(preg_replace('~^get~', '', $methodName));
            $expectedValue = $expectedValues[$propertyName] ?? null;
            if ($expectedValue !== null) {
                self::assertGreaterThan(
                    0,
                    preg_match('~@return\s+(?:null\|)?(?<returnType>\w+)(\|?:null)?~', $method->getDocComment(), $matches)
                );
                switch ($matches['returnType']) {
                    case 'int' :
                        $expectedValue = (int)$expectedValue;
                        break;
                    case 'string' :
                        $expectedValue = is_array($expectedValue) ? implode(',', $expectedValue) : $expectedValue;
                        break;
                    default :
                }
            }
            self::assertSame(
                $expectedValue,
                $fromArrayCardPayRequestValues->$methodName(),
                "Getter {$methodName} returns unexpected value"
            );
        }
    }

    /**
     * @param string|null $expectedCurrencyCode
     * @param int|null $currencyPrecision
     * @param bool $isValidCurrencyCode = true
     * @return \Mockery\MockInterface|CurrencyCodes
     */
    private function createCurrencyCodes(
        string $expectedCurrencyCode = null,
        int $currencyPrecision = null,
        bool $isValidCurrencyCode = true
    )
    {
        $currencyCodes = $this->mockery(CurrencyCodes::class);
        $currencyCodes->shouldReceive('getCurrencyPrecision')
            ->with($expectedCurrencyCode)
            ->andReturn($currencyPrecision);
        $currencyCodes->shouldReceive('isCurrencyNumericCode')
            ->with($expectedCurrencyCode)
            ->andReturn($isValidCurrencyCode);
        $currencyCodes->shouldReceive('isCurrencyNumericCode')
            ->andReturn(!$isValidCurrencyCode);

        return $currencyCodes;
    }

    public function provideParameters()
    {
        $orderNumber = 123;
        $price = 456.789;
        $currencyNumericCode = 978; // EUR
        $depositFlag = true;
        $merchantOrderIdentification = 135;
        $description = 'Bought happiness';
        $merchantNote = 'Just a note';
        $fastPayId = '1470';
        $payMethod = PayMethodCodes::CRD;
        $disabledPayMethod = PayMethodCodes::BTNCS;
        $payMethods = PayMethodCodes::getPayMethodCodes();
        $cardHolderEmail = 'someone@example.com';
        $referenceNumber = '246';
        $addInfo = '<?xml ?>';
        $lang = 'cs';

        return [
            'all values' => [
                $orderNumber,
                $price,
                $currencyNumericCode,
                $depositFlag,
                $merchantOrderIdentification,
                $description,
                $merchantNote,
                $fastPayId,
                $payMethod,
                $disabledPayMethod,
                $payMethods,
                $cardHolderEmail,
                $referenceNumber,
                $addInfo,
                $lang,
            ],
            'only required' => [
                $orderNumber,
                $price,
                $currencyNumericCode,
                $depositFlag,
            ],
        ];
    }

    /**
     * @test
     */
    public function I_can_not_create_it_from_array_without_all_required_parameters()
    {
        $this->expectException(\Granam\GpWebPay\Exceptions\InvalidRequest::class);
        $this->expectExceptionMessageMatches('~DEPOSITFLAG~');
        CardPayRequestValues::createFromArray(
            [
                RequestDigestKeys::ORDERNUMBER => 123,
                RequestDigestKeys::AMOUNT => 456,
                RequestDigestKeys::CURRENCY => 789,
                // missing deposit flag
            ],
            $this->createCurrencyCodes()
        );
    }

    /**
     * @test
     */
    public function I_can_not_create_it_from_array_with_invalid_list_of_payment_methods()
    {
        $this->expectException(\Granam\GpWebPay\Exceptions\InvalidRequest::class);
        $this->expectExceptionMessageMatches('~PAYMETHODS.+string~');
        CardPayRequestValues::createFromArray(
            [
                RequestDigestKeys::ORDERNUMBER => 123,
                RequestDigestKeys::AMOUNT => 456,
                RequestDigestKeys::CURRENCY => 789,
                RequestDigestKeys::DEPOSITFLAG => true,
                RequestDigestKeys::PAYMETHODS => PayMethodCodes::CRD // should be array - cause exception
            ],
            $this->createCurrencyCodes()
        );
    }

    /**
     * @test
     * @dataProvider provideTooLongParameters
     * @param int $orderNumber
     * @param float $price
     * @param int $currencyNumericCode
     * @param int $currencyPrecision
     * @param bool $depositFlag
     * @param int|null $merchantOrderIdentification
     * @param string|null $description
     * @param string|null $merchantNote
     * @param string|null $fastPayId
     * @param string|null $payMethod
     * @param string|null $disabledPayMethod
     * @param array|null $payMethods
     * @param string|null $cardHolderEmail
     * @param string|null $referenceNumber
     * @param string|null $addInfo
     * @param string|null $lang
     */
    public function I_can_not_create_it_with_too_long_parameters(
        int $orderNumber,
        float $price,
        int $currencyNumericCode,
        int $currencyPrecision,
        bool $depositFlag,
        int $merchantOrderIdentification = null,
        string $merchantNote = null,
        string $description = null,
        string $fastPayId = null,
        string $payMethod = null,
        string $disabledPayMethod = null,
        array $payMethods = null,
        string $cardHolderEmail = null,
        string $referenceNumber = null,
        string $addInfo = null,
        string $lang = null
    )
    {
        $parameters = func_get_args();
        unset($parameters[3]); // removing currency precision
        $parameters = array_values($parameters); // re-indexing
        $parametersCount = count($parameters);
        foreach ($parameters as $index => $tooLongParameter) {
            if ($tooLongParameter === null || in_array($index, [2 /* currency */, 3 /* deposit */], true)) {
                continue;
            }
            $roulette = array_fill(0, $parametersCount, null);
            $roulette[0] = 123; // order number
            $roulette[1] = 456.789; // price
            $roulette[2] = 978; // EUR
            $roulette[3] = true; // deposit flag
            $roulette[$index] = $tooLongParameter; // can overload even one of just set required parameter
            try {
                new CardPayRequestValues(
                    $this->createCurrencyCodes($roulette[2], $currencyPrecision),
                    $roulette[0],
                    $roulette[1],
                    $roulette[2],
                    $roulette[3],
                    $roulette[4],
                    $roulette[5],
                    $roulette[6],
                    $roulette[7],
                    $roulette[8],
                    $roulette[9],
                    $roulette[10],
                    $roulette[11],
                    $roulette[12],
                    $roulette[13],
                    $roulette[14]
                );
                $valueLength = strlen($roulette[$index]);
                $parametersReflections = (new \ReflectionClass(CardPayRequestValues::class))
                    ->getMethod('__construct')->getParameters();
                array_shift($parametersReflections); // removes CurrencyCodes to get value parameters only
                self::fail(
                    'Expected ' . ValueTooLong::class . ' to be thrown because of too long value for'
                    . " '{$parametersReflections[$index]->getName()}': (length {$valueLength}) '{$roulette[$index]}'"
                );
            } catch (ValueTooLong $valueTooLong) {
                self::assertNotEmpty($valueTooLong->getMessage());
            }
        }
    }

    public function provideTooLongParameters(): array
    {
        $orderNumber = 1234567891234567;
        $price = 1234567891234567;
        $currencyNumericCode = 978; // EUR
        $currencyPrecision = 2;
        $depositFlag = true;
        $merchantOrderIdentification = null;
        $description = str_repeat('b', 256);
        $merchantNote = str_repeat('a', 256);
        $fastPayId = str_repeat('4', 16);
        $payMethod = null;
        $disabledPayMethod = null;
        $payMethods = null;
        $cardHolderEmail = str_repeat('c', 256);
        $referenceNumber = str_repeat('d', 21);
        $addInfo = str_repeat('e', 24001);
        $lang = null;

        return [
            [
                $orderNumber,
                $price,
                $currencyNumericCode,
                $currencyPrecision,
                $depositFlag,
                $merchantOrderIdentification,
                $description,
                $merchantNote,
                $fastPayId,
                $payMethod,
                $disabledPayMethod,
                $payMethods,
                $cardHolderEmail,
                $referenceNumber,
                $addInfo,
                $lang,
            ],
        ];
    }

    /**
     * @test
     */
    public function I_can_not_use_unsupported_currency_code()
    {
        $this->expectException(\Granam\GpWebPay\Exceptions\UnknownCurrency::class);
        $this->expectExceptionMessageMatches('~789~');
        new CardPayRequestValues(
            $this->createCurrencyCodes(789, 321, false /* unsupported/unknown currency code */),
            123,
            456,
            789,
            false
        );
    }

    /**
     * @test
     */
    public function My_description_and_reference_number_and_merchant_note_have_replaced_characters_which_out_of_allowed_range()
    {
        $messageOutOfAllowedAsciiRange = 'こんにちは';
        error_clear_last();
        foreach ([RequestDigestKeys::DESCRIPTION, RequestDigestKeys::MD, RequestDigestKeys::REFERENCENUMBER] as $index => $name) {
            $roulette = [null, null, null];
            $roulette[$index] = $messageOutOfAllowedAsciiRange;
            $previousErrorReporting = ini_set('error_reporting', -1 ^ E_USER_WARNING);
            new CardPayRequestValues(
                $this->createCurrencyCodes(789, 321),
                123,
                456,
                789,
                false,
                null,
                $roulette[0], // description
                $roulette[1], // merchant note (MD)
                null,
                null,
                null,
                null,
                null,
                $roulette[2] // reference number
            );
            ini_set('error_reporting', $previousErrorReporting);
            $lastError = error_get_last();
            error_clear_last();
            self::assertNotEmpty($lastError);
            self::assertSame(E_USER_WARNING, $lastError['type']);
            $nameForRegexp = preg_quote($name, '~');
            self::assertMatchesRegularExpression(<<<REGEXP
~{$nameForRegexp}.+\D5\D.+'こ'.+'ko'.+'ん'.+'n'.+'に'.+'ni'.+'ち'.+'chi'.+'は'.+'ha'~s
REGEXP
                ,
                $lastError['message']
            );
        }
    }

    /**
     * @test
     */
    public function My_description_and_reference_number_and_merchant_note_have_replaced_characters_which_can_not_be_detected_by_regexp()
    {
        $nonDetectableCharacter = chr(128) . chr(129);
        error_clear_last();
        foreach ([RequestDigestKeys::DESCRIPTION, RequestDigestKeys::MD, RequestDigestKeys::REFERENCENUMBER] as $index => $name) {
            $roulette = [null, null, null];
            $roulette[$index] = $nonDetectableCharacter;
            $previousErrorReporting = ini_set('error_reporting', -1 ^ E_USER_WARNING);
            new CardPayRequestValues(
                $this->createCurrencyCodes(789, 321),
                123,
                456,
                789,
                false,
                null,
                $roulette[0], // description
                $roulette[1], // merchant note (MD)
                null,
                null,
                null,
                null,
                null,
                $roulette[2] // reference number
            );
            ini_set('error_reporting', $previousErrorReporting);
            $lastError = error_get_last();
            error_clear_last();
            self::assertNotEmpty($lastError);
            self::assertSame(E_USER_WARNING, $lastError['type']);
            $nameForRegexp = preg_quote($name, '~');
            self::assertMatchesRegularExpression("~{$nameForRegexp}.+128,129~s", $lastError['message']);
        }
    }

    /**
     * @test
     */
    public function My_reference_number_has_replaced_characters_which_are_out_of_range()
    {
        $allowedCharacters = ' #$';
        for ($ascii = 0x2A; $ascii <= 0x3B; $ascii++) { // *...;
            $allowedCharacters .= chr($ascii);
        }
        $allowedCharacters .= '=';
        for ($ascii = 0x40; $ascii <= 0x5A; $ascii++) { // @A...Z
            $allowedCharacters .= chr($ascii);
        }
        $allowedCharacters .= '^_';
        for ($ascii = 0x61; $ascii <= 0x7A; $ascii++) { // a...z
            $allowedCharacters .= chr($ascii);
        }
        $startPosition = random_int(0, strlen($allowedCharacters) - 18); // because of max length of 20
        $invalidValue = substr($allowedCharacters, $startPosition, 18) . 'ñ'; // ñ takes 2 bytes un UTF-8
        error_clear_last();
        $previousErrorReporting = ini_set('error_reporting', -1 ^ E_USER_WARNING);
        $cardPayRequestValues = new CardPayRequestValues(
            $this->createCurrencyCodes(789, 321),
            123,
            456,
            789,
            false,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $invalidValue
        );
        ini_set('error_reporting', $previousErrorReporting);
        $lastError = error_get_last();
        error_clear_last();
        self::assertNotEmpty($lastError);
        self::assertSame(E_USER_WARNING, $lastError['type']);
        $nameForRegexp = preg_quote(RequestDigestKeys::REFERENCENUMBER, '~');
        self::assertMatchesRegularExpression("~{$nameForRegexp}.+'ñ' => 'n'~s", $lastError['message']);
        self::assertSame(StringTools::removeDiacritics($invalidValue), $cardPayRequestValues->getReferenceNumber());
    }

    /**
     * @test
     */
    public function I_can_not_use_unknown_pay_method()
    {
        foreach ([RequestDigestKeys::PAYMETHOD, RequestDigestKeys::DISABLEPAYMETHOD, RequestDigestKeys::PAYMETHODS] as $index => $name) {
            $roulette = [0 => null, 1 => null, 2 => null];
            $roulette[$index] = 'cash';
            try {
                new CardPayRequestValues(
                    $this->createCurrencyCodes(789, 321),
                    123,
                    456,
                    789,
                    false,
                    null,
                    null,
                    null,
                    null,
                    $roulette[0], // pay method
                    $roulette[1], // disabled pay method
                    $roulette[2] !== null
                        ? [$roulette[2]] // pay methods
                        : null
                );
            } catch (UnsupportedPayMethod $unsupportedPayMethod) {
                $nameForRegexp = preg_quote($name, '~');
                $rouletteForRegexp = preg_quote($roulette[$index], '~');
                self::assertMatchesRegularExpression(
                    '~' . $nameForRegexp . '.+' . $rouletteForRegexp . '~',
                    $unsupportedPayMethod->getMessage()
                );
            }
        }
    }

    /**
     * @test
     */
    public function I_can_not_use_empty_array_as_allowed_pay_method()
    {
        $previousErrorReporting = error_reporting(-1 ^ E_USER_WARNING);
        error_clear_last();
        $cardPayRequestValues = new CardPayRequestValues(
            $this->createCurrencyCodes(789, 321),
            123,
            456,
            789,
            false,
            null,
            null,
            null,
            null,
            null,
            null,
            [] // empty array as allowed pay methods
        );
        $lastError = error_get_last();
        self::assertNotEmpty($lastError);
        self::assertSame(E_USER_WARNING, $lastError['type']);
        self::assertStringContainsString(RequestDigestKeys::PAYMETHODS, $lastError['message']);
        self::assertNull($cardPayRequestValues->getPayMethods());
        error_clear_last();
        error_reporting($previousErrorReporting);
    }

    /**
     * @test
     */
    public function No_user_email_is_used_at_all_if_invalid_one_provided()
    {
        error_clear_last();
        $previousErrorReporting = ini_set('error_reporting', -1 ^ E_USER_WARNING);
        new CardPayRequestValues(
            $this->createCurrencyCodes(789, 321),
            123,
            456,
            789,
            false,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            'local post'
        );
        ini_set('error_reporting', $previousErrorReporting);
        $lastError = error_get_last();
        error_clear_last();
        self::assertNotEmpty($lastError);
        self::assertSame(E_USER_WARNING, $lastError['type']);
        self::assertMatchesRegularExpression('~email.+local post~', $lastError['message']);
    }

    /**
     * @test
     */
    public function No_user_language_is_used_at_all_if_invalid_one_provided()
    {
        error_clear_last();
        $previousErrorReporting = ini_set('error_reporting', -1 ^ E_USER_WARNING);
        new CardPayRequestValues(
            $this->createCurrencyCodes(789, 321),
            123,
            456,
            789,
            false,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            'aboriginal'
        );
        ini_set('error_reporting', $previousErrorReporting);
        $lastError = error_get_last();
        error_clear_last();
        self::assertNotEmpty($lastError);
        self::assertSame(E_USER_WARNING, $lastError['type']);
        self::assertMatchesRegularExpression('~language.+aboriginal~', $lastError['message']);
    }
}
