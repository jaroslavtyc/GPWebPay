<?php declare(strict_types=1);

namespace Granam\GpWebPay;

use Granam\GpWebPay\Codes\OperationCodes;
use Granam\GpWebPay\Codes\RequestDigestKeys;
use Granam\GpWebPay\Codes\RequestPayloadKeys;
use Granam\Strict\Object\StrictObject;

class CardPayRequest extends StrictObject implements \IteratorAggregate, PayRequest
{
    private \Granam\GpWebPay\CardPayRequestValues $cardPayRequestValues;
    private string $requestUrl;
    private array $parametersForRequest;

    /**
     * @param CardPayRequestValues $cardPayRequestValues
     * @param SettingsInterface $settings
     * @param DigestSignerInterface $digestSigner
     * @throws \Granam\GpWebPay\Exceptions\InvalidArgumentException
     * @throws \Granam\GpWebPay\Exceptions\PrivateKeyUsageFailed
     * @throws \Granam\GpWebPay\Exceptions\CanNotSignDigest
     */
    public function __construct(
        CardPayRequestValues $cardPayRequestValues,
        SettingsInterface $settings,
        DigestSignerInterface $digestSigner
    )
    {
        $this->cardPayRequestValues = $cardPayRequestValues;
        $this->parametersForRequest = $this->buildParametersForRequest(
            $this->buildParametersForDigest($cardPayRequestValues, $settings),
            $digestSigner,
            $cardPayRequestValues->getLang()
        );
        $this->requestUrl = $settings->getBaseUrlForRequest();
    }

    /**
     * @param CardPayRequestValues $requestValues
     * @param SettingsInterface $settings
     * @return array
     */
    private function buildParametersForDigest(CardPayRequestValues $requestValues, SettingsInterface $settings): array
    {
        $parametersWithoutDigest = [];
        // parameters HAVE TO be in this order, see GP_webpay_HTTP_EN.pdf / GP_webpay_HTTP.pdf
        $parametersWithoutDigest[RequestDigestKeys::MERCHANTNUMBER] = $settings->getMerchantNumber();
        $parametersWithoutDigest[RequestDigestKeys::OPERATION] = OperationCodes::CREATE_ORDER; // the only operation currently available
        $parametersWithoutDigest[RequestDigestKeys::ORDERNUMBER] = $requestValues->getOrderNumber(); // HAS TO be unique
        $parametersWithoutDigest[RequestDigestKeys::AMOUNT] = $requestValues->getAmount();
        $parametersWithoutDigest[RequestDigestKeys::CURRENCY] = $requestValues->getCurrency();
        $parametersWithoutDigest[RequestDigestKeys::DEPOSITFLAG] = $requestValues->getDepositFlag();
        if ($requestValues->getMerOrderNum() !== null) {
            $parametersWithoutDigest[RequestDigestKeys::MERORDERNUM] = $requestValues->getMerOrderNum();
        }
        $parametersWithoutDigest[RequestDigestKeys::URL] = $settings->getUrlForResponse();
        if ($requestValues->getDescription() !== null) {
            $parametersWithoutDigest[RequestDigestKeys::DESCRIPTION] = $requestValues->getDescription();
        }
        if ($requestValues->getMd() !== null) {
            $parametersWithoutDigest[RequestDigestKeys::MD] = $requestValues->getMd();
        }
        if ($requestValues->getPayMethod() !== null) {
            $parametersWithoutDigest[RequestDigestKeys::PAYMETHOD] = $requestValues->getPayMethod();
        }
        if ($requestValues->getDisablePayMethod() !== null) {
            $parametersWithoutDigest[RequestDigestKeys::DISABLEPAYMETHOD] = $requestValues->getDisablePayMethod();
        }
        if ($requestValues->getPayMethods() !== null) {
            $parametersWithoutDigest[RequestDigestKeys::PAYMETHODS] = $requestValues->getPayMethods();
        }
        if ($requestValues->getEmail() !== null) {
            $parametersWithoutDigest[RequestDigestKeys::EMAIL] = $requestValues->getEmail();
        }
        if ($requestValues->getReferenceNumber() !== null) {
            $parametersWithoutDigest[RequestDigestKeys::REFERENCENUMBER] = $requestValues->getReferenceNumber();
        }
        if ($requestValues->getAddInfo() !== null) {
            $parametersWithoutDigest[RequestDigestKeys::ADDINFO] = $requestValues->getAddInfo();
        }
        if ($requestValues->getFastPayId() !== null) {
            $parametersWithoutDigest[RequestDigestKeys::FASTPAYID] = $requestValues->getFastPayId();
        }

        return $parametersWithoutDigest;
    }

    /**
     * @param array $parametersForDigest
     * @param DigestSignerInterface $digestSigner
     * @param string|null $lang
     * @return array
     */
    private function buildParametersForRequest(
        array $parametersForDigest,
        DigestSignerInterface $digestSigner,
        string $lang = null
    ): array
    {
        $parametersForRequest = $parametersForDigest;
        // digest HAS TO be calculated after parameters population
        $parametersForRequest[RequestPayloadKeys::DIGEST] = $digestSigner->createSignedDigest($parametersForDigest);
        if ($lang) { // lang IS NOT part of digest
            $parametersForRequest[RequestPayloadKeys::LANG] = $lang;
        }

        return $parametersForRequest;
    }

    /**
     * @return string
     */
    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }

    /**
     * To send a request via GET method and REDIRECT you can use this URL
     *
     * @return string
     */
    public function getRequestUrlWithGetParameters(): string
    {
        return $this->getRequestUrl() . '?' . \http_build_query($this->parametersForRequest);
    }

    /**
     * To build request by your own.
     *
     * @return array
     */
    public function getParametersForRequest(): array
    {
        return $this->parametersForRequest;
    }

    /**
     * @return CardPayRequestValues
     */
    public function getCardPayRequestValues(): CardPayRequestValues
    {
        return $this->cardPayRequestValues;
    }

    /**
     * To easy create a POST request by using values one by one for hidden inputs.
     *
     * @return \Iterator
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->getParametersForRequest());
    }
}
