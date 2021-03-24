<?php

namespace Granam\Tests\GpWebPay\Exceptions;

use Granam\GpWebPay\Codes\LanguageCodes;
use Granam\GpWebPay\Codes\PrCodes;
use Granam\GpWebPay\Exceptions\GpWebPayErrorResponse;
use PHPUnit\Framework\TestCase;

class GpWebPayErrorResponseTest extends TestCase
{
    /**
     * @test
     */
    public function I_can_ask_it_if_given_pr_code_means_error()
    {
        self::assertFalse(GpWebPayErrorResponse::isError(0));
        self::assertFalse(GpWebPayErrorResponse::isError(200));
        self::assertTrue(GpWebPayErrorResponse::isError(1));
        self::assertTrue(GpWebPayErrorResponse::isError(50));
    }

    /**
     * @test
     */
    public function I_can_find_out_easily_if_currency_was_refused()
    {
        self::assertTrue(GpWebPayErrorResponse::isUnsupportedCurrencyError(3, 7));
        self::assertFalse(GpWebPayErrorResponse::isUnsupportedCurrencyError(2, 7));
        self::assertFalse(GpWebPayErrorResponse::isUnsupportedCurrencyError(3, 8));
    }

    /**
     * @test
     */
    public function I_can_get_languages_usable_for_message_localization()
    {
        self::assertEquals(
            [LanguageCodes::CS, LanguageCodes::EN],
            GpWebPayErrorResponse::getSupportedLanguagesForLocalization()
        );
    }

    /**
     * @test
     * @dataProvider provideCodesWithTextAndExpectedResult
     * @param int $prCode
     * @param int $srCode
     * @param string $resultText
     * @param string $expectedEnglishResultText
     * @param string $expectedCzechResultText
     * @param int $expectedExceptionCode
     * @param int|null $exceptionCode
     * @param \Exception|null $previousException
     */
    public function I_can_throw_this_special_exception_with_detailed_info(
        int $prCode,
        int $srCode,
        string $resultText,
        string $expectedEnglishResultText,
        string $expectedCzechResultText,
        int $expectedExceptionCode,
        int $exceptionCode = null,
        \Exception $previousException = null
    )
    {
        try {
            throw new GpWebPayErrorResponse(
                $prCode,
                $srCode,
                $resultText,
                $exceptionCode,
                $previousException
            );
        } catch (GpWebPayErrorResponse $gpWebPayResponseHasAnError) {
            self::assertSame($prCode, $gpWebPayResponseHasAnError->getPrCode());
            self::assertSame($srCode, $gpWebPayResponseHasAnError->getSrCode());
            self::assertSame($resultText, $gpWebPayResponseHasAnError->getResultText());
            self::assertSame($expectedExceptionCode, $gpWebPayResponseHasAnError->getCode());
            self::assertSame($previousException, $gpWebPayResponseHasAnError->getPrevious());
            $expectedErrorMessage = $expectedEnglishResultText;
            if ($resultText !== '' && $expectedErrorMessage !== $resultText) {
                $expectedErrorMessage = $resultText . ' - ' . $expectedErrorMessage;
            }
            $expectedErrorMessage .= "; error code $prCode($srCode)";
            self::assertSame($expectedErrorMessage, $gpWebPayResponseHasAnError->getMessage());
            self::assertSame($expectedEnglishResultText, $gpWebPayResponseHasAnError->getLocalizedMessage());
            self::assertSame($expectedEnglishResultText, $gpWebPayResponseHasAnError->getLocalizedMessage(LanguageCodes::EN));
            self::assertSame($expectedCzechResultText, $gpWebPayResponseHasAnError->getLocalizedMessage(LanguageCodes::CS));
            // test of the reaction to unsupported language for error messages
            error_clear_last();
            $previousErrorReporting = ini_set('error_reporting', E_ALL ^ E_USER_WARNING);
            self::assertSame($expectedEnglishResultText, $gpWebPayResponseHasAnError->getLocalizedMessage($chinese = '汉语'));
            ini_set('error_reporting', $previousErrorReporting);
            $lastError = error_get_last();
            error_clear_last();
            self::assertArrayHasKey('message', $lastError);
            self::assertSame(
                "Unsupported language for error message requested: '汉语', 'en' is used instead",
                $lastError['message']
            );
        }
    }

    public function provideCodesWithTextAndExpectedResult(): array
    {
        return [
            [0 /* even OK can be thrown as an exception */, 0, '', 'OK', 'OK', 0],
            [1, 0, 'foo', 'Field too long', 'Pole příliš dlouhé', 123, 123, new \Exception()],
            [4, 8, 'bar', 'Field is null (DEPOSITFLAG)', 'Pole je prázdné (DEPOSITFLAG)', 4008],
            [50, 0, 'The cardholder canceled the payment', 'The cardholder canceled the payment', 'Držitel karty zrušil platbu', 50000],
        ];
    }

    /**
     * @test
     */
    public function I_can_ask_it_for_hint_if_message_should_be_shown_to_customer()
    {
        $gpWebPayErrorResponse = new GpWebPayErrorResponse(26, 10);
        self::assertFalse($gpWebPayErrorResponse->isLocalizedMessageForCustomer());
        $gpWebPayErrorResponse = new GpWebPayErrorResponse(26, 1002);
        self::assertTrue($gpWebPayErrorResponse->isLocalizedMessageForCustomer());
        $gpWebPayErrorResponse = new GpWebPayErrorResponse(1, 1002);
        self::assertFalse($gpWebPayErrorResponse->isLocalizedMessageForCustomer());
        $gpWebPayErrorResponse = new GpWebPayErrorResponse(50, 0);
        self::assertTrue($gpWebPayErrorResponse->isLocalizedMessageForCustomer());
    }

    /**
     * @test
     */
    public function I_can_easily_detect_duplicate_order_number_error()
    {
        $gpWebPayErrorResponse = new GpWebPayErrorResponse(PrCodes::TECHNICAL_PROBLEM, 0);
        self::assertFalse($gpWebPayErrorResponse->isDuplicateOrderNumber());
        $gpWebPayErrorResponse = new GpWebPayErrorResponse(PrCodes::DUPLICATE_ORDER_NUMBER, 0);
        self::assertTrue($gpWebPayErrorResponse->isDuplicateOrderNumber());
    }
}
