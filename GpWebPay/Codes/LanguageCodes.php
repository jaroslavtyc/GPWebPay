<?php declare(strict_types=1);

namespace Granam\GpWebPay\Codes;

use Granam\Strict\Object\StrictObject;

/**
 * For list of supported languages see @link http://www.gpwebpay.cz/en/Faq
 * Supported formats of languages are ISO 639-1:2002, ISO 639-2:1998, RFC 3066
 * listed in GP_webpay_HTTP_EN.pdf / GP_webpay_HTTP.pdf
 */
class LanguageCodes extends StrictObject implements Codes
{
    /** ISO 639-1 @link https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes */
    const AR = 'ar'; // arabic - العربية
    const CS = 'cs'; // czech - čeština
    const DA = 'da'; // danish - dansk
    const NL = 'nl'; // dutch - nederlands
    const EN = 'en'; // english (UK)
    const EN_US = 'en_US'; // english (US)
    const FI = 'fi'; // finnish - suomi
    const FR = 'fr'; // french - français
    const DE = 'de'; // german - deutsch
    const HU = 'hu'; // hungarian - magyar
    const IT = 'it'; // italian - italiano
    const JA = 'ja'; // japanese - 日本語
    const LV = 'lv'; // latvian - latviešu valoda
    const NO = 'no'; // norwegian - norsk
    const PL = 'pl'; // polish - polszczyzna
    const PT = 'pt'; // portuguese - português
    const RO = 'ro'; // romanian - Română
    const RU = 'ru'; // russian - русский
    const SK = 'sk'; // slovak - slovenčina
    const ES = 'es'; // spanish - español
    const SV = 'sv'; // swedish - svenska
    const UK = 'uk'; // ukrainian - українська
    const VI = 'vi'; // vietnamese - Tiếng Việt

    /**
     * @return array|string[]
     */
    public static function getLanguageCodes(): array
    {
        return [
            self::AR,
            self::CS,
            self::DA,
            self::NL,
            self::EN,
            self::EN_US,
            self::FI,
            self::FR,
            self::DE,
            self::HU,
            self::IT,
            self::JA,
            self::LV,
            self::NO,
            self::PL,
            self::PT,
            self::RO,
            self::RU,
            self::SK,
            self::ES,
            self::SV,
            self::UK,
            self::VI,
        ];
    }

    /**
     * @param string $languageCode
     * @return bool
     */
    public static function isLanguageSupported(string $languageCode): bool
    {
        return \in_array($languageCode, self::getLanguageCodes(), true);
    }
}
