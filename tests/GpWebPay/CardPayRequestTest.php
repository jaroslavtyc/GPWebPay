<?php

namespace Granam\Tests\GpWebPay;

use Granam\GpWebPay\CardPayRequest;
use Granam\GpWebPay\CardPayRequestValues;
use Granam\GpWebPay\Codes\RequestPayloadKeys;
use Granam\GpWebPay\DigestSigner;
use Granam\GpWebPay\SettingsInterface;

class CardPayRequestTest extends PayRequestTest
{
    /**
     * @test
     * @dataProvider provideParametersToCreateRequestAndExpectedResult
     * @param CardPayRequestValues $cardPayRequestValues
     * @param SettingsInterface $settings
     * @param DigestSigner $digestSigner
     * @param array $expectedParametersForRequest
     */
    public function I_can_use_it(
        CardPayRequestValues $cardPayRequestValues,
        SettingsInterface $settings,
        DigestSigner $digestSigner,
        array $expectedParametersForRequest
    )
    {
        $cardPayRequest = new CardPayRequest($cardPayRequestValues, $settings, $digestSigner);
        self::assertSame($cardPayRequestValues, $cardPayRequest->getCardPayRequestValues());
        self::assertEquals($expectedParametersForRequest, $cardPayRequest->getParametersForRequest());
        self::assertEquals(
            $settings->getBaseUrlForRequest() . '?' . http_build_query($expectedParametersForRequest),
            $cardPayRequest->getRequestUrlWithGetParameters()
        );
        self::assertEquals($settings->getBaseUrlForRequest(), $cardPayRequest->getRequestUrl());
        $iterator = $cardPayRequest->getIterator();
        self::assertInstanceOf(\Iterator::class, $iterator);
        $collectedValues = [];
        foreach ($iterator as $name => $value) {
            $collectedValues[$name] = $value;
        }
        self::assertEquals($expectedParametersForRequest, $collectedValues);
    }

    public function provideParametersToCreateRequestAndExpectedResult()
    {
        $parameters = [];

        // with all optional values
        $expectedParametersWithoutDigest = $this->buildExpectedValues(self::$testingValues, self::$settingsValues);
        $digest = 'right bottom';
        $expectedParametersForRequest = $expectedParametersWithoutDigest;
        $expectedParametersForRequest[RequestPayloadKeys::DIGEST] = $digest;
        $expectedParametersForRequest[RequestPayloadKeys::LANG] = self::$testingValues['lang'];
        $parameters[] = [
            $this->createCardPayRequestValues(self::$testingValues),
            $this->createSettingsInterface(self::$settingsValues),
            $this->createDigestSigner($expectedParametersWithoutDigest, $digest),
            $expectedParametersForRequest,
        ];

        // with required values only
        $requiredParameters = [];
        foreach (self::$requiredParameterNames as $requiredParameterName) {
            if (array_key_exists($requiredParameterName, self::$testingValues)) {
                $requiredParameters[$requiredParameterName] = self::$testingValues[$requiredParameterName];
            }
        }
        foreach (self::$testingValues as $parameterName => $testingValue) {
            if (!array_key_exists($parameterName, $requiredParameters)) {
                $requiredParameters[$parameterName] = null;
            }
        }
        $expectedRequiredParametersWithoutDigest = $this->buildExpectedValues($requiredParameters, self::$settingsValues);
        $expectedRequiredNonEmptyParameters = array_filter($expectedRequiredParametersWithoutDigest, function ($parameter) {
            return (bool)$parameter;
        });
        $digest = 'right there';
        $expectedRequiredParametersForRequest = $expectedRequiredNonEmptyParameters;
        $expectedRequiredParametersForRequest[RequestPayloadKeys::DIGEST] = $digest;
        $parameters[] = [
            $this->createCardPayRequestValues($requiredParameters),
            $this->createSettingsInterface(self::$settingsValues),
            $this->createDigestSigner($expectedRequiredNonEmptyParameters, $digest),
            $expectedRequiredParametersForRequest,
        ];

        // with optional values as zero or empty string
        $parametersWithOptionalEmpty = [];
        foreach (self::$requiredParameterNames as $requiredParameterName) {
            if (array_key_exists($requiredParameterName, self::$testingValues)) {
                $parametersWithOptionalEmpty[$requiredParameterName] = self::$testingValues[$requiredParameterName];
            }
        }
        foreach (self::$testingValues as $parameterName => $testingValue) {
            if (!array_key_exists($parameterName, $parametersWithOptionalEmpty)) {
                if (in_array($parameterName, ['merOrderNum', 'fastPayId'], true)) {
                    $parametersWithOptionalEmpty[$parameterName] = 0;
                } else {
                    $parametersWithOptionalEmpty[$parameterName] = '';
                }
            }
        }
        $expectedRequiredParametersWithoutDigest = $this->buildExpectedValues($parametersWithOptionalEmpty, self::$settingsValues);
        $expectedRequiredNonEmptyParameters = array_filter($expectedRequiredParametersWithoutDigest, function ($parameter) {
            return $parameter !== null;
        });
        $digest = 'over here';
        $expectedRequiredParametersForRequest = $expectedRequiredNonEmptyParameters;
        $expectedRequiredParametersForRequest[RequestPayloadKeys::DIGEST] = $digest;
        $parameters[] = [
            $this->createCardPayRequestValues($parametersWithOptionalEmpty),
            $this->createSettingsInterface(self::$settingsValues),
            $this->createDigestSigner($expectedRequiredNonEmptyParameters, $digest),
            $expectedRequiredParametersForRequest,
        ];

        return $parameters;
    }

    // in required order
    private static $testingValues = [
        'orderNumber' => 123,
        'amount' => 456,
        'currency' => 789,
        'depositFlag' => 987,
        'merOrderNum' => 654,
        'description' => 'bar',
        'md' => 'baz',
        'payMethod' => 'qux',
        'disablePayMethod' => 'FooBar',
        'payMethods' => 'FooBaz',
        'email' => 'FooQux',
        'referenceNumber' => 'BarBaz',
        'addInfo' => 'BarQux',
        'fastPayId' => 321,
        'lang' => 'FOO',
    ];

    /**
     * @param array $values
     * @return \Mockery\MockInterface|CardPayRequestValues
     */
    private function createCardPayRequestValues(array $values)
    {
        $cardPayRequestValues = $this->mockery(CardPayRequestValues::class);
        foreach ($values as $name => $value) {
            $cardPayRequestValues->shouldReceive('get' . ucfirst($name))
                ->andReturn($value);
        }

        return $cardPayRequestValues;
    }

    private static $settingsValues = [
        'merchantNumber' => '111',
        'urlForResponse' => 'FoO',
        'baseUrlForRequest' => 'BaR',
    ];

    /**
     * @param array $values
     * @return \Mockery\MockInterface|SettingsInterface
     */
    private function createSettingsInterface(array $values)
    {
        $settings = $this->mockery(SettingsInterface::class);
        foreach ($values as $name => $value) {
            $settings->shouldReceive('get' . ucfirst($name))
                ->andReturn($value);
        }

        return $settings;
    }

    /**
     * @param array $expectedValues
     * @param $digest
     * @return \Mockery\MockInterface|DigestSigner
     */
    private function createDigestSigner(array $expectedValues, $digest)
    {
        $digestSigner = $this->mockery(DigestSigner::class);
        $digestSigner->shouldReceive('createSignedDigest')
            ->with($expectedValues)
            ->andReturn($digest);

        return $digestSigner;
    }

    /**
     * @param array $cardPayRequestValues
     * @param array $settingValues
     * @return array
     */
    private function buildExpectedValues(array $cardPayRequestValues, array $settingValues)
    {
        $expectedValues = array_merge($cardPayRequestValues, $settingValues);
        $expectedValues['operation'] = 'CREATE_ORDER';
        $expectedValues = $this->remapKeysToRequest($expectedValues);
        $expectedValues = $this->reorderByKeysToRequest($expectedValues);

        return $expectedValues;
    }

    /**
     * @param array $values
     * @return array
     */
    private function remapKeysToRequest(array $values)
    {
        $reMapped = [];
        foreach ($values as $name => $value) {
            if ($name === 'urlForResponse') {
                $name = 'url';
            }
            $reMapped[strtoupper($name)] = $value;
        }

        return $reMapped;
    }

    /**
     * @param array $values
     * @return array
     */
    private function reorderByKeysToRequest(array $values)
    {
        $reordered = [];
        foreach (RequestPayloadKeys::getDigestKeys() as $digestKey) {
            if (array_key_exists($digestKey, $values)) {
                $reordered[$digestKey] = $values[$digestKey];
            }
        }

        return $reordered;
    }

    private static $requiredParameterNames = [
        'merchantNumber',
        'operation',
        'orderNumber',
        'amount',
        'currency',
        'depositFlag',
    ];
}
