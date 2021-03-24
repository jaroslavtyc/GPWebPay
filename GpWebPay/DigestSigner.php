<?php declare(strict_types=1);

namespace Granam\GpWebPay;

use Granam\Strict\Object\StrictObject;

class DigestSigner extends StrictObject implements DigestSignerInterface
{

    /** @var SettingsInterface */
    private $settings;
    /** @var resource */
    private $privateKeyResource;
    /** @var resource */
    private $publicKeyResource;

    /**
     * @param SettingsInterface $settings
     * @throws \Granam\GpWebPay\Exceptions\PrivateKeyFileCanNotBeRead
     * @throws \Granam\GpWebPay\Exceptions\PublicKeyFileCanNotBeRead
     */
    public function __construct(SettingsInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param array|string[] $partsOfDigest
     * @return string Digest as encrypted content of the request for its validation on GpWebPay side
     * @throws \Granam\GpWebPay\Exceptions\PrivateKeyUsageFailed
     * @throws \Granam\GpWebPay\Exceptions\CanNotSignDigest
     */
    public function createSignedDigest(array $partsOfDigest): string
    {
        $digestText = \implode('|', $partsOfDigest);
        if (!\openssl_sign($digestText, $digest, $this->getPrivateKeyResource())) {
            throw new Exceptions\CanNotSignDigest('Can not sign ' . $digestText);
        }

        return \base64_encode($digest);
    }

    /**
     * @return resource
     * @throws \Granam\GpWebPay\Exceptions\PrivateKeyUsageFailed
     */
    private function getPrivateKeyResource()
    {
        if (\is_resource($this->privateKeyResource)) {
            return $this->privateKeyResource;
        }
        $key = \file_get_contents($this->settings->getPrivateKeyFile());
        if (!($this->privateKeyResource = \openssl_pkey_get_private($key, $this->settings->getPrivateKeyPassword()))) {
            $errorMessage = "'{$this->settings->getPrivateKeyFile()}' is not valid PEM private key";
            if ($this->settings->getPrivateKeyPassword() !== '') {
                $errorMessage = "Password for private key is incorrect (or $errorMessage)";
            }
            throw new Exceptions\PrivateKeyUsageFailed($errorMessage);
        }

        return $this->privateKeyResource;
    }

    /**
     * @param array|string[] $expectedPartsOfDigest
     * @param string $digestToVerify
     * @return bool
     * @throws \Granam\GpWebPay\Exceptions\ResponseDigestCanNotBeVerified
     * @throws \Granam\GpWebPay\Exceptions\PublicKeyUsageFailed
     */
    public function verifySignedDigest(string $digestToVerify, array $expectedPartsOfDigest): bool
    {
        $expectedDigest = \implode('|', $expectedPartsOfDigest);
        $decodedDigestToVerify = base64_decode($digestToVerify);
        if (\openssl_verify($expectedDigest, $decodedDigestToVerify, $this->getPublicKeyResource()) !== 1) {
            throw new Exceptions\ResponseDigestCanNotBeVerified(
                'Given digest does not match expected ' . $expectedDigest
            );
        }

        return true;
    }

    /**
     * @return resource
     * @throws \Granam\GpWebPay\Exceptions\PublicKeyUsageFailed
     */
    private function getPublicKeyResource()
    {
        if (\is_resource($this->publicKeyResource)) {
            return $this->publicKeyResource;
        }
        $publicKey = \file_get_contents($this->settings->getPublicKeyFile());
        if (!($this->publicKeyResource = \openssl_pkey_get_public($publicKey))) {
            throw new Exceptions\PublicKeyUsageFailed(
                "'{$this->settings->getPublicKeyFile()}' is not valid PEM public key."
            );
        }

        return $this->publicKeyResource;
    }
}
