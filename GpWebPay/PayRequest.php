<?php declare(strict_types=1);

namespace Granam\GpWebPay;

interface PayRequest extends \IteratorAggregate
{

    /**
     * @return string
     */
    public function getRequestUrl(): string;

    /**
     * @return string
     */
    public function getRequestUrlWithGetParameters(): string;

    /**
     * @return array
     */
    public function getParametersForRequest(): array;
}
