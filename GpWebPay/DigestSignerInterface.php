<?php declare(strict_types=1);

namespace Granam\GpWebPay;

interface DigestSignerInterface
{
    /**
     * @param array $partsOfDigest
     * @return string
     */
    public function createSignedDigest(array $partsOfDigest): string;

    /**
     * @param string $digestToVerify
     * @param array $expectedPartsOfDigest
     * @return bool
     * @throws \Granam\GpWebPay\Exceptions\ResponseDigestCanNotBeVerified
     * @throws \Granam\GpWebPay\Exceptions\PublicKeyUsageFailed
     */
    public function verifySignedDigest(string $digestToVerify, array $expectedPartsOfDigest): bool;
}
