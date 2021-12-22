<?php

namespace Salamzadeh\PBPayment\Exceptions;

use Salamzadeh\PBPayment\Exceptions\PBPaymentException;

use Throwable;

class GatewayNotFoundException extends PBPaymentException
{
    public function __construct($message = "درگاه پرداخت انتخابی معتبر نمی‌باشد", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function productionUnavailableGateway(): self
    {
        return new self('امکان استفاده از این درگاه در محیط پروداکشن وجود ندارد');
    }

    public static function detectionProblem(): self
    {
        return new self('امکان تشخیص درگاه پرداخت وجود ندارد');
    }
}
