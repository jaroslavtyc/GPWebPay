<?php
namespace Granam\GpWebPay\Exceptions;

use Granam\GpWebPay\Codes\LanguageCodes;
use Granam\GpWebPay\Codes\PrCodes;
use Granam\GpWebPay\Codes\SrCodes;

class GpWebPayErrorResponse extends \RuntimeException implements Runtime
{
    /**
     * @param int $prCode
     * @return bool
     */
    public static function isError(int $prCode): bool
    {
        return $prCode !== PrCodes::OK_CODE && $prCode !== PrCodes::ADDITIONAL_INFO_REQUEST_CODE;
    }

    /**
     * GPWebPay supports only CZK, EUR, GBP, HUF, PLN, RUB, USD (according to @link http://gpwebpay.cz/en/Faq and answer
     * to "Which ISO currency codes (numerical codes) are accepted via GP webpay?").
     * Every other currency is refused, even if existing.
     *
     * @param int $prCode
     * @param int $srCode
     * @return bool
     */
    public static function isUnsupportedCurrencyError(int $prCode, int $srCode): bool
    {
        return $prCode === 3 && $srCode === 7;
    }

    /** @var int */
    private $prCode;
    /** @var int */
    private $srCode;
    /** @var string|null */
    private $resultText;

    /**
     * @param int $prCode
     * @param int $srCode
     * @param string|null $resultText
     * @param int $exceptionCode
     * @param \Exception $previousException
     */
    public function __construct(
        int $prCode,
        int $srCode,
        string $resultText = null,
        $exceptionCode = null, // intentionally without scalar type hint
        \Exception $previousException = null
    )
    {
        $this->prCode = $prCode;
        $this->srCode = $srCode;
        $this->resultText = (string)$resultText;
        if ($exceptionCode === null) { // note: any value will be internally converted to int
            $exceptionCode = $prCode * 1000 + $srCode;
        }
        parent::__construct(
            ($this->resultText !== ''
                ? "{$this->resultText} - "
                : ''
            ) . $this->getLocalizedMessage(LanguageCodes::EN) . "; error code {$prCode}({$srCode})",
            $exceptionCode,
            $previousException
        );
    }

    /**
     * @return int
     */
    public function getPrCode(): int
    {
        return $this->prCode;
    }

    /**
     * @return int
     */
    public function getSrCode(): int
    {
        return $this->srCode;
    }

    /**
     * @return string|null
     */
    public function getResultText()
    {
        return $this->resultText;
    }

    /**
     * @param string $languageCode
     * @return string
     */
    public function getLocalizedMessage(string $languageCode = LanguageCodes::EN): string
    {
        $languageCode = strtolower(trim($languageCode));
        if ($languageCode !== LanguageCodes::CS && $languageCode !== LanguageCodes::EN) {
            trigger_error(
                "Unsupported language for error message requested: '$languageCode'"
                . ', \'' . LanguageCodes::EN . '\' is used instead',
                E_USER_WARNING
            );
            $languageCode = LanguageCodes::EN;
        }
        $message = PrCodes::getLocalizedMainMessage($this->prCode, $languageCode);
        $detailMessage = SrCodes::getLocalizedDetailMessage($this->srCode, $languageCode);
        if ($detailMessage) {
            $message .= ' (' . $detailMessage . ')';
        }

        return $message;
    }

    /**
     * Its recommended to show a localized message to an user / customer if it has sense to him.
     *
     * @return bool
     */
    public function isLocalizedMessageForCustomer()
    {
        return PrCodes::isErrorForCustomer($this->getPrCode()) && SrCodes::isErrorForCustomer($this->getSrCode());
    }
}