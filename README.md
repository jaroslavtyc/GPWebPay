# GPWebPay interface
[![Build Status](https://travis-ci.org/jaroslavtyc/granam-gpwebpay.svg?branch=master)](https://travis-ci.org/jaroslavtyc/granam-gpwebpay)
[![Test Coverage](https://codeclimate.com/github/jaroslavtyc/granam-gpwebpay/badges/coverage.svg)](https://codeclimate.com/github/jaroslavtyc/granam-gpwebpay/coverage)
[![Latest Stable Version](https://poser.pugx.org/granam/gpwebpay/v/stable)](https://packagist.org/packages/granam/gpwebpay)
[![License](https://poser.pugx.org/granam/gpwebpay/license)](https://packagist.org/packages/granam/gpwebpay)

GPWebPay is a PHP library for online payments via [GPWebPay service](http://www.gpwebpay.cz/en)

If your are using [Nette framework](https://nette.org/en/), you may want
[Pixidos/GPWebPay](https://github.com/Pixidos/GPWebPay) Nette extension instead.

## Quickstart

### Set up & usage

```php
<?php
namespace Foo\Bar;

use Granam\GpWebPay\Settings;
use Granam\GpWebPay\DigestSigner;
use Granam\GpWebPay\CardPayResponse;
use Granam\GpWebPay\Codes\CurrencyCodes;
use Alcohol\ISO4217 as IsoCurrencies;
use Granam\GpWebPay\CardPayRequestValues;
use Granam\GpWebPay\CardPayRequest;
use Granam\GpWebPay\Exceptions\GpWebPayErrorByCustomerResponse;
use Granam\GpWebPay\Exceptions\GpWebPayErrorResponse;
use Granam\GpWebPay\Exceptions\ResponseDigestCanNotBeVerified;
use Granam\GpWebPay\Exceptions\Exception as GpWebPayException;

// SET UP
$settings = Settings::createForProduction(
    __DIR__ . '/foo/bar/your_private_key_downloaded_from_gp_web_pay.pem',
    'TopSecretPasswordForPrivateKey',
    __DIR__ . '/foo/bar/gp_web_pay_server_public_key_also_downloaded_from_gp_web_pay.pem',
    '123456789' // your 'merchant number' given to you by GP WebPay
    // without explicit URL for response the current will be used - INCLUDING query string
);
$digestSigner = new DigestSigner($settings);

// RESPONSE
if (count($_POST) > 0) {
    try {
        $response = CardPayResponse::createFromArray($_POST, $settings, $digestSigner);
    } catch(GpWebPayErrorByCustomerResponse $gpWebPayErrorByCustomerResponse) {
        // some pretty error box for customer information about HIS mistake like invalid card number
        /**
         * WARNING: do not rely blindly on this detection - for example if YOU (developer) are sending
         * card number in a hidden field, because the customer provided it to its account before and
         * does not need to enter it again, but the card number has been refused by GP WebPay,
         * you will show to the customer confusing message about an invalid card number,
         * although he does not enter it.
         * For full list of auto-detected customer
         * mistakes @see GpWebPayErrorByCustomerResponse::isErrorCausedByCustomer
         */
        echo $gpWebPayErrorByCustomerResponse->getLocalizedMessage();
    } catch(GpWebPayErrorResponse $gpWebPayErrorResponse) {
        /* GP WebPay refuses request by OUR (developer) mistake like duplicate order number
         * - show an apology to the customer and log this, solve this */
    } catch(ResponseDigestCanNotBeVerified $responseDigestCanNotBeVerified) {
        /* values in response have been changed(!),
         * show an apology (or a warning?) to the customer and probably log this for evidence */
    } catch(GpWebPayException $gpWebPayException) { // EVERY exception share this interface
        /* some generic error like processing error on GP WebPay server,
         * show an apology to the customer and log this, solve this */
    }
    /**
     * its OK, lets process $response->getParametersForDigest();
     * @see \Granam\GpWebPay\CardPayResponse::getParametersForDigest
     */
} else { // REQUEST
    $currencyCodes = new CurrencyCodes(new IsoCurrencies());
    try {
        $cardPayRequestValues = CardPayRequestValues::createFromArray($_POST, $currencyCodes);
        $cardPayRequest = new CardPayRequest($cardPayRequestValues, $settings, $digestSigner);
    } catch (GpWebPayException $exception) {
        /* show an apology to the customer
         * like "we are sorry, our payment gateway is temporarily unavailable" and log it, solve it */
        exit();
    } ?>
    <html>
    <body>
        <!-- some pretty recapitulation of the order -->
        <form method="post" action="<?= $cardPayRequest->getRequestUrl() ?>">
            <?php foreach ($cardPayRequest as $name => $value) {
                ?><input type="hidden" name="<?= $name ?>" value="<?= $value ?>"
            <?php } ?>
            <input type="submit" value="Confirm order">
       </form>
    </body>
    </html>
<?php } ?>
```

### Troubleshooting

Almost all possible error cases are covered clearly by many of exceptions, but some are so nasty so they can not be:
 - after sending a request to GP WebPay you see just a logo and HTTP response code is 401
    - probably the URL for response you provided to GP WebPay in URL parameter is not valid in the point of view of GP WebPay
        - ensure that URL exists and there is **NO redirection**, like https://www.github.com to https://github.com/ with trailing slash
        (don't believe your eyes in a browser address bar, the trailing slash is often hidden there)

For tests against [testing payment gateway](https://test.3dsecure.gpwebpay.com/pgw/order.do) you can use payment card
- Card number: `4056070000000008`
- Card valdity: `12/2020`
- CVC2: `200`
- 3D Secure password: `password`


### Installation

```sh
composer require granam/gpwebpay
```

## Credits
This library originates from [Pixidos/GPWebPay](https://github.com/Pixidos/GPWebPay) library, which has same
functionality but can be used **only** as a [Nette framework](https://nette.org/en/) extension.
All credits belongs to the author Ondra Votava from Pixidos.

Nevertheless I am grateful to him for sharing that library publicly. Please more of such people.
