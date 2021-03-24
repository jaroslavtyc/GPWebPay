<?php declare(strict_types=1);

namespace Granam\GpWebPay;

use Granam\GpWebPay\Codes\RequestDigestKeys;
use Granam\Strict\Object\StrictObject;

class Settings extends StrictObject implements SettingsInterface
{
    const PRODUCTION_REQUEST_URL = 'https://3dsecure.gpwebpay.com/pgw/order.do';
    const TEST_REQUEST_URL = 'https://test.3dsecure.gpwebpay.com/pgw/order.do';

    /**
     * @param string $privateKeyFile
     * @param string $privateKeyPassword
     * @param string $publicKeyFile
     * @param string $merchantNumber
     * @param string|null $urlForResponse NUL means the current request URL will be used - INCLUDING query string
     * @return Settings
     * @throws \Granam\GpWebPay\Exceptions\CanNotDetermineCurrentRequestUrl
     * @throws \Granam\GpWebPay\Exceptions\PrivateKeyFileCanNotBeRead
     * @throws \Granam\GpWebPay\Exceptions\PrivateKeyUsageFailed
     * @throws \Granam\GpWebPay\Exceptions\PublicKeyFileCanNotBeRead
     * @throws \Granam\GpWebPay\Exceptions\InvalidUrl
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     * @throws \Granam\GpWebPay\Exceptions\MerchantNumberCanNotBeEmpty
     */
    public static function createForProduction(
        string $privateKeyFile,
        string $privateKeyPassword,
        string $publicKeyFile,
        string $merchantNumber,
        string $urlForResponse = null
    ): Settings
    {
        return new static(
            self::PRODUCTION_REQUEST_URL,
            $privateKeyFile,
            $privateKeyPassword,
            $publicKeyFile,
            $merchantNumber,
            $urlForResponse
        );
    }

    /**
     * @param string $privateKeyFile
     * @param string $privateKeyPassword
     * @param string $publicKeyFile
     * @param string $merchantNumber
     * @param string|null $urlForResponse NUL means the current request URL will be used - INCLUDING query string
     * @return Settings
     * @throws \Granam\GpWebPay\Exceptions\CanNotDetermineCurrentRequestUrl
     * @throws \Granam\GpWebPay\Exceptions\PrivateKeyFileCanNotBeRead
     * @throws \Granam\GpWebPay\Exceptions\PrivateKeyUsageFailed
     * @throws \Granam\GpWebPay\Exceptions\PublicKeyFileCanNotBeRead
     * @throws \Granam\GpWebPay\Exceptions\InvalidUrl
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     * @throws \Granam\GpWebPay\Exceptions\MerchantNumberCanNotBeEmpty
     */
    public static function createForTest(
        string $privateKeyFile,
        string $privateKeyPassword,
        string $publicKeyFile,
        string $merchantNumber,
        string $urlForResponse = null
    ): Settings
    {
        return new static(
            self::TEST_REQUEST_URL,
            $privateKeyFile,
            $privateKeyPassword,
            $publicKeyFile,
            $merchantNumber,
            $urlForResponse
        );
    }

    private ?string $baseUrlForRequest = null;
    private ?string $privateKeyFile = null;
    private ?string $privateKeyPassword = null;
    private ?string $publicKeyFile = null;
    private ?string $urlForResponse = null;
    private ?string $merchantNumber = null;

    /**
     * @param string $baseUrlForRequest
     * @param string $privateKeyFile
     * @param string $privateKeyPassword
     * @param string $publicKeyFile
     * @param string|null $urlForResponse NUL means the current request URL will be used - INCLUDING query string
     * @param string $merchantNumber
     * @throws \Granam\GpWebPay\Exceptions\CanNotDetermineCurrentRequestUrl
     * @throws \Granam\GpWebPay\Exceptions\PrivateKeyFileCanNotBeRead
     * @throws \Granam\GpWebPay\Exceptions\PrivateKeyUsageFailed
     * @throws \Granam\GpWebPay\Exceptions\PublicKeyFileCanNotBeRead
     * @throws \Granam\GpWebPay\Exceptions\InvalidUrl
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     * @throws \Granam\GpWebPay\Exceptions\MerchantNumberCanNotBeEmpty
     */
    public function __construct(
        string $baseUrlForRequest,
        string $privateKeyFile,
        string $privateKeyPassword,
        string $publicKeyFile,
        string $merchantNumber,
        string $urlForResponse = null
    )
    {
        $this->setBaseUrlForRequest($baseUrlForRequest);
        $this->setPrivateKeyFile($privateKeyFile);
        $this->setPrivateKeyPassword($privateKeyPassword);
        $this->setPublicKeyFile($publicKeyFile);
        $this->setMerchantNumber($merchantNumber);
        $this->setUrlForResponse($urlForResponse);
    }

    /**
     * @param string $baseUrlForRequest
     * @throws \Granam\GpWebPay\Exceptions\InvalidUrl
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     */
    private function setBaseUrlForRequest(string $baseUrlForRequest)
    {
        $baseUrlForRequest = \trim($baseUrlForRequest);
        if (!\filter_var($baseUrlForRequest, FILTER_VALIDATE_URL)) {
            throw new Exceptions\InvalidUrl("Given URL for request is not valid: '{$baseUrlForRequest}'");
        }

        $this->baseUrlForRequest = $baseUrlForRequest;
    }

    /**
     * @param string $privateKeyFile
     * @throws \Granam\GpWebPay\Exceptions\PrivateKeyFileCanNotBeRead
     */
    private function setPrivateKeyFile(string $privateKeyFile)
    {
        $privateKeyFile = \trim($privateKeyFile);
        if (!\is_readable($privateKeyFile)) {
            throw new Exceptions\PrivateKeyFileCanNotBeRead(
                "Private key '{$privateKeyFile} 'can not be read. Ensure that it exists and with correct rights."
            );
        }
        $this->privateKeyFile = $privateKeyFile;
    }

    /**
     * @param string $privateKeyPassword
     * @throws \Granam\GpWebPay\Exceptions\PrivateKeyUsageFailed
     */
    private function setPrivateKeyPassword(string $privateKeyPassword)
    {
        if (!\openssl_pkey_get_private(\file_get_contents($this->privateKeyFile), $privateKeyPassword)) {
            $errorMessage = "'{$this->privateKeyFile}' is not valid PEM private key";
            if ($privateKeyPassword !== '') {
                $errorMessage = "Password for private key is incorrect (or $errorMessage)";
            }
            throw new Exceptions\PrivateKeyUsageFailed($errorMessage);
        }
        $this->privateKeyPassword = $privateKeyPassword;
    }

    /**
     * @param string $publicKeyFile
     * @throws \Granam\GpWebPay\Exceptions\PublicKeyFileCanNotBeRead
     */
    private function setPublicKeyFile(string $publicKeyFile)
    {
        $publicKeyFile = \trim($publicKeyFile);
        if (!\is_readable($publicKeyFile)) {
            throw new Exceptions\PublicKeyFileCanNotBeRead(
                "Public key '{$publicKeyFile}' can not be read. Ensure that it exists and with correct rights."
            );
        }
        $this->publicKeyFile = $publicKeyFile;
    }

    /**
     * @param string $merchantNumber
     * @throws \Granam\GpWebPay\Exceptions\MerchantNumberCanNotBeEmpty
     */
    private function setMerchantNumber(string $merchantNumber)
    {
        $merchantNumber = \trim($merchantNumber);
        if ($merchantNumber === '') {
            throw new Exceptions\MerchantNumberCanNotBeEmpty('Merchant number is required');
        }
        $this->merchantNumber = $merchantNumber;
    }

    const MAXIMAL_LENGTH_OF_URL = 300;

    /**
     * @param string|null $urlForResponse with maximal length of 300 characters
     * @throws \Granam\GpWebPay\Exceptions\InvalidUrl
     * @throws \Granam\GpWebPay\Exceptions\ValueTooLong
     * @throws \Granam\GpWebPay\Exceptions\CanNotDetermineCurrentRequestUrl
     */
    private function setUrlForResponse(string $urlForResponse = null)
    {
        $urlForResponse ??= $this->getCurrentRequestUrl();
        $urlForResponse = \trim($urlForResponse);
        if (!\filter_var($urlForResponse, FILTER_VALIDATE_URL)) {
            throw new Exceptions\InvalidUrl('Given ' . RequestDigestKeys::URL . " is not valid: '{$urlForResponse}'");
        }
        if (\strlen($urlForResponse) > self::MAXIMAL_LENGTH_OF_URL) {
            throw new Exceptions\ValueTooLong(
                "Maximal length of '" . RequestDigestKeys::URL . '\' is ' . self::MAXIMAL_LENGTH_OF_URL
                . ', got one with length of ' . \strlen($urlForResponse) . " and value '{$urlForResponse}'"
            );
        }

        $this->urlForResponse = $urlForResponse;
    }

    /**
     * Gives current request base URL - INCLUDING query string (as part of REQUEST_URI)
     *
     * @return string
     * @throws \Granam\GpWebPay\Exceptions\CanNotDetermineCurrentRequestUrl
     */
    private function getCurrentRequestUrl(): string
    {
        $protocol = 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $protocol = 'https';
        } elseif (!empty($_SERVER['REQUES_SCHEME'])) {
            $protocol = $_SERVER['REQUES_SCHEME'];
        }
        if (empty($_SERVER['SERVER_NAME'])) {
            throw new Exceptions\CanNotDetermineCurrentRequestUrl("Missing 'SERVER_NAME' in \$_SERVER global variable");
        }
        $port = 80;
        if (!empty($_SERVER['SERVER_PORT']) && is_numeric($_SERVER['SERVER_PORT'])
            && (int)$_SERVER['SERVER_PORT'] !== 80
        ) {
            $port = (int)$_SERVER['SERVER_PORT'];
        }
        $portString = $port === 80
            ? ''
            : (':' . $port);
        if (!\array_key_exists('REQUEST_URI', $_SERVER)) {
            throw new Exceptions\CanNotDetermineCurrentRequestUrl(
                "Missing 'REQUEST_URI' key in \$_SERVER global variable"
            );
        }

        return "{$protocol}://{$_SERVER['SERVER_NAME']}{$portString}{$_SERVER['REQUEST_URI']}";
    }

    /**
     * @return string
     */
    public function getBaseUrlForRequest(): string
    {
        return $this->baseUrlForRequest;
    }

    /**
     * @return string
     */
    public function getPrivateKeyFile(): string
    {
        return $this->privateKeyFile;
    }

    /**
     * @return string
     */
    public function getPrivateKeyPassword(): string
    {
        return $this->privateKeyPassword;
    }

    /**
     * @return string
     */
    public function getPublicKeyFile(): string
    {
        return $this->publicKeyFile;
    }

    /**
     * @return string
     */
    public function getUrlForResponse(): string
    {
        return $this->urlForResponse;
    }

    /**
     * @return string
     */
    public function getMerchantNumber(): string
    {
        return $this->merchantNumber;
    }
}
