<?php declare(strict_types=1);

namespace Granam\GpWebPay\Codes;

use Granam\Strict\Object\StrictObject;

class PrCodes extends StrictObject implements Codes
{
    // NON-ERROR CODES
    const OK_CODE = 0;
    const INVALID_FIELD_CONTENT_CODE = 3;
    const ADDITIONAL_INFO_REQUEST_CODE = 200;

    // ERROR CODES
    const FIELD_IS_TOO_LONG = 1;
    const FIELD_IS_TOO_SHORT = 2;
    const FIELD_IS_EMPTY = 4;
    const MISSING_REQUIRED_FIELD = 5;
    const UNKNOWN_MERCHANT = 11;
    const DUPLICATE_ORDER_NUMBER = 14;
    const OBJECT_NOT_FOUND = 15;
    const AMOUNT_TO_DEPOSIT_EXCEEDS_APPROVED_AMOUNT = 17;
    const TOTAL_SUM_OF_CREDITED_AMOUNTS_EXCEEDED_DEPOSITED_AMOUNT = 18;
    const OBJECT_NOT_IN_VALID_STATE_FOR_OPERATION = 20;
    const OPERATION_NOT_ALLOWED_FOR_USER = 25;
    const TECHNICAL_PROBLEM_IN_CONNECTION_TO_AUTHORIZATION_CENTER = 26;
    const INCORRECT_ORDER_TYPE = 27;
    const DECLINED_IN_3D_SECURE = 28;
    const DECLINED_IN_AUTHORIZATION_CENTRE = 30;
    const WRONG_DIGEST = 31;
    const SESSION_EXPIRED = 35;
    const DECLINED_DUE_TO_SUSPICION_OF_UNAUTHORIZED_USE_OF_A_CARD = 40;
    const THE_CARDHOLDER_CANCELED_THE_PAYMENT = 50;
    const TECHNICAL_PROBLEM = 1000;
    const GENERIC_PROBLEM = 'genericProblem';

    private static $prCodes = [
        LanguageCodes::CS => [
            self::GENERIC_PROBLEM => 'Technický problém v GP webpay systému, kontaktujte obchodníka',
            self::OK_CODE => 'OK',
            self::FIELD_IS_TOO_LONG => 'Pole příliš dlouhé',
            self::FIELD_IS_TOO_SHORT => 'Pole příliš krátké',
            self::INVALID_FIELD_CONTENT_CODE => 'Chybný obsah pole',
            self::FIELD_IS_EMPTY => 'Pole je prázdné',
            self::MISSING_REQUIRED_FIELD => 'Chybí povinné pole',
            self::UNKNOWN_MERCHANT => 'Neznámý obchodník',
            self::DUPLICATE_ORDER_NUMBER => 'Duplikátní číslo platby',
            self::OBJECT_NOT_FOUND => 'Objekt nenalezen',
            self::AMOUNT_TO_DEPOSIT_EXCEEDS_APPROVED_AMOUNT => 'Částka k zaplacení překročila povolenou (autorizovanou) částku',
            self::TOTAL_SUM_OF_CREDITED_AMOUNTS_EXCEEDED_DEPOSITED_AMOUNT => 'Součet vracených částek překročil zaplacenou částku',
            self::OBJECT_NOT_IN_VALID_STATE_FOR_OPERATION => 'Objekt není ve stavu odpovídajícím této operaci
Info: Pokud v případě vytváření objednávky (CREATE_ORDER) obdrží obchodník tento návratový kód,
vytvoření objednávky již proběhlo a objednávka je v určitém stavu
– tento návratový kód je zapříčiněn aktivitou držitele karty (například pokusem o přechod zpět, použití refresh...)',
            self::OPERATION_NOT_ALLOWED_FOR_USER => 'Uživatel není oprávněn k provedení operace',
            self::TECHNICAL_PROBLEM_IN_CONNECTION_TO_AUTHORIZATION_CENTER => 'Technický problém při spojení s autorizačním centrem',
            self::INCORRECT_ORDER_TYPE => 'Chybný typ objednávky',
            self::DECLINED_IN_3D_SECURE => 'Zamítnuto v 3D Secure',
            self::DECLINED_IN_AUTHORIZATION_CENTRE => 'Zamítnuto v autorizačním centru',
            self::WRONG_DIGEST => 'Chybný podpis (digest)',
            self::SESSION_EXPIRED => 'Expirovaná session (nastává při vypršení webové session při zadávání karty)',
            self::DECLINED_DUE_TO_SUSPICION_OF_UNAUTHORIZED_USE_OF_A_CARD => 'Zamítnuto z podezření na neoprávněné použití platební karty',
            self::THE_CARDHOLDER_CANCELED_THE_PAYMENT => 'Držitel karty zrušil platbu',
            self::ADDITIONAL_INFO_REQUEST_CODE => 'Žádost o doplňující informace',
            self::TECHNICAL_PROBLEM => 'Technický problém',
        ],
        LanguageCodes::EN => [
            self::GENERIC_PROBLEM => 'Technical problem in GP webpay system, contact the merchant',
            self::OK_CODE => 'OK',
            self::FIELD_IS_TOO_LONG => 'Field too long',
            self::FIELD_IS_TOO_SHORT => 'Field too short',
            self::INVALID_FIELD_CONTENT_CODE => 'Incorrect content of field',
            self::FIELD_IS_EMPTY => 'Field is null',
            self::MISSING_REQUIRED_FIELD => 'Missing required field',
            self::UNKNOWN_MERCHANT => 'Unknown merchant',
            self::DUPLICATE_ORDER_NUMBER => 'Duplicate order number',
            self::OBJECT_NOT_FOUND => 'Object not found',
            self::AMOUNT_TO_DEPOSIT_EXCEEDS_APPROVED_AMOUNT => 'Amount to deposit exceeds approved amount',
            self::TOTAL_SUM_OF_CREDITED_AMOUNTS_EXCEEDED_DEPOSITED_AMOUNT => 'Total sum of credited amounts exceeded deposited amount',
            self::OBJECT_NOT_IN_VALID_STATE_FOR_OPERATION => 'Object not in valid state for operation',
            self::OPERATION_NOT_ALLOWED_FOR_USER => 'Operation not allowed for user',
            self::TECHNICAL_PROBLEM_IN_CONNECTION_TO_AUTHORIZATION_CENTER => 'Technical problem in connection to authorization center',
            self::INCORRECT_ORDER_TYPE => 'Incorrect order type',
            self::DECLINED_IN_3D_SECURE => 'Declined in 3D Secure',
            self::DECLINED_IN_AUTHORIZATION_CENTRE => 'Declined in authorization centre',
            self::WRONG_DIGEST => 'Wrong digest',
            self::SESSION_EXPIRED => 'Session expired (happens on web session expiration when entering a card)',
            self::DECLINED_DUE_TO_SUSPICION_OF_UNAUTHORIZED_USE_OF_A_CARD => 'Declined due to suspicion of unauthorized use of a card',
            self::THE_CARDHOLDER_CANCELED_THE_PAYMENT => 'The cardholder canceled the payment',
            self::ADDITIONAL_INFO_REQUEST_CODE => 'Additional info request',
            self::TECHNICAL_PROBLEM => 'Technical problem',
        ],
    ];

    /**
     * @return array|string[][][]
     */
    public static function getPrCodes(): array
    {
        return self::$prCodes;
    }

    const LANGUAGE_CS = LanguageCodes::CS;
    const LANGUAGE_EN = LanguageCodes::EN;

    /**
     * @param int $prCode
     * @param string $languageCode
     * @return string
     */
    public static function getLocalizedMainMessage(int $prCode, string $languageCode = self::LANGUAGE_EN): string
    {
        $languageCode = \strtolower($languageCode);
        if (\array_key_exists($languageCode, self::$prCodes) && \array_key_exists($prCode, self::$prCodes[$languageCode])) {
            return self::$prCodes[$languageCode][$prCode];
        }
        if ($languageCode !== self::LANGUAGE_EN && \array_key_exists($prCode, self::$prCodes[self::LANGUAGE_EN])) {
            \trigger_error(
                "Unsupported language for main error code requested: '{$languageCode}', " . self::LANGUAGE_EN . ' used instead',
                E_USER_NOTICE
            );

            return self::$prCodes[self::LANGUAGE_EN][$prCode]; // fallback
        }

        \trigger_error("Unknown PR error code: '{$prCode}', a generic text used instead", E_USER_WARNING);

        return self::$prCodes[$languageCode]['genericProblem'];
    }

    /**
     * Messages of those errors can be shown to a customer / user for his clear information.
     *
     * @param int $prCode
     * @return bool
     */
    public static function isErrorForCustomer(int $prCode): bool
    {
        return \in_array(
            $prCode,
            [
                self::AMOUNT_TO_DEPOSIT_EXCEEDS_APPROVED_AMOUNT,
                self::OPERATION_NOT_ALLOWED_FOR_USER,
                self::TECHNICAL_PROBLEM_IN_CONNECTION_TO_AUTHORIZATION_CENTER,
                self::DECLINED_IN_3D_SECURE,
                self::DECLINED_IN_AUTHORIZATION_CENTRE,
                self::DECLINED_DUE_TO_SUSPICION_OF_UNAUTHORIZED_USE_OF_A_CARD,
                self::SESSION_EXPIRED,
                self::THE_CARDHOLDER_CANCELED_THE_PAYMENT,
                self::TECHNICAL_PROBLEM,
            ],
            true
        );
    }
}
