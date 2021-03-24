<?php declare(strict_types=1);

namespace Granam\GpWebPay;

use Granam\Boolean\Tools\ToBoolean;
use Granam\Float\Tools\ToFloat;
use Granam\GpWebPay\Codes\CurrencyCodes;
use Granam\GpWebPay\Codes\LanguageCodes;
use Granam\GpWebPay\Codes\PayMethodCodes;
use Granam\GpWebPay\Codes\RequestDigestKeys;
use Granam\GpWebPay\Codes\RequestPayloadKeys;
use Granam\Integer\Tools\ToInteger;
use Granam\Scalar\Tools\ToString;
use Granam\Strict\Object\StrictObject;
use \Granam\Scalar\Tools\Exceptions\Runtime as ConversionException;
use Granam\String\StringTools;

class CardPayRequestValues extends StrictObject
{

    // name => is required
    private static $keysExpectedInArray = [
        // required
        RequestDigestKeys::ORDERNUMBER => true,
        RequestDigestKeys::AMOUNT => true,
        RequestDigestKeys::CURRENCY => true,
        RequestDigestKeys::DEPOSITFLAG => true,
        // optional
        RequestDigestKeys::MERORDERNUM => false,
        RequestDigestKeys::DESCRIPTION => false,
        RequestDigestKeys::MD => false,
        RequestDigestKeys::FASTPAYID => false,
        RequestDigestKeys::PAYMETHOD => false,
        RequestDigestKeys::DISABLEPAYMETHOD => false,
        RequestDigestKeys::PAYMETHODS => false,
        RequestDigestKeys::EMAIL => false,
        RequestDigestKeys::REFERENCENUMBER => false,
        RequestDigestKeys::ADDINFO => false,
        RequestPayloadKeys::LANG => false,
    ];
    private static $integerKeysExpectedInArray = [
        RequestDigestKeys::ORDERNUMBER,
        RequestDigestKeys::CURRENCY,
        RequestDigestKeys::MERORDERNUM,
        RequestDigestKeys::FASTPAYID,
    ];
    private static $booleanKeysExpectedInArray = [RequestDigestKeys::DEPOSITFLAG];
    private static $floatKeysExpectedInArray = [RequestDigestKeys::AMOUNT]; // as float price like 3.25 EUR
    private static $arrayWithStringKeysExpectedInArray = [RequestDigestKeys::PAYMETHODS];

    public const PRICE_INDEX = 'PRICE';

    /**
     * @param array $valuesFromGetOrPost
     * @param CurrencyCodes $currencyCodes
     * @return CardPayRequestValues
     * @throws \Granam\GpWebPay\Exceptions\InvalidArgumentException
     * @throws \Granam\GpWebPay\Exceptions\InvalidRequest
     * @throws \Granam\Float\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Float\Tools\Exceptions\ValueLostOnCast
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     * @throws \Granam\Scalar\Tools\Exceptions\WrongParameterType
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     * @throws \Granam\GpWebPay\Exceptions\UnknownCurrency
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     * @throws \Granam\GpWebPay\Exceptions\UnsupportedPayMethod
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     */
    public static function createFromArray(array $valuesFromGetOrPost, CurrencyCodes $currencyCodes): CardPayRequestValues
    {
        $withUpperCasedKeys = [];
        foreach ($valuesFromGetOrPost as $key => $value) {
            $withUpperCasedKeys[\strtoupper(\trim($key))] = $value;
        }
        if (($withUpperCasedKeys[RequestDigestKeys::AMOUNT] ?? null) === null
            && ($withUpperCasedKeys[self::PRICE_INDEX] ?? null) !== null
        ) {
            $withUpperCasedKeys[RequestDigestKeys::AMOUNT] = $withUpperCasedKeys[self::PRICE_INDEX];
        }
        $normalizedValues = self::normalizeValues($withUpperCasedKeys);

        return new static(
            $currencyCodes,
            $normalizedValues[RequestDigestKeys::ORDERNUMBER],
            $normalizedValues[RequestDigestKeys::AMOUNT],
            $normalizedValues[RequestDigestKeys::CURRENCY],
            $normalizedValues[RequestDigestKeys::DEPOSITFLAG],
            $normalizedValues[RequestDigestKeys::MERORDERNUM],
            $normalizedValues[RequestDigestKeys::DESCRIPTION],
            $normalizedValues[RequestDigestKeys::MD],
            $normalizedValues[RequestDigestKeys::FASTPAYID],
            $normalizedValues[RequestDigestKeys::PAYMETHOD],
            $normalizedValues[RequestDigestKeys::DISABLEPAYMETHOD],
            $normalizedValues[RequestDigestKeys::PAYMETHODS],
            $normalizedValues[RequestDigestKeys::EMAIL],
            $normalizedValues[RequestDigestKeys::REFERENCENUMBER],
            $normalizedValues[RequestDigestKeys::ADDINFO],
            $normalizedValues[RequestPayloadKeys::LANG]
        );
    }

    /**
     * @param array $withUpperCasedKeys
     * @return array
     * @throws \Granam\GpWebPay\Exceptions\InvalidArgumentException
     * @throws \Granam\GpWebPay\Exceptions\InvalidRequest
     */
    private static function normalizeValues(array $withUpperCasedKeys): array
    {
        $normalizedValues = [];
        foreach (self::$keysExpectedInArray as $key => $required) {
            if (($withUpperCasedKeys[$key] ?? null) === null) {
                if (!$required) {
                    $normalizedValues[$key] = null;
                    continue;
                }
                throw new Exceptions\InvalidRequest(
                    'Values to create ' . static::class . " are missing required '{$key}'"
                );
            }
            try {
                if (in_array($key, self::$integerKeysExpectedInArray, true)) {
                    $normalizedValues[$key] = ToInteger::toInteger($withUpperCasedKeys[$key]);
                } elseif (in_array($key, self::$floatKeysExpectedInArray, true)) {
                    $normalizedValues[$key] = ToFloat::toFloat($withUpperCasedKeys[$key]);
                } elseif (in_array($key, self::$booleanKeysExpectedInArray, true)) {
                    $normalizedValues[$key] = ToBoolean::toBoolean($withUpperCasedKeys[$key]);
                } elseif (in_array($key, self::$arrayWithStringKeysExpectedInArray, true)) {
                    $subArray = $withUpperCasedKeys[$key];
                    if (!is_array($subArray)) {
                        throw new Exceptions\InvalidRequest(
                            "Given '{$key}' should be an array, got " . \gettype($subArray)
                        );
                    }
                    $normalizedValues[$key] = $subArray;
                } else {
                    $normalizedValues[$key] = ToString::toString($withUpperCasedKeys[$key]);
                }
            } catch (ConversionException $conversionException) {
                throw new Exceptions\InvalidArgumentException(
                    "Value of key '{$key}' could not be converted to scalar: " . $conversionException->getMessage()
                );
            }
        }

        return $normalizedValues;
    }

    // REQUIRED VALUES

    /** @var int */
    private $orderNumber;
    /** @var int */
    private $amount;
    /** @var int */
    private $currency;
    /** @var int */
    private $depositFlag;
    // OPTIONAL VALUES
    /** @var int|null */
    private $merOrderNum;
    /** @var string|null */
    private $description;
    /** @var string|null merchant data (note) */
    private $md;
    /** @var int|null */
    private $fastPayId;
    /** @var string|null */
    private $payMethod;
    /** @var string|null */
    private $disablePayMethod;
    /** @var string|null */
    private $payMethods;
    /** @var string|null */
    private $email;
    /** @var string|null */
    private $referenceNumber;
    /** @var string|null */
    private $addInfo;
    /** @var string|null */
    private $lang;

    // SUPPORTIVE
    /** @var float */
    private $price;

    /**
     * @param CurrencyCodes $currencyCodes list of supported currencies in ISO 4217
     * @param int $orderNumber with max length of 15
     * @param float $price real price of the order (purchase) like 3.74 EUR
     * @param int $currencyNumericCode ISO 4217
     * @param bool $depositFlag (false = instant payment not required, true = requires immediate payment)
     * @param int|null $merchantOrderIdentification = null
     * @param string|null $description = null
     * @param string|null $merchantNote = null
     * @param string|null $fastPayId = null
     * @param string|null $payMethod = null
     * @param string|null $disabledPayMethod = null
     * @param array|null $payMethods = null
     * @param string|null $cardHolderEmail = null
     * @param string|null $referenceNumber = null
     * @param string|null $additionalInfo = null
     * @param string|null $languageTwoCharCode = null
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     * @throws \Granam\GpWebPay\Exceptions\UnknownCurrency
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     * @throws \Granam\GpWebPay\Exceptions\UnsupportedPayMethod
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     * @throws \Granam\Scalar\Tools\Exceptions\WrongParameterType
     */
    public function __construct(
        CurrencyCodes $currencyCodes,
        int $orderNumber,
        float $price,
        int $currencyNumericCode,
        bool $depositFlag, // false = instant payment not required, true = requires immediate payment
        int $merchantOrderIdentification = null,
        string $description = null,
        string $merchantNote = null,
        int $fastPayId = null,
        string $payMethod = null,
        string $disabledPayMethod = null,
        array $payMethods = null,
        string $cardHolderEmail = null,
        string $referenceNumber = null,
        string $additionalInfo = null,
        string $languageTwoCharCode = null
    )
    {
        // MERCHANTNUMBER is taken from Settings by CardPayRequest
        // OPERATION is handled by CardPayRequest
        $this->setOrderNumber($orderNumber);
        $this->setAmount($price, $currencyNumericCode, $currencyCodes);
        $this->setCurrency($currencyNumericCode, $currencyCodes);
        $this->setDepositFlag($depositFlag);
        $this->setMerOrderNum($merchantOrderIdentification);
        // URL is taken from Settings by CardPayRequest
        $this->setDescription($description);
        $this->setMd($merchantNote);
        $this->setFastPayId($fastPayId); // "This parameter is located behind the MD parameter", see GP_webpay_HTTP_EN.pdf page 15
        $this->setPayMethod($payMethod);
        $this->setDisabledPayMethod($disabledPayMethod);
        $this->setPayMethods($payMethods);
        $this->setEmail($cardHolderEmail);
        $this->setReferenceNumber($referenceNumber);
        $this->setAddInfo($additionalInfo);
        // DIGEST is handled by CardPayRequest
        $this->setLang($languageTwoCharCode);
    }

    const MAXIMAL_LENGTH_OF_ORDER_NUMBER = 15;

    /**
     * @param int $orderNumber
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     */
    private function setOrderNumber(int $orderNumber)
    {
        $this->guardMaximalLength($orderNumber, self::MAXIMAL_LENGTH_OF_ORDER_NUMBER, RequestDigestKeys::ORDERNUMBER);
        $this->orderNumber = $orderNumber;
    }

    /**
     * @param int|float|string $value
     * @param int $maximalLength
     * @param string $name
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     */
    private function guardMaximalLength($value, int $maximalLength, string $name)
    {
        if (\strlen((string)$value) > $maximalLength) {
            throw new Exceptions\ValueTooLong(
                "Maximal length of '{$name}' is {$maximalLength}, got one with length of "
                . \strlen((string)$value) . " and value '{$value}'"
            );
        }
    }

    const MAXIMAL_LENGTH_OF_AMOUNT = 15;

    /**
     * @param float $price
     * @param int $currencyCode
     * @param CurrencyCodes $currencyCodes
     * @throws \Granam\GpWebPay\Exceptions\UnknownCurrency
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     */
    private function setAmount(float $price, int $currencyCode, CurrencyCodes $currencyCodes)
    {
        $this->price = $price;
        $precision = $currencyCodes->getCurrencyPrecision($currencyCode);
        if ($precision > 0) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $price *= 10 ** $precision;
        }
        $amount = (int)\round($price);
        $this->guardMaximalLength($amount, self::MAXIMAL_LENGTH_OF_AMOUNT, RequestDigestKeys::AMOUNT);
        $this->amount = $amount;
    }

    /**
     * @param int $currencyCode
     * @param CurrencyCodes $currencyCodes
     * @throws \Granam\GpWebPay\Exceptions\UnknownCurrency
     */
    private function setCurrency(int $currencyCode, CurrencyCodes $currencyCodes)
    {
        if (!$currencyCodes->isCurrencyNumericCode($currencyCode)) {
            throw new Exceptions\UnknownCurrency(
                'Unknown ' . RequestDigestKeys::CURRENCY
                . " code given, got '{$currencyCode}', expected one of those defined by ISO 4217"
            );
        }
        $this->currency = $currencyCode;
    }

    /**
     * @param bool $depositFlag
     */
    private function setDepositFlag(bool $depositFlag)
    {
        $this->depositFlag = (int)$depositFlag; // 0 or 1
    }

    /*
     * Some banks uses shorter MEMORDERNUM (rest is truncated)
     * Komerční banka 16
     * Raiffiesen bank 10
     * UniCredit bank 12
     * (others are unknown - see GP_webpay_HTTP_EN.pdf / GP_webpay_HTTP.pdf)
     */
    const MAXIMAL_LENGTH_OF_MERORDERNUM = 30;

    /**
     * @param int|null $merchantOrderIdentification with maximal length of 30 characters
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     */
    private function setMerOrderNum(int $merchantOrderIdentification = null)
    {
        if ($merchantOrderIdentification === null) {
            return;
        }
        $this->guardMaximalLength($merchantOrderIdentification, self::MAXIMAL_LENGTH_OF_MERORDERNUM, RequestDigestKeys::MERORDERNUM);
        $this->merOrderNum = $merchantOrderIdentification;
    }

    const MAXIMAL_LENGTH_OF_DESCRIPTION = 255;

    /**
     * @param string|null $description with maximal length of 255 and ASCII characters in range of 0x20–0x7E (printable characters)
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     */
    private function setDescription(string $description = null)
    {
        if ($description === null) {
            return;
        }
        $description = \trim($description);
        $this->guardMaximalLength($description, self::MAXIMAL_LENGTH_OF_DESCRIPTION, RequestDigestKeys::DESCRIPTION);
        $this->description = $this->sanitizeAsciiRange($description, RequestDigestKeys::DESCRIPTION);
    }

    /**
     * @link https://en.wikipedia.org/wiki/ASCII#Printable_characters
     * @param string $value
     * @param string $nameOfParameter
     * @param array $characterRanges
     * @return string
     */
    private function sanitizeAsciiRange(
        string $value,
        string $nameOfParameter,
        array $characterRanges = [[' ' /* 0x20 = space */, '~' /* 0x7E = tilde */]]
    ): string
    {
        $changes = [];
        $regexpWithRange = '~(?<outOfRange>[^';
        foreach ($characterRanges as $characterRange) {
            $regexpWithRange .= \preg_quote(\reset($characterRange), '~') . '-' . \preg_quote(\end($characterRange), '~');
        }
        $regexpWithRange .= '])~';
        $sanitized = \preg_replace_callback(
            '~(?<character>\w)~u',
            function (array $characterMatch) use (&$changes, $regexpWithRange) {
                $character = $characterMatch['character'];
                if (!\preg_match($regexpWithRange, $character)) {
                    return $character; // character is in the allowed range
                }

                $withoutDiacritics = StringTools::removeDiacritics($character);
                $replacement = \preg_replace_callback(
                    $regexpWithRange,
                    function (string $stillOutOfRange) {
                        return \str_repeat('?', \mb_strlen($stillOutOfRange));
                    },
                    $withoutDiacritics
                );
                $changes[] = [$character => $replacement];

                return $replacement;
            },
            $value
        );
        if (\count($changes) > 0) {
            \trigger_error("'{$nameOfParameter}' contains " . \count($changes)
                . ' characters out of allowed ASCII range(s) ' . $this->describeAsciiRange($characterRanges)
                . ' , replacements have to be made: ' . \var_export($changes, true),
                E_USER_WARNING
            );
        }
        if ($sanitized === null) { // like for ASCII 128
            \trigger_error("'{$nameOfParameter}' contains some characters out of allowed ASCII range(s) "
                . $this->describeAsciiRange($characterRanges)
                . ' which was not detected by regexp. Given string as ASCII chain: '
                . \implode(
                    ',',
                    \array_map(
                        function ($character) {
                            return \ord($character);
                        },
                        \str_split($value)
                    )
                ),
                E_USER_WARNING
            );

            return '';
        }

        return $sanitized;
    }

    /**
     * @param array $characterRanges
     * @return string
     */
    private function describeAsciiRange(array $characterRanges): string
    {
        return \implode(
            ',',
            \array_map(
                function (array $characterRange) {
                    $start = \reset($characterRange);
                    if (\count($characterRange) === 1) {
                        return "'{$start}'(0x" . \dechex(\ord($start)) . ')';
                    }
                    $end = \end($characterRange);

                    return "'{$start}'(0x" . \dechex(\ord($start)) . ')'
                        . "-'{$end}'(0x" . \dechex(\ord($end)) . ')';
                },
                $characterRanges
            )
        );
    }

    const MAXIMAL_LENGTH_OF_MD = 255;

    /**
     * @param string $merchantNote with maximal length of 255 and ASCII characters in range of 0x20–0x7E (printable characters)
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     */
    private function setMd(string $merchantNote = null)
    {
        if ($merchantNote === null) {
            return;
        }
        $merchantNote = \trim($merchantNote);
        $this->guardMaximalLength($merchantNote, self::MAXIMAL_LENGTH_OF_MD, RequestDigestKeys::MD . ' (merchant note)');
        $this->md = $this->sanitizeAsciiRange($merchantNote, RequestDigestKeys::MD . ' (merchant note)');
    }

    const MAXIMAL_LENGTH_OF_FASTPAYID = 15;

    /**
     * @param int|null $fastPayId with maximal length of 15
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     */
    private function setFastPayId(?int $fastPayId)
    {
        if ($fastPayId === null) {
            return;
        }
        $this->guardMaximalLength($fastPayId, self::MAXIMAL_LENGTH_OF_FASTPAYID, RequestDigestKeys::FASTPAYID);
        $this->fastPayId = $fastPayId;
    }

    /**
     * @param string|null $payMethod supported val: CRD – payment card | MCM – MasterCard Mobile | MPS – MasterPass | BTNCS - PLATBA 24
     * @throws \Granam\GpWebPay\Exceptions\UnsupportedPayMethod
     */
    private function setPayMethod(string $payMethod = null)
    {
        if ($payMethod === null) {
            return;
        }
        $payMethod = \trim($payMethod);
        $upperPayMethod = \strtoupper($payMethod);
        if (!PayMethodCodes::isSupportedPaymentMethod($upperPayMethod)) {
            throw new Exceptions\UnsupportedPayMethod(
                'Given ' . RequestDigestKeys::PAYMETHOD . " '{$payMethod}' is not supported, use one of "
                . \implode(',', PayMethodCodes::getPayMethodCodes())
            );
        }
        $this->payMethod = $upperPayMethod;
    }

    /**
     * Explicitly disable use of a payment method, even if is technically possible.
     *
     * @param string|null $disabledPayMethod supported val: CRD – payment card | MCM – MasterCard Mobile | MPS – MasterPass | BTNCS - PLATBA 24
     * @throws \Granam\GpWebPay\Exceptions\UnsupportedPayMethod
     */
    private function setDisabledPayMethod(string $disabledPayMethod = null)
    {
        if ($disabledPayMethod === null) {
            return;
        }
        $disabledPayMethod = \trim($disabledPayMethod);
        $upperDisabledPayMethod = \strtoupper($disabledPayMethod);
        if (!PayMethodCodes::isSupportedPaymentMethod($upperDisabledPayMethod)) {
            throw new Exceptions\UnsupportedPayMethod(
                'Can not disable ' . RequestDigestKeys::DISABLEPAYMETHOD . " by unknown pay method '{$disabledPayMethod}',"
                . ' use one of ' . \implode(',', PayMethodCodes::getPayMethodCodes())
            );
        }
        $this->disablePayMethod = $upperDisabledPayMethod;
    }

    /**
     * Sets allowed pay methods, therefore disable those non-listed there, even if they are technically possible.
     * If DISABLEPAYMETHOD is set as well than an intersection of both rules is used.
     *
     * @param array|string[] $payMethods supported val: CRD – payment card | MCM – MasterCard Mobile | MPS – MasterPass | BTNCS - PLATBA 24
     * @throws \Granam\Scalar\Tools\Exceptions\WrongParameterType
     * @throws \Granam\GpWebPay\Exceptions\UnsupportedPayMethod
     */
    private function setPayMethods(array $payMethods = null)
    {
        if ($payMethods === null) {
            return;
        }
        $upperPayMethods = [];
        foreach ($payMethods as $payMethod) {
            $upperPayMethods[$payMethod] = \strtoupper(\trim(ToString::toString($payMethod)));
        }
        $unknownPayMethods = array_diff($upperPayMethods, PayMethodCodes::getPayMethodCodes());
        if (\count($unknownPayMethods) > 0) {
            $unknownOriginalPayMethods = [];
            foreach ($unknownPayMethods as $unknownPayMethod) {
                $unknownOriginalPayMethods[] = array_search($unknownPayMethod, $upperPayMethods, true);
            }
            throw new Exceptions\UnsupportedPayMethod(
                'Can not set \'' . RequestDigestKeys::PAYMETHODS . '\' by unknown pay method ' . \implode(',', $unknownOriginalPayMethods)
                . '; use only ' . \implode(',', PayMethodCodes::getPayMethodCodes())
            );
        }
        if (\count($upperPayMethods) === 0) {
            trigger_error(
                'Empty array of \'' . RequestDigestKeys::PAYMETHODS . '\' provided, which would disable all of them.'
                . ' That is considered as a mistake and NO restriction to payment methods will be used instead'
                . ' (via NULL)',
                E_USER_WARNING
            );

            return;
        }
        $this->payMethods = \implode(',', $upperPayMethods);
    }

    const MAXIMAL_LENGTH_OF_EMAIL = 255;

    /**
     * @param string $email with maximal length of 255
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     */
    private function setEmail(string $email = null)
    {
        if ($email === null) {
            return;
        }
        $email = \trim($email);
        $this->guardMaximalLength($email, self::MAXIMAL_LENGTH_OF_EMAIL, RequestDigestKeys::EMAIL);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            trigger_error("Given user email '{$email}' has invalid format, email will not be used", E_USER_WARNING);

            return;
        }
        $this->email = $email;
    }

    const MAXIMAL_LENGTH_OF_REFERENCENUMBER = 20;

    /**
     * Merchant internal ID of an order
     *
     * @param string|null $referenceNumber with maximal length of 20
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     */
    private function setReferenceNumber(string $referenceNumber = null)
    {
        if ($referenceNumber === null) {
            return;
        }
        $referenceNumber = \trim($referenceNumber);
        $this->guardMaximalLength($referenceNumber, self::MAXIMAL_LENGTH_OF_REFERENCENUMBER, RequestDigestKeys::REFERENCENUMBER);
        $this->referenceNumber = $this->sanitizeAsciiRange(
            $referenceNumber,
            RequestDigestKeys::REFERENCENUMBER,
            [
                [' ' /* 0x20 */], ['#' /* 0x23 */, '$' /* 0x24 */], ['*' /* 0x2A */, ';' /* 0x3B */], ['=' /* 0x3D */],
                ['@' /* 0x40 */, 'Z' /* 0x5A */], ['^' /* 0x5E */, '_' /* 0x5F */], ['a' /* 0x61 */, 'z' /* 0x7A */],
            ]
        );
    }

    const MAXIMAL_LENGTH_OF_ADDINFO = 24000;

    /**
     * XML schema
     *
     * @param string $addInfo with maximal length of 24000
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     */
    private function setAddInfo(string $addInfo = null)
    {
        if ($addInfo === null) {
            return;
        }
        $addInfo = \trim($addInfo);
        $this->guardMaximalLength($addInfo, self::MAXIMAL_LENGTH_OF_ADDINFO, RequestDigestKeys::ADDINFO);
        $this->addInfo = $addInfo;
    }

    /**
     * Note: LANG is not part of digest
     *
     * @param string|null $lang
     */
    private function setLang(string $lang = null)
    {
        if ($lang === null) {
            return;
        }
        $lang = \trim($lang);
        if (!LanguageCodes::isLanguageSupported($lang)) {
            trigger_error(
                "Unsupported language code '{$lang}', GPWebPay auto-detection of a language will be used."
                . ' Supported languages are ' . \implode(',', LanguageCodes::getLanguageCodes()),
                E_USER_WARNING
            );

            return;
        }
        $this->lang = $lang;
    }

    /**
     * @return int
     */
    public function getOrderNumber(): int
    {
        return $this->orderNumber;
    }

    /**
     * Originally provided price
     *
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * Price turned to its integer representation according to provided currency
     * precision, @return int
     * @see CurrencyCodes::getCurrencyPrecision()
     *
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return int
     */
    public function getCurrency(): int
    {
        return $this->currency;
    }

    /**
     * @return int
     */
    public function getDepositFlag(): int
    {
        return $this->depositFlag;
    }

    /**
     * @return null|string
     */
    public function getMd()
    {
        return $this->md;
    }

    /**
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return int|null
     */
    public function getMerOrderNum()
    {
        return $this->merOrderNum;
    }

    /**
     * @return null|string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @return null|string
     */
    public function getPayMethod()
    {
        return $this->payMethod;
    }

    /**
     * @return null|string
     */
    public function getDisablePayMethod()
    {
        return $this->disablePayMethod;
    }

    /**
     * @return null|string
     */
    public function getPayMethods()
    {
        return $this->payMethods;
    }

    /**
     * @return null|string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return null|string
     */
    public function getReferenceNumber()
    {
        return $this->referenceNumber;
    }

    /**
     * @return null|string
     */
    public function getAddInfo()
    {
        return $this->addInfo;
    }

    /**
     * @return int|null
     */
    public function getFastPayId()
    {
        return $this->fastPayId;
    }
}
