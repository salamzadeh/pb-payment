<?php

namespace Salamzadeh\PBPayment\Exceptions;

use Exception;
use Throwable;

class PBPaymentException extends Exception
{
    public static function unknown(Throwable $previous = null)
    {
        return new self('متاسفانه خطای ناشناخته ای رخ داده است', 500, $previous);
    }
}
