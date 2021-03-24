<?php declare(strict_types=1);

namespace Granam\GpWebPay\Codes;

use Granam\Strict\Object\StrictObject;

class OperationCodes extends StrictObject implements Codes
{
    const CREATE_ORDER = 'CREATE_ORDER'; // this is used both for request and response
}
