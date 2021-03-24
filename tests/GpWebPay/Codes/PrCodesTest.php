<?php

namespace Granam\Tests\GpWebPay\Codes;

use Granam\GpWebPay\Codes\LanguageCodes;
use Granam\GpWebPay\Codes\PrCodes;

class PrCodesTest extends CodesTest
{
    /**
     * @test
     */
    public function I_can_get_all_codes_at_once()
    {
        $prCodes = PrCodes::getPrCodes();
        $codeValues = [];
        foreach ([LanguageCodes::CS, LanguageCodes::EN] as $language) {
            self::assertArrayHasKey($language, $prCodes);
            $codeValues[$language] = $prCodes[$language];
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
     * @param int $prCode
     * @param string $language
     * @param string $expectedMessage
     * @param int|null $expectedErrorType
     * @param string|null $expectedWarningMessageRegExp
     */
    public function I_can_get_localized_message(
        int $prCode,
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
        self::assertSame($expectedMessage, PrCodes::getLocalizedMainMessage($prCode, $language));
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

    public function provideValuesToTestMessageLocalization(): array
    {
        return [
            [0, LanguageCodes::CS, 'OK'],
            [0, LanguageCodes::EN, 'OK'],
            [31, LanguageCodes::EN, 'Wrong digest'],
            [31, LanguageCodes::CS, 'Chybný podpis (digest)'],
            [PHP_INT_MAX, LanguageCodes::EN, 'Technical problem in GP webpay system, contact the merchant', E_USER_WARNING, '~' . PHP_INT_MAX . '.+generic~'], // unknown code
            [PHP_INT_MAX, LanguageCodes::CS, 'Technický problém v GP webpay systému, kontaktujte obchodníka', E_USER_WARNING, '~' . PHP_INT_MAX . '.+generic~'], // unknown code
            [2, LanguageCodes::EN, 'Field too short'],
            [2, LanguageCodes::FI, 'Field too short', E_USER_NOTICE, '~\Wfi\W.+\Wen ~'] // unknown language - english will be used instead
        ];
    }

    /**
     * @test
     */
    public function I_can_easily_find_out_if_error_code_means_info_for_customer()
    {
        self::assertTrue(PrCodes::isErrorForCustomer(17));
        self::assertTrue(PrCodes::isErrorForCustomer(1000));
        self::assertFalse(PrCodes::isErrorForCustomer(4));
    }
}
