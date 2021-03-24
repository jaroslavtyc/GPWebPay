<?php declare(strict_types=1);

namespace Granam\GpWebPay;

interface SettingsInterface
{
    /**
     * @return string
     */
    public function getBaseUrlForRequest(): string;

    /**
     * @return string
     */
    public function getPrivateKeyFile(): string;

    /**
     * @return string
     */
    public function getPrivateKeyPassword(): string;

    /**
     * @return string
     */
    public function getPublicKeyFile(): string;

    /**
     * @return string
     */
    public function getUrlForResponse(): string;

    /**
     * @return string
     */
    public function getMerchantNumber(): string;
}
