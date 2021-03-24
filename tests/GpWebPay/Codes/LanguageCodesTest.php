<?php

namespace Granam\Tests\GpWebPay\Codes;

use Granam\GpWebPay\Codes\LanguageCodes;

class LanguageCodesTest extends CodesTest
{
    /**
     * @test
     */
    public function I_can_get_list_of_all_digest_keys()
    {
        $reflectionClass = new \ReflectionClass(LanguageCodes::class);
        $constantValues = array_values($reflectionClass->getConstants());
        sort($constantValues);
        $languageCodes = LanguageCodes::getLanguageCodes();
        sort($languageCodes);
        self::assertSame($constantValues, $languageCodes);
    }

    /**
     * @test
     * @dataProvider provideLanguageCodeAndIfIsSupported
     * @param string $languageCode
     * @param bool $isSupported
     */
    public function I_can_ask_if_language_code_is_supported(string $languageCode, bool $isSupported)
    {
        self::assertSame($isSupported, LanguageCodes::isLanguageSupported($languageCode));
    }

    public function provideLanguageCodeAndIfIsSupported()
    {
        $values = [];
        foreach (LanguageCodes::getLanguageCodes() as $languageCode) {
            $values[] = [$languageCode, true];
        }
        $values[] = ['zu' /* Zulu */, false];

        return $values;
    }
}
