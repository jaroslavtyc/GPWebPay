<?php declare(strict_types=1);

namespace Granam\GpWebPay;

interface PayResponse
{
    // HELPERS
    /**
     * @return bool
     */
    public function hasError(): bool;

    /**
     * @return array
     */
    public function getParametersForDigest(): array;

    // GETTERS OF VALUES ITSELF

    /**
     * DIGEST
     *
     * @return string
     */
    public function getDigest(): string;

    /**
     * DIGEST1
     *
     * @return string
     */
    public function getDigest1(): string;

    /**
     * PRCODE
     *
     * @return int
     */
    public function getPrCode(): int;

    /**
     * SRCODE
     *
     * @return int
     */
    public function getSrCode(): int;

    /**
     * OPERATION
     *
     * @return string
     */
    public function getOperation(): string;

    /**
     * ORDERNUMBER
     *
     * @return int
     */
    public function getOrderNumber(): int;

    /**
     * MERORDERNUM
     *
     * @return int|null
     */
    public function getMerchantOrderNumber();

    /**
     * MD
     *
     * @return string|null
     */
    public function getMerchantNote();

    /**
     * USERPARAM1
     *
     * @return string|null
     */
    public function getUserParam1();

    /**
     * ADDINFO
     *
     * @return string|null
     */
    public function getAdditionalInfo();

    /**
     * RESULTTEXT
     *
     * @return string|null
     */
    public function getResultText();
}
