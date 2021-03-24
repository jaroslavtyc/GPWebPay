<?php

namespace Granam\Tests\GpWebPay;

use Granam\GpWebPay\DigestSigner;
use Granam\GpWebPay\Settings;
use PHPUnit\Framework\TestCase;

class DigestSignerTest extends TestCase
{
    /**
     * @test
     */
    public function I_can_create_signed_digest_and_verify_it()
    {
        $digestSigner = new DigestSigner(
            $this->createSettings(
                __DIR__ . '/files/testing_private_key.pem',
                '1234567', // password
                __DIR__ . '/files/testing_public_key.pub'
            )
        );
        $valuesForDigest = ['foo' => 'bar', 'baz' => 'qux', 123 => 456];
        $signedDigest = $digestSigner->createSignedDigest($valuesForDigest);
        self::assertNotEmpty($signedDigest);
        self::assertTrue($digestSigner->verifySignedDigest($signedDigest, $valuesForDigest));
    }

    /**
     * @param string $privateKeyFile
     * @param string $privateKeyPassword
     * @param string $publicKeyFile
     * @return Settings|\Mockery\MockInterface
     */
    private function createSettings(string $privateKeyFile, string $privateKeyPassword, string $publicKeyFile)
    {
        $settings = \Mockery::mock(Settings::class);
        $settings->shouldReceive('getPrivateKeyFile')
            ->andReturn($privateKeyFile);
        $settings->shouldReceive('getPrivateKeyPassword')
            ->andReturn($privateKeyPassword);
        $settings->shouldReceive('getPublicKeyFile')
            ->andReturn($publicKeyFile);

        return $settings;
    }

    /**
     * @test
     */
    public function I_am_stopped_by_exception_if_digest_can_not_be_verified()
    {
        $digestSigner = new DigestSigner(
            $this->createSettings(
                __DIR__ . '/files/testing_private_key.pem',
                '1234567', // password
                __DIR__ . '/files/testing_public_key.pub'
            )
        );
        $this->expectException(\Granam\GpWebPay\Exceptions\ResponseDigestCanNotBeVerified::class);
        $this->expectExceptionMessageMatches('~baz|qux~');
        $digestSigner->verifySignedDigest('SignedInBottomRight', ['foo' => 'bar', 'baz' => 'qux']);
    }

    /**
     * @test
     */
    public function I_can_not_create_signer_with_invalid_private_key_password()
    {
        $digestSigner = new DigestSigner(
            $this->createSettings(
                __DIR__ . '/files/testing_private_key.pem',
                'knock knock', // password
                __DIR__ . '/files/testing_public_key.pub'
            )
        );
        $valuesForDigest = ['foo' => 'bar', 'baz' => 'qux', 123 => 456];

        $this->expectException(\Granam\GpWebPay\Exceptions\PrivateKeyUsageFailed::class);
        $signedDigest = $digestSigner->createSignedDigest($valuesForDigest);
        self::assertNotEmpty($signedDigest);
        self::assertTrue($digestSigner->verifySignedDigest($signedDigest, $valuesForDigest));
    }

}
