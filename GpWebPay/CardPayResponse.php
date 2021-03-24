<?php declare(strict_types=1);

namespace Granam\GpWebPay;

use Granam\GpWebPay\Codes\RequestDigestKeys;
use Granam\GpWebPay\Codes\ResponseDigestKeys;
use Granam\GpWebPay\Codes\ResponsePayloadKeys;
use Granam\Integer\Tools\ToInteger;
use Granam\Scalar\Tools\ToString;
use Granam\Strict\Object\StrictObject;
use Granam\Scalar\Tools\Exceptions\Exception as ConversionException;

class CardPayResponse extends StrictObject implements PayResponse
{
    private static $expectedKeys = [
        ResponseDigestKeys::OPERATION => true,
        ResponseDigestKeys::ORDERNUMBER => true,
        ResponseDigestKeys::PRCODE => true,
        ResponseDigestKeys::SRCODE => true,
        ResponsePayloadKeys::DIGEST => true,
        ResponsePayloadKeys::DIGEST1 => true,
        ResponseDigestKeys::MERORDERNUM => false,
        ResponseDigestKeys::MD => false,
        ResponseDigestKeys::RESULTTEXT => false,
        ResponseDigestKeys::USERPARAM1 => false, // hash of the payment card number
        ResponseDigestKeys::ADDINFO => false,
    ];

    private static $integerValues = [
        ResponseDigestKeys::ORDERNUMBER,
        ResponseDigestKeys::PRCODE,
        ResponseDigestKeys::SRCODE,
        ResponseDigestKeys::MERORDERNUM,
    ];

    /**
     * @param array $valuesFromGetOrPost
     * @param SettingsInterface $settings
     * @param DigestSignerInterface $digestSigner
     * @return CardPayResponse
     * @throws \Granam\GpWebPay\Exceptions\GpWebPayErrorResponse
     * @throws \Granam\GpWebPay\Exceptions\GpWebPayErrorByCustomerResponse
     * @throws \Granam\GpWebPay\Exceptions\ResponseDigestCanNotBeVerified
     * @throws \Granam\GpWebPay\Exceptions\BrokenResponse
     * @throws \Granam\GpWebPay\Exceptions\PublicKeyUsageFailed
     */
    public static function createFromArray(
        array $valuesFromGetOrPost,
        SettingsInterface $settings,
        DigestSignerInterface $digestSigner
    ): CardPayResponse
    {
        $normalizedValues = self::normalizeValues($valuesFromGetOrPost);

        return new static(
            $settings,
            $digestSigner,
            $normalizedValues[ResponseDigestKeys::OPERATION],
            $normalizedValues[ResponseDigestKeys::ORDERNUMBER],
            $normalizedValues[ResponseDigestKeys::PRCODE],
            $normalizedValues[ResponseDigestKeys::SRCODE],
            $normalizedValues[ResponsePayloadKeys::DIGEST],
            $normalizedValues[ResponsePayloadKeys::DIGEST1],
            $normalizedValues[ResponseDigestKeys::MERORDERNUM],
            $normalizedValues[ResponseDigestKeys::MD],
            $normalizedValues[ResponseDigestKeys::RESULTTEXT],
            $normalizedValues[ResponseDigestKeys::USERPARAM1],
            $normalizedValues[ResponseDigestKeys::ADDINFO]
        );
    }

    protected static function normalizeValues(array $values): array
    {
        $normalizedValues = [];
        foreach (self::$expectedKeys as $key => $required) {
            $values[$key] = $values[$key] ?? null;
            if ($required && $values[$key] === null) {
                throw new Exceptions\BrokenResponse(
                    'Values to create ' . static::class . " are missing required '{$key}'"
                );
            }
            try {
                if ($values[$key] === null) {
                    $normalizedValues[$key] = null;
                } elseif (\in_array($key, self::$integerValues, true)) {
                    $normalizedValues[$key] = ToInteger::toInteger($values[$key]);
                } else {
                    $normalizedValues[$key] = ToString::toString($values[$key]);
                }
            } catch (ConversionException $conversionException) {
                throw new Exceptions\BrokenResponse(
                    "Value of '{$key}' has invalid format: " . $conversionException->getMessage()
                );
            }
        }
        return $normalizedValues;
    }

    /** @var array $parametersForDigest */
    private $parametersForDigest;
    /** @var string */
    private $digest;
    /** @var string */
    private $digest1;

    /**
     * @param SettingsInterface $settings
     * @param DigestSignerInterface $digestSigner
     * @param string $operation
     * @param int $orderNumber
     * @param int $prCode
     * @param int $srCode
     * @param string $digest
     * @param string $digest1
     * @param int|null $merOrderNum
     * @param string|null $md
     * @param string|null $resultText
     * @param string|null $userParam1
     * @param string|null $addInfo
     * @throws \Granam\GpWebPay\Exceptions\GpWebPayErrorResponse
     * @throws \Granam\GpWebPay\Exceptions\GpWebPayErrorByCustomerResponse
     * @throws \Granam\GpWebPay\Exceptions\ResponseDigestCanNotBeVerified
     * @throws \Granam\GpWebPay\Exceptions\PublicKeyUsageFailed
     */
    public function __construct(
        SettingsInterface $settings,
        DigestSignerInterface $digestSigner,
        string $operation,
        int $orderNumber,
        int $prCode,
        int $srCode,
        string $digest,
        string $digest1,
        int $merOrderNum = null,
        string $md = null,
        string $resultText = null,
        string $userParam1 = null,
        string $addInfo = null
    )
    {
        if (Exceptions\GpWebPayErrorResponse::isError($prCode)) {
            if (Exceptions\GpWebPayErrorByCustomerResponse::isErrorCausedByCustomer($prCode, $srCode)) {
                throw new Exceptions\GpWebPayErrorByCustomerResponse($prCode, $srCode, $resultText);
            }
            throw new Exceptions\GpWebPayErrorResponse($prCode, $srCode, $resultText);
        }
        // keys HAVE TO be exactly in this order to provide correct values for digest calculation
        $this->parametersForDigest[ResponseDigestKeys::OPERATION] = $operation; // string up to length of 20 (always FINALIZE_ORDER)
        $this->parametersForDigest[ResponseDigestKeys::ORDERNUMBER] = $orderNumber; // numeric up to length of 15
        if ($merOrderNum !== null) {
            $this->parametersForDigest[ResponseDigestKeys::MERORDERNUM] = $merOrderNum; // numeric up to length of 30
        }
        if ($md !== null) {
            $this->parametersForDigest[ResponseDigestKeys::MD] = $md; // string up to length of 255
        }
        $this->parametersForDigest[ResponseDigestKeys::PRCODE] = $prCode; // numeric
        $this->parametersForDigest[ResponseDigestKeys::SRCODE] = $srCode; // numeric
        if ($resultText !== null) {
            $this->parametersForDigest[ResponseDigestKeys::RESULTTEXT] = $resultText; // string up to length of 255
        }
        if ($userParam1 !== null) {
            $this->parametersForDigest[ResponseDigestKeys::USERPARAM1] = $userParam1; // string up to length of 64
        }
        if ($addInfo !== null) {
            $this->parametersForDigest[ResponseDigestKeys::ADDINFO] = $addInfo; // long string
        }
        $this->digest = $digest; // string up to length of 2000
        $this->digest1 = $digest1; // string up to length of 2000

        $this->verifyResponseDigests($digestSigner, $settings->getMerchantNumber());
    }

    /**
     * @param DigestSignerInterface $digestSigner
     * @param string $merchantNumber
     * @return bool
     * @throws \Granam\GpWebPay\Exceptions\ResponseDigestCanNotBeVerified
     * @throws \Granam\GpWebPay\Exceptions\PublicKeyUsageFailed
     */
    private function verifyResponseDigests(DigestSignerInterface $digestSigner, string $merchantNumber): bool
    {
        // verify digest & digest1
        $parametersForDigest = $this->getParametersForDigest();
        if (!$digestSigner->verifySignedDigest($this->getDigest(), $parametersForDigest)) {
            throw new Exceptions\ResponseDigestCanNotBeVerified(
                'Given \'' . ResponsePayloadKeys::DIGEST . '\' does not match expected one calculated from values '
                . \var_export($parametersForDigest, true)
            );
        }
        // merchant number is not part of the response to provide additional security
        $parametersForDigest1 = $parametersForDigest;
        $parametersForDigest1[RequestDigestKeys::MERCHANTNUMBER] = $merchantNumber;
        if (!$digestSigner->verifySignedDigest($this->getDigest1(), $parametersForDigest1)) {
            throw new Exceptions\ResponseDigestCanNotBeVerified(
                'Given \'' . ResponsePayloadKeys::DIGEST1 . '\' does not match expected one'
                . ' (\'' . ResponsePayloadKeys::DIGEST1 . '\' has been modified).'
                . ' \'' . ResponsePayloadKeys::DIGEST1 . '\' was calculated from values '
                . \var_export($parametersForDigest1, true)
            );
        }

        return true;
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return Exceptions\GpWebPayErrorResponse::isError($this->parametersForDigest[ResponseDigestKeys::PRCODE]);
    }

    /**
     * @return array
     */
    public function getParametersForDigest(): array
    {
        return $this->parametersForDigest;
    }

    /**
     * @return string
     */
    public function getDigest(): string
    {
        return $this->digest;
    }

    /**
     * @return string
     */
    public function getDigest1(): string
    {
        return $this->digest1;
    }

    /**
     * @return int
     */
    public function getSrCode(): int
    {
        return $this->parametersForDigest[ResponseDigestKeys::SRCODE];
    }

    /**
     * @return int
     */
    public function getPrCode(): int
    {
        return $this->parametersForDigest[ResponseDigestKeys::PRCODE];
    }

    /**
     * @return string
     */
    public function getOperation(): string
    {
        return $this->parametersForDigest[ResponseDigestKeys::OPERATION];
    }

    /**
     * @return int
     */
    public function getOrderNumber(): int
    {
        return $this->parametersForDigest[ResponseDigestKeys::ORDERNUMBER];
    }

    /**
     * @return int|null
     */
    public function getMerchantOrderNumber()
    {
        return ($this->parametersForDigest[ResponseDigestKeys::MERORDERNUM] ?? null) !== null
            ? $this->parametersForDigest[ResponseDigestKeys::MERORDERNUM]
            : null;
    }

    /**
     * @return string|null
     */
    public function getMerchantNote()
    {
        return $this->parametersForDigest[ResponseDigestKeys::MD] ?? null;
    }

    /**
     * @return string|null
     */
    public function getUserParam1()
    {
        return $this->parametersForDigest[ResponseDigestKeys::USERPARAM1] ?? null;
    }

    /**
     * @return string|null
     */
    public function getAdditionalInfo()
    {
        return $this->parametersForDigest[ResponseDigestKeys::ADDINFO] ?? null;
    }

    /**
     * @return string|null
     */
    public function getResultText()
    {
        return $this->parametersForDigest[ResponseDigestKeys::RESULTTEXT] ?? null;
    }
}
