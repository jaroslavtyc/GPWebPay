<?php

namespace Granam\Tests\GpWebPay;

use Granam\GpWebPay\Settings;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{

    /**
     * @test
     */
    public function I_can_create_it()
    {
        $requestBaseUrl = 'http://example.com';
        $privateKeyFile = __DIR__ . '/files/testing_private_key.pem';
        $privateKeyPassword = '1234567'; // password
        $publicKeyFile = __DIR__ . '/files/testing_public_key.pub';
        $merchantNumber = '123456';
        $responseUrl = 'https://example.com/gp-webpay/response';

        foreach (['new', 'production', 'test'] as $howToCreate) {
            switch ($howToCreate) {
                case 'production' :
                    $settings = Settings::createForProduction(
                        $privateKeyFile,
                        $privateKeyPassword,
                        $publicKeyFile,
                        $merchantNumber,
                        $responseUrl
                    );
                    $requestBaseUrl = Settings::PRODUCTION_REQUEST_URL;
                    break;
                case 'test' :
                    $settings = Settings::createForTest(
                        $privateKeyFile,
                        $privateKeyPassword,
                        $publicKeyFile,
                        $merchantNumber,
                        $responseUrl
                    );
                    $requestBaseUrl = Settings::TEST_REQUEST_URL;
                    break;
                case 'new' :
                default :
                    $settings = new Settings(
                        $requestBaseUrl,
                        $privateKeyFile,
                        $privateKeyPassword,
                        $publicKeyFile,
                        $merchantNumber,
                        $responseUrl
                    );
            }
            self::assertSame($requestBaseUrl, $settings->getBaseUrlForRequest());
            self::assertSame($privateKeyFile, $settings->getPrivateKeyFile());
            self::assertSame($privateKeyPassword, $settings->getPrivateKeyPassword());
            self::assertSame($publicKeyFile, $settings->getPublicKeyFile());
            self::assertSame($responseUrl, $settings->getUrlForResponse());
            self::assertSame($merchantNumber, $settings->getMerchantNumber());
        }
    }

    /**
     * @test
     */
    public function I_can_not_create_settings_without_merchant_number()
    {
        $this->expectException(\Granam\GpWebPay\Exceptions\MerchantNumberCanNotBeEmpty::class);
        new Settings(
            'http://localhost',
            __DIR__ . '/files/testing_private_key.pem',
            '1234567', // password
            __DIR__ . '/files/testing_public_key.pub',
            '', // empty merchant number
            'http://localhost'
        );
    }

    /**
     * @test
     */
    public function I_can_not_create_settings_with_invalid_request_base_url()
    {
        $this->expectException(\Granam\GpWebPay\Exceptions\InvalidUrl::class);
        $this->expectExceptionMessageMatches('~localhost~');
        new Settings(
            'localhost', // protocol missing
            __DIR__ . '/files/testing_private_key.pem',
            '1234567', // password
            __DIR__ . '/files/testing_public_key.pub',
            '',
            ''
        );
    }

    /**
     * @test
     */
    public function I_can_not_create_settings_with_unreachable_private_key_file()
    {
        $this->expectException(\Granam\GpWebPay\Exceptions\PrivateKeyFileCanNotBeRead::class);
        $this->expectExceptionMessageMatches('~on keychain~');
        new Settings(
            'http://example.com',
            'on keychain',
            '1234567', // password
            __DIR__ . '/files/testing_public_key.pub',
            '',
            ''
        );
    }

    /**
     * @test
     */
    public function I_can_not_create_settings_with_invalid_private_key_password()
    {
        $this->expectException(\Granam\GpWebPay\Exceptions\PrivateKeyUsageFailed::class);
        new Settings(
            'http://example.com',
            __DIR__ . '/files/testing_private_key.pem',
            'knock knock', // password
            __DIR__ . '/files/testing_public_key.pub',
            '',
            ''
        );
    }

    /**
     * @test
     */
    public function I_can_not_create_settings_with_unreachable_public_key_file()
    {
        $this->expectException(\Granam\GpWebPay\Exceptions\PublicKeyFileCanNotBeRead::class);
        $this->expectExceptionMessageMatches('~in a cloud~');
        new Settings(
            'http://example.com',
            __DIR__ . '/files/testing_private_key.pem',
            1_234_567, // password
            'in a cloud',
            '',
            ''
        );
    }

    /**
     * @test
     */
    public function I_can_not_create_settings_with_invalid_response_url()
    {
        $this->expectException(\Granam\GpWebPay\Exceptions\InvalidUrl::class);
        $this->expectExceptionMessageMatches('~/dev/null~');
        new Settings(
            'http://example.com',
            __DIR__ . '/files/testing_private_key.pem',
            1_234_567, // password
            __DIR__ . '/files/testing_public_key.pub',
            '321',
            '/dev/null'
        );
    }

    /**
     * @test
     */
    public function I_can_not_create_settings_with_too_long_response_url()
    {
        $this->expectException(\Granam\GpWebPay\Exceptions\ValueTooLong::class);
        $this->expectExceptionMessageMatches('~300~');
        new Settings(
            'http://example.com',
            __DIR__ . '/files/testing_private_key.pem',
            1_234_567, // password
            __DIR__ . '/files/testing_public_key.pub',
            '321',
            'http://example.com/' . str_repeat('u', 301 - strlen('http://example.com/'))
        );
    }

    /**
     * @test
     * @backupGlobals enabled
     * @dataProvider provideGlobalsForCurrentRequestUrlBuild
     * @param $httpXForwardedProto
     * @param $https
     * @param $requestScheme
     * @param string $serverName
     * @param $serverPort
     * @param string $requestUri
     * @param string $expectedUrlForResponse
     */
    public function I_can_create_settings_with_response_url_same_as_from_current_request(
        $httpXForwardedProto,
        $https,
        $requestScheme,
        string $serverName,
        $serverPort,
        string $requestUri,
        string $expectedUrlForResponse
    )
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = $httpXForwardedProto;
        $_SERVER['HTTPS'] = $https;
        $_SERVER['REQUES_SCHEME'] = $requestScheme;
        $_SERVER['SERVER_NAME'] = $serverName;
        $_SERVER['SERVER_PORT'] = $serverPort;
        $_SERVER['REQUEST_URI'] = $requestUri;

        $settings = new Settings(
            'http://example.com',
            __DIR__ . '/files/testing_private_key.pem',
            1_234_567, // password
            __DIR__ . '/files/testing_public_key.pub',
            '321',
            null
        );
        self::assertSame($expectedUrlForResponse, $settings->getUrlForResponse());
    }

    public function provideGlobalsForCurrentRequestUrlBuild(): array
    {
        // $httpXForwardedProto, $https, $requestScheme, $serverName, $serverPort, $requestUri, $expectedUrlForResponse
        return [
            [null, null, null, 'bar', null, '', 'http://bar'], // implicit web protocol used
            ['foo', null, null, 'bar', null, '', 'foo://bar'],
            ['foo', null, null, 'bar', 80, '/', 'foo://bar/'], // just a trailing slash
            ['foo', 'FOO', 'Foo', 'bar', 88, '/baz?qux', 'foo://bar:88/baz?qux'], // $httpXForwardedProto priority
            [null, 'FOO', 'Foo', 'bar', 0, '/', 'https://bar/'], // HTTPS before request scheme
            /**
             * request scheme is the last because of its buggy behaviour on some
             * systems, @link http://stackoverflow.com/questions/18008135/is-serverrequest-scheme-reliable
             */
            [null, null, 'Foo', 'bar', 1, '/?bar[]=qux', 'Foo://bar:1/?bar[]=qux'],
            [null, null, 'Foo', 'bar', 1, $query = '/' . http_build_query(['baz' => __CLASS__]), 'Foo://bar:1' . $query],
        ];
    }

    /**
     * @test
     * @backupGlobals enabled
     * @dataProvider provideMissingGlobalsForCurrentRequestUrlBuild
     * @param $serverName
     * @param $requestUri
     */
    public function I_can_not_create_settings_with_current_url_if_it_can_not_be_determined($serverName, $requestUri)
    {
        $_SERVER['SERVER_NAME'] = $serverName;
        if ($requestUri !== null) {
            $_SERVER['REQUEST_URI'] = $requestUri;
        } else {
            unset($_SERVER['REQUEST_URI']);
        }
        $this->expectException(\Granam\GpWebPay\Exceptions\CanNotDetermineCurrentRequestUrl::class);
        new Settings(
            'http://example.com',
            __DIR__ . '/files/testing_private_key.pem',
            1_234_567, // password
            __DIR__ . '/files/testing_public_key.pub',
            '321',
            null
        );
    }

    public function provideMissingGlobalsForCurrentRequestUrlBuild(): array
    {
        // $serverName, $requestUri
        return [
            'missing server name' => [null, ''],
            'missing server name (can not be even empty string unlike request URI)' => ['', ''],
            'missing request URI' => ['example.com', null],
        ];
    }
}
