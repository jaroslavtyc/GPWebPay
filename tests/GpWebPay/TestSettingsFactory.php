<?php

namespace Granam\Tests\GpWebPay;

use Granam\GpWebPay\Settings;
use Symfony\Component\Yaml\Yaml;

class TestSettingsFactory extends Settings
{
    const PRIVATE_KEY_FILE_INDEX = 'privateKeyFile';
    const PRIVATE_KEY_PASSWORD_INDEX = 'privateKeyPassword';
    const PUBLIC_KEY_FILE_INDEX = 'publicKeyFile';
    const BASE_URL_FOR_REQUEST_INDEX = 'baseUrlForRequest';
    const MERCHANT_NUMBER_INDEX = 'merchantNumber';
    const URL_FOR_RESPONSE_INDEX = 'urlForResponse';

    /**
     * @return Settings
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public static function createTestSettings(): Settings
    {
        $config = Yaml::parse(file_get_contents(self::findLiveTestConfigFile()));

        foreach ([self::PRIVATE_KEY_FILE_INDEX, self::PUBLIC_KEY_FILE_INDEX, self::BASE_URL_FOR_REQUEST_INDEX,
                     self::MERCHANT_NUMBER_INDEX] as $required) {
            if (empty($config[$required])) {
                throw new \LogicException("Required config entry '{$required}' for live test is missing");
            }
        }

        return new Settings(
            $config[self::BASE_URL_FOR_REQUEST_INDEX],
            preg_match('~^\\/~', $config[self::PRIVATE_KEY_FILE_INDEX])
                ? $config[self::PRIVATE_KEY_FILE_INDEX] // absolute path
                : __DIR__ . '/Tests/' . $config[self::PRIVATE_KEY_FILE_INDEX], // relative to config file
            $config[self::PRIVATE_KEY_PASSWORD_INDEX],
            preg_match('~^\\/~', $config[self::PUBLIC_KEY_FILE_INDEX])
                ? $config[self::PUBLIC_KEY_FILE_INDEX] // absolute path
                : __DIR__ . '/Tests/' . $config[self::PUBLIC_KEY_FILE_INDEX], // relative to config file
            $config[self::MERCHANT_NUMBER_INDEX],
            $config[self::URL_FOR_RESPONSE_INDEX] ?? null
        );
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    private static function findLiveTestConfigFile(): string
    {
        $liveTestConfigFileInTests = dirname(__DIR__) . '/webpay_live_test_config.yml'; // directly in this library, together with dist version
        if (is_readable($liveTestConfigFileInTests)) {
            return $liveTestConfigFileInTests;
        }
        $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
        $projectRootDir = dirname($reflection->getFileName(), 3);
        $liveTestConfigFileInProjectRoot = $projectRootDir . '/webpay_live_test_config.yml';
        if (is_readable($liveTestConfigFileInProjectRoot)) {
            return $liveTestConfigFileInProjectRoot;
        }

        throw new \RuntimeException(
            sprintf(
                "Could not find webpay_live_test_config.yml. Were looking on paths %s",
                implode(', ', [$liveTestConfigFileInTests, $liveTestConfigFileInProjectRoot])
            )
        );
    }
}
