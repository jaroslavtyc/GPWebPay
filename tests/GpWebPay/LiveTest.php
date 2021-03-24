<?php declare(strict_types=1);

namespace Granam\Tests\GpWebPay;

use Alcohol\ISO4217;
use Granam\GpWebPay\CardPayRequest;
use Granam\GpWebPay\CardPayRequestValues;
use Granam\GpWebPay\Codes\CurrencyCodes;
use Granam\GpWebPay\Codes\LanguageCodes;
use Granam\GpWebPay\Codes\RequestDigestKeys;
use Granam\GpWebPay\Codes\RequestPayloadKeys;
use Granam\GpWebPay\DigestSigner;
use Granam\GpWebPay\Settings;
use Granam\TestWithMockery\TestWithMockery;
use Gt\Dom\HTMLDocument;

/**
 * @group online
 */
class LiveTest extends TestWithMockery
{
    /**
     * @var Settings
     */
    private $settings;

    protected function setUp(): void
    {
        try {
            $this->settings = TestSettingsFactory::createTestSettings();
        } catch (\RuntimeException $runtimeException) { // local config file not found
            self::markTestSkipped($runtimeException->getMessage());
        }
    }

    /**
     * @test
     */
    public function I_can_create_order(): void
    {
        $ISO4217 = new ISO4217();
        $_POSTLIKE = [
            RequestDigestKeys::ORDERNUMBER => (string)time(),
            RequestDigestKeys::AMOUNT => '123.45',
            RequestDigestKeys::CURRENCY => (string)$ISO4217->getByCode('EUR')['numeric'],
            RequestDigestKeys::DEPOSITFLAG => '1',
            RequestPayloadKeys::LANG => LanguageCodes::EN,
        ];
        $cardPayRequestValues = CardPayRequestValues::createFromArray($_POSTLIKE, new CurrencyCodes($ISO4217));
        $cardPayRequest = new CardPayRequest(
            $cardPayRequestValues,
            $this->settings,
            new DigestSigner($this->settings)
        );
        self::assertInstanceOf(CardPayRequest::class, $cardPayRequest);

        $response = $this->fetchResponse($cardPayRequest);
        $document = new HTMLDocument($response);
        self::assertSame(
            '3D Secure payment gateway',
            $document->title,
            'Unexpected response content from GpWebPay'
        );
        $buttonSend = $document->getElementById('send');
        self::assertSame('Pay', $buttonSend->textContent);
        $orderAmount = $document->getElementById('orderAmount');
        // the price may contains decoded &nbsp;, which results into some UTF-8 space-like character
        self::assertMatchesRegularExpression('~^123\.45\s+EUR$~u', \html_entity_decode($orderAmount->textContent));
    }

    private function fetchResponse(CardPayRequest $cardPayRequest): string
    {
        $curl = curl_init($cardPayRequest->getRequestUrlWithGetParameters());
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
        curl_setopt($curl, CURLOPT_SSL_VERIFYSTATUS, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        if ($response) {
            curl_close($curl);

            return (string)$response;
        }
        $curlError = curl_error($curl);
        curl_close($curl);
        if ($curlError !== 'No OCSP response received') {
            self::fail(
                'Requesting a new order via GET fails, got CURL error ' . $curlError
                . '; used URL ' . $cardPayRequest->getRequestUrlWithGetParameters()
            );
        }

        if (!is_callable('shell_exec') || strpos(ini_get('disable_functions'), 'shell_exec') !== false) {
            self::fail(
                'Requesting a new order via GET fails because of by CURL used openssl with bug'
                . ', see @link https://github.com/curl/curl/issues/219'
                . '; used URL ' . $cardPayRequest->getRequestUrlWithGetParameters()
            );
        }
        $response = shell_exec(
            'curl --connect-timeout 15 --max-redirs 5 --location 2>/dev/null '
            . escapeshellarg($cardPayRequest->getRequestUrlWithGetParameters())
        );
        self::assertNotEmpty($response);

        return $response;
    }
}
