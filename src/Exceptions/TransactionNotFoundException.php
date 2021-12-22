<?php

namespace Salamzadeh\PBPayment\Exceptions;

use Salamzadeh\PBPayment\Exceptions\PBPaymentException;

use Throwable;

class TransactionNotFoundException extends PBPaymentException
{
    public function __construct(string $message = 'تراکنش مورد نظر یافت نشد.', int $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}