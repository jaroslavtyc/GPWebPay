<?php

namespace Granam\Tests\GpWebPay\Codes;

use Granam\GpWebPay\Codes\LanguageCodes;
use Granam\GpWebPay\Codes\SrCodes;
use Granam\TestWithMockery\TestWithMockery;

class SrCodesTest extends TestWithMockery
{
    /**
     * @test
     */
    public function I_can_get_all_codes_at_once()
    {
        $srCodes = SrCodes::getSrCodes();
        $codeValues = [];
        foreach ([LanguageCodes::CS, LanguageCodes::EN] as $language) {
            self::assertArrayHasKey($language, $srCodes);
            $codeValues[$language] = $srCodes[$language];
        }
        foreach ([LanguageCodes::CS, LanguageCodes::EN] as $language) {
            $codeValuesFromOtherLanguages = $codeValues;
            unset($codeValuesFromOtherLanguages[$language]);
            foreach ($codeValuesFromOtherLanguages as $otherLanguage => $codeValuesFromOtherLanguage) {
                $missingKeys = array_diff_key($codeValues[$language], $codeValuesFromOtherLanguage);
                self::assertCount(
                    0,
                    $missingKeys,
                    "Language {$otherLanguage} has missing following codes in comparison to {$language}: "
                    . implode(',', $missingKeys)
                );
            }
        }
    }

    /**
     * @test
     * @dataProvider provideValuesToTestMessageLocalization
     * @param int $srCode
     * @param string $language
     * @param string $expectedMessage
     * @param int|null $expectedErrorType
     * @param string|null $expectedWarningMessageRegExp
     */
    public function I_can_get_localized_message(
        int $srCode,
        string $language,
        string $expectedMessage,
        int $expectedErrorType = null,
        string $expectedWarningMessageRegExp = null
    )
    {
        $previousErrorReporting = null;
        if ($expectedErrorType !== null) {
            $previousErrorReporting = ini_set('error_reporting', -1 ^ $expectedErrorType);
            error_clear_last();
        }
        self::assertSame($expectedMessage, SrCodes::getLocalizedDetailMessage($srCode, $language));
        if ($expectedErrorType !== null) {
            $lastError = error_get_last();
            self::assertNotEmpty($lastError);
            self::assertSame($expectedErrorType, $lastError['type']);
            if ($expectedWarningMessageRegExp !== null) {
                self::assertMatchesRegularExpression($expectedWarningMessageRegExp, $lastError['message']);
            }
            error_clear_last();
        }
        if ($previousErrorReporting !== null) {
            ini_set('error_reporting', $previousErrorReporting);
        }
    }

    public function provideValuesToTestMessageLocalization()
    {
        return [
            [0, LanguageCodes::EN, ''],
            [1, LanguageCodes::EN, 'ORDERNUMBER'],
            [1, LanguageCodes::CS, 'ORDERNUMBER'],
            [1001, LanguageCodes::EN, 'Declined in AC, Card is blocked'],
            [1001, LanguageCodes::CS, 'Zamítnuto v autorizačním centru, katra je blokována'],
            [PHP_INT_MAX, LanguageCodes::EN, '', E_USER_WARNING, '~' . PHP_INT_MAX . '.+no message~'], // unknown code
            [PHP_INT_MAX, LanguageCodes::CS, '', E_USER_WARNING, '~' . PHP_INT_MAX . '.+no message~'], // unknown code
            [3001, LanguageCodes::EN, 'Authenticated'],
            [3001, LanguageCodes::FI, 'Authenticated', E_USER_NOTICE, '~\Wfi\W.+\Wen ~'] // unknown language - english will be used instead
        ];
    }

    /**
     * @test
     */
    public function I_can_easily_find_out_if_error_code_means_info_for_customer()
    {
        self::assertTrue(SrCodes::isErrorForCustomer(0));
        self::assertTrue(SrCodes::isErrorForCustomer(6));
        self::assertTrue(SrCodes::isErrorForCustomer(1001));
        self::assertTrue(SrCodes::isErrorForCustomer(PHP_INT_MAX));
        self::assertFalse(SrCodes::isErrorForCustomer(5));
    }
}
