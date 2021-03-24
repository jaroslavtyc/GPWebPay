<?php
namespace Granam\GpWebPay;

require_once __DIR__ . '/../../tests_bootstrap.php';

use Alcohol\ISO4217;
use Granam\GpWebPay\Codes\CurrencyCodes;
use Granam\GpWebPay\Codes\RequestDigestKeys;
use Granam\Tests\GpWebPay\TestSettingsFactory;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GP WEbPay test</title>
</head>
<body>
<?php
$ISO4217 = new ISO4217();
$currencies = [];
foreach ($ISO4217->getAll() as $currency) {
    $currencies[$currency['numeric']] = $currency['alpha3'];
}

if (($_GET['price'] ?? null) !== null) {
    $settings = TestSettingsFactory::createTestSettings();
    $provider = new Provider($settings, new DigestSigner($settings));
    $values = $_GET;
    $values[RequestDigestKeys::ORDERNUMBER] = time();
    $values[RequestDigestKeys::DEPOSITFLAG] = true;
    $cardPayRequest = $provider->createCardPayRequest(
        CardPayRequestValues::createFromArray($values, new CurrencyCodes($ISO4217))
    );
    ?>
    <label>
        Price
        <input type="number" value="<?= $values['price'] ?>" disabled>
    </label><br>
    <label>
        Currency
        <select disabled>
            <option value="<?= $values['currency']; ?>" selected>
                <?= $currencies[$values['currency']]; ?>
            </option>
        </select>
    </label><br>
    <br>
    <form action="<?= $cardPayRequest->getRequestUrl() ?>" method="get">
        <?php foreach ($cardPayRequest as $name => $value) {
            ?><input type="hidden" name="<?= $name ?>" value="<?= $value ?>">
        <?php } ?>
        <button type="submit">Confirm via GET</button>
    </form><br>
    <form action="<?= $cardPayRequest->getRequestUrl() ?>" method="post">
        <?php foreach ($cardPayRequest as $name => $value) {
            ?><input type="hidden" name="<?= $name ?>" value="<?= $value ?>">
        <?php } ?>
        <button type="submit">Confirm via POST</button>
    </form><br>
    <a href="gp_web_pay.php">Reset</a>
<?php } else {
    ?>
    <form action="" method="get">
        <label>Price <input name="price" type="number" value="123.456"
        </label><br>
        <label>Currency
            <select name="currency">
                <?php foreach ($currencies as $currencyNumericCode => $currencyName) {
                    ?>
                    <option value="<?= $currencyNumericCode ?>"
                            <?php if ($currencyName === 'EUR') { ?>selected<?php } ?>>
                        <?= $currencyName ?>
                    </option>
                <?php } ?>
            </select><br>
            <button type="submit">Check</button>
        </label>
    </form>
<?php }
?>
</body>
</html>