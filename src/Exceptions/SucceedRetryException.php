<?php

namespace Salamzadeh\PBPayment\Exceptions;

use Salamzadeh\PBPayment\Exceptions\PBPaymentException;

use Throwable;

class SucceedRetryException extends PBPaymentException
{
    public function __construct($message = "پرداخت موفقیت آمیز بوده و قبلا عملیات تایید تراکنش انجام شده است.", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
